<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public REST surface. Every route is anonymous by design - these are the
 * endpoints a game-playing visitor's browser calls, with no WordPress
 * login involved. Trust comes from the signed session/run tokens
 * (PairsMG_Token) and bot protection (PairsMG_Captcha), not WP auth.
 *
 * Flow: POST /verify (captcha token -> session token) once per visit,
 * POST /start-run (session token + tier -> run token + deck) per game,
 * POST /finish-run (run token + moves -> server-timed score) the instant
 * the board is cleared, POST /submit-score (run token + name -> stored).
 * GET /leaderboard and GET /config are read-only.
 */
class PairsMG_REST {

    const NS = 'pairs-memory-game/v1';

    public static function register_routes() {
        register_rest_route(self::NS, '/verify', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'verify'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'captchaToken' => array('required' => false, 'type' => 'string'),
            ),
        ));

        register_rest_route(self::NS, '/start-run', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'start_run'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'sessionToken' => array('required' => true, 'type' => 'string'),
                'tier'         => array('required' => true, 'type' => 'string'),
            ),
        ));

        register_rest_route(self::NS, '/finish-run', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'finish_run'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'runToken' => array('required' => true, 'type' => 'string'),
                'moves'    => array('required' => true, 'type' => 'integer'),
            ),
        ));

        register_rest_route(self::NS, '/submit-score', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'submit_score'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'runToken' => array('required' => true, 'type' => 'string'),
                'name'     => array('required' => false, 'type' => 'string'),
            ),
        ));

        register_rest_route(self::NS, '/leaderboard', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'leaderboard'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'tier'  => array('required' => true, 'type' => 'string'),
                'limit' => array('required' => false, 'type' => 'integer'),
            ),
        ));

        register_rest_route(self::NS, '/config', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'config'),
            'permission_callback' => '__return_true',
        ));
    }

    /* ---------------- helpers ---------------- */

    /**
     * The visitor's IP. Proxy headers (Cloudflare, X-Forwarded-For) are
     * only trusted when the admin says the site is behind such a proxy -
     * otherwise a client could forge them and dodge the rate limiter.
     */
    private static function client_ip() {
        $s = PairsMG_Settings::get();
        $candidates = array();
        if (!empty($s['trust_proxy_headers'])) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $candidates[] = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $parts = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
                $candidates[] = trim($parts[0]);
            }
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $candidates[] = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        $ip = '0.0.0.0';
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate;
                break;
            }
        }
        /**
         * Filter the visitor IP used for rate limiting and captcha checks.
         *
         * @param string $ip
         */
        return (string) apply_filters('pairsmg_client_ip', $ip);
    }

    /** Hashed and salted with a WordPress-managed secret; never stored raw. */
    private static function ip_hash() {
        return hash_hmac('sha256', self::client_ip(), wp_salt('auth'));
    }

    /** Fixed-window limiter. True when over budget. A limit of 0 disables it. */
    private static function rate_limited($bucket, $limit, $window = HOUR_IN_SECONDS) {
        $limit = (int) $limit;
        if ($limit <= 0) {
            return false;
        }
        $key = 'pairsmg_rl_' . $bucket . '_' . substr(self::ip_hash(), 0, 32);
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            return true;
        }
        set_transient($key, $count + 1, $window);
        return false;
    }

    /**
     * Same-origin guard for the state-changing routes.
     *
     * WordPress's REST layer answers CORS preflights permissively (it echoes
     * any Origin back), so without this a third-party page could drive the
     * game API - and its leaderboard - from another site. Browsers always
     * send Origin on cross-site POSTs; a request with an Origin that is not
     * this site (or an explicitly allowed one) is refused. Requests without
     * an Origin header (non-browser clients) are left to the rate limiter.
     *
     * @return bool True when the request may proceed.
     */
    public static function origin_allowed($origin) {
        $origin = trim((string) $origin);
        if ($origin === '' || $origin === 'null') {
            return $origin === '';
        }
        $allowed = array(wp_parse_url(home_url(), PHP_URL_HOST));
        /**
         * Filter the hosts allowed to call the game's POST endpoints
         * cross-origin (host names, no scheme). The site's own host is
         * always allowed.
         *
         * @param string[] $allowed
         */
        $allowed = (array) apply_filters('pairsmg_allowed_origins', $allowed);
        $host = wp_parse_url($origin, PHP_URL_HOST);
        return $host && in_array(strtolower($host), array_map('strtolower', array_filter($allowed)), true);
    }

    private static function cross_origin_rejected() {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';
        if (self::origin_allowed($origin)) {
            return null;
        }
        return self::error('cross_origin', __('Requests from other sites are not allowed.', 'pairs-memory-game'), 403);
    }

    private static function error($code, $message, $status) {
        return new WP_REST_Response(array('ok' => false, 'error' => $code, 'message' => $message), $status);
    }

    private static function pending_key($nonce) {
        return 'pairsmg_pend_' . md5((string) $nonce);
    }

    private static function valid_tier($tier) {
        return in_array($tier, PairsMG_Settings::TIERS, true);
    }

    private static function sanitize_name($raw) {
        $s = PairsMG_Settings::get();
        $max = max(3, min(40, (int) $s['name_max_length']));
        $name = wp_strip_all_tags((string) $raw);
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $name = mb_substr($name, 0, $max);
        if ($name === '') {
            $name = PairsMG_Settings::text('anonymous_name');
        }
        /**
         * Filter the display name before it is stored.
         *
         * @param string $name
         */
        return (string) apply_filters('pairsmg_sanitize_name', $name);
    }

    /* ---------------- routes ---------------- */

    public static function verify(WP_REST_Request $req) {
        $blocked = self::cross_origin_rejected();
        if ($blocked) {
            return $blocked;
        }
        $s = PairsMG_Settings::get();
        if (self::rate_limited('verify', $s['rate_limit_verify'])) {
            return self::error('rate_limited', __('Too many attempts. Please try again in a few minutes.', 'pairs-memory-game'), 429);
        }
        $token = (string) $req->get_param('captchaToken');
        $result = PairsMG_Captcha::verify($token, self::client_ip());
        if (is_wp_error($result)) {
            return self::error($result->get_error_code(), $result->get_error_message(), 400);
        }
        return new WP_REST_Response(array(
            'ok'           => true,
            'sessionToken' => PairsMG_Token::issue_session(),
        ), 200);
    }

    public static function start_run(WP_REST_Request $req) {
        $blocked = self::cross_origin_rejected();
        if ($blocked) {
            return $blocked;
        }
        $s = PairsMG_Settings::get();
        if (self::rate_limited('start', $s['rate_limit_start'])) {
            return self::error('rate_limited', __('Too many attempts. Please try again in a few minutes.', 'pairs-memory-game'), 429);
        }
        $session = (string) $req->get_param('sessionToken');
        $tier = sanitize_key((string) $req->get_param('tier'));

        $payload = PairsMG_Token::verify($session, 'session');
        if (is_wp_error($payload)) {
            return self::error($payload->get_error_code(), $payload->get_error_message(), 401);
        }
        if (!self::valid_tier($tier)) {
            return self::error('invalid_tier', __('Unknown difficulty.', 'pairs-memory-game'), 400);
        }

        $counts = PairsMG_Settings::pair_counts();
        $pairs = $counts[$tier];
        $built = PairsMG_Deck::build($pairs);

        if (count($built['deck']) < $pairs) {
            return self::error('not_enough_cards', __('There are not enough cards for this board size yet.', 'pairs-memory-game'), 409);
        }

        /**
         * Fires when a run starts.
         *
         * @param string $tier
         * @param int    $pairs
         */
        do_action('pairsmg_run_started', $tier, $pairs);

        return new WP_REST_Response(array(
            'ok'           => true,
            'runToken'     => PairsMG_Token::issue_run($tier, $pairs),
            'pairs'        => $pairs,
            'deck'         => $built['deck'],
            'usedDefaults' => $built['usedDefaults'],
        ), 200);
    }

    /**
     * Freezes the run the moment the board is cleared. Elapsed time is
     * stamped here from the server clock, so the time a player spends
     * typing a name never counts against them - and the client can never
     * claim a faster time than it really took.
     */
    public static function finish_run(WP_REST_Request $req) {
        $blocked = self::cross_origin_rejected();
        if ($blocked) {
            return $blocked;
        }
        $run_token = (string) $req->get_param('runToken');
        $moves = absint($req->get_param('moves'));

        $payload = PairsMG_Token::verify($run_token, 'run');
        if (is_wp_error($payload)) {
            return self::error($payload->get_error_code(), $payload->get_error_message(), 401);
        }
        if (!PairsMG_Token::consume_once($payload['n'])) {
            return self::error('already_finished', __('This run has already been finished.', 'pairs-memory-game'), 409);
        }

        $pairs = (int) $payload['pairs'];
        $elapsed = max(1, time() - (int) $payload['iat']);
        // Nothing legitimate runs at 20x par pace; caps a token parked for
        // hours so it cannot skew anything.
        $elapsed = min($elapsed, (int) round(PairsMG_Scoring::par_time($pairs) * 20));
        $moves = max($moves, $pairs);

        $score = PairsMG_Scoring::compute($pairs, $moves, $elapsed);

        set_transient(self::pending_key($payload['n']), array(
            'tier'    => $payload['tier'],
            'pairs'   => $pairs,
            'moves'   => $moves,
            'elapsed' => $elapsed,
            'score'   => $score,
        ), HOUR_IN_SECONDS);

        return new WP_REST_Response(array(
            'ok'          => true,
            'score'       => $score,
            'timeSeconds' => $elapsed,
            'moves'       => $moves,
            'tier'        => $payload['tier'],
        ), 200);
    }

    public static function submit_score(WP_REST_Request $req) {
        $blocked = self::cross_origin_rejected();
        if ($blocked) {
            return $blocked;
        }
        $s = PairsMG_Settings::get();
        if (empty($s['leaderboard_enabled'])) {
            return self::error('leaderboard_disabled', __('The leaderboard is turned off.', 'pairs-memory-game'), 403);
        }
        if (self::rate_limited('submit', $s['rate_limit_submit'])) {
            return self::error('rate_limited', __('Too many attempts. Please try again in a few minutes.', 'pairs-memory-game'), 429);
        }

        $run_token = (string) $req->get_param('runToken');
        $payload = PairsMG_Token::verify($run_token, 'run');
        if (is_wp_error($payload)) {
            return self::error($payload->get_error_code(), $payload->get_error_message(), 401);
        }

        $pending_key = self::pending_key($payload['n']);
        $pending = get_transient($pending_key);
        if (!is_array($pending)) {
            return self::error('already_submitted', __('This score has already been saved.', 'pairs-memory-game'), 409);
        }
        delete_transient($pending_key);

        $name = self::sanitize_name($req->get_param('name'));

        $row = array(
            'name'         => $name,
            'tier'         => $pending['tier'],
            'pairs'        => (int) $pending['pairs'],
            'moves'        => (int) $pending['moves'],
            'time_seconds' => (int) $pending['elapsed'],
            'score'        => (int) $pending['score'],
            'ip_hash'      => self::ip_hash(),
        );
        PairsMG_DB::insert($row);

        /**
         * Fires after a score is stored.
         *
         * @param array $row name/tier/pairs/moves/time_seconds/score/ip_hash.
         */
        do_action('pairsmg_score_saved', $row);

        return new WP_REST_Response(array(
            'ok'    => true,
            'score' => (int) $pending['score'],
            'tier'  => $pending['tier'],
            'name'  => $name,
        ), 200);
    }

    public static function leaderboard(WP_REST_Request $req) {
        $s = PairsMG_Settings::get();
        $tier = sanitize_key((string) $req->get_param('tier'));
        if (!self::valid_tier($tier)) {
            return self::error('invalid_tier', __('Unknown difficulty.', 'pairs-memory-game'), 400);
        }
        $max = max(1, min(200, (int) $s['leaderboard_limit']));
        $limit = (int) $req->get_param('limit');
        $limit = $limit > 0 ? min($max, $limit) : min($max, 20);

        return new WP_REST_Response(array(
            'ok'      => true,
            'tier'    => $tier,
            'entries' => PairsMG_DB::top($tier, $limit),
        ), 200);
    }

    /** Non-secret config the frontend needs to boot. */
    public static function config() {
        return new WP_REST_Response(array(
            'ok'         => true,
            'pairCounts' => PairsMG_Settings::pair_counts(),
            'pool'       => PairsMG_Deck::stats(),
        ), 200);
    }
}

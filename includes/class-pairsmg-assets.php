<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend assets: registered on wp_enqueue_scripts, enqueued only where
 * the shortcode/block actually renders. All configuration and every
 * user-facing string the script needs is passed in one localized object,
 * so the JS has no hardcoded copy and translations only need .po/.mo files.
 */
class PairsMG_Assets {

    const HANDLE_FONTS = 'pairsmg-fonts';
    const HANDLE_GAME = 'pairsmg-game';
    const HANDLE_CAPTCHA = 'pairsmg-captcha';

    public static function register() {
        wp_register_style(self::HANDLE_FONTS, PAIRSMG_URL . 'assets/css/fonts.css', array(), PAIRSMG_VERSION);
        wp_register_style(self::HANDLE_GAME, PAIRSMG_URL . 'assets/css/game.css', array(), PAIRSMG_VERSION);
        wp_add_inline_style(self::HANDLE_GAME, self::theme_css());

        $captcha_url = PairsMG_Captcha::script_url();
        if ($captcha_url !== '') {
            wp_register_script(self::HANDLE_CAPTCHA, $captcha_url, array(), null, true); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party, unversioned by design.
        }
        wp_register_script(self::HANDLE_GAME, PAIRSMG_URL . 'assets/js/game.js', array(), PAIRSMG_VERSION, true);

        // Attached at registration on purpose: with block themes the
        // content (and so the shortcode) renders before wp_enqueue_scripts,
        // and inline data added to an unregistered handle is silently
        // dropped. It only prints if the handle is actually enqueued.
        // wp_add_inline_script + wp_json_encode rather than
        // wp_localize_script, because the latter casts top-level scalars to
        // strings (false becomes "") and the frontend relies on real
        // booleans and numbers.
        wp_add_inline_script(
            self::HANDLE_GAME,
            'window.PairsMGConfig = ' . wp_json_encode(self::config()) . ';',
            'before'
        );
    }

    public static function enqueue() {
        $s = PairsMG_Settings::get();
        if ($s['font_mode'] === 'bundled') {
            wp_enqueue_style(self::HANDLE_FONTS);
        }
        wp_enqueue_style(self::HANDLE_GAME);
        if (PairsMG_Captcha::script_url() !== '') {
            wp_enqueue_script(self::HANDLE_CAPTCHA);
        }
        wp_enqueue_script(self::HANDLE_GAME);
    }

    /** CSS custom properties from the Appearance settings. */
    public static function theme_css() {
        $s = PairsMG_Settings::get();
        $presets = PairsMG_Settings::theme_presets();
        $colors = isset($presets[$s['theme']]) ? $presets[$s['theme']] : array(
            'color_bg' => $s['color_bg'], 'color_panel' => $s['color_panel'], 'color_ink' => $s['color_ink'],
            'color_accent' => $s['color_accent'], 'color_success' => $s['color_success'],
            'color_card_back' => $s['color_card_back'], 'color_card_frame' => $s['color_card_frame'],
        );
        foreach ($colors as $k => $v) {
            $colors[$k] = sanitize_hex_color($v) ? sanitize_hex_color($v) : '#888888';
        }
        $ratio = in_array($s['card_ratio'], PairsMG_Settings::RATIOS, true) ? $s['card_ratio'] : '7:10';
        $ratio_css = str_replace(':', ' / ', $ratio);
        $radius = max(0, min(32, (int) $s['corner_radius']));

        $font_display = $s['font_mode'] === 'bundled'
            ? "'Rajdhani', -apple-system, 'Segoe UI', sans-serif"
            : 'inherit';
        $font_body = $s['font_mode'] === 'bundled'
            ? "'Open Sans', -apple-system, 'Segoe UI', sans-serif"
            : 'inherit';

        $back_img = '';
        if (!empty($s['card_back_image_id'])) {
            $url = wp_get_attachment_image_url((int) $s['card_back_image_id'], 'medium');
            if ($url) {
                $back_img = 'url("' . esc_url_raw($url) . '")';
            }
        }

        $css = ".pairsmg-app{"
            . "--pmg-bg:{$colors['color_bg']};"
            . "--pmg-panel:{$colors['color_panel']};"
            . "--pmg-ink:{$colors['color_ink']};"
            . "--pmg-accent:{$colors['color_accent']};"
            . "--pmg-success:{$colors['color_success']};"
            . "--pmg-card-back:{$colors['color_card_back']};"
            . "--pmg-card-frame:{$colors['color_card_frame']};"
            . "--pmg-card-ratio:{$ratio_css};"
            . "--pmg-radius:{$radius}px;"
            . "--pmg-font-display:{$font_display};"
            . "--pmg-font-body:{$font_body};"
            . ($back_img !== '' ? "--pmg-card-back-image:{$back_img};" : '')
            . "}";
        /**
         * Filter the inline theme CSS.
         *
         * @param string $css
         */
        return apply_filters('pairsmg_theme_css', $css);
    }

    /** Everything the frontend needs, including its strings. */
    public static function config() {
        $s = PairsMG_Settings::get();
        $counts = PairsMG_Settings::pair_counts();
        $default_tier = in_array($s['default_tier'], PairsMG_Settings::TIERS, true) ? $s['default_tier'] : 'medium';

        $config = array(
            'restUrl'         => esc_url_raw(rest_url(PairsMG_REST::NS)),
            'captchaProvider' => PairsMG_Captcha::provider(),
            'captchaSiteKey'  => PairsMG_Captcha::site_key(),
            'captchaHasWidget'=> PairsMG_Captcha::has_widget(),
            'pairCounts'      => $counts,
            'tierLabels'      => PairsMG_Settings::tier_labels(),
            'defaultTier'     => $default_tier,
            'leaderboard'     => !empty($s['leaderboard_enabled']),
            'leaderboardLimit'=> max(1, min(200, (int) $s['leaderboard_limit'])),
            'soundDefault'    => !empty($s['sound_default']),
            'confetti'        => !empty($s['confetti']),
            'immersive'       => !empty($s['immersive_mobile']),
            'nameMaxLength'   => max(3, min(40, (int) $s['name_max_length'])),
            'exitUrl'         => PairsMG_Game_Page::exit_url(),
            'parSecondsPerPair' => PairsMG_Scoring::PAR_SECONDS_PER_PAIR,
            'i18n'            => self::strings(),
        );
        /**
         * Filter the frontend config object.
         *
         * @param array $config
         */
        return apply_filters('pairsmg_frontend_config', $config);
    }

    private static function strings() {
        return array(
            'tagGate'          => __('Verification', 'pairs-memory-game'),
            'tagSetup'         => __('Setup', 'pairs-memory-game'),
            'tagGame'          => __('Game', 'pairs-memory-game'),
            'tagWin'           => __('Result', 'pairs-memory-game'),
            'tagLeaderboard'   => __('Leaderboard', 'pairs-memory-game'),
            'verifying'        => __('Verifying...', 'pairs-memory-game'),
            'verifyFailed'     => __('Verification failed. Please refresh the page and try again.', 'pairs-memory-game'),
            'verifyFirst'      => __('Please complete the verification first.', 'pairs-memory-game'),
            'sessionExpired'   => __('Your session has expired. Please verify again.', 'pairs-memory-game'),
            /* translators: %d: number of pairs */
            'pairsCount'       => __('%d pairs', 'pairs-memory-game'),
            /* translators: %d: number of cards */
            'cardsOnBoard'     => __('%d cards on the board', 'pairs-memory-game'),
            /* translators: %d: best score */
            'best'             => __('Best: %d', 'pairs-memory-game'),
            'loading'          => __('Loading...', 'pairs-memory-game'),
            'noScoresYet'      => __('No scores for this difficulty yet - be the first.', 'pairs-memory-game'),
            'lbUnavailable'    => __('The leaderboard cannot be loaded right now.', 'pairs-memory-game'),
            // Forms for n = 1, 2, 3, 5 so the script can pick a plural without
            // knowing the locale's rules (covers English and Slavic patterns).
            'resultsCount'     => array(
                /* translators: %d: number of results */
                _n('%d result', '%d results', 1, 'pairs-memory-game'),
                /* translators: %d: number of results */
                _n('%d result', '%d results', 2, 'pairs-memory-game'),
                /* translators: %d: number of results */
                _n('%d result', '%d results', 3, 'pairs-memory-game'),
                /* translators: %d: number of results */
                _n('%d result', '%d results', 5, 'pairs-memory-game'),
            ),
            /* translators: %d: number of moves */
            'movesCount'       => __('%d moves', 'pairs-memory-game'),
            'saveFailed'       => __('The score could not be saved.', 'pairs-memory-game'),
            'alreadySaved'     => __('This score has already been saved.', 'pairs-memory-game'),
            'rateLimited'      => __('Too many attempts. Please try again in a few minutes.', 'pairs-memory-game'),
            'sessionLost'      => __('The session has expired, the score could not be saved.', 'pairs-memory-game'),
            'notEnoughCards'   => __('There are not enough cards for this board size yet.', 'pairs-memory-game'),
            'leaveConfirm'     => __('The game is not finished. Leave anyway?', 'pairs-memory-game'),
            'cardLabel'        => __('Memory card', 'pairs-memory-game'),
            'soundOn'          => __('Sound on', 'pairs-memory-game'),
            'soundOff'         => __('Sound off', 'pairs-memory-game'),
        );
    }
}

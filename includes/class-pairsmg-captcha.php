<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bot protection behind one small interface. Providers:
 *
 *   none          - no challenge; a session token is issued straight away
 *                   (rate limits still apply)
 *   turnstile     - Cloudflare Turnstile (checkbox / managed widget)
 *   recaptcha_v2  - Google reCAPTCHA v2 "I'm not a robot" checkbox
 *   recaptcha_v3  - Google reCAPTCHA v3, invisible, score-based
 *   hcaptcha      - hCaptcha checkbox
 *
 * Verification is always server-side (the provider's siteverify endpoint).
 * "Test mode" swaps in the provider's official always-pass test keypair,
 * so a local or staging site (where real keys will not validate, being
 * locked to a domain) still exercises the real verification path.
 * reCAPTCHA v3 has no official test keys; in test mode it is skipped and
 * the admin screen says so.
 */
class PairsMG_Captcha {

    const TEST_KEYS = array(
        'turnstile'    => array('1x00000000000000000000AA', '1x0000000000000000000000000000000AA'),
        'recaptcha_v2' => array('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'),
        'hcaptcha'     => array('10000000-ffff-ffff-ffff-000000000001', '0x0000000000000000000000000000000000000000'), // phpcs:ignore PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound -- hCaptcha's literal test secret, used as a string.
    );

    // Server-side verification endpoints (wp_remote_post from PHP, nothing
    // is offloaded to the browser here).
    const VERIFY_URLS = array(
        'turnstile'    => 'https://challenges.cloudflare.com/turnstile/v0/siteverify', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent
        'recaptcha_v2' => 'https://www.google.com/recaptcha/api/siteverify',
        'recaptcha_v3' => 'https://www.google.com/recaptcha/api/siteverify',
        'hcaptcha'     => 'https://api.hcaptcha.com/siteverify',
    );

    public static function provider() {
        $s = PairsMG_Settings::get();
        $p = (string) $s['captcha_provider'];
        return in_array($p, PairsMG_Settings::PROVIDERS, true) ? $p : 'none';
    }

    public static function enabled() {
        return self::provider() !== 'none';
    }

    public static function test_mode() {
        $s = PairsMG_Settings::get();
        return !empty($s['captcha_test_mode']);
    }

    public static function site_key() {
        $p = self::provider();
        if (self::test_mode() && isset(self::TEST_KEYS[$p])) {
            return self::TEST_KEYS[$p][0];
        }
        $s = PairsMG_Settings::get();
        return (string) $s['captcha_site_key'];
    }

    private static function secret_key() {
        $p = self::provider();
        if (self::test_mode() && isset(self::TEST_KEYS[$p])) {
            return self::TEST_KEYS[$p][1];
        }
        $s = PairsMG_Settings::get();
        return (string) $s['captcha_secret_key'];
    }

    /** True when the game can run: either protection is off, or keys exist. */
    public static function is_configured() {
        if (!self::enabled()) {
            return true;
        }
        if (self::test_mode()) {
            return true;
        }
        return self::site_key() !== '' && self::secret_key() !== '';
    }

    /** Whether the provider draws a visible widget the player interacts with. */
    public static function has_widget() {
        return in_array(self::provider(), array('turnstile', 'recaptcha_v2', 'hcaptcha'), true);
    }

    /**
     * Third-party widget script for the active provider, or '' for none.
     * The provider's widget can only be served by the provider (the keys are
     * domain-locked and the script talks to their challenge backend); this is
     * disclosed under "Third-party services" in readme.txt and only loads
     * when an admin has enabled a provider.
     */
    public static function script_url() {
        switch (self::provider()) {
            case 'turnstile':
                return 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'; // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent
            case 'recaptcha_v2':
                return 'https://www.google.com/recaptcha/api.js?render=explicit';
            case 'recaptcha_v3':
                return 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode(self::site_key());
            case 'hcaptcha':
                return 'https://js.hcaptcha.com/1/api.js?render=explicit';
        }
        return '';
    }

    public static function labels() {
        return array(
            'none'         => __('None (no challenge)', 'pairs-memory-game'),
            'turnstile'    => __('Cloudflare Turnstile', 'pairs-memory-game'),
            'recaptcha_v2' => __('Google reCAPTCHA v2 (checkbox)', 'pairs-memory-game'),
            'recaptcha_v3' => __('Google reCAPTCHA v3 (invisible)', 'pairs-memory-game'),
            'hcaptcha'     => __('hCaptcha', 'pairs-memory-game'),
        );
    }

    /**
     * @param string $token     The response token from the widget.
     * @param string $remote_ip Visitor IP, forwarded to the provider.
     * @return true|WP_Error
     */
    public static function verify($token, $remote_ip) {
        $p = self::provider();
        if ($p === 'none') {
            return true;
        }
        if ($p === 'recaptcha_v3' && self::test_mode()) {
            // No official test keys exist for v3; test mode means "skip".
            return true;
        }
        if (empty($token)) {
            return new WP_Error('pairsmg_no_token', __('Missing verification token.', 'pairs-memory-game'));
        }
        $secret = self::secret_key();
        if ($secret === '') {
            return new WP_Error('pairsmg_not_configured', __('Bot protection is not configured. Enter the keys in the plugin settings.', 'pairs-memory-game'));
        }

        $body = array(
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remote_ip,
        );
        if ($p === 'hcaptcha') {
            $body['sitekey'] = self::site_key();
        }

        $response = wp_remote_post(self::VERIFY_URLS[$p], array(
            'timeout' => 10,
            'body'    => $body,
        ));
        if (is_wp_error($response)) {
            return $response;
        }
        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($data['success'])) {
            return new WP_Error('pairsmg_captcha_failed', __('Verification failed. Please try again.', 'pairs-memory-game'));
        }

        if ($p === 'recaptcha_v3') {
            $s = PairsMG_Settings::get();
            $threshold = (float) $s['recaptcha_v3_threshold'];
            $score = isset($data['score']) ? (float) $data['score'] : 0.0;
            if ($score < $threshold) {
                return new WP_Error('pairsmg_captcha_low_score', __('Verification failed. Please try again.', 'pairs-memory-game'));
            }
        }

        /**
         * Fires after a successful bot-protection verification.
         *
         * @param string $provider Provider slug.
         * @param array  $data     Raw provider response.
         */
        do_action('pairsmg_captcha_verified', $p, $data);

        return true;
    }
}

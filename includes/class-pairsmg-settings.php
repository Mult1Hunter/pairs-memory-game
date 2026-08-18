<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single option, one array, defaults merged on read. Everything the game
 * can be configured with lives here; the admin screen
 * (PairsMG_Admin_Settings) is only a form over this array.
 */
class PairsMG_Settings {

    const OPTION = 'pairsmg_settings';

    const TIERS = array('easy', 'medium', 'hard');
    const MIN_PAIRS = 3;
    const MAX_PAIRS = 24;

    const PROVIDERS = array('none', 'turnstile', 'recaptcha_v2', 'recaptcha_v3', 'hcaptcha');
    const THEMES = array('parchment', 'light', 'dark', 'custom');
    const RATIOS = array('7:10', '3:4', '1:1');
    const FONT_MODES = array('bundled', 'inherit');
    const FITS = array('inset', 'full');

    public static function defaults() {
        return array(
            // General
            'brand_name'          => get_bloginfo('name'),
            'intro_eyebrow'       => '',
            'intro_title'         => '',
            'intro_copy'          => '',
            'leaderboard_title'   => '',
            'name_max_length'     => 20,
            'anonymous_name'      => '',
            'create_page'         => true,
            'game_slug'           => 'memory-game',
            'exit_url'            => '',

            // Game
            'pairs_easy'          => 6,
            'pairs_medium'        => 10,
            'pairs_hard'          => 14,
            'default_tier'        => 'medium',
            'special_per_game'    => 0,
            'use_default_deck'    => true,
            'leaderboard_enabled' => true,
            'leaderboard_limit'   => 50,
            'sound_default'       => true,
            'confetti'            => true,
            'immersive_mobile'    => true,

            // Bot protection
            'captcha_provider'    => 'none',
            'captcha_site_key'    => '',
            'captcha_secret_key'  => '',
            'captcha_test_mode'   => false,
            'recaptcha_v3_threshold' => 0.5,

            // Appearance
            'theme'               => 'light',
            'color_bg'            => '#f4f5f7',
            'color_panel'         => '#ffffff',
            'color_ink'           => '#1f2933',
            'color_accent'        => '#2f3e4e',
            'color_success'       => '#2e7d4f',
            'color_card_back'     => '#2f3e4e',
            'color_card_frame'    => '#c9d3dd',
            'font_mode'           => 'bundled',
            'card_ratio'          => '7:10',
            'corner_radius'       => 0,
            'card_back_image_id'  => 0,
            'card_image_fit'      => 'inset',

            // Advanced
            'rate_limit_submit'   => 30,
            'rate_limit_verify'   => 60,
            'rate_limit_start'    => 120,
            'trust_proxy_headers' => false,
            'delete_on_uninstall' => false,
        );
    }

    /** Built-in theme presets; "custom" keeps whatever the colour fields hold. */
    public static function theme_presets() {
        return array(
            'parchment' => array(
                'color_bg' => '#f8eadc', 'color_panel' => '#fffbf3', 'color_ink' => '#3e2f27',
                'color_accent' => '#594a41', 'color_success' => '#6b7a4a',
                'color_card_back' => '#56554a', 'color_card_frame' => '#d5ab69',
            ),
            'light' => array(
                'color_bg' => '#f4f5f7', 'color_panel' => '#ffffff', 'color_ink' => '#1f2933',
                'color_accent' => '#2f3e4e', 'color_success' => '#2e7d4f',
                'color_card_back' => '#2f3e4e', 'color_card_frame' => '#c9d3dd',
            ),
            'dark' => array(
                'color_bg' => '#15181d', 'color_panel' => '#1f242b', 'color_ink' => '#e6e8eb',
                'color_accent' => '#e0b458', 'color_success' => '#7cb342',
                'color_card_back' => '#2b313a', 'color_card_frame' => '#e0b458',
            ),
        );
    }

    public static function get() {
        $saved = get_option(self::OPTION, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        $s = wp_parse_args($saved, self::defaults());
        /**
         * Filter the effective settings array.
         *
         * @param array $s Settings merged with defaults.
         */
        return apply_filters('pairsmg_settings', $s);
    }

    /** Pairs per tier, clamped, as tier => int. */
    public static function pair_counts() {
        $s = self::get();
        $counts = array();
        foreach (self::TIERS as $tier) {
            $counts[$tier] = max(self::MIN_PAIRS, min(self::MAX_PAIRS, (int) $s['pairs_' . $tier]));
        }
        /**
         * Filter the pairs-per-tier table.
         *
         * @param array $counts tier => pairs.
         */
        return apply_filters('pairsmg_pair_counts', $counts);
    }

    /** Human labels for the tiers, translated. */
    public static function tier_labels() {
        return array(
            'easy'   => __('Easy', 'pairs-memory-game'),
            'medium' => __('Medium', 'pairs-memory-game'),
            'hard'   => __('Hard', 'pairs-memory-game'),
        );
    }

    /** Text fields fall back to translated defaults when left empty. */
    public static function text($key) {
        $s = self::get();
        $val = isset($s[$key]) ? trim((string) $s[$key]) : '';
        if ($val !== '') {
            return $val;
        }
        switch ($key) {
            case 'intro_eyebrow':
                return __('Memory challenge', 'pairs-memory-game');
            case 'intro_title':
                return __('Find the pairs', 'pairs-memory-game');
            case 'intro_copy':
                return __('Turn two cards at a time and find every matching pair. Fewer moves and a faster time mean a higher score. See if you can make it onto the leaderboard.', 'pairs-memory-game');
            case 'leaderboard_title':
                return __('Leaderboard', 'pairs-memory-game');
            case 'anonymous_name':
                return __('Anonymous player', 'pairs-memory-game');
            case 'brand_name':
                return get_bloginfo('name');
        }
        return '';
    }
}

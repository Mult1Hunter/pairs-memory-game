<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * [pairs_memory_game] - drops the game into any page or post.
 *
 * Attributes:
 *   tier="easy|medium|hard"  preselected difficulty (default from Settings)
 *   title="..."              overrides the intro title for this instance
 */
class PairsMG_Shortcode {

    const SHORTCODE = 'pairs_memory_game';

    public static function register() {
        add_shortcode(self::SHORTCODE, array(__CLASS__, 'render'));
    }

    public static function render($atts = array()) {
        $atts = shortcode_atts(array(
            'tier'  => '',
            'title' => '',
        ), $atts, self::SHORTCODE);

        return self::render_game(array(
            'tier'  => sanitize_key($atts['tier']),
            'title' => sanitize_text_field($atts['title']),
        ));
    }

    /** Shared by the shortcode and the block. */
    public static function render_game($args = array()) {
        if (!PairsMG_Captcha::is_configured()) {
            if (current_user_can('manage_options')) {
                return '<div class="notice notice-warning" style="padding:12px;border-left:4px solid #d63638;background:#fff;">'
                    . esc_html__('Pairs - Memory Game: bot protection is enabled but the keys are missing. Enter them in Memory Game > Settings, or set the provider to "None". Only administrators see this notice.', 'pairs-memory-game')
                    . '</div>';
            }
            return '';
        }

        PairsMG_Assets::enqueue();

        $args = wp_parse_args($args, array('tier' => '', 'title' => ''));
        $settings = PairsMG_Settings::get();
        $instance = array(
            'tier'  => in_array($args['tier'], PairsMG_Settings::TIERS, true) ? $args['tier'] : $settings['default_tier'],
            'title' => $args['title'] !== '' ? $args['title'] : PairsMG_Settings::text('intro_title'),
        );

        ob_start();
        include PAIRSMG_DIR . 'templates/game.php';
        return ob_get_clean();
    }
}

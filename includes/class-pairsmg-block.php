<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gutenberg block - a thin dynamic wrapper around the same renderer the
 * shortcode uses, so both paths produce identical markup.
 */
class PairsMG_Block {

    public static function register() {
        if (!function_exists('register_block_type')) {
            return;
        }
        wp_register_script(
            'pairsmg-block-editor',
            PAIRSMG_URL . 'blocks/game/index.js',
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render'),
            PAIRSMG_VERSION,
            true
        );
        wp_set_script_translations('pairsmg-block-editor', 'pairs-memory-game', PAIRSMG_DIR . 'languages');

        register_block_type(PAIRSMG_DIR . 'blocks/game', array(
            'render_callback' => array(__CLASS__, 'render'),
        ));
    }

    public static function render($attributes) {
        $attributes = is_array($attributes) ? $attributes : array();
        // In the editor preview the assets are not enqueued on the page,
        // so ServerSideRender shows the raw markup; that is fine as a
        // placeholder - the frontend is what matters.
        return PairsMG_Shortcode::render_game(array(
            'tier'  => isset($attributes['tier']) ? sanitize_key($attributes['tier']) : '',
            'title' => isset($attributes['title']) ? sanitize_text_field($attributes['title']) : '',
        ));
    }
}

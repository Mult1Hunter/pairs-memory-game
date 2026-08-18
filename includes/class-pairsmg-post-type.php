<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * "Cards" - one custom post per matchable image. The featured image IS the
 * card face; publish/draft controls whether it is in the active pool.
 * A card can be flagged "special": the game guarantees a configurable
 * number of special cards on every board (a sponsor's own logo, an
 * easter egg, a prize card).
 */
class PairsMG_Post_Type {

    const POST_TYPE = 'pairsmg_pair';
    const CACHE_KEY = 'pairsmg_pairs_cache';
    const META_SPECIAL = '_pairsmg_special';
    const META_FIT = '_pairsmg_fit';

    public static function register() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name'                  => __('Cards', 'pairs-memory-game'),
                'singular_name'         => __('Card', 'pairs-memory-game'),
                'add_new'               => __('Add card', 'pairs-memory-game'),
                'add_new_item'          => __('Add new card', 'pairs-memory-game'),
                'edit_item'             => __('Edit card', 'pairs-memory-game'),
                'new_item'              => __('New card', 'pairs-memory-game'),
                'view_item'             => __('View card', 'pairs-memory-game'),
                'all_items'             => __('Cards', 'pairs-memory-game'),
                'search_items'          => __('Search cards', 'pairs-memory-game'),
                'not_found'             => __('No cards yet.', 'pairs-memory-game'),
                'not_found_in_trash'    => __('No cards in the trash.', 'pairs-memory-game'),
                'menu_name'             => __('Cards', 'pairs-memory-game'),
                'featured_image'        => __('Card image (front face)', 'pairs-memory-game'),
                'set_featured_image'    => __('Set card image', 'pairs-memory-game'),
                'remove_featured_image' => __('Remove card image', 'pairs-memory-game'),
                'use_featured_image'    => __('Use as card image', 'pairs-memory-game'),
            ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => PairsMG_Admin_Settings::MENU_SLUG,
            'show_in_rest'    => false,
            'supports'        => array('title', 'thumbnail'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ));
    }

    public static function add_meta_box() {
        add_meta_box(
            'pairsmg_pair_meta',
            __('Card settings', 'pairs-memory-game'),
            array(__CLASS__, 'render_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('pairsmg_pair_meta', 'pairsmg_pair_meta_nonce');
        $special = get_post_meta($post->ID, self::META_SPECIAL, true);
        $fit = get_post_meta($post->ID, self::META_FIT, true);
        ?>
        <p>
            <label>
                <input type="checkbox" name="pairsmg_special" value="1" <?php checked($special, '1'); ?> />
                <?php esc_html_e('Special card (guaranteed on every board, up to the quota in Settings)', 'pairs-memory-game'); ?>
            </label>
        </p>
        <p>
            <label for="pairsmg_fit"><?php esc_html_e('Image fit', 'pairs-memory-game'); ?></label><br />
            <select name="pairsmg_fit" id="pairsmg_fit">
                <option value=""><?php esc_html_e('Use the global setting', 'pairs-memory-game'); ?></option>
                <option value="inset" <?php selected($fit, 'inset'); ?>><?php esc_html_e('Inset (logo with margin)', 'pairs-memory-game'); ?></option>
                <option value="full" <?php selected($fit, 'full'); ?>><?php esc_html_e('Full face (edge to edge)', 'pairs-memory-game'); ?></option>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e('Set the featured image below - that is the picture shown when the card is turned. Published cards are in the game; drafts are not.', 'pairs-memory-game'); ?>
        </p>
        <hr />
        <p style="margin-bottom:4px"><strong><?php esc_html_e('Image guidelines', 'pairs-memory-game'); ?></strong></p>
        <ul style="margin:0 0 6px 16px; list-style:disc; font-size:12px; color:#555;">
            <li><?php esc_html_e('Square (1:1), about 600 x 600 px, or the card ratio chosen in Appearance for full-face images', 'pairs-memory-game'); ?></li>
            <li><?php esc_html_e('PNG with transparent background for logos, JPG/WebP for photos', 'pairs-memory-game'); ?></li>
            <li><?php esc_html_e('Under 200 KB', 'pairs-memory-game'); ?></li>
            <li><?php esc_html_e('No fine print - a card is only about 80 px wide on a phone', 'pairs-memory-game'); ?></li>
        </ul>
        <?php
    }

    public static function save_meta($post_id) {
        if (!isset($_POST['pairsmg_pair_meta_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pairsmg_pair_meta_nonce'])), 'pairsmg_pair_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        update_post_meta($post_id, self::META_SPECIAL, isset($_POST['pairsmg_special']) ? '1' : '');
        $fit = isset($_POST['pairsmg_fit']) ? sanitize_key(wp_unslash($_POST['pairsmg_fit'])) : '';
        if (!in_array($fit, PairsMG_Settings::FITS, true)) {
            $fit = '';
        }
        update_post_meta($post_id, self::META_FIT, $fit);
    }

    public static function bust_cache() {
        delete_transient(self::CACHE_KEY);
    }

    /** Extra list-table columns: thumbnail + special flag. */
    public static function columns($columns) {
        $out = array();
        foreach ($columns as $key => $label) {
            if ($key === 'title') {
                $out['pairsmg_thumb'] = __('Image', 'pairs-memory-game');
            }
            $out[$key] = $label;
            if ($key === 'title') {
                $out['pairsmg_special'] = __('Special', 'pairs-memory-game');
            }
        }
        return $out;
    }

    public static function column_content($column, $post_id) {
        if ($column === 'pairsmg_thumb') {
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(48, 48), array('style' => 'object-fit:contain;background:#eee;'));
            } else {
                echo '<span style="color:#b32d2e">' . esc_html__('missing', 'pairs-memory-game') . '</span>';
            }
        } elseif ($column === 'pairsmg_special') {
            echo get_post_meta($post_id, self::META_SPECIAL, true) ? esc_html__('yes', 'pairs-memory-game') : '&mdash;';
        }
    }

    /**
     * The pool the game draws from: published cards with a featured image.
     * Cached (busted on any save/trash/delete of a card).
     *
     * @return array<int, array{id:string,url:string,alt:string,special:bool,fit:string}>
     */
    public static function get_active_pairs() {
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false && is_array($cached)) {
            return $cached;
        }

        $query = new WP_Query(array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));

        $s = PairsMG_Settings::get();
        $global_fit = in_array($s['card_image_fit'], PairsMG_Settings::FITS, true) ? $s['card_image_fit'] : 'inset';

        $pairs = array();
        foreach ($query->posts as $post_id) {
            $image_id = get_post_thumbnail_id($post_id);
            if (!$image_id) {
                continue;
            }
            $url = wp_get_attachment_image_url($image_id, 'medium');
            if (!$url) {
                continue;
            }
            $fit = get_post_meta($post_id, self::META_FIT, true);
            $pairs[] = array(
                'id'      => (string) $post_id,
                'url'     => $url,
                'alt'     => get_the_title($post_id),
                'special' => (bool) get_post_meta($post_id, self::META_SPECIAL, true),
                'fit'     => in_array($fit, PairsMG_Settings::FITS, true) ? $fit : $global_fit,
            );
        }

        /**
         * Filter the active card pool.
         *
         * @param array $pairs Cards as id/url/alt/special/fit arrays.
         */
        $pairs = apply_filters('pairsmg_active_pairs', $pairs);

        set_transient(self::CACHE_KEY, $pairs, 12 * HOUR_IN_SECONDS);
        return $pairs;
    }
}

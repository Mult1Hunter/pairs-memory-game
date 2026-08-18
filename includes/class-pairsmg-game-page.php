<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Optional dedicated game page at a stable slug (default /memory-game/).
 *
 * The shortcode/block can go anywhere, but many sites want one fixed
 * address to print on a poster or turn into a QR code, and that address
 * must survive someone renaming a page. When "create page" is on, the
 * plugin owns one page and keeps its slug in step with the setting.
 *
 *  - It only ever renames a page it CREATED. A page that already existed at
 *    the configured slug is adopted, never renamed.
 *  - WordPress resolves slug collisions with "-2". If that happens the
 *    setting is corrected to the slug that actually took effect.
 */
class PairsMG_Game_Page {

    const OPTION_ID = 'pairsmg_page_id';
    const OPTION_CREATED = 'pairsmg_page_created';
    const DEFAULT_SLUG = 'memory-game';

    private static $running = false;

    public static function enabled() {
        $s = PairsMG_Settings::get();
        return !empty($s['create_page']);
    }

    public static function slug() {
        $s = PairsMG_Settings::get();
        $slug = sanitize_title($s['game_slug']);
        return $slug !== '' ? $slug : self::DEFAULT_SLUG;
    }

    /** @return int|null Page ID. */
    public static function ensure() {
        if (self::$running || !self::enabled()) {
            return null;
        }
        self::$running = true;
        try {
            return self::resolve();
        } finally {
            self::$running = false;
        }
    }

    private static function resolve() {
        $slug = self::slug();
        $page_id = (int) get_option(self::OPTION_ID);
        $we_made_it = (bool) get_option(self::OPTION_CREATED);
        $page = $page_id ? get_post($page_id) : null;
        $usable = ($page instanceof WP_Post)
            && $page->post_type === 'page'
            && $page->post_status !== 'trash';

        if ($usable && $we_made_it) {
            if ($page->post_name !== $slug) {
                wp_update_post(array('ID' => $page->ID, 'post_name' => $slug));
                self::sync_slug_setting($page->ID);
            }
            return $page->ID;
        }
        if ($usable && !$we_made_it && $page->post_name === $slug) {
            return $page->ID;
        }

        $existing = get_page_by_path($slug);
        if ($existing instanceof WP_Post && $existing->post_type === 'page') {
            update_option(self::OPTION_ID, $existing->ID, false);
            update_option(self::OPTION_CREATED, 0, false);
            return $existing->ID;
        }

        $new_id = wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => __('Memory Game', 'pairs-memory-game'),
            'post_name'    => $slug,
            'post_content' => '<!-- wp:pairs-memory-game/game /-->',
        ));

        if ($new_id && !is_wp_error($new_id)) {
            update_option(self::OPTION_ID, $new_id, false);
            update_option(self::OPTION_CREATED, 1, false);
            self::sync_slug_setting($new_id);
            return $new_id;
        }
        return null;
    }

    private static function sync_slug_setting($page_id) {
        $actual = get_post_field('post_name', $page_id);
        if (!$actual) {
            return;
        }
        $s = PairsMG_Settings::get();
        if ($s['game_slug'] !== $actual) {
            $saved = get_option(PairsMG_Settings::OPTION, array());
            $saved = is_array($saved) ? $saved : array();
            $saved['game_slug'] = $actual;
            update_option(PairsMG_Settings::OPTION, $saved);
        }
    }

    public static function url() {
        $page_id = (int) get_option(self::OPTION_ID);
        if ($page_id && get_post_status($page_id) === 'publish') {
            return get_permalink($page_id);
        }
        return home_url('/' . self::slug() . '/');
    }

    /** Where the "back to site" control on a phone sends the player. */
    public static function exit_url() {
        $s = PairsMG_Settings::get();
        $url = trim((string) $s['exit_url']);
        return $url === '' ? home_url('/') : esc_url_raw($url);
    }

    public static function page_missing() {
        $page_id = (int) get_option(self::OPTION_ID);
        return !$page_id || get_post_status($page_id) !== 'publish';
    }
}

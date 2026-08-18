<?php
/**
 * Plugin Name:       Pairs - Memory Game
 * Plugin URI:        https://github.com/Mult1Hunter/pairs-memory-game
 * Description:       A memory (concentration) game with your own card images, three difficulty tiers, server-verified scores, per-tier leaderboards and optional bot protection (Turnstile, reCAPTCHA, hCaptcha).
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Matic Korošec
 * Author URI:        https://nextgen-solutions.xyz
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pairs-memory-game
 * Domain Path:       /languages
 *
 * Pairs - Memory Game is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 2 of the License, or any
 * later version.
 *
 * Pairs - Memory Game is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 *
 * Security model in one paragraph: the browser never sends a score or an
 * elapsed time that is trusted. Every game start issues a signed,
 * single-use run token carrying the tier and a server-side timestamp;
 * finishing a run stamps elapsed time from the server clock and recomputes
 * the score from that plus the client-reported move count (floored at the
 * theoretical minimum). Bot protection, when enabled, is verified
 * server-side before a session token is issued. All DB access goes
 * through $wpdb->prepare(), all output is escaped, and every destructive
 * admin action needs manage_options plus a nonce.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PAIRSMG_VERSION', '1.0.2');
define('PAIRSMG_FILE', __FILE__);
define('PAIRSMG_DIR', plugin_dir_path(__FILE__));
define('PAIRSMG_URL', plugin_dir_url(__FILE__));

require_once PAIRSMG_DIR . 'includes/class-pairsmg-settings.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-db.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-token.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-captcha.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-post-type.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-deck.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-scoring.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-rest.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-assets.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-shortcode.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-block.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-game-page.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-admin-settings.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-admin-leaderboard.php';
require_once PAIRSMG_DIR . 'includes/class-pairsmg-cron.php';

/**
 * Boot. Everything is hooked from one place so the load order is obvious.
 */
function pairsmg_boot() {
    load_plugin_textdomain('pairs-memory-game', false, dirname(plugin_basename(PAIRSMG_FILE)) . '/languages');

    PairsMG_Post_Type::register();
    PairsMG_Shortcode::register();
    PairsMG_Block::register();
    PairsMG_Cron::register();
}
add_action('init', 'pairsmg_boot');

register_activation_hook(__FILE__, 'pairsmg_activate');
function pairsmg_activate() {
    PairsMG_DB::install();
    PairsMG_Post_Type::register();
    PairsMG_Game_Page::ensure();
    PairsMG_Cron::schedule();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'pairsmg_deactivate');
function pairsmg_deactivate() {
    PairsMG_Cron::unschedule();
    flush_rewrite_rules();
}

// Sites that updated without re-activating still get the sweep scheduled.
add_action('admin_init', array('PairsMG_Cron', 'schedule'));

// Upgrade path: dbDelta is idempotent, so re-running install on a version
// bump keeps the table schema in step without a separate migration file.
add_action('plugins_loaded', function () {
    if (get_option(PairsMG_DB::VERSION_OPTION) !== PAIRSMG_VERSION) {
        PairsMG_DB::install();
    }
});

add_action('rest_api_init', array('PairsMG_REST', 'register_routes'));
add_action('wp_enqueue_scripts', array('PairsMG_Assets', 'register'));

// The dedicated game page (optional) follows the slug setting and is
// recreated if deleted.
add_action('update_option_' . PairsMG_Settings::OPTION, array('PairsMG_Game_Page', 'ensure'), 10, 0);
add_action('admin_init', array('PairsMG_Game_Page', 'ensure'));

add_action('admin_menu', array('PairsMG_Admin_Settings', 'register_menu'));
add_action('admin_init', array('PairsMG_Admin_Settings', 'register_settings'));
add_action('admin_enqueue_scripts', array('PairsMG_Admin_Settings', 'enqueue'));
add_action('admin_post_pairsmg_delete_score', array('PairsMG_Admin_Leaderboard', 'handle_delete'));
add_action('admin_post_pairsmg_clear_tier', array('PairsMG_Admin_Leaderboard', 'handle_clear_tier'));
add_action('admin_post_pairsmg_export_csv', array('PairsMG_Admin_Leaderboard', 'handle_export'));

add_action('add_meta_boxes', array('PairsMG_Post_Type', 'add_meta_box'));
add_action('save_post_' . PairsMG_Post_Type::POST_TYPE, array('PairsMG_Post_Type', 'save_meta'));
add_action('save_post_' . PairsMG_Post_Type::POST_TYPE, array('PairsMG_Post_Type', 'bust_cache'));
add_action('trashed_post', array('PairsMG_Post_Type', 'bust_cache'));
add_action('deleted_post', array('PairsMG_Post_Type', 'bust_cache'));
add_filter('manage_' . PairsMG_Post_Type::POST_TYPE . '_posts_columns', array('PairsMG_Post_Type', 'columns'));
add_action('manage_' . PairsMG_Post_Type::POST_TYPE . '_posts_custom_column', array('PairsMG_Post_Type', 'column_content'), 10, 2);

// "Settings" link on the Plugins screen.
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $url = admin_url('admin.php?page=' . PairsMG_Admin_Settings::MENU_SLUG);
    array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'pairs-memory-game') . '</a>');
    return $links;
});

<?php
/**
 * Runs when the plugin is deleted from wp-admin. Only removes data when the
 * admin opted in under Settings > Advanced; otherwise scores and cards are
 * left in place so a reinstall picks them up again.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$pairsmg_settings = get_option('pairsmg_settings', array());
if (!is_array($pairsmg_settings) || empty($pairsmg_settings['delete_on_uninstall'])) {
    return;
}

global $wpdb;

// Scores table.
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'pairsmg_scores'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- removing the plugin's own table on uninstall.

// Cards.
$pairsmg_posts = get_posts(array(
    'post_type'      => 'pairsmg_pair',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
));
foreach ($pairsmg_posts as $pairsmg_post_id) {
    wp_delete_post($pairsmg_post_id, true);
}

// The dedicated page, only if the plugin created it.
if (get_option('pairsmg_page_created')) {
    $pairsmg_page_id = (int) get_option('pairsmg_page_id');
    if ($pairsmg_page_id) {
        wp_delete_post($pairsmg_page_id, true);
    }
}

// Options and transients.
foreach (array('pairsmg_settings', 'pairsmg_db_version', 'pairsmg_hmac_secret', 'pairsmg_page_id', 'pairsmg_page_created') as $pairsmg_option) {
    delete_option($pairsmg_option);
}
delete_transient('pairsmg_pairs_cache');
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_pairsmg\_%' OR option_name LIKE '\_transient\_timeout\_pairsmg\_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- sweeping the plugin's own transients on uninstall. 

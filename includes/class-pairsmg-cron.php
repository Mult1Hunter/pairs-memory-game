<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Housekeeping. The rate limiter and the single-use token markers live in
 * transients; without a persistent object cache those are rows in
 * wp_options, and WordPress only purges expired transients on a core
 * upgrade. A daily sweep keeps a busy (or attacked) site from accumulating
 * thousands of dead rows.
 */
class PairsMG_Cron {

    const HOOK = 'pairsmg_daily_cleanup';

    public static function register() {
        add_action(self::HOOK, array(__CLASS__, 'cleanup'));
    }

    public static function schedule() {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::HOOK);
        }
    }

    public static function unschedule() {
        $ts = wp_next_scheduled(self::HOOK);
        while ($ts) {
            wp_unschedule_event($ts, self::HOOK);
            $ts = wp_next_scheduled(self::HOOK);
        }
    }

    /**
     * Deletes this plugin's expired transients (rate-limit buckets, spent
     * run nonces, pending results) directly from wp_options. Only rows
     * whose timeout has passed are touched, and only rows with our prefix.
     *
     * @return int Rows removed (timeout + value rows).
     */
    public static function cleanup() {
        global $wpdb;
        if (wp_using_ext_object_cache()) {
            // Transients live in the object cache and expire on their own.
            return 0;
        }
        $now = time();
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- housekeeping on wp_options; the only interpolated piece is a "%s,%s,..." list built from count().
        $expired = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like('_transient_timeout_pairsmg_') . '%',
            $now
        ));
        if (empty($expired)) {
            return 0;
        }
        $removed = 0;
        foreach (array_chunk($expired, 200) as $chunk) {
            $names = array();
            foreach ($chunk as $timeout_name) {
                $names[] = $timeout_name;
                $names[] = '_transient_' . substr($timeout_name, strlen('_transient_timeout_'));
            }
            $placeholders = implode(',', array_fill(0, count($names), '%s'));
            $removed += (int) $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name IN ($placeholders)",
                $names
            ));
        }
        // phpcs:enable

        /**
         * Fires after the daily cleanup.
         *
         * @param int $removed Option rows deleted.
         */
        do_action('pairsmg_cleanup_done', $removed);
        return $removed;
    }
}

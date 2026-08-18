<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The scores table. One row per submitted score, tagged by tier so the
 * per-tier leaderboards are simple filtered reads of a single table.
 */
class PairsMG_DB {

    const VERSION_OPTION = 'pairsmg_db_version';

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'pairsmg_scores';
    }

    public static function install() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // dbDelta is picky about formatting: two spaces after PRIMARY KEY,
        // no trailing commas.
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_nonce CHAR(32) NOT NULL DEFAULT '',
            name VARCHAR(40) NOT NULL,
            tier VARCHAR(10) NOT NULL,
            pairs SMALLINT UNSIGNED NOT NULL,
            moves SMALLINT UNSIGNED NOT NULL,
            time_seconds SMALLINT UNSIGNED NOT NULL,
            score SMALLINT UNSIGNED NOT NULL,
            ip_hash CHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY tier_score (tier, score),
            UNIQUE KEY run_nonce (run_nonce)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        self::backfill_run_nonce();
        dbDelta($sql);

        update_option(self::VERSION_OPTION, PAIRSMG_VERSION, false);
    }

    /**
     * Rows written before 1.0.2 have no run nonce. The unique index cannot
     * be created while they all share the empty default, so give each a
     * synthetic, obviously-not-a-token value first ("legacy-<id>", hashed
     * to fit the column). Idempotent; a no-op on fresh installs.
     */
    private static function backfill_run_nonce() {
        global $wpdb;
        $table = self::table_name();
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- schema maintenance on the plugin's own table.
        $has_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        if (!$has_table) {
            return;
        }
        $has_column = (bool) $wpdb->get_var("SHOW COLUMNS FROM {$table} LIKE 'run_nonce'");
        if (!$has_column) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN run_nonce CHAR(32) NOT NULL DEFAULT '' AFTER id");
        }
        $wpdb->query("UPDATE {$table} SET run_nonce = MD5(CONCAT('legacy-', id)) WHERE run_nonce = ''");
        // phpcs:enable
    }

    /**
     * Top scores for one tier.
     *
     * @return array<int, array>
     */
    public static function top($tier, $limit) {
        global $wpdb;
        $table = self::table_name();
        $rows = $wpdb->get_results($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
            "SELECT name, score, pairs, moves, time_seconds FROM {$table} WHERE tier = %s ORDER BY score DESC, id ASC LIMIT %d",
            $tier,
            (int) $limit
        ), ARRAY_A);
        return $rows ? $rows : array();
    }

    /** @return bool False when the run nonce already exists (duplicate submit). */
    public static function insert($row) {
        global $wpdb;
        // A duplicate run nonce is an expected outcome, not a DB error worth logging.
        $prev = $wpdb->suppress_errors(true);
        $ok = $wpdb->insert(
            self::table_name(),
            array(
                'run_nonce'    => isset($row['run_nonce']) ? (string) $row['run_nonce'] : substr(hash('sha256', wp_generate_password(16, false)), 0, 32),
                'name'         => $row['name'],
                'tier'         => $row['tier'],
                'pairs'        => (int) $row['pairs'],
                'moves'        => (int) $row['moves'],
                'time_seconds' => (int) $row['time_seconds'],
                'score'        => (int) $row['score'],
                'ip_hash'      => $row['ip_hash'],
                'created_at'   => current_time('mysql', true),
            ),
            array('%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s')
        );
        $wpdb->suppress_errors($prev);
        return (bool) $ok;
    }
}

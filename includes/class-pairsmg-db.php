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
            name VARCHAR(40) NOT NULL,
            tier VARCHAR(10) NOT NULL,
            pairs SMALLINT UNSIGNED NOT NULL,
            moves SMALLINT UNSIGNED NOT NULL,
            time_seconds SMALLINT UNSIGNED NOT NULL,
            score SMALLINT UNSIGNED NOT NULL,
            ip_hash CHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY tier_score (tier, score)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::VERSION_OPTION, PAIRSMG_VERSION, false);
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

    public static function insert($row) {
        global $wpdb;
        return $wpdb->insert(
            self::table_name(),
            array(
                'name'         => $row['name'],
                'tier'         => $row['tier'],
                'pairs'        => (int) $row['pairs'],
                'moves'        => (int) $row['moves'],
                'time_seconds' => (int) $row['time_seconds'],
                'score'        => (int) $row['score'],
                'ip_hash'      => $row['ip_hash'],
                'created_at'   => current_time('mysql', true),
            ),
            array('%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s')
        );
    }
}

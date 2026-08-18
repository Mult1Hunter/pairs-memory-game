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

    const CACHE_GROUP = 'pairsmg';

    /**
     * Leaderboard reads are cached in the object cache under a generation
     * number that every write bumps, so a persistent cache (Redis, Memcached)
     * serves the hot leaderboard queries without a DB hit and never serves a
     * stale board; without a persistent cache this is a per-request memo.
     */
    private static function generation() {
        $gen = wp_cache_get('generation', self::CACHE_GROUP);
        if ($gen === false) {
            $gen = 1;
            wp_cache_set('generation', $gen, self::CACHE_GROUP);
        }
        return (int) $gen;
    }

    public static function invalidate() {
        $gen = self::generation();
        wp_cache_set('generation', $gen + 1, self::CACHE_GROUP);
    }

    private static function cached($key, $callback) {
        $key = $key . ':' . self::generation();
        $value = wp_cache_get($key, self::CACHE_GROUP);
        if ($value === false) {
            $value = $callback();
            wp_cache_set($key, $value, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }
        return $value;
    }

    /**
     * Top scores for one tier.
     *
     * @return array<int, array>
     */
    public static function top($tier, $limit) {
        $limit = (int) $limit;
        return self::cached("top:{$tier}:{$limit}", function () use ($tier, $limit) {
            global $wpdb;
            $table = self::table_name();
            $rows = $wpdb->get_results($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin's own table; wrapped in cached().
                "SELECT name, score, pairs, moves, time_seconds FROM {$table} WHERE tier = %s ORDER BY score DESC, id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
                $tier,
                $limit
            ), ARRAY_A);
            return $rows ? $rows : array();
        });
    }

    /** Number of stored scores for one tier. */
    public static function count($tier) {
        return (int) self::cached("count:{$tier}", function () use ($tier) {
            global $wpdb;
            $table = self::table_name();
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- plugin's own table; wrapped in cached().
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE tier = %s", $tier));
        });
    }

    /**
     * One page of a tier's scores for the admin screen, best first.
     *
     * @return array<int, array>
     */
    public static function page($tier, $per_page, $offset) {
        return self::cached("page:{$tier}:{$per_page}:{$offset}", function () use ($tier, $per_page, $offset) {
            global $wpdb;
            $table = self::table_name();
            $rows = $wpdb->get_results($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin's own table; wrapped in cached().
                "SELECT id, name, score, pairs, moves, time_seconds, created_at FROM {$table} WHERE tier = %s ORDER BY score DESC, id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
                $tier,
                (int) $per_page,
                (int) $offset
            ), ARRAY_A);
            return $rows ? $rows : array();
        });
    }

    /** Every row of a tier, for export. Not cached: one-off admin action. */
    public static function all($tier) {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- plugin's own table; admin export.
        $rows = $wpdb->get_results($wpdb->prepare("SELECT name, score, pairs, moves, time_seconds, created_at FROM {$table} WHERE tier = %s ORDER BY score DESC, id ASC", $tier), ARRAY_A);
        return $rows ? $rows : array();
    }

    public static function delete_row($id) {
        global $wpdb;
        $n = $wpdb->delete(self::table_name(), array('id' => (int) $id), array('%d')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin's own table; invalidate() follows.
        self::invalidate();
        return (int) $n;
    }

    public static function delete_tier($tier) {
        global $wpdb;
        $n = $wpdb->delete(self::table_name(), array('tier' => $tier), array('%s')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- plugin's own table; invalidate() follows.
        self::invalidate();
        return (int) $n;
    }

    /** @return bool False when the run nonce already exists (duplicate submit). */
    public static function insert($row) {
        global $wpdb;
        // A duplicate run nonce is an expected outcome, not a DB error worth logging.
        $prev = $wpdb->suppress_errors(true);
        $ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- plugin's own table.
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
        if ($ok) {
            self::invalidate();
        }
        return (bool) $ok;
    }
}

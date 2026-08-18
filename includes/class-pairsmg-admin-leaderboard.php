<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Leaderboard moderation: per-tier list, delete one row, clear a tier,
 * export CSV. Every action needs manage_options plus a nonce.
 */
class PairsMG_Admin_Leaderboard {

    const MENU_SLUG = 'pairs-memory-game-leaderboard';
    const PER_PAGE = 50;

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'pairs-memory-game'));
        }
        $labels = PairsMG_Settings::tier_labels();

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters.
        $tier = isset($_GET['tier']) ? sanitize_key(wp_unslash($_GET['tier'])) : 'easy';
        if (!isset($labels[$tier])) {
            $tier = 'easy';
        }
        $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $deleted = isset($_GET['deleted']);
        $cleared = isset($_GET['cleared']) ? (int) $_GET['cleared'] : null;
        // phpcs:enable
        $offset = ($paged - 1) * self::PER_PAGE;

        $total = PairsMG_DB::count($tier);
        $rows = PairsMG_DB::page($tier, self::PER_PAGE, $offset);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Memory Game - Leaderboards', 'pairs-memory-game'); ?></h1>

            <?php if ($deleted) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Score deleted.', 'pairs-memory-game'); ?></p></div>
            <?php endif; ?>
            <?php if ($cleared !== null) : ?>
                <div class="notice notice-success is-dismissible"><p>
                    <?php
                    printf(
                        /* translators: %d: number of deleted rows */
                        esc_html__('Deleted %d scores.', 'pairs-memory-game'),
                        (int) $cleared
                    );
                    ?>
                </p></div>
            <?php endif; ?>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($labels as $key => $label) :
                    $count = PairsMG_DB::count($key);
                    $url = add_query_arg(array('page' => self::MENU_SLUG, 'tier' => $key), admin_url('admin.php'));
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="nav-tab <?php echo $tier === $key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?> <span class="count">(<?php echo (int) $count; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </h2>

            <p style="margin:12px 0;">
                <a class="button" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'pairsmg_export_csv', 'tier' => $tier), admin_url('admin-post.php')), 'pairsmg_export_csv')); ?>">
                    <?php esc_html_e('Export this tier as CSV', 'pairs-memory-game'); ?>
                </a>
            </p>

            <?php if (empty($rows)) : ?>
                <p><?php esc_html_e('No scores for this difficulty yet.', 'pairs-memory-game'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th style="width:60px;"><?php esc_html_e('Rank', 'pairs-memory-game'); ?></th>
                        <th><?php esc_html_e('Name', 'pairs-memory-game'); ?></th>
                        <th style="width:90px;"><?php esc_html_e('Score', 'pairs-memory-game'); ?></th>
                        <th style="width:80px;"><?php esc_html_e('Time', 'pairs-memory-game'); ?></th>
                        <th style="width:80px;"><?php esc_html_e('Moves', 'pairs-memory-game'); ?></th>
                        <th style="width:160px;"><?php esc_html_e('Date', 'pairs-memory-game'); ?></th>
                        <th style="width:90px;"><?php esc_html_e('Action', 'pairs-memory-game'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $i => $row) : ?>
                        <tr>
                            <td><?php echo (int) ($offset + $i + 1); ?></td>
                            <td><strong><?php echo esc_html($row['name']); ?></strong></td>
                            <td><?php echo (int) $row['score']; ?></td>
                            <td><?php echo esc_html(sprintf('%02d:%02d', intdiv((int) $row['time_seconds'], 60), (int) $row['time_seconds'] % 60)); ?></td>
                            <td><?php echo (int) $row['moves']; ?></td>
                            <td><?php echo esc_html(get_date_from_gmt($row['created_at'], get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                            <td>
                                <?php
                                $delete_url = wp_nonce_url(
                                    add_query_arg(array('action' => 'pairsmg_delete_score', 'id' => (int) $row['id'], 'tier' => $tier), admin_url('admin-post.php')),
                                    'pairsmg_delete_score_' . (int) $row['id']
                                );
                                ?>
                                <a href="<?php echo esc_url($delete_url); ?>" class="submitdelete"
                                   onclick="return confirm('<?php echo esc_js(__('Delete this score?', 'pairs-memory-game')); ?>');">
                                    <?php esc_html_e('Delete', 'pairs-memory-game'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $pages = (int) ceil($total / self::PER_PAGE);
                if ($pages > 1) :
                    ?>
                    <div class="tablenav"><div class="tablenav-pages">
                        <?php
                        echo wp_kses_post(paginate_links(array(
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $pages,
                        )));
                        ?>
                    </div></div>
                <?php endif; ?>

                <hr style="margin:24px 0;" />
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                      onsubmit="return confirm('<?php echo esc_js(__('Delete ALL scores for this difficulty? This cannot be undone.', 'pairs-memory-game')); ?>');">
                    <input type="hidden" name="action" value="pairsmg_clear_tier" />
                    <input type="hidden" name="tier" value="<?php echo esc_attr($tier); ?>" />
                    <?php wp_nonce_field('pairsmg_clear_tier_' . $tier); ?>
                    <?php submit_button(__('Clear this leaderboard', 'pairs-memory-game'), 'delete', 'submit', false); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function guard($nonce_action, $nonce_field = '_wpnonce') {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do that.', 'pairs-memory-game'));
        }
        $nonce = isset($_REQUEST[$nonce_field]) ? sanitize_text_field(wp_unslash($_REQUEST[$nonce_field])) : '';
        if (!wp_verify_nonce($nonce, $nonce_action)) {
            wp_die(esc_html__('Security check failed.', 'pairs-memory-game'));
        }
    }

    private static function tier_from_request() {
        $tier = isset($_REQUEST['tier']) ? sanitize_key(wp_unslash($_REQUEST['tier'])) : 'easy'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in guard().
        return in_array($tier, PairsMG_Settings::TIERS, true) ? $tier : 'easy';
    }

    public static function handle_delete() {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in guard().
        self::guard('pairsmg_delete_score_' . $id);
        PairsMG_DB::delete_row($id);
        wp_safe_redirect(add_query_arg(array('page' => self::MENU_SLUG, 'tier' => self::tier_from_request(), 'deleted' => 1), admin_url('admin.php')));
        exit;
    }

    public static function handle_clear_tier() {
        $tier = self::tier_from_request();
        self::guard('pairsmg_clear_tier_' . $tier);
        $count = PairsMG_DB::delete_tier($tier);
        wp_safe_redirect(add_query_arg(array('page' => self::MENU_SLUG, 'tier' => $tier, 'cleared' => (int) $count), admin_url('admin.php')));
        exit;
    }

    public static function handle_export() {
        self::guard('pairsmg_export_csv');
        $tier = self::tier_from_request();
        $rows = PairsMG_DB::all($tier);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="memory-game-' . $tier . '-' . gmdate('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('rank', 'name', 'score', 'pairs', 'moves', 'time_seconds', 'created_at_utc'));
        foreach ((array) $rows as $i => $row) {
            fputcsv($out, array($i + 1, $row['name'], $row['score'], $row['pairs'], $row['moves'], $row['time_seconds'], $row['created_at']));
        }
        fclose($out); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream.
        exit;
    }
}

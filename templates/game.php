<?php
/**
 * Frontend markup for the game.
 *
 * Available: $settings (array), $instance (tier, title).
 *
 * JS hooks are data-pmg="..." attributes rather than ids, so the markup
 * stays valid even if the game appears twice on a page. There is
 * deliberately no "reset leaderboard" control on the frontend; deleting
 * scores is a wp-admin action only.
 */
if (!defined('ABSPATH')) {
    exit;
}
$pairsmg_lb = !empty($settings['leaderboard_enabled']);
$pairsmg_immersive = !empty($settings['immersive_mobile']);
$pairsmg_widget = PairsMG_Captcha::has_widget();
$pairsmg_max = max(3, min(40, (int) $settings['name_max_length']));
?>
<div class="pairsmg-app" data-pmg-tier="<?php echo esc_attr($instance['tier']); ?>" data-pmg-immersive="<?php echo $pairsmg_immersive ? '1' : '0'; ?>">

    <div class="pmg-confetti-layer" data-pmg="confettiLayer" aria-hidden="true"></div>

    <div class="pmg-brand-row">
        <div class="pmg-brand-name"><?php echo esc_html(PairsMG_Settings::text('brand_name')); ?></div>
        <div class="pmg-brand-tools">
            <?php if ($pairsmg_immersive) : ?>
                <button class="pmg-exit" data-pmg="exitBtn" type="button">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php esc_html_e('Back to site', 'pairs-memory-game'); ?>
                </button>
            <?php endif; ?>
            <div class="pmg-eyebrow" data-pmg="screenTag"></div>
            <button class="pmg-sound-toggle" data-pmg="soundToggle" type="button" aria-label="<?php esc_attr_e('Toggle sound', 'pairs-memory-game'); ?>">
                <svg class="pmg-icon-on" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" fill="currentColor"/><path d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                <svg class="pmg-icon-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" fill="currentColor"/><path d="M16 9l5 6M21 9l-5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>

    <!-- ============ GATE ============ -->
    <section class="pmg-screen pmg-active" data-screen="gate">
        <div class="pmg-gate-wrap">
            <div class="pmg-panel pmg-gate-card">
                <?php if ($pairsmg_widget) : ?>
                    <h2 class="pmg-gate-title"><?php esc_html_e('One quick check', 'pairs-memory-game'); ?></h2>
                    <p class="pmg-gate-copy"><?php esc_html_e('A short verification keeps the leaderboard for real players. It only takes a moment.', 'pairs-memory-game'); ?></p>
                    <div class="pmg-captcha-holder" data-pmg="captchaWidget"></div>
                <?php else : ?>
                    <p class="pmg-gate-copy" data-pmg="gateLoading"><?php esc_html_e('Loading...', 'pairs-memory-game'); ?></p>
                <?php endif; ?>
                <div class="pmg-gate-status" data-pmg="gateStatus" role="status" aria-live="polite"></div>
            </div>
        </div>
    </section>

    <!-- ============ SETUP ============ -->
    <section class="pmg-screen" data-screen="setup">
        <div class="pmg-panel pmg-setup-hero">
            <div class="pmg-eyebrow"><?php echo esc_html(PairsMG_Settings::text('intro_eyebrow')); ?></div>
            <h2 class="pmg-setup-title"><?php echo esc_html($instance['title']); ?></h2>
            <p class="pmg-setup-copy"><?php echo esc_html(PairsMG_Settings::text('intro_copy')); ?></p>

            <div class="pmg-tier-grid" data-pmg="tierGrid" role="group" aria-label="<?php esc_attr_e('Choose difficulty', 'pairs-memory-game'); ?>"></div>

            <div class="pmg-setup-notice pmg-error" data-pmg="setupNotice" role="alert" style="display:none"></div>

            <div class="pmg-setup-actions">
                <button class="pmg-btn pmg-btn-primary" data-pmg="startBtn" type="button"><?php esc_html_e('Start game', 'pairs-memory-game'); ?></button>
                <?php if ($pairsmg_lb) : ?>
                    <button class="pmg-btn pmg-btn-ghost" data-pmg="toLeaderboardBtn" type="button"><?php esc_html_e('View leaderboard', 'pairs-memory-game'); ?></button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pairsmg_lb) : ?>
        <div class="pmg-panel pmg-leader-preview">
            <div class="pmg-leader-preview-head">
                <h3><?php esc_html_e('Top scores', 'pairs-memory-game'); ?> <span data-pmg="miniBoardTier"></span></h3>
                <button class="pmg-link-btn" data-pmg="toLeaderboardBtn2" type="button"><?php esc_html_e('Show all', 'pairs-memory-game'); ?></button>
            </div>
            <div class="pmg-mini-board" data-pmg="miniBoard"></div>
        </div>
        <?php endif; ?>
    </section>

    <!-- ============ GAME ============ -->
    <section class="pmg-screen" data-screen="game">
        <div class="pmg-panel pmg-game-topbar">
            <div class="pmg-stat-group">
                <div class="pmg-stat"><span class="pmg-stat-label"><?php esc_html_e('Difficulty', 'pairs-memory-game'); ?></span><span class="pmg-stat-value pmg-tier-tag" data-pmg="gTier">-</span></div>
                <div class="pmg-stat"><span class="pmg-stat-label"><?php esc_html_e('Time', 'pairs-memory-game'); ?></span><span class="pmg-stat-value" data-pmg="gTime">00:00</span></div>
                <div class="pmg-stat"><span class="pmg-stat-label"><?php esc_html_e('Moves', 'pairs-memory-game'); ?></span><span class="pmg-stat-value" data-pmg="gMoves">0</span></div>
                <div class="pmg-stat"><span class="pmg-stat-label"><?php esc_html_e('Pairs', 'pairs-memory-game'); ?></span><span class="pmg-stat-value" data-pmg="gPairs">0 / 0</span></div>
            </div>
            <button class="pmg-btn pmg-btn-ghost" data-pmg="quitBtn" type="button"><?php esc_html_e('Quit', 'pairs-memory-game'); ?></button>
        </div>

        <div class="pmg-board-wrap">
            <div class="pmg-board" data-pmg="board"></div>
        </div>

        <div class="pmg-game-bottombar">
            <button class="pmg-btn pmg-btn-ghost" data-pmg="restartBtn" type="button"><?php esc_html_e('Restart', 'pairs-memory-game'); ?></button>
        </div>
    </section>

    <!-- ============ WIN ============ -->
    <section class="pmg-screen" data-screen="win">
        <div class="pmg-win-wrap">
            <div class="pmg-panel pmg-win-card">
                <h2 class="pmg-win-title"><?php esc_html_e('All pairs found!', 'pairs-memory-game'); ?></h2>
                <div>
                    <div class="pmg-win-score" data-pmg="winScore">0</div>
                    <div class="pmg-win-score-label"><?php esc_html_e('score', 'pairs-memory-game'); ?></div>
                </div>
                <details class="pmg-score-explainer">
                    <summary><?php esc_html_e('How is the score calculated?', 'pairs-memory-game'); ?></summary>
                    <p><?php esc_html_e('The score combines speed and accuracy: fewer wrong guesses and a shorter time mean a higher score (1000 at most). Each difficulty has its own leaderboard, so scores are always compared between boards of the same size.', 'pairs-memory-game'); ?></p>
                </details>
                <div class="pmg-win-stats">
                    <div class="pmg-win-stat"><span class="pmg-win-stat-value" data-pmg="winTime">00:00</span><span class="pmg-win-stat-label"><?php esc_html_e('Time', 'pairs-memory-game'); ?></span></div>
                    <div class="pmg-win-stat"><span class="pmg-win-stat-value" data-pmg="winMoves">0</span><span class="pmg-win-stat-label"><?php esc_html_e('Moves', 'pairs-memory-game'); ?></span></div>
                    <div class="pmg-win-stat"><span class="pmg-win-stat-value" data-pmg="winTier">-</span><span class="pmg-win-stat-label"><?php esc_html_e('Board', 'pairs-memory-game'); ?></span></div>
                </div>

                <?php if ($pairsmg_lb) : ?>
                <form class="pmg-name-form" data-pmg="nameForm">
                    <label class="pmg-name-help" for="pmg-name-input"><?php esc_html_e('Your name for the leaderboard', 'pairs-memory-game'); ?></label>
                    <input class="pmg-name-input" id="pmg-name-input" data-pmg="nameInput" type="text"
                           maxlength="<?php echo (int) $pairsmg_max; ?>" placeholder="<?php esc_attr_e('e.g. Alex', 'pairs-memory-game'); ?>" autocomplete="off" />
                    <div class="pmg-name-help">
                        <?php
                        printf(
                            /* translators: %d: max characters */
                            esc_html__('Up to %d characters. The name is shown publicly on the leaderboard.', 'pairs-memory-game'),
                            (int) $pairsmg_max
                        );
                        ?>
                    </div>
                    <div class="pmg-win-actions">
                        <button class="pmg-btn pmg-btn-primary" type="submit" data-pmg="saveScoreBtn"><?php esc_html_e('Save to leaderboard', 'pairs-memory-game'); ?></button>
                        <button class="pmg-btn pmg-btn-ghost" type="button" data-pmg="playAgainBtn"><?php esc_html_e('Play again', 'pairs-memory-game'); ?></button>
                    </div>
                    <div class="pmg-saved-note" data-pmg="savedNote"><?php esc_html_e('Saved to the leaderboard', 'pairs-memory-game'); ?></div>
                    <div class="pmg-name-help pmg-error" data-pmg="submitError"></div>
                </form>
                <?php else : ?>
                <div class="pmg-win-actions">
                    <button class="pmg-btn pmg-btn-primary" type="button" data-pmg="playAgainBtn"><?php esc_html_e('Play again', 'pairs-memory-game'); ?></button>
                    <button class="pmg-btn pmg-btn-ghost" type="button" data-pmg="backToSetupBtn"><?php esc_html_e('Change difficulty', 'pairs-memory-game'); ?></button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($pairsmg_lb) : ?>
    <!-- ============ LEADERBOARD ============ -->
    <section class="pmg-screen" data-screen="leaderboard">
        <div class="pmg-panel">
            <div class="pmg-lb-head">
                <h3 class="pmg-lb-title"><?php echo esc_html(PairsMG_Settings::text('leaderboard_title')); ?></h3>
                <button class="pmg-btn pmg-btn-ghost" data-pmg="backFromLbBtn" type="button"><?php esc_html_e('Back', 'pairs-memory-game'); ?></button>
            </div>
            <div class="pmg-lb-tabs" data-pmg="lbTabs" role="tablist" aria-label="<?php esc_attr_e('Choose leaderboard difficulty', 'pairs-memory-game'); ?>"></div>
            <div class="pmg-lb-list" data-pmg="lbList"></div>
            <div class="pmg-lb-footer"><span class="pmg-lb-meta" data-pmg="lbCount"></span></div>
        </div>
        <div class="pmg-footer-note"><?php esc_html_e('Each difficulty has its own leaderboard, so scores are always compared between boards of the same size. Within a difficulty the score combines speed and accuracy.', 'pairs-memory-game'); ?></div>
    </section>
    <?php endif; ?>

</div>

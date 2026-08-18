<?php
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

    protected function setUp(): void { pairsmg_test_reset(); }

    public function test_defaults_are_used_when_nothing_is_saved() {
        $s = PairsMG_Settings::get();
        $this->assertSame('light', $s['theme']);
        $this->assertSame('none', $s['captcha_provider']);
        $this->assertSame(0, $s['special_per_game']);
        $this->assertSame(array('easy' => 6, 'medium' => 10, 'hard' => 14), PairsMG_Settings::pair_counts());
    }

    public function test_pair_counts_are_clamped() {
        update_option(PairsMG_Settings::OPTION, array('pairs_easy' => 1, 'pairs_hard' => 999));
        $c = PairsMG_Settings::pair_counts();
        $this->assertSame(PairsMG_Settings::MIN_PAIRS, $c['easy']);
        $this->assertSame(PairsMG_Settings::MAX_PAIRS, $c['hard']);
    }

    public function test_empty_texts_fall_back_to_defaults() {
        update_option(PairsMG_Settings::OPTION, array('intro_title' => '  ', 'anonymous_name' => 'Nobody'));
        $this->assertSame('Find the pairs', PairsMG_Settings::text('intro_title'));
        $this->assertSame('Nobody', PairsMG_Settings::text('anonymous_name'));
    }

    public function test_sanitize_only_touches_the_submitted_tab() {
        update_option(PairsMG_Settings::OPTION, array('confetti' => false, 'pairs_easy' => 4, 'captcha_provider' => 'turnstile'));
        // Saving the "protection" tab must not flip confetti back on even
        // though no confetti checkbox is in the POST.
        $out = PairsMG_Admin_Settings::sanitize(array('_tab' => 'protection', 'captcha_provider' => 'hcaptcha', 'captcha_site_key' => 'k'));
        $this->assertFalse($out['confetti']);
        $this->assertSame(4, $out['pairs_easy']);
        $this->assertSame('hcaptcha', $out['captcha_provider']);
        // And an unchecked box on its own tab IS turned off.
        $out = PairsMG_Admin_Settings::sanitize(array('_tab' => 'game', 'pairs_easy' => 5, 'pairs_medium' => 5, 'pairs_hard' => 5));
        $this->assertFalse($out['sound_default']);
        $this->assertFalse($out['confetti']);
    }

    public function test_sanitize_keeps_secret_when_field_left_blank() {
        update_option(PairsMG_Settings::OPTION, array('captcha_secret_key' => 'keep-me'));
        $out = PairsMG_Admin_Settings::sanitize(array('_tab' => 'protection', 'captcha_provider' => 'turnstile', 'captcha_secret_key' => ''));
        $this->assertSame('keep-me', $out['captcha_secret_key']);
        $out = PairsMG_Admin_Settings::sanitize(array('_tab' => 'protection', 'captcha_provider' => 'turnstile', 'captcha_secret_key' => 'new'));
        $this->assertSame('new', $out['captcha_secret_key']);
    }

    public function test_sanitize_enforces_tier_ordering_and_quota_bounds() {
        $out = PairsMG_Admin_Settings::sanitize(array('_tab' => 'game', 'pairs_easy' => 12, 'pairs_medium' => 6, 'pairs_hard' => 3, 'special_per_game' => 99));
        $this->assertSame(12, $out['pairs_easy']);
        $this->assertSame(12, $out['pairs_medium']);
        $this->assertSame(12, $out['pairs_hard']);
        $this->assertSame(12, $out['special_per_game']);
    }

    public function test_sanitize_rejects_unknown_enum_values() {
        $out = PairsMG_Admin_Settings::sanitize(array('_tab' => 'appearance', 'theme' => 'neon', 'card_ratio' => '9:16', 'color_bg' => 'red', 'corner_radius' => 500));
        $this->assertSame('light', $out['theme']);
        $this->assertSame('7:10', $out['card_ratio']);
        $this->assertSame('#f4f5f7', $out['color_bg']);
        $this->assertSame(32, $out['corner_radius']);
    }
}

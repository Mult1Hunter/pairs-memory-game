<?php
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase {

    protected function setUp(): void { pairsmg_test_reset(); }

    public function test_none_needs_no_keys_and_verifies_without_a_token() {
        $this->assertTrue(PairsMG_Captcha::is_configured());
        $this->assertFalse(PairsMG_Captcha::has_widget());
        $this->assertSame('', PairsMG_Captcha::script_url());
        $this->assertTrue(PairsMG_Captcha::verify('', '127.0.0.1'));
    }

    public function test_provider_without_keys_is_not_configured() {
        update_option(PairsMG_Settings::OPTION, array('captcha_provider' => 'turnstile'));
        $this->assertFalse(PairsMG_Captcha::is_configured());
        $err = PairsMG_Captcha::verify('tok', '127.0.0.1');
        $this->assertInstanceOf(WP_Error::class, $err);
    }

    public function test_test_mode_uses_official_test_keys() {
        update_option(PairsMG_Settings::OPTION, array('captcha_provider' => 'turnstile', 'captcha_test_mode' => true));
        $this->assertTrue(PairsMG_Captcha::is_configured());
        $this->assertSame('1x00000000000000000000AA', PairsMG_Captcha::site_key());
        $this->assertStringContainsString('challenges.cloudflare.com', PairsMG_Captcha::script_url());
    }

    public function test_successful_siteverify_passes_and_fires_action() {
        update_option(PairsMG_Settings::OPTION, array('captcha_provider' => 'hcaptcha', 'captcha_site_key' => 'site', 'captcha_secret_key' => 'sec'));
        $GLOBALS['pairsmg_test_remote'] = array('response' => array('code' => 200), 'body' => json_encode(array('success' => true)));
        $this->assertTrue(PairsMG_Captcha::verify('tok', '1.2.3.4'));
        $sent = $GLOBALS['pairsmg_test_remote_last'];
        $this->assertSame('https://api.hcaptcha.com/siteverify', $sent['url']);
        $this->assertSame('sec', $sent['args']['body']['secret']);
        $this->assertSame('site', $sent['args']['body']['sitekey']);
        $this->assertSame('1.2.3.4', $sent['args']['body']['remoteip']);
        $this->assertSame('pairsmg_captcha_verified', $GLOBALS['pairsmg_test_actions'][0][0]);
    }

    public function test_failed_siteverify_is_an_error() {
        update_option(PairsMG_Settings::OPTION, array('captcha_provider' => 'recaptcha_v2', 'captcha_site_key' => 'site', 'captcha_secret_key' => 'sec'));
        $GLOBALS['pairsmg_test_remote'] = array('response' => array('code' => 200), 'body' => json_encode(array('success' => false)));
        $this->assertInstanceOf(WP_Error::class, PairsMG_Captcha::verify('tok', '1.2.3.4'));
    }

    public function test_recaptcha_v3_threshold_is_enforced() {
        update_option(PairsMG_Settings::OPTION, array('captcha_provider' => 'recaptcha_v3', 'captcha_site_key' => 'site', 'captcha_secret_key' => 'sec', 'recaptcha_v3_threshold' => 0.7));
        $GLOBALS['pairsmg_test_remote'] = array('response' => array('code' => 200), 'body' => json_encode(array('success' => true, 'score' => 0.4)));
        $err = PairsMG_Captcha::verify('tok', '1.2.3.4');
        $this->assertInstanceOf(WP_Error::class, $err);
        $this->assertSame('pairsmg_captcha_low_score', $err->get_error_code());
        $GLOBALS['pairsmg_test_remote'] = array('response' => array('code' => 200), 'body' => json_encode(array('success' => true, 'score' => 0.9)));
        $this->assertTrue(PairsMG_Captcha::verify('tok', '1.2.3.4'));
    }

    public function test_transport_errors_bubble_up() {
        update_option(PairsMG_Settings::OPTION, array('captcha_provider' => 'turnstile', 'captcha_site_key' => 'site', 'captcha_secret_key' => 'sec'));
        $GLOBALS['pairsmg_test_remote'] = new WP_Error('http_request_failed', 'down');
        $err = PairsMG_Captcha::verify('tok', '1.2.3.4');
        $this->assertInstanceOf(WP_Error::class, $err);
        $this->assertSame('http_request_failed', $err->get_error_code());
    }
}

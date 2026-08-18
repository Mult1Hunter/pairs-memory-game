<?php
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase {

    protected function setUp(): void { pairsmg_test_reset(); }

    public function test_session_token_round_trips() {
        $t = PairsMG_Token::issue_session();
        $payload = PairsMG_Token::verify($t, 'session');
        $this->assertIsArray($payload);
        $this->assertSame('session', $payload['type']);
    }

    public function test_run_token_carries_tier_pairs_and_issue_time() {
        $before = time();
        $t = PairsMG_Token::issue_run('hard', 14);
        $p = PairsMG_Token::verify($t, 'run');
        $this->assertSame('hard', $p['tier']);
        $this->assertSame(14, $p['pairs']);
        $this->assertGreaterThanOrEqual($before, $p['iat']);
    }

    public function test_type_mismatch_is_rejected() {
        $t = PairsMG_Token::issue_session();
        $this->assertInstanceOf(WP_Error::class, PairsMG_Token::verify($t, 'run'));
    }

    public function test_tampered_payload_is_rejected() {
        $t = PairsMG_Token::issue_run('easy', 6);
        list($b64, $sig) = explode('.', $t, 2);
        $json = base64_decode(strtr($b64, '-_', '+/'));
        $forged = json_decode($json, true);
        $forged['iat'] = time() + 3600; // claim the run started in the future, i.e. took no time
        $forged_b64 = rtrim(strtr(base64_encode(json_encode($forged)), '+/', '-_'), '=');
        $err = PairsMG_Token::verify($forged_b64 . '.' . $sig, 'run');
        $this->assertInstanceOf(WP_Error::class, $err);
        $this->assertSame('pairsmg_bad_signature', $err->get_error_code());
    }

    public function test_expired_token_is_rejected() {
        $t = PairsMG_Token::issue_run('easy', 6, -1);
        $err = PairsMG_Token::verify($t, 'run');
        $this->assertInstanceOf(WP_Error::class, $err);
        $this->assertSame('pairsmg_token_expired', $err->get_error_code());
    }

    public function test_garbage_is_rejected() {
        $this->assertInstanceOf(WP_Error::class, PairsMG_Token::verify('nope', 'run'));
        $this->assertInstanceOf(WP_Error::class, PairsMG_Token::verify('a.b', 'run'));
        $this->assertInstanceOf(WP_Error::class, PairsMG_Token::verify(null, 'run'));
    }

    public function test_secret_is_generated_once_and_persisted() {
        PairsMG_Token::issue_session();
        $secret = get_option(PairsMG_Token::SECRET_OPTION);
        $this->assertNotEmpty($secret);
        PairsMG_Token::issue_session();
        $this->assertSame($secret, get_option(PairsMG_Token::SECRET_OPTION));
    }

    public function test_tokens_signed_with_another_secret_do_not_verify() {
        $t = PairsMG_Token::issue_session();
        update_option(PairsMG_Token::SECRET_OPTION, 'a-different-secret');
        $this->assertInstanceOf(WP_Error::class, PairsMG_Token::verify($t, 'session'));
    }

    public function test_consume_once_rejects_replay() {
        $this->assertTrue(PairsMG_Token::consume_once('nonce-1'));
        $this->assertFalse(PairsMG_Token::consume_once('nonce-1'));
        $this->assertTrue(PairsMG_Token::consume_once('nonce-2'));
    }
}

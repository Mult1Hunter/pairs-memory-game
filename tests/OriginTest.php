<?php
use PHPUnit\Framework\TestCase;

class OriginTest extends TestCase {

    protected function setUp(): void { pairsmg_test_reset(); }

    public function test_same_host_is_allowed_regardless_of_scheme_or_case() {
        $this->assertTrue(PairsMG_REST::origin_allowed('https://example.test'));
        $this->assertTrue(PairsMG_REST::origin_allowed('http://EXAMPLE.test'));
        $this->assertTrue(PairsMG_REST::origin_allowed('https://example.test:8443'));
    }

    public function test_missing_origin_is_allowed_but_null_origin_is_not() {
        $this->assertTrue(PairsMG_REST::origin_allowed(''));
        $this->assertFalse(PairsMG_REST::origin_allowed('null'));
    }

    public function test_other_hosts_are_rejected() {
        $this->assertFalse(PairsMG_REST::origin_allowed('https://evil.example'));
        $this->assertFalse(PairsMG_REST::origin_allowed('https://example.test.evil.example'));
        $this->assertFalse(PairsMG_REST::origin_allowed('garbage'));
    }

    public function test_filter_can_allow_extra_hosts() {
        add_filter('pairsmg_allowed_origins', function ($hosts) { $hosts[] = 'partner.example'; return $hosts; });
        $this->assertTrue(PairsMG_REST::origin_allowed('https://partner.example'));
        $this->assertTrue(PairsMG_REST::origin_allowed('https://example.test'));
        $this->assertFalse(PairsMG_REST::origin_allowed('https://other.example'));
    }
}

<?php
use PHPUnit\Framework\TestCase;

class ScoringTest extends TestCase {

    protected function setUp(): void { pairsmg_test_reset(); }

    public function test_perfect_game_scores_max() {
        // 6 pairs in 6 moves within par (6 * 3.2 = 19.2s).
        $this->assertSame(1000, PairsMG_Scoring::compute(6, 6, 10));
    }

    public function test_extra_moves_reduce_score_proportionally() {
        // 12 moves for 6 pairs -> move factor 0.5.
        $this->assertSame(500, PairsMG_Scoring::compute(6, 12, 10));
    }

    public function test_slow_time_reduces_score() {
        // Twice par -> time factor 0.5.
        $this->assertSame(500, PairsMG_Scoring::compute(10, 10, 64));
    }

    public function test_moves_are_floored_at_pair_count() {
        // A client claiming fewer moves than pairs cannot beat 1000.
        $this->assertSame(1000, PairsMG_Scoring::compute(6, 1, 5));
    }

    public function test_floor_is_25() {
        $this->assertSame(25, PairsMG_Scoring::compute(6, 600, 6000));
    }

    public function test_score_filter_is_applied() {
        add_filter('pairsmg_score', function ($score) { return $score + 1; });
        $this->assertSame(1001, PairsMG_Scoring::compute(6, 6, 1));
    }

    public function test_min_time_scales_with_pairs_and_is_filterable() {
        $this->assertEqualsWithDelta(4.8, PairsMG_Scoring::min_time(6), 0.001);
        $this->assertEqualsWithDelta(11.2, PairsMG_Scoring::min_time(14), 0.001);
        $this->assertGreaterThanOrEqual(1, PairsMG_Scoring::min_time(1));
        add_filter('pairsmg_min_time', function ($t, $pairs) { return $pairs * 2; });
        $this->assertEqualsWithDelta(12, PairsMG_Scoring::min_time(6), 0.001);
    }

    public function test_min_time_is_always_below_par() {
        foreach (array(3, 6, 10, 14, 24) as $pairs) {
            $this->assertLessThan(PairsMG_Scoring::par_time($pairs), PairsMG_Scoring::min_time($pairs));
        }
    }
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The score formula, in one place. The frontend has a copy for the
 * count-up preview on the win screen, but only THIS copy - run against
 * server-measured time - is ever stored or ranked.
 *
 * score = 1000 * (pairs / moves) * min(1, par / elapsed), floor 25,
 * where par = pairs * 3.2 seconds. Filterable for sites that want a
 * different curve.
 */
class PairsMG_Scoring {

    const PAR_SECONDS_PER_PAIR = 3.2;
    const MIN_SECONDS_PER_PAIR = 0.8;
    const MAX_SCORE = 1000;
    const MIN_SCORE = 25;

    public static function par_time($pairs) {
        return (float) apply_filters('pairsmg_par_time', $pairs * self::PAR_SECONDS_PER_PAIR, $pairs);
    }

    /**
     * Fastest a run can plausibly be finished by a person: every one of the
     * 2 x pairs cards has to be turned. Runs finished faster than this are
     * refused, which stops a script from earning a perfect score by calling
     * start and finish back to back. Filterable per site.
     */
    public static function min_time($pairs) {
        return (float) apply_filters('pairsmg_min_time', max(1, $pairs * self::MIN_SECONDS_PER_PAIR), $pairs);
    }

    public static function compute($pairs, $moves, $elapsed) {
        $pairs = max(1, (int) $pairs);
        $eff_moves = max((int) $moves, $pairs);
        $par = self::par_time($pairs);
        $time_factor = min(1, $par / max((int) $elapsed, 1));
        $move_factor = $pairs / $eff_moves;
        $score = (int) round(self::MAX_SCORE * $move_factor * $time_factor);
        $score = max($score, self::MIN_SCORE);

        /**
         * Filter the computed score.
         *
         * @param int $score
         * @param int $pairs
         * @param int $moves
         * @param int $elapsed Seconds, server-measured.
         */
        return (int) apply_filters('pairsmg_score', $score, $pairs, $eff_moves, (int) $elapsed);
    }
}

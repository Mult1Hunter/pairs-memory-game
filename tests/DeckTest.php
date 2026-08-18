<?php
use PHPUnit\Framework\TestCase;

class DeckTest extends TestCase {

    protected function setUp(): void { pairsmg_test_reset(); }

    private function seed_pool(array $cards) {
        set_transient(PairsMG_Post_Type::CACHE_KEY, $cards);
    }

    private function card($id, $special = false) {
        return array('id' => (string) $id, 'url' => "https://x/$id.png", 'alt' => "Card $id", 'special' => $special, 'fit' => 'inset');
    }

    private function ids(array $deck) {
        $ids = array_map(function ($c) { return $c['id']; }, $deck);
        sort($ids);
        return $ids;
    }

    public function test_own_cards_are_used_before_the_default_deck() {
        $this->seed_pool(array($this->card(1), $this->card(2), $this->card(3)));
        $built = PairsMG_Deck::build(3);
        $this->assertCount(3, $built['deck']);
        $this->assertSame(0, $built['usedDefaults']);
        $this->assertSame(array('1', '2', '3'), $this->ids($built['deck']));
    }

    public function test_default_deck_only_tops_up_the_shortfall() {
        $this->seed_pool(array($this->card(1), $this->card(2)));
        $built = PairsMG_Deck::build(6);
        $this->assertCount(6, $built['deck']);
        $this->assertSame(4, $built['usedDefaults']);
        $ids = $this->ids($built['deck']);
        $this->assertContains('1', $ids);
        $this->assertContains('2', $ids);
    }

    public function test_default_deck_can_be_disabled() {
        update_option(PairsMG_Settings::OPTION, array('use_default_deck' => false));
        $this->seed_pool(array($this->card(1), $this->card(2)));
        $built = PairsMG_Deck::build(6);
        $this->assertCount(2, $built['deck']);
        $this->assertSame(0, $built['usedDefaults']);
        $this->assertSame(0, PairsMG_Deck::stats()['defaults']);
    }

    public function test_special_quota_is_guaranteed_when_enough_exist() {
        update_option(PairsMG_Settings::OPTION, array('special_per_game' => 2));
        $pool = array($this->card('s1', true), $this->card('s2', true), $this->card('s3', true));
        for ($i = 1; $i <= 20; $i++) { $pool[] = $this->card($i); }
        $this->seed_pool($pool);
        for ($run = 0; $run < 10; $run++) {
            $deck = PairsMG_Deck::build(6)['deck'];
            $specials = array_filter($deck, function ($c) { return $c['special']; });
            $this->assertGreaterThanOrEqual(2, count($specials), 'quota not met on run ' . $run);
        }
    }

    public function test_special_quota_zero_means_no_guarantee_but_still_allowed() {
        // Default quota is 0: specials are ordinary cards, so with a huge
        // plain pool they can be absent, and with only specials they fill.
        $this->seed_pool(array($this->card('s1', true), $this->card('s2', true), $this->card('s3', true)));
        $deck = PairsMG_Deck::build(3)['deck'];
        $this->assertSame(array('s1', 's2', 's3'), $this->ids($deck));
    }

    public function test_deck_is_shuffled_and_exactly_needed_size() {
        $pool = array();
        for ($i = 1; $i <= 30; $i++) { $pool[] = $this->card($i); }
        $this->seed_pool($pool);
        $a = PairsMG_Deck::build(10)['deck'];
        $b = PairsMG_Deck::build(10)['deck'];
        $this->assertCount(10, $a);
        $this->assertCount(10, $b);
        // 30 choose 10 - identical picks twice in a row would be astonishing.
        $this->assertNotSame(array_column($a, 'id'), array_column($b, 'id'));
    }

    public function test_default_cards_are_flagged_and_point_into_the_plugin() {
        $cards = PairsMG_Deck::default_cards();
        $this->assertCount(16, $cards);
        foreach ($cards as $c) {
            $this->assertTrue($c['isDefault']);
            $this->assertStringStartsWith(PAIRSMG_URL . 'assets/cards/', $c['url']);
            $this->assertStringStartsWith('default:', $c['id']);
        }
    }
}

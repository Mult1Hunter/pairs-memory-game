<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chooses which pairs appear on a given board.
 *
 * Three rules, in priority order:
 *
 *  1. Your own cards win. The built-in deck is a fallback, not a mixer -
 *     it is only drawn from when there are not enough published cards to
 *     fill the chosen board size (and only if enabled in Settings).
 *  2. Special cards are guaranteed, up to the configured quota, as long as
 *     enough exist.
 *  3. Everything else is filled randomly, so the board differs every game
 *     once there are more cards than a board can hold.
 *
 * Selection happens on the server (during /start-run) so the quota is
 * actually enforced and the client is handed only the cards for the run it
 * is about to play.
 */
class PairsMG_Deck {

    /** slug => label; files live in assets/cards/<slug>.svg */
    const DEFAULT_CARDS = array(
        'stag'     => 'Stag',
        'boar'     => 'Boar',
        'wolf'     => 'Wolf',
        'owl'      => 'Owl',
        'eagle'    => 'Eagle',
        'hare'     => 'Hare',
        'fox'      => 'Fox',
        'bear'     => 'Bear',
        'lynx'     => 'Lynx',
        'chamois'  => 'Chamois',
        'hedgehog' => 'Hedgehog',
        'trout'    => 'Trout',
        'snake'    => 'Snake',
        'squirrel' => 'Squirrel',
        'heron'    => 'Heron',
        'badger'   => 'Badger',
    );

    public static function default_cards() {
        $out = array();
        foreach (self::DEFAULT_CARDS as $slug => $label) {
            $out[] = array(
                'id'        => 'default:' . $slug,
                'url'       => PAIRSMG_URL . 'assets/cards/' . $slug . '.svg',
                'alt'       => $label,
                'special'   => false,
                'isDefault' => true,
                'fit'       => 'inset',
            );
        }
        /**
         * Filter the built-in fallback deck.
         *
         * @param array $out Cards.
         */
        return apply_filters('pairsmg_default_cards', $out);
    }

    public static function default_enabled() {
        $s = PairsMG_Settings::get();
        return !empty($s['use_default_deck']);
    }

    public static function default_count() {
        return self::default_enabled() ? count(self::default_cards()) : 0;
    }

    /**
     * @param int $needed Pairs required for the chosen tier.
     * @return array{deck:array,usedDefaults:int} Cards for one board.
     */
    public static function build($needed) {
        $needed = max(0, (int) $needed);
        $custom = PairsMG_Post_Type::get_active_pairs();

        $special = array();
        $plain = array();
        foreach ($custom as $pair) {
            if (!empty($pair['special'])) {
                $special[] = $pair;
            } else {
                $plain[] = $pair;
            }
        }

        $s = PairsMG_Settings::get();
        $quota = max(0, min((int) $s['special_per_game'], $needed));

        shuffle($special);
        shuffle($plain);

        $deck = array();

        // 1. Guaranteed special cards, as far as the pool allows.
        $take = min($quota, count($special));
        for ($i = 0; $i < $take; $i++) {
            $deck[] = $special[$i];
        }

        // 2. Fill with the remaining own cards. Specials beyond the quota
        //    go back into the general pool - they are still good cards.
        $rest = array_merge($plain, array_slice($special, $take));
        shuffle($rest);
        foreach ($rest as $pair) {
            if (count($deck) >= $needed) {
                break;
            }
            $deck[] = $pair;
        }

        // 3. Only now, if there simply are not enough own cards, top up
        //    from the built-in deck.
        $used_defaults = 0;
        if (count($deck) < $needed && self::default_enabled()) {
            $defaults = self::default_cards();
            shuffle($defaults);
            foreach ($defaults as $card) {
                if (count($deck) >= $needed) {
                    break;
                }
                $deck[] = $card;
                $used_defaults++;
            }
        }

        shuffle($deck);

        /**
         * Filter the deck chosen for one board, after selection and shuffle.
         *
         * @param array $deck   Cards.
         * @param int   $needed Pairs the board needs.
         */
        $deck = apply_filters('pairsmg_build_deck', $deck, $needed);

        return array('deck' => $deck, 'usedDefaults' => $used_defaults);
    }

    /** Pool numbers for the admin screen and the frontend. */
    public static function stats() {
        $custom = PairsMG_Post_Type::get_active_pairs();
        $special = 0;
        foreach ($custom as $p) {
            if (!empty($p['special'])) {
                $special++;
            }
        }
        return array(
            'custom'   => count($custom),
            'special'  => $special,
            'defaults' => self::default_count(),
            'total'    => count($custom) + self::default_count(),
        );
    }

    /** Largest board that can currently be filled. */
    public static function max_fillable() {
        $stats = self::stats();
        return (int) $stats['total'];
    }
}

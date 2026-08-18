# Pairs - Memory Game (WordPress plugin)

A memory (concentration) game for WordPress: your own card images, three
difficulty tiers with separate leaderboards, server-verified scores and
optional bot protection (Cloudflare Turnstile, Google reCAPTCHA v2/v3,
hCaptcha). Drop it in with a block, a shortcode, or the dedicated page the
plugin maintains for you.

- Requires WordPress 6.0+, PHP 7.4+. Tested up to WordPress 7.0.
- License: GPLv2 or later.
- Author: [Matic Korošec](https://nextgen-solutions.xyz)

The user-facing description, FAQ and changelog live in [`readme.txt`](readme.txt)
(the WordPress.org format). This file is for people working on the code.

## Layout

```
pairs-memory-game.php        bootstrap: constants, requires, hooks
includes/
  class-pairsmg-settings.php     one option array, defaults, presets
  class-pairsmg-db.php           scores table (dbDelta), reads/writes
  class-pairsmg-token.php        HMAC session/run tokens, single-use marker
  class-pairsmg-captcha.php      provider abstraction + server-side verify
  class-pairsmg-post-type.php    "Cards" CPT, meta box, cached active pool
  class-pairsmg-deck.php         board selection (specials, own cards, fallback)
  class-pairsmg-scoring.php      the score formula
  class-pairsmg-rest.php         public REST routes + rate limiting
  class-pairsmg-assets.php       registration, theme CSS vars, localized config
  class-pairsmg-shortcode.php    [pairs_memory_game] + shared renderer
  class-pairsmg-block.php        dynamic block wrapper
  class-pairsmg-game-page.php    optional dedicated page at a stable slug
  class-pairsmg-admin-settings.php   tabbed settings screen
  class-pairsmg-admin-leaderboard.php moderation, clear, CSV export
  class-pairsmg-cron.php         daily sweep of expired rate-limit/token transients
templates/game.php           frontend markup (data-pmg hooks, no ids)
assets/js/game.js            frontend logic (no build step, ES5)
assets/css/game.css          scoped styles, all colours via --pmg-* vars
assets/cards/*.svg           built-in 16-card fallback deck
assets/fonts/                Rajdhani + Open Sans (OFL), served locally
blocks/game/                 block.json + plain-JS editor script
languages/                   .pot + shipped translations
uninstall.php                removes data only if opted in
```

## Request flow

```
GET  /config          -> pair counts, pool stats
POST /verify          captcha token (or nothing when provider = none) -> session token
POST /start-run       session token + tier -> run token + server-picked deck
POST /finish-run      run token + moves -> server-timed score (single use)
POST /submit-score    run token + name -> stored, ranked
GET  /leaderboard     tier, limit -> entries
```

Namespace: `pairs-memory-game/v1`. All routes are anonymous by design (players
are visitors, not WordPress users); trust comes from the signed tokens, not
WordPress auth. The POST routes additionally refuse browser requests whose
`Origin` is another site (filter `pairsmg_allowed_origins`). See
[SECURITY.md](SECURITY.md).

## Development

Local WordPress via Docker is not part of this repo - point any WordPress
install's `wp-content/plugins/pairs-memory-game` at a checkout.

```
composer install          # PHPCS (WordPress + PHPCompatibility), parallel-lint, PHPUnit
composer check            # lint + phpcs + unit tests
composer test             # unit tests only (tests/, no WordPress needed - see tests/bootstrap.php)
node --check assets/js/game.js blocks/game/index.js
```

The unit tests cover the parts that must not regress silently: the score
formula, token signing / expiry / tamper / replay, deck selection (own
cards first, special quota, fallback deck), settings sanitising (per-tab
checkbox handling, secret retention, clamping) and the captcha provider
matrix. They run against a ~100-line stub of the WordPress functions the
classes use, so they take milliseconds and need no database.

Regenerate the translation template after changing strings:

```
wp i18n make-pot . languages/pairs-memory-game.pot --exclude=vendor,node_modules,.github,bin,tests
python3 bin/build-sl_SI.py && wp i18n make-mo languages
```

Build the release zip (respects `.distignore`):

```
mkdir -p /tmp/dist/pairs-memory-game
rsync -a --exclude-from=.distignore ./ /tmp/dist/pairs-memory-game/
(cd /tmp/dist && zip -r pairs-memory-game.zip pairs-memory-game)
```

Tagging `vX.Y.Z` runs the release workflow, which checks that the tag,
plugin header, `PAIRSMG_VERSION` and `Stable tag` agree and attaches the zip
to the GitHub release.

## Hooks

Filters: `pairsmg_settings`, `pairsmg_pair_counts`, `pairsmg_active_pairs`,
`pairsmg_default_cards`, `pairsmg_build_deck`, `pairsmg_par_time`,
`pairsmg_score`, `pairsmg_sanitize_name`, `pairsmg_client_ip`,
`pairsmg_theme_css`, `pairsmg_frontend_config`, `pairsmg_allowed_origins`,
`pairsmg_min_time`.

Actions: `pairsmg_run_started`, `pairsmg_run_rejected`, `pairsmg_score_saved`,
`pairsmg_captcha_verified`, `pairsmg_cleanup_done`.

## Contributing

Issues and pull requests are welcome. Keep to the existing style (4-space
PSR-ish PHP, ES5 JS, no build step), escape everything on output, sanitize
everything on input, and make CI green.

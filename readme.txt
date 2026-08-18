=== Pairs - Memory Game ===
Contributors: mult1hunter
Tags: memory game, matching game, game, leaderboard, gamification
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Memory game with your own card images, three difficulties, server-verified scores, per-tier leaderboards and optional bot protection.

== Description ==

Pairs turns any page into a memory game (also known as concentration, matching pairs, pexeso or memo): players flip two cards at a time and find every matching pair as fast and as accurately as they can. Use it for sponsor logos at a club event, a brand or product campaign, a school, kindergarten or museum page, a quiz night, or just for fun.

**Cards are yours.** Upload images as "Cards" (a custom post type with the familiar Media Library uploader). Published cards are in the game, drafts are not. Optionally flag a card as *special* and set a quota to guarantee it appears on every board - a main sponsor, an easter egg, a prize card. Until you have enough cards, a built-in deck of 16 hand-drawn animal cards tops up the board.

**Scores cannot be faked.** The browser never sends a score or a time that is trusted. Each game issues a signed, single-use run token; when the board is cleared the server stamps the elapsed time from its own clock and computes the score itself. Names are stored, IPs are only stored as a salted hash for rate limiting.

**Three difficulties, three leaderboards.** Easy / Medium / Hard with configurable pair counts (3-24), each with its own leaderboard so scores are always compared between boards of the same size. Moderate scores, clear a tier or export CSV from wp-admin.

**Optional bot protection.** Cloudflare Turnstile, Google reCAPTCHA v2 / v3, hCaptcha - or none. Verification is always server-side, and every provider has a test mode for local and staging sites.

**Looks like your site.** Light, dark and parchment presets plus fully custom colours, bundled or inherited fonts, card shape (7:10 / 3:4 / square), corner radius, an optional card-back image. Flat and square by default. Phones can run the game full-screen with a "back to site" button.

**Drop it anywhere.** A block ("Pairs - Memory Game"), a shortcode (`[pairs_memory_game]`) and an optional dedicated page at a slug you choose (default `/memory-game/`) - handy for a QR code on a poster.

**For developers.** Every decision is filterable: `pairsmg_settings`, `pairsmg_pair_counts`, `pairsmg_active_pairs`, `pairsmg_default_cards`, `pairsmg_build_deck`, `pairsmg_score`, `pairsmg_par_time`, `pairsmg_sanitize_name`, `pairsmg_client_ip`, `pairsmg_theme_css`, `pairsmg_frontend_config`; actions `pairsmg_run_started`, `pairsmg_score_saved`, `pairsmg_captcha_verified`. Source and issues on [GitHub](https://github.com/Mult1Hunter/pairs-memory-game).

= Third-party services =

The plugin makes no external requests unless you enable a bot-protection provider under Memory Game > Settings > Bot protection. When a provider is enabled, the game page loads that provider's script and, on verification, the plugin sends the response token and the visitor's IP address to the provider's verification endpoint:

* Cloudflare Turnstile - [terms](https://www.cloudflare.com/website-terms/), [privacy](https://www.cloudflare.com/privacypolicy/)
* Google reCAPTCHA - [terms](https://policies.google.com/terms), [privacy](https://policies.google.com/privacy)
* hCaptcha - [terms](https://www.hcaptcha.com/terms), [privacy](https://www.hcaptcha.com/privacy)

Mention the provider you use in your privacy policy. With the provider set to "None", nothing leaves your server.

The bundled fonts (Rajdhani, Open Sans - both under the SIL Open Font License) are served from your own site; no font CDN is contacted.

== Installation ==

1. Upload the `pairs-memory-game` folder to `/wp-content/plugins/`, or install it from Plugins > Add New.
2. Activate the plugin. A page at `/memory-game/` is created for you (you can turn that off).
3. Add cards under Memory Game > Cards: title + featured image, publish.
4. Optionally set up bot protection, colours and texts under Memory Game > Settings.
5. Put the "Pairs - Memory Game" block or `[pairs_memory_game]` on any other page you like.

== Frequently Asked Questions ==

= What images should I upload? =

Square PNGs with a transparent background work best for logos (about 600 x 600 px, under 200 KB). Photos can be JPG or WebP. Keep them simple: on a phone a card is only about 80 px wide. If your artwork is a finished card face, set "Card image fit" to "Full face" so it fills the card.

= How is the score calculated? =

`1000 x (pairs / moves) x min(1, par / time)`, where par is 3.2 seconds per pair and time is measured by the server. Fewer wrong guesses and a faster time mean a higher score; the floor is 25. Filter `pairsmg_score` to change it.

= Can players cheat? =

They can flip cards however they like, but not the score. Time is stamped by the server when the last pair is matched, moves are floored at the theoretical minimum, runs finished faster than a person can turn the cards are refused, and each run is stored at most once (enforced by the database). Bot protection additionally keeps scripted play out.

= Can I disable the leaderboard? =

Yes - Memory Game > Settings > Game > Leaderboard. The game still shows the score, but nothing is stored and no name is asked for.

= Does it work with page builders and block themes? =

Yes. The shortcode works in any content area and the block in the block editor. Assets are only loaded on pages where the game renders.

= Can I run more than one game per page? =

One instance per page is supported.

= Is it GDPR friendly? =

Only the display name a player types is stored with the score. IP addresses are stored as a salted, non-reversible hash for rate limiting only. If you enable a bot-protection provider, that provider receives the visitor's IP - see "Third-party services" above.

== Screenshots ==

1. Setup screen with the three difficulties and the top scores.
2. The board, mid-game.
3. Win screen with the server-verified score.
4. Leaderboard.
5. Settings - Game tab.
6. Settings - Appearance tab.
7. Cards list in wp-admin.

== Changelog ==

= 1.0.4 =
* Leaderboard reads are served from the object cache (invalidated on every write), so sites with Redis/Memcached no longer hit the database for the hot queries.
* Plugin Check now reports no findings on the shipped plugin.

= 1.0.3 =
* Stored run nonces are derived with SHA-256 (was MD5); no functional change.

= 1.0.2 =
* Runs finished faster than a person can turn the cards (0.8 s per pair, filter `pairsmg_min_time`) are refused, so a script cannot post perfect scores by starting and finishing a run back to back.
* One score per run is now guaranteed by a unique index on the scores table, not only by transient checks; existing rows are migrated automatically.

= 1.0.1 =
* Cross-site requests to the game API are refused (Origin check on the POST routes; `pairsmg_allowed_origins` filter for legitimate exceptions).
* Daily WP-Cron sweep of expired rate-limit and token transients.

= 1.0.0 =
* First public release.

== Upgrade Notice ==

= 1.0.0 =
First release.

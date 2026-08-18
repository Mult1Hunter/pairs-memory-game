# Security policy

## Supported versions

Only the latest release on the `main` branch receives security fixes.

## Reporting a vulnerability

Please do not open a public issue for security problems.

Report privately via GitHub's "Report a vulnerability" button on the
Security tab of this repository (private vulnerability reporting), or email
the address on https://nextgen-solutions.xyz. You will get an
acknowledgement within a few days and a fix or a mitigation plan as soon as
one exists. Credit is given in the changelog unless you prefer otherwise.

## Security model, in short

- The browser never sends a score or an elapsed time that is trusted. Every
  game start issues an HMAC-signed, single-use run token with a server-side
  timestamp; the server stamps elapsed time and recomputes the score when the
  board is cleared. Replays are rejected.
- Bot protection (Turnstile / reCAPTCHA / hCaptcha), when enabled, is
  verified server-side against the provider's endpoint before a session token
  is issued.
- All public REST endpoints are rate limited per IP (configurable). IPs are
  stored only as a salted hash. Expired limiter/token transients are swept
  daily by WP-Cron so wp_options cannot bloat under a flood.
- The state-changing endpoints reject cross-site browser requests: an
  `Origin` header that is not this site (or a host allowed through the
  `pairsmg_allowed_origins` filter) gets a 403, so a third-party page cannot
  drive the game API or fill the leaderboard from elsewhere.
- Runs finished faster than a person can physically turn the cards
  (`pairs x 0.8 s`, filter `pairsmg_min_time`) are refused, so start+finish
  back to back earns nothing. One score per run is enforced by a UNIQUE
  index on the run nonce in the scores table - two racing submits cannot
  both land.
- Known limit: a script that plays at human pace can still post good
  scores; bot protection (per-session challenge) and the rate limits are the
  mitigation. Server-side card flips were considered and rejected as too
  chatty for shared hosting.
- All database access goes through `$wpdb->prepare()` or `$wpdb->insert()`
  / `->delete()`. All admin output is escaped, all input sanitized. Every
  destructive admin action requires `manage_options` and a nonce.
- Secrets (the bot-protection secret key, the HMAC secret) are stored in
  WordPress options and never sent to the browser.

CI runs WordPress security sniffs, PHPCompatibility, CodeQL, Semgrep and a
secret scan on every push - see `.github/workflows/`.

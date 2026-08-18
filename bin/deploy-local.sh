#!/usr/bin/env bash
# Build the release zip exactly like the GitHub workflow does and install it
# into the local Docker WordPress (PERUN stack, wp-cli container "wpcli") as
# a real plugin upgrade - the way an end user gets it, not a bind mount.
#
# Usage: bin/deploy-local.sh            (from the plugin repo root)
#        STACK_DIR=/path/to/compose bin/deploy-local.sh
set -euo pipefail

HERE="$(cd "$(dirname "$0")/.." && pwd)"
STACK_DIR="${STACK_DIR:-$HERE/..}"
VERSION="$(sed -n "s/^define('PAIRSMG_VERSION', '\([^']*\)');/\1/p" "$HERE/pairs-memory-game.php")"
DIST="$(mktemp -d)"
trap 'rm -rf "$DIST"' EXIT

mkdir -p "$DIST/pairs-memory-game"
rsync -a --exclude-from="$HERE/.distignore" "$HERE/" "$DIST/pairs-memory-game/"
(cd "$DIST" && zip -qr "pairs-memory-game-$VERSION.zip" pairs-memory-game)

# Same guard as the release workflow: everything under one slug folder.
if unzip -l "$DIST/pairs-memory-game-$VERSION.zip" | awk 'NR>3 {print $4}' | grep -v '^$' | grep -qvE '^pairs-memory-game/'; then
  echo "zip has files outside pairs-memory-game/" >&2; exit 1
fi

CID="$(cd "$STACK_DIR" && docker compose ps -q wpcli)"
[ -n "$CID" ] || { echo "wpcli container not running in $STACK_DIR" >&2; exit 1; }
docker cp "$DIST/pairs-memory-game-$VERSION.zip" "$CID:/tmp/pairs-memory-game.zip"
(cd "$STACK_DIR" && docker compose exec -T wpcli wp plugin install /tmp/pairs-memory-game.zip --force --activate)
(cd "$STACK_DIR" && docker compose exec -T wpcli wp plugin get pairs-memory-game --fields=name,version,status)

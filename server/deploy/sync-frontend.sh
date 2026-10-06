#!/usr/bin/env bash
# Copies the pages (the same files as the GitHub Pages demo) into server/public and switches them to the Laravel API.
# Run after every change to the pages:  server/deploy/sync-frontend.sh [--demo-accounts]
#   --demo-accounts  show the demo accounts box on the login page (for a demo server seeded with DemoSeeder)
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"
site="$(cd "$here/../.." && pwd)"
public="$here/../public"
demo=false
[ "${1:-}" = "--demo-accounts" ] && demo=true

for item in index.html spg leader supervisor katalog admin assets; do
  rm -rf "${public:?}/$item"
  cp -R "$site/$item" "$public/$item"
done
cat > "$public/assets/js/config.js" <<JS
/* Written by server/deploy/sync-frontend.sh: pages use the Laravel API of this server. */
window.APP_CONFIG = { backend: 'laravel', api: '/api', demoAccounts: $demo };
JS
echo "Pages copied to $public (demo accounts: $demo)"

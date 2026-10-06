#!/usr/bin/env bash
# Refreshes server/public/assets (committed, so the app runs right after a checkout) from the shared
# CSS, JavaScript and images in the repository root (assets/, also used by the GitHub Pages demo).
# The pages themselves are Blade views in resources/views.
# Run after every change in the root assets/ and commit the result:  server/deploy/sync-assets.sh
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"
site="$(cd "$here/../.." && pwd)"
public="$here/../public"
rm -rf "${public:?}/assets"
cp -R "$site/assets" "$public/assets"
# Demo-only files: the server injects its settings into each page and versions files itself
rm -f "$public/assets/js/config.js" "$public/assets/version.json"
echo "Assets copied to $(cd "$public" && pwd)/assets"

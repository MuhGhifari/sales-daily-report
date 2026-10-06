#!/usr/bin/env bash
# Copies the shared CSS, JavaScript and images (assets/ in the repository root, also used by the
# GitHub Pages demo) into server/public/assets. The pages themselves are Blade views in resources/views.
# Run after every change in assets/:  server/deploy/sync-assets.sh
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"
site="$(cd "$here/../.." && pwd)"
public="$here/../public"
rm -rf "${public:?}/assets"
cp -R "$site/assets" "$public/assets"
# Demo-only files: the server injects its settings into each page and versions files itself
rm -f "$public/assets/js/config.js" "$public/assets/version.json"
echo "Assets copied to $(cd "$public" && pwd)/assets"

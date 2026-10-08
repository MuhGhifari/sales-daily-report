# Brand assets

- `beiersdorf-logo.png` — Beiersdorf logo (blue wordmark on a transparent or white background, about 600 px wide), from
  https://upload.wikimedia.org/wikipedia/commons/b/b8/Beiersdorf_Logo_blue_RGB_%281%29.png
  Used in the header and on the login page (`BRAND_LOGO` in `assets/js/common.js`). While the file is missing, the pages show only the app name.
- `favicon.png` — 64 px icon for the browser tab.

To replace the logo, overwrite `beiersdorf-logo.png` (and `favicon.png`), run `python3 tools/bump-version.py`, and on the Laravel branch `server/deploy/sync-assets.sh`.

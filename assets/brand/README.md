# Brand assets

Put the official NIVEA logo here as **`nivea-logo.svg`** (preferred) or **`nivea-logo.png`**
(white or blue version on a transparent background; it is shown on a white tile in the blue header).

Then set `BRAND_LOGO` at the top of `assets/js/common.js` to that path (e.g. `'assets/brand/nivea-logo.svg'`)
and run `python3 tools/bump-version.py`. The header and login page then show the logo; while it is empty
they show the generic app mark. Use the logo files supplied by the client's brand team.

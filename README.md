# Laporan SPG — Laporan Penjualan Harian (NIVEA)

Prototype of a daily sales report app for SPGs, Team Leaders and Supervisors. Static HTML/CSS/JS with demo data; no server needed.

**Live demo:** https://muhghifari.github.io/sales-daily-report/

## Demo accounts

| Role | Username | Password | Lands on |
|------|----------|----------|----------|
| SPG | `sari` (also `dewi`, `rani`, `putri`, `maya`, `indah`, `fitri`, `lina`) | `spg123` | Beranda (daily % ring, level, rank, streak) |
| Team Leader | `rina` | `leader123` | Dashboard Tim |
| Supervisor | `budi` | `super123` | Dashboard Area |
| Admin | `admin` | `admin123` | Produk |

Try `rani` / `spg123` to start a shift from scratch (Sari already has a shift running with sales). "Today" in the demo is **Kamis, 22 Oktober 2026**. Changes (reports, targets, settings) are saved in your browser; use **Reset data demo** on the login page to start over.
The passwords are hardcoded in `assets/js/data.js` and visible to anyone — demo only.

## Pages

| Page | Who |
|------|-----|
| `index.html` — Masuk | everyone |
| `spg/beranda.html` — big daily % ring, level, rank, streak, week/month progress | SPG |
| `spg/laporan.html` — Penjualan: start/end shift, record each sale as it happens | SPG (Leader/Supervisor can edit too) |
| `spg/peringkat.html` — team leaderboard by % of target | SPG |
| `spg/riwayat.html` — report history per month | SPG |
| `leader/dashboard.html` — team summary, SPG ranking, missing reports, products, CSV export | Leader, Supervisor |
| `leader/laporan.html` — team reports, detail, unlock | Leader, Supervisor |
| `leader/target.html` — monthly targets, auto weekly/daily, overrides | Leader, Supervisor |
| `leader/tim.html` — add / deactivate SPGs | Leader, Supervisor |
| `leader/pengaturan.html` — working days, holidays, edit window | Leader, Supervisor |
| `supervisor/dashboard.html` — team comparison, top SPGs in the area | Supervisor |
| `admin/produk.html` — product catalog & prices | Admin |

See [`PLAN.md`](PLAN.md) for the product plan, [`IMPLEMENTATION.md`](IMPLEMENTATION.md) for how the prototype is built, [`IMPLEMENTATION-GOOGLE-SHEETS.md`](IMPLEMENTATION-GOOGLE-SHEETS.md) for the plan to run it on Google Sheets, and [`IMPLEMENTATION-WEB.md`](IMPLEMENTATION-WEB.md) for the Laravel + MySQL web version.

## Publishing changes

After editing any page, run `python3 tools/bump-version.py` before committing. It stamps a new version on every page and in `assets/version.json`; pages that a browser still has cached detect the newer version and reload themselves, so nobody sees a mix of old and new pages.

## Photos

- **Profile photos**: SPGs tap their photo on Beranda to take or choose a new one; Team Leaders can set an SPG's photo in Kelola Tim. Demo users start with illustrated portraits in `assets/avatars/` (DiceBear "Lorelei" by Lisa Wischofsky, CC0 1.0, generated with `tools/generate-avatars.js`).
- **Product photos**: Admin can upload a photo per product. Demo products start with simple illustrations in `assets/products/` — replace them with the official packshots from the client.
- Uploaded photos are resized in the browser and stored with the demo data.
- **Logo**: see `assets/brand/README.md`.

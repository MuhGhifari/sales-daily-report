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

"Today" in the demo is **Kamis, 22 Oktober 2026**. Changes (reports, targets, settings) are saved in your browser; use **Reset data demo** on the login page to start over.
The passwords are hardcoded in `assets/js/data.js` and visible to anyone — demo only.

## Pages

| Page | Who |
|------|-----|
| `index.html` — Masuk | everyone |
| `spg/beranda.html` — big daily % ring, level, rank, streak, week/month progress | SPG |
| `spg/laporan.html` — daily report per product | SPG (Leader/Supervisor can edit too) |
| `spg/peringkat.html` — team leaderboard by % of target | SPG |
| `spg/riwayat.html` — report history per month | SPG |
| `leader/dashboard.html` — team summary, SPG ranking, missing reports, products, CSV export | Leader, Supervisor |
| `leader/laporan.html` — team reports, detail, unlock | Leader, Supervisor |
| `leader/target.html` — monthly targets, auto weekly/daily, overrides | Leader, Supervisor |
| `leader/tim.html` — add / deactivate SPGs | Leader, Supervisor |
| `leader/pengaturan.html` — working days, holidays, edit window | Leader, Supervisor |
| `supervisor/dashboard.html` — team comparison, top SPGs in the area | Supervisor |
| `admin/produk.html` — product catalog & prices | Admin |

See [`PLAN.md`](PLAN.md) for the product plan and [`IMPLEMENTATION.md`](IMPLEMENTATION.md) for how it's built.

## Publishing changes

After editing any page, run `python3 tools/bump-version.py` before committing. It stamps a new version on every page and in `assets/version.json`; pages that a browser still has cached detect the newer version and reload themselves, so nobody sees a mix of old and new pages.

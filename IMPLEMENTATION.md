# Implementation Plan — Page-by-Page Prototype

This plan replaces the current single-file mockup (`mockup/index.html`, the gallery of phone and browser frames) with **one real HTML page per screen** and a simple layout.
The pages are static and are served from GitHub Pages. They use mock data for now, and the same pages are later connected to the real backend (see §8).

> Status: plan only. No code until this is approved.

---

## 1. What changes

| Now | After |
|-----|-------|
| One `mockup/index.html` containing every screen | One HTML file per screen |
| Screens drawn inside fake phone and browser frames | Each page *is* the app: full screen, no frames |
| Role tabs to switch screens | Real navigation links between pages; login sends you to your role's home page |
| Sidebar + cards + charts | Simple layout: top bar, content, navigation (see §3) |
| Screenshots folder | Removed. GitHub Pages is the preview. |

---

## 2. File structure

```
/
├── index.html                  → Masuk (login)
├── spg/
│   ├── beranda.html            → SPG home: target progress
│   ├── laporan.html            → Fill in / edit daily report (per product)
│   └── riwayat.html            → Report history
├── leader/
│   ├── dashboard.html          → Team summary
│   ├── laporan.html            → Team reports list + one report's detail
│   ├── target.html             → Set targets
│   ├── tim.html                → Manage SPGs (add / deactivate)
│   └── pengaturan.html         → Working days, holidays, edit window
├── area/
│   └── dashboard.html          → Team comparison across the area
├── admin/
│   └── produk.html             → Product catalog & prices
└── assets/
    ├── css/style.css           → All styles (one file)
    └── js/
        ├── data.js             → Mock data + data functions (later: real API)
        ├── common.js           → Rp/date formatting, header/nav, login check
        └── pages/              → One small script per page (only if the page needs JS)
```

Rules:
- **Relative links only** (`../spg/beranda.html`), so everything works under `https://muhghifari.github.io/sales-daily-report/`.
- No build step and no framework: plain HTML, CSS and JS that open directly in a browser.
- Each page includes only `style.css`, `data.js`, `common.js` and its own page script.

---

## 3. Layout (simple)

One layout for every page, the same on phone and desktop:

```
┌──────────────────────────────────────┐
│  Laporan SPG          Sari W.  [Keluar] │  ← top bar (NIVEA blue)
├──────────────────────────────────────┤
│  Page title                           │
│                                       │
│  Content (single column, max 960px,   │
│  centered on desktop)                 │
│                                       │
├──────────────────────────────────────┤
│  Beranda | Isi Laporan | Riwayat      │  ← SPG: bottom nav on phone
└──────────────────────────────────────┘
```

- **SPG pages**: top bar + bottom navigation (3 items). Designed for phones first.
- **Leader / Area / Admin pages**: top bar + a row of navigation links under it (it scrolls sideways on phones). No sidebar.
- Single column by default. Tables scroll sideways inside their own box on small screens, never the whole page.
- **No charts in v1.** Progress bars and tables only. A chart can be added later if the client asks.
- Colors: NIVEA blue `#00136F`, white, light blue tints, plus green, amber and red only for status. Font: system font stack (nothing to download).

---

## 4. Pages

### 4.1 `index.html` — Masuk
- Phone number field → "Kirim Kode WhatsApp" → 6-digit OTP field → "Masuk".
- Prototype only: any OTP works. A small "Akun demo" box underneath has three links: masuk sebagai SPG / Leader / Area Manager.
- After login, redirect by role: SPG → `spg/beranda.html`, Leader → `leader/dashboard.html`, Area → `area/dashboard.html`.

### 4.2 `spg/beranda.html` — Home
- Three progress blocks: **Hari ini**, **Minggu ini**, **Bulan ini**. Each shows sales / target, a bar and a %.
- Week and month also show "Sisa Rp X dalam N hari kerja → ± Rp Y/hari".
- Status line: "Laporan hari ini: sudah / belum dikirim" with a button to `laporan.html`.

### 4.3 `spg/laporan.html` — Isi Laporan
- Date (today and the previous 2 days only), store (read-only).
- Product rows: product dropdown, qty, unit price (pre-filled, editable), row total.
- "+ Tambah Produk", remove row, "Tidak ada penjualan" checkbox.
- Total at the bottom, then "Kirim Laporan".
- Opening a date that already has a report loads it for editing. A date older than 2 days shows "Terkunci".
- Prototype: saving goes to the browser's localStorage, so it shows up on Beranda and Riwayat.

### 4.4 `spg/riwayat.html` — Riwayat
- Month selector.
- List of days, one row each: date, total Rp, number of products, status (Terkirim / Belum / Terkunci / Libur).
- Tap a row → opens `laporan.html?tanggal=YYYY-MM-DD`: editable if within 2 days, otherwise read-only.

### 4.5 `leader/dashboard.html` — Dashboard Tim
- Period filter: Hari ini / Minggu ini / Bulan ini.
- Summary: total sales, team target, % achieved, reports submitted today (x / y).
- "Belum lapor hari ini" list with a "Ingatkan WA" button (opens a `wa.me` link with a prefilled message, so no paid API is needed).
- SPG table: name, store, sales, target, %, today's status. Click a row → `laporan.html?spg=…`.
- Product table: product, qty, Rp.
- "Export Excel" button (prototype: downloads a CSV).

### 4.6 `leader/laporan.html` — Laporan Tim
- Filters: SPG, date range.
- List of reports. Click one to see its product lines.
- Leader can edit or unlock a locked report.

### 4.7 `leader/target.html` — Target
- Month selector, with "27 hari kerja" shown for the month.
- One row per SPG: monthly target input; weekly and daily targets calculated next to it.
- Optional manual override for weekly/daily (highlighted when overridden).
- Buttons: "Terapkan ke semua", "Salin bulan lalu", "Simpan".

### 4.8 `leader/tim.html` — Kelola Tim
- SPG list: name, phone, store, active/inactive.
- "Tambah SPG" form: name, WhatsApp number, store.

### 4.9 `leader/pengaturan.html` — Pengaturan Tim
- Working days: 7 toggle buttons (Sen–Min).
- Holidays list: date, name, "tetap hari kerja?" toggle; add/remove.
- Report edit window: number of days (default 2).
- Reminder time (default 20:00).

### 4.10 `area/dashboard.html` — Dashboard Area
- Same summary as the team dashboard, for the whole area.
- Team table: team, leader, SPG count, sales, target, %, reported today. Click → that team's dashboard (read-only view of `leader/dashboard.html?tim=…`).

### 4.11 `admin/produk.html` — Produk
- Product table: name, SKU, default price, active.
- Add / edit product form.

---

## 5. Data layer (`assets/js/data.js`)

All pages get data **only** through these functions, never by reading mock arrays directly. When the backend exists, only this file changes.

| Function | Used by |
|----------|---------|
| `login(phone, otp)` / `currentUser()` / `logout()` | all |
| `getMyProgress(date)` → today / week / month actual & target | spg/beranda |
| `getReport(userId, date)` / `saveReport(report)` | spg/laporan, leader/laporan |
| `listReports({ userId?, teamId?, from, to })` | spg/riwayat, leader/laporan |
| `getProducts()` / `saveProduct(p)` | spg/laporan, admin/produk |
| `getTeamDashboard(teamId, period)` | leader/dashboard |
| `getTargets(teamId, month)` / `saveTargets(rows)` | leader/target |
| `getTeam(teamId)` / `saveSpg(spg)` | leader/tim |
| `getTeamSettings(teamId)` / `saveTeamSettings(s)` | leader/pengaturan |
| `getAreaDashboard(areaId, period)` | area/dashboard |

- Mock data: 1 area, 5 teams, 1 fully filled team (8 SPGs), 7 products, and October 2026 reports generated with a fixed random seed so the numbers are always the same.
- Data the user changes (reports, targets, settings) is kept in localStorage so the demo feels real. A "Reset data demo" link restores it.
- Target logic (daily = monthly ÷ working days, weekly = daily × working days in that week, overrides win, holidays respected) lives in one function, `effectiveTarget(user, periodType, date)`.

---

## 6. Order of work

1. **Clean up**: delete `mockup/` (page + screenshots), point the root `index.html` at the new login page.
2. **Foundation**: `style.css` (tokens, top bar, nav, cards, progress bar, table, form, buttons), `common.js` (formatting, header, nav, role guard), `data.js` with mock data and the target calculation.
3. **SPG pages**: login → beranda → laporan → riwayat.
4. **Leader pages**: dashboard → target → laporan → tim → pengaturan.
5. **Area + Admin pages**.
6. **Check** (§7), then push. GitHub Pages updates automatically.

Each step is a separate commit, so you can review the SPG pages on GitHub Pages before the leader pages are built.

---

## 7. Done when

- [ ] Every page opens on its own by URL, with no gallery or frames.
- [ ] Login → correct home page for each role; opening another role's page redirects to login.
- [ ] Works at 375px phone width with no sideways page scroll; tables scroll inside their box.
- [ ] A report submitted on `laporan.html` shows on `beranda.html`, `riwayat.html` and the leader dashboard.
- [ ] Changing a monthly target updates the weekly/daily values and the SPG's Beranda.
- [ ] All links work on GitHub Pages (relative paths).
- [ ] No errors in the browser console.

---

## 8. After the prototype (not in this round)

The pages stay. Only `data.js` is swapped from mock data to real calls, depending on the backend decision still open in `PLAN.md`:
- **Google Sheets + Apps Script** (Rp 0): `data.js` calls the Apps Script web app URL.
- **Server + database**: `data.js` calls the API.

---

## 9. Questions before coding

1. Is the page list in §2 right? Anything to add or drop (e.g. an SPG profile page, an Admin users page)?
2. OK to drop charts for now and use tables + progress bars only?
3. OK to delete the old `mockup/` folder?

# Laporan SPG — Laravel server

The Laporan SPG app on Laravel. Pages are Blade views (`resources/views`, layout `layouts/app.blade.php`) served at clean URLs (`/`, `/spg/beranda`, `/leader/dashboard`, …). Each page embeds the logged-in user's data. CSS, JavaScript and images are shared with the GitHub Pages demo (`assets/` in the repository root).
Plan and decisions: [`../IMPLEMENTATION-WEB.md`](../IMPLEMENTATION-WEB.md).

## Run locally (demo data)

```bash
composer install
cp .env.example .env && php artisan key:generate
# in .env: APP_DEMO_TODAY=2026-10-22 so the demo data ends "today"
touch database/database.sqlite
php artisan migrate --seed          # DemoSeeder: same people, stores, products and sales as the demo
php artisan storage:link            # uploaded photos
deploy/sync-assets.sh               # CSS/JS/images from ../assets into public/assets
# in .env: APP_DEMO_ACCOUNTS=true to list the demo logins on the login page
php artisan serve                   # http://localhost:8000
```

Demo logins are the same as the demo (e.g. SPG `0813 0000 0001` / `spg123`, Team Leader `0812 0000 0001` / `leader123`).

## Production

1. PHP 8.3+, MySQL 8, HTTPS. Set `DB_*` in `.env`, `APP_ENV=production`, `APP_DEBUG=false`, leave `APP_DEMO_TODAY` empty.
2. `composer install --no-dev && php artisan migrate --force && php artisan storage:link`
3. `php artisan app:create-admin 08xxxxxxxxxx "Nama Admin"`. The Admin then adds Supervisors and Team Leaders in the app; they add SPGs.
4. `deploy/sync-assets.sh` after every change in `assets/`, then `php artisan optimize`.

## Where things are

| | |
|---|---|
| `routes/web.php` | page routes (`PageController`: login and role checks) and the `/api` routes (session cookie + CSRF) |
| `resources/views` | Blade pages; `auth/login`, `spg/*`, `leader/*`, `supervisor/*`, `katalog/*`, `admin/*` |
| `app/Services/Access.php` | who may do what (same rules as the demo's `data.js`) |
| `app/Services/StateBuilder.php` | `/api/bootstrap`: the data a user may see, in the demo's shape |
| `app/Http/Controllers/Api` | auth, shifts, sales, targets/settings, users, products/stores |
| `database/seeders/DemoSeeder.php` | loads `demo-data.json` (made by `node tools/export-demo-data.js` in the repo root) |
| `tests/Feature` | API and permission tests: `php artisan test` |

# Sales Daily Report — Project Plan

A simple, mobile-first web app where **SPG / sales staff** submit their daily sales report and track progress against their targets, and **Leaders** set those targets and monitor cumulative team performance.

> Status: planning only. No code yet.

---

## 1. Goals

| # | Goal |
|---|------|
| G1 | An SPG can submit a daily sales report in under 1 minute from a phone. |
| G2 | An SPG can see their **daily, weekly, and monthly** target and how much of it they've achieved. |
| G3 | A Leader can set targets for each SPG (or the whole team at once). |
| G4 | A Leader can see the **cumulative** report for their team: totals, per-SPG breakdown, achievement %, and who hasn't reported today. |

### Out of scope for v1
- Inventory / stock management
- Payroll, commissions, incentives calculation
- Integration with POS systems
- Native mobile apps (the web app will be mobile-friendly / installable as a PWA instead)

---

## 2. Users & Roles

| Role | Description | Can do |
|------|-------------|--------|
| **SPG** (Sales) | Field / in-store salesperson | Submit & edit own daily report, view own history, view own targets & achievement |
| **Leader** | Supervises a team of SPGs | Everything an SPG can see, *for their team*; set / edit targets; view cumulative reports; export data |
| **Admin** *(optional, can be merged into Leader for v1)* | Owner / back-office | Manage users, teams, stores, and products |

For v1, a Leader can also act as Admin for their own team (create SPG accounts, reset passwords) to keep things simple.

---

## 3. Core Features

### 3.1 Authentication
- Login with phone number or email + password.
- Leader creates SPG accounts (no public sign-up).
- "Forgot password" handled by Leader reset for v1.

### 3.2 SPG: Daily Report Submission
Form fields (v1):
- **Date** — defaults to today; cannot be in the future.
- **Store / location** — defaults to SPG's assigned store.
- **Total sales amount** (Rp) — required.
- **Units sold** — required.
- **Number of transactions** — optional.
- **Notes** — optional (e.g. "rainy day, low traffic").
- *(Optional, phase 2)* Product line items: product + qty + amount, auto-summing into totals.
- *(Optional, phase 2)* Photo attachment (e.g. receipt / display photo).

Rules:
- One report per SPG per day (editing replaces, not duplicates).
- SPG can edit a report until end of the same day (or configurable N days). After that it is **locked**; the Leader can unlock or edit it.
- Every edit is recorded in an audit log (who, when, old → new value).

### 3.3 SPG: My Targets & Progress
A single home screen showing three cards:

```
┌───────────── Today ─────────────┐
│ Rp 1.250.000 / Rp 2.000.000     │
│ ███████████░░░░░░░  62%         │
└─────────────────────────────────┘
┌──────────── This Week ──────────┐
│ Rp 6.800.000 / Rp 12.000.000    │
│ ██████████░░░░░░░░  57%         │
│ Remaining: Rp 5.2jt in 3 days   │
│ → needs ~Rp 1.73jt/day          │
└─────────────────────────────────┘
┌─────────── This Month ──────────┐
│ Rp 21.4jt / Rp 50jt       43%   │
└─────────────────────────────────┘
```
- Toggle between **amount (Rp)** and **units** if both targets exist.
- History list of past reports with status (submitted / missing / locked).

### 3.4 Leader: Target Setting
- Set targets per SPG, per period type: **daily**, **weekly**, **monthly**.
- Target metric: sales amount (Rp) and/or units.
- **Bulk set**: apply the same target to all SPGs in the team, or copy last month's targets.
- **Auto-derive** (to reduce leader effort): if the Leader only sets a monthly target, weekly and daily targets are derived automatically:
  - daily = monthly ÷ number of working days in the month
  - weekly = daily × working days in that week
  - Any explicitly set daily/weekly target **overrides** the derived value.
- Working days configurable per team (e.g. Mon–Sat, or 7 days for mall SPGs), plus holidays.

### 3.5 Leader: Cumulative Dashboard
- **Period filter**: today / this week / this month / custom range.
- **Team summary**: total sales, total units, team target, achievement %.
- **Per-SPG table**: sales, units, target, achievement %, sortable (leaderboard).
- **Submission status**: who has / hasn't submitted today, with a quick "remind" action (phase 2: WhatsApp/push reminder).
- **Trend chart**: daily sales over the selected period vs. target line.
- **Drill-down**: click an SPG → their daily reports for that period.
- **Export** to Excel/CSV.

### 3.6 Notifications *(phase 2)*
- Reminder to SPG if no report by a set time (e.g. 21:00).
- Daily summary to Leader.
- Channel options: web push (PWA), email, or WhatsApp (via a provider such as Fonnte / WhatsApp Business API).

---

## 4. Data Model (draft)

```
users
  id, name, phone, email, password_hash,
  role (SPG | LEADER | ADMIN), team_id, store_id,
  is_active, created_at

teams
  id, name, leader_id, working_days (e.g. "MON-SAT"), timezone

stores
  id, name, address, team_id

daily_reports
  id, user_id, store_id, report_date,
  sales_amount, units_sold, transactions, notes,
  status (SUBMITTED | LOCKED),
  created_at, updated_at
  UNIQUE (user_id, report_date)

report_items            -- phase 2
  id, report_id, product_id, qty, amount

products                -- phase 2
  id, name, sku, price, is_active

targets
  id, user_id, period_type (DAILY | WEEKLY | MONTHLY),
  period_start (date), metric (AMOUNT | UNITS), value,
  set_by (leader user_id), created_at, updated_at
  UNIQUE (user_id, period_type, period_start, metric)

holidays
  id, team_id (nullable = global), date, name

audit_logs
  id, actor_id, entity, entity_id, action, before_json, after_json, created_at
```

### Key calculations
- **Achievement (period)** = Σ `daily_reports.sales_amount` in period ÷ effective target for that period.
- **Effective target** = explicit target if set, otherwise derived from the monthly target (see 3.4).
- **Team cumulative** = Σ over all active SPGs in the team.
- **Week** = Monday–Sunday (ISO week). **Month** = calendar month.
- All dates computed in the team's timezone (default `Asia/Jakarta`).

---

## 5. Screens / Pages

### SPG
1. Login
2. **Home** — target progress cards (today / week / month) + "Submit today's report" button
3. **Submit / Edit Report** form
4. **My History** — list/calendar of past reports

### Leader
1. Login
2. **Dashboard** — team summary, per-SPG table, submission status, trend chart
3. **SPG Detail** — one SPG's reports & achievement
4. **Targets** — grid of SPGs × periods, editable, with bulk actions
5. **Team Management** — add/deactivate SPGs, assign stores
6. **Settings** — working days, holidays, report edit window

---

## 6. Suggested Tech Stack

Chosen to keep it simple, cheap to host, and fast to build.

| Layer | Recommendation | Why |
|-------|----------------|-----|
| Frontend + Backend | **Next.js** (App Router, TypeScript) | One codebase for UI and API; easy deploy |
| UI | **Tailwind CSS** + **shadcn/ui** | Fast, clean, mobile-friendly components |
| Database | **PostgreSQL** (e.g. Supabase or Neon) | Relational data, great for aggregations |
| ORM | **Prisma** or **Drizzle** | Type-safe queries, migrations |
| Auth | **Auth.js** (credentials) or **Supabase Auth** | Role-based sessions |
| Charts | **Recharts** | Simple charts for dashboard |
| Export | **SheetJS (xlsx)** | Excel export |
| Hosting | **Vercel** + managed Postgres | Free/low-cost tier is enough for small teams |
| PWA | next-pwa / manifest | "Install" on SPG phones |

Alternative low-code option (if speed matters more than flexibility): **AppSheet** or **Google Forms + Google Sheets + Looker Studio**. Worth considering for a quick pilot, but targets/achievement logic and role permissions get awkward quickly.

---

## 7. Permissions Matrix

| Action | SPG | Leader | Admin |
|--------|:---:|:------:|:-----:|
| Submit own report | ✅ | ✅ (if also selling) | – |
| Edit own report (within window) | ✅ | ✅ | – |
| Edit/unlock any team report | ❌ | ✅ | ✅ |
| View own targets & progress | ✅ | ✅ | ✅ |
| View other SPGs' data | ❌ | ✅ (own team) | ✅ (all) |
| Set targets | ❌ | ✅ (own team) | ✅ |
| Manage users | ❌ | ✅ (own team) | ✅ |
| Export data | ❌ | ✅ | ✅ |

All permission checks enforced **server-side** (API / DB row-level security), not just hidden in the UI.

---

## 8. Delivery Phases

### Phase 0 — Setup (≈ 1–2 days)
- Repo, Next.js project, lint/format, DB + ORM, deploy pipeline.
- Seed script with a demo team (1 Leader, 5 SPGs, 1 month of data).

### Phase 1 — MVP (≈ 1–2 weeks)
- Auth + roles
- SPG: submit/edit daily report, home screen with daily/weekly/monthly progress
- Leader: set monthly targets (with auto-derived weekly/daily), bulk set
- Leader: dashboard (team totals, per-SPG table, today's submission status)
- Basic team management

### Phase 2 — Improvements (≈ 1–2 weeks)
- Explicit weekly/daily target overrides, copy-last-month
- Trend charts, custom date range, drill-down
- Excel export
- Report locking + audit log
- Holidays & working-day settings
- PWA install + reminders

### Phase 3 — Nice to have
- Product-level line items & product targets
- Photo attachments
- Multi-team / multi-level (Area Manager sees multiple Leaders)
- WhatsApp notifications
- Incentive / commission estimates based on achievement

---

## 9. Testing Plan
- **Unit tests**: target derivation (working days, holidays, overrides), achievement %, week/month boundaries, timezone edge cases (report at 23:59 WIB).
- **Integration tests**: API permission checks (SPG cannot read another SPG's data; Leader cannot touch another team).
- **E2E (Playwright)**: SPG submits report → Leader dashboard reflects it; Leader sets target → SPG home shows it.
- **Pilot**: run with one real team for 1–2 weeks before wider rollout.

---

## 10. Open Questions (need your input)

1. **Target metric** — sales amount (Rp) only, units only, or both?
2. **Product detail** — do SPGs need to report per product/SKU, or just daily totals?
3. **Hierarchy** — is it just Leader → SPG, or are there Area Managers above Leaders?
4. **Stores** — is each SPG fixed to one store, or do they rotate between locations?
5. **Working days** — do SPGs work 7 days (mall) or Mon–Sat? Should holidays reduce the target?
6. **Edit window** — how long can an SPG edit a submitted report?
7. **Language** — Bahasa Indonesia, English, or both?
8. **Login** — phone number or email? Any need for WhatsApp OTP?
9. **Scale** — roughly how many SPGs and Leaders at launch?
10. **Hosting / budget** — any preference or constraints (e.g. must be free tier, must be on-prem)?

---

## 11. Next Step
Once the open questions are answered, finalize the data model and start **Phase 0 + Phase 1 (MVP)**.

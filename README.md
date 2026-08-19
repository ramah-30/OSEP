# OSEP — Event Planning Platform

**Plan Smarter. Create Unforgettable Events.**

A premium SaaS platform for AI-powered event planning.

- **Phase 1** — landing page + secure, role-aware authentication.
- **Phase 2** — the role workspaces: sidebar/topbar dashboards for Event Planners,
  Clients and Vendors, a permission (RBAC) layer, profile systems, notifications
  and settings.
- **Phase 3** — the Core Event Planning Engine: planners create and run events
  through a full lifecycle (Draft → Planning → Client Approval → Execution →
  Completed → Archived) inside a dedicated per-event workspace — Overview,
  Timeline, Tasks (drag-and-drop Kanban), Budget, Guests, Vendors, Venue,
  a drag-and-drop **Venue Designer** (Konva canvas: object library, layers,
  seating, live stats, validation, PNG/PDF export), Documents, Approvals and an
  Activity log — plus a global calendar, a client book, a vendor directory and
  global search.
- **Phase 4** — the Guest Management, Invitation & RSVP system: a full guest
  lifecycle inside every event workspace. A guest hub (Dashboard, List,
  Categories, Invitations, RSVP, Check-in, Communication, Import/Export,
  Settings) with rich guest profiles, colour-coded categories, CSV import
  (duplicate detection) and CSV/PDF export, multi-channel invitations with
  delivery logs and templates, scheduled reminders (dispatched by an artisan
  command), a **public token-based RSVP portal**, QR digital tickets and a
  QR/manual **check-in** desk, a per-guest communication log, and live
  dashboards/charts. Payments, messaging, reports and the AI assistant (including
  AI-generated venue layouts and seating) remain navigable placeholders for later
  phases.

```
AIOSEP/
├── backend/    Laravel 12 REST API (Sanctum + MySQL)
└── frontend/   React + Vite + Tailwind v4
```

The two apps are fully separated — the only contract between them is the JSON API.

---

## Requirements

| Tool | Version used |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node | 20+ |
| MySQL | 8.x / MariaDB (XAMPP) |

---

## Getting started

From a fresh clone to a running app:

```bash
# 1. Clone
git clone https://github.com/ramah-30/OSEP.git
cd OSEP

# 2. Backend dependencies + environment
cd backend
composer install
cp .env.example .env
php artisan key:generate

# 3. Database — pick ONE option:
#    (a) Build a fresh schema + demo data
php artisan migrate --seed
#    (b) OR load the bundled dump (full schema + data; recreates the `osep` DB).
#        Start MySQL first, then from the backend/ folder:
#        mysql -u root < ../osep_backup.sql

# 4. Finish the backend
php artisan storage:link
php artisan serve --port=8001         # http://localhost:8001

# 5. Frontend (new terminal, from the repo root)
cd frontend
npm install
cp .env.example .env                  # VITE_API_URL=http://localhost:8001/api/v1
npm run dev                           # http://localhost:5173
```

Then open **http://localhost:5173** and sign in with any demo account — all use the
password **`Password123!`** (e.g. `planner@osep.test`). See **Backend setup** below
for the required `.env` values, and **Demo tenant** for every login.

> **Windows / XAMPP:** start MySQL from the XAMPP Control Panel first. Option (b)
> needs the MySQL client on your PATH (`C:\xampp\mysql\bin`); the bundled
> `osep_backup.sql` already contains `CREATE DATABASE osep`, so you don't pre-create
> it. After importing, **don't** run `php artisan migrate:fresh` — that would wipe
> the data you just loaded.

---

## Backend setup

```bash
cd backend
composer install
cp .env.example .env          # then fill in the values below
php artisan key:generate
php artisan migrate --seed
php artisan storage:link      # serve uploaded avatars/logos from /storage
php artisan serve --port=8001 # http://localhost:8001
```

> Port 8001, not Laravel's default 8000: an unrelated Laravel project
> (`C:\xampp\htdocs\OSEP`) already listens on 8000 on this machine. Change
> `APP_URL` and the frontend's `VITE_API_URL` together if you move it.

### Required `.env` values

```dotenv
APP_URL=http://localhost:8001
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=mysql
DB_DATABASE=osep
DB_USERNAME=root
DB_PASSWORD=

# Mail — swap MAIL_MAILER to smtp once credentials are in place
MAIL_MAILER=log
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@osep.app
MAIL_FROM_NAME=OSEP
```

With `MAIL_MAILER=log`, verification and reset emails are written to
`backend/storage/logs/laravel.log` instead of being sent.

---

## Frontend setup

```bash
cd frontend
npm install
cp .env.example .env          # VITE_API_URL=http://localhost:8001/api/v1
npm run dev                   # http://localhost:5173
```

---

## API

All routes are prefixed `/api/v1` and return a fixed envelope:

```json
{ "success": true, "message": "...", "data": {}, "errors": null }
```

| Method | Endpoint | Auth |
|---|---|---|
| POST | `/auth/register` | — |
| POST | `/auth/login` | — |
| POST | `/auth/logout` | Bearer |
| GET | `/auth/me` | Bearer |
| POST | `/auth/forgot-password` | — |
| POST | `/auth/reset-password` | — |
| GET | `/auth/verify-email/{id}/{hash}` | signed |
| POST | `/auth/resend-verification` | — |
| POST | `/contact` | — |

### Phase 2 — the workspace (all `Bearer`)

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/dashboard/stats` | Role-branched overview + stat cards |
| GET / PUT | `/profile` | Role-aware profile read / update |
| POST | `/profile/image` | Avatar / logo upload (multipart) |
| GET | `/notifications` | Paginated + `unread_count` |
| PUT | `/notifications/{id}/read` | Mark one read |
| POST | `/notifications/read-all` | Mark all read |
| PUT | `/settings/account` | Name, phone, country |
| PUT | `/settings/email` | Password-confirmed, re-verifies |
| PUT | `/settings/password` | Password-confirmed, revokes other tokens |
| PUT | `/settings/preferences` | Language, timezone, theme |
| GET | `/my-event` | Client only |
| GET | `/approvals` | Client only |
| POST | `/approvals/{id}/respond` | Client only — approve / reject / request_changes |

### Phase 3 — the event engine (all `Bearer`, `role:event_planner`)

| Method | Endpoint | Notes |
|---|---|---|
| GET / POST | `/events` | List (filters: `q`, `status`, `priority`, `client_id`, `from`, `to`) / create |
| GET / PUT / DELETE | `/events/{event}` | Full workspace payload / update / delete |
| PUT | `/events/{event}/status` | Advance the lifecycle (validated transitions) |
| GET / POST | `/events/{event}/tasks` | Kanban tasks |
| PUT | `/events/{event}/tasks/reorder` | Persist a drag (status + order) |
| PUT / DELETE | `/events/{event}/tasks/{task}` | Update / delete a task |
| POST | `/events/{event}/tasks/{task}/comments` | Comment on a task |
| GET / POST | `/events/{event}/milestones` | Timeline milestones |
| PUT / DELETE | `/events/{event}/milestones/{milestone}` | Update / delete |
| GET / POST | `/events/{event}/guests` | Guest list + RSVP summary |
| PUT / DELETE | `/events/{event}/guests/{guest}` | Update / delete |
| GET / PUT | `/events/{event}/venue` | Read / upsert the venue logistics |
| GET / POST | `/events/{event}/venue-layouts` | Venue Designer floor-plan versions |
| GET / PUT / DELETE | `/events/{event}/venue-layouts/{layout}` | Load / bulk-save (autosave) / delete a layout |
| POST | `/events/{event}/venue-layouts/{layout}/duplicate` | Duplicate a layout version |
| PUT | `/events/{event}/venue-layouts/{layout}/objects/{object}/seating` | Replace a table's seat assignments |
| GET / POST | `/events/{event}/vendor-assignments` | Assign vendors |
| PUT / DELETE | `/events/{event}/vendor-assignments/{assignment}` | Update / delete |
| GET / POST | `/events/{event}/budget-items` | Budget lines + summary |
| PUT / DELETE | `/events/{event}/budget-items/{item}` | Update / delete |
| GET / POST | `/events/{event}/approvals` | List / submit for client approval |
| GET / POST | `/events/{event}/documents` | List / upload (multipart) |
| DELETE | `/events/{event}/documents/{document}` | Delete a document |
| GET | `/events/{event}/activity` | Paginated activity log |
| GET | `/calendar` | Cross-event events, tasks & milestones (`from` / `to`) |
| GET | `/search` | Global search (events, clients, tasks, vendors) |
| GET / POST | `/clients` | Client book / create a client inline |
| GET | `/vendors` | Registered vendor directory |
| GET | `/categories` | Event / guest / budget option catalogues |
| POST | `/categories/{type}` | Add a custom category (`event` / `guest` / `budget`) |

### Phase 4 — guests, invitations & RSVP (planner routes `Bearer`, `role:event_planner`)

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/events/{event}/guests/dashboard` | Cards + RSVP/category/meal/trend chart data |
| GET / POST | `/events/{event}/guests` | List (filters) / add a guest |
| PUT / DELETE | `/events/{event}/guests/{guest}` | Update / delete |
| POST | `/events/{event}/guests/{guest}/archive` | Archive / restore |
| POST | `/events/{event}/guests/{guest}/duplicate` | Duplicate a guest |
| GET | `/events/{event}/guests/{guest}/ticket` | Issue + fetch the digital ticket |
| GET | `/events/{event}/guests/{guest}/history` | Guest profile + communication log |
| POST | `/events/{event}/guests/{guest}/notes` | Add a planner note |
| POST | `/events/{event}/guests/bulk` | Bulk add |
| POST | `/events/{event}/guests/bulk-action` | Bulk invite / categorise / seat / archive / delete |
| POST | `/events/{event}/guests/import` | CSV import (duplicate detection + row errors) |
| GET | `/events/{event}/guests/export` | CSV download |
| GET / POST | `/events/{event}/invitations` · `/send` | List + summary / send or schedule |
| GET / POST | `/events/{event}/invitations/{invitation}` · `/resend` | Detail + delivery logs / resend |
| GET / POST | `/events/{event}/reminders` · `/send` | Scheduled reminders / send to pending |
| GET | `/events/{event}/rsvp` | RSVP responses (planner view) |
| GET/POST/PUT/DELETE | `/events/{event}/meal-options` | RSVP meal catalogue CRUD |
| GET | `/events/{event}/checkins` · `/statistics` | Roster + live stats |
| POST | `/events/{event}/checkins` | Check in by QR `token` or `guest_id` (duplicate-safe) |
| DELETE | `/events/{event}/checkins/{guest}` | Undo a check-in |
| GET | `/events/{event}/communications` | Event-wide communication log |
| GET/POST/PUT/DELETE | `/guest-categories` | Rich guest categories (colour / priority / seating) |
| GET/POST/PUT/DELETE | `/invitation-templates` (+ `/{template}/duplicate`) | Template library |

**Public RSVP portal** (no auth — the URL token is the credential):

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/rsvp/{token}` | Invitation details for the guest's RSVP page |
| POST | `/rsvp/{token}` | Submit a response (attending / maybe / declined) |

Scheduled invitations and reminders are delivered by `php artisan
osep:dispatch-reminders` (wired into the scheduler in `routes/console.php`, so a
host cron running `php artisan schedule:run` every minute fires them). With
`MAIL_MAILER=log`, invitation emails are written to the log instead of sent.

Authentication uses Laravel Sanctum **bearer tokens**. Send them as
`Authorization: Bearer <token>`. Role-gated routes use `role:` middleware; granular
capabilities use `permission:` middleware (see `permissions` / `role_permissions`).

---

## Account types & roles

`users.account_type` is the identity chosen at signup (`event_planner`, `vendor`,
`client`) and drives dashboard routing. `roles` / `user_roles` is the extensible
permission layer — `admin` and `staff` are already seeded, so adding them to a
user later requires no schema change. `permissions` / `role_permissions` add the
granular capability grants (`PermissionSeeder`).

Each user gets a matching profile row (`planner_profiles` / `client_profiles` /
`vendor_profiles`) created at registration.

---

## Demo tenant

`php artisan migrate --seed` loads `DemoSeeder` (non-production only) — a complete
sample tenant so the dashboards show real, related data on first run. The demo
wedding (`EVT-2026-000001`) comes fully populated: timeline, a Kanban of tasks,
a full guest list (with invitations, RSVP responses, meal options, QR tickets and
check-ins), a venue + floor-plan layout, vendor assignments, budget lines,
approvals, documents and an activity trail. All demo logins use the password
**`Password123!`**:

| Role | Email |
|---|---|
| Event Planner | `planner@osep.test` (Sarah Bennett, Elegant Events Ltd) |
| Client | `client@osep.test` (John Carter — "Sarah & John's Wedding") |
| Vendor | `vendor@osep.test` (Zawadi Photography) |

Reference data — roles, permissions, the default event/guest/budget category
catalogues and the starter invitation templates — is always seeded (`RoleSeeder`,
`PermissionSeeder`, `CategorySeeder`), in every environment. Wipe the demo tenant before real use with
`php artisan migrate:fresh --seed` under `APP_ENV=production` (which skips
`DemoSeeder` but keeps the reference seeders).

---

## Testing

```bash
cd backend && php artisan test
cd frontend && npm run build
```

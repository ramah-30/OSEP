# Deploying OSEP for free

This guide takes you from the GitHub repo to a live, shareable system using
**free** tiers only:

| Piece | Platform | Why |
|---|---|---|
| Frontend (React/Vite) | **Vercel** | Free, auto-deploys from GitHub, instant |
| Backend (Laravel API) | **Render** (Docker web service) | Free, auto-deploys from GitHub |
| Database (MySQL) | **TiDB Cloud Serverless** | Free, MySQL-compatible, TLS via public CA, no expiry |

> The repo already contains everything the platforms need: `backend/Dockerfile`,
> `render.yaml`, `frontend/vercel.json`, and the `osep_backup.sql` database dump.

### Know the trade-offs (fine for a demo/presentation)
- **Cold start:** the free backend sleeps after ~15 min idle; the first request then
  takes ~30–50s to wake. **Open the site a minute before you present.**
- **Uploads are temporary:** new file uploads (avatars, venue images) reset on each
  backend redeploy. Seeded demo data lives in the database and is safe.
- **No background jobs:** scheduled invitation reminders won't auto-send
  (`QUEUE_CONNECTION=sync` is set). Everything else works normally.

---

## Part 1 — Database (TiDB Cloud Serverless)

1. Sign up at **https://tidbcloud.com** (free, Google/GitHub login works).
2. Create a **Serverless** cluster (pick the free plan / nearest region). It takes a minute.
3. Click **Connect**. Choose **General**. Note these five values it shows you:
   `Host`, `Port` (**4000**), `User`, `Password`, and the database name (create one
   called **`osep`** under the *Databases* tab if there isn't one).
4. **Load your data.** From the folder that has `osep_backup.sql`, run the `mysql`
   command TiDB shows you, adding the file at the end. It looks like:

   ```bash
   mysql --host <HOST> --port 4000 -u <USER> -p \
     --ssl-mode=VERIFY_IDENTITY --ssl-ca=/etc/ssl/certs/ca-certificates.crt \
     < osep_backup.sql
   ```
   - On **Windows/XAMPP** the CA path differs; the simplest reliable option is to
     install the **MySQL command-line client** (or MySQL Shell) and use the exact
     command TiDB gives you. The dump already contains `CREATE DATABASE osep`, so
     you don't pre-create tables.
   - **Alternative (no local client):** skip this step and seed from the app instead
     — see the note at the end of Part 2.

---

## Part 2 — Backend (Render)

1. Sign up at **https://render.com** with your GitHub account and grant access to
   the `ramah-30/OSEP` repo.
2. **New +**  →  **Blueprint**  →  select the repo. Render reads `render.yaml` and
   proposes the `osep-api` service. Click **Apply**.
3. It will ask for the env vars marked `sync: false`. Fill them in:

   | Key | Value |
   |---|---|
   | `APP_KEY` | run `php artisan key:generate --show` locally and paste it (starts with `base64:`) |
   | `APP_URL` | leave blank for now, or the Render URL once known |
   | `FRONTEND_URL` | leave blank for now (set in Part 4) |
   | `DB_HOST` | from TiDB |
   | `DB_PORT` | `4000` |
   | `DB_DATABASE` | `osep` |
   | `DB_USERNAME` | from TiDB |
   | `DB_PASSWORD` | from TiDB |

   All the other vars (mail, cache, SSL CA, etc.) are already set by `render.yaml`.
4. Deploy. The first build takes a few minutes. When it's live you'll get a URL like
   `https://osep-api.onrender.com`.
5. Set **`APP_URL`** to that URL (Environment tab) and let it redeploy.
6. Check it works: open `https://osep-api.onrender.com/up` → you should see a green
   health page.

> **No local MySQL client? Seed from the app instead.** Open the service's **Shell**
> tab in Render and run:
> ```bash
> php artisan migrate:fresh --seed --force
> ```
> For the demo tenant + logins to seed, temporarily set `APP_ENV=staging` (the demo
> seeder is skipped when `APP_ENV=production`), run the command, then switch
> `APP_ENV` back to `production`. Otherwise, prefer the `osep_backup.sql` import in
> Part 1 — it carries the demo data as-is.

---

## Part 3 — Frontend (Vercel)

1. Sign up at **https://vercel.com** with GitHub and import the `ramah-30/OSEP` repo.
2. In the import screen set:
   - **Root Directory:** `frontend`
   - Framework preset **Vite** is auto-detected (build `npm run build`, output `dist`).
3. Add one Environment Variable:

   | Key | Value |
   |---|---|
   | `VITE_API_URL` | `https://osep-api.onrender.com/api/v1` (your Render URL + `/api/v1`) |

4. Deploy. You'll get a URL like `https://osep.vercel.app`.

---

## Part 4 — Connect the two & finish

1. Back in **Render** → `osep-api` → Environment, set:
   - `FRONTEND_URL` = your Vercel URL, e.g. `https://osep.vercel.app`

   Save (it redeploys). This lets the browser (CORS) accept your frontend.
2. Open your Vercel URL and sign in with a demo account — all use password
   **`Password123!`**:
   - `planner@osep.test` · `client@osep.test` · `vendor@osep.test`

Done — the system is live. 🎉

---

## Optional extras
- **Google sign-in:** set `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` on Render and
  update the authorised redirect URI to
  `https://osep-api.onrender.com/api/v1/auth/google/callback`.
- **Custom domain / no cold starts:** both are paid upgrades; not needed for a demo.
- **Keep it awake during a presentation:** just open the site a minute early, or ping
  the `/up` URL, so the backend is already warm.

## Troubleshooting
- **Frontend loads but every request fails / CORS error** → `FRONTEND_URL` on Render
  doesn't match your Vercel URL exactly (check `https://`, no trailing slash).
- **Backend 500 on first data call** → database env vars wrong, or TLS: confirm
  `DB_HOST/PORT/USER/PASSWORD` and that `MYSQL_ATTR_SSL_CA` is set (it is by default).
- **“No application encryption key”** → `APP_KEY` isn't set; paste a
  `php artisan key:generate --show` value.
- **Login works but dashboards are empty** → the database has schema but no demo
  data; import `osep_backup.sql` (Part 1) or seed via Shell (note in Part 2).

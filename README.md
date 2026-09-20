# ReadIn52 (Node.js edition)

**Journey Through Scripture in 52 Weeks** — a modern Node.js rebuild of the original PHP/MySQL [ReadIn52](https://github.com/robinson-vidva/readin52) Bible‑reading PWA.

Read the whole Bible in a year across four weekly tracks, with a built‑in reader (50+ translations), chapter‑level progress, streaks, achievements, notes, highlights and bookmarks — all in a clean, responsive interface with light/dark themes.

> *“Your word is a lamp for my feet, a light on my path.”* — Psalm 119:105

---

## Why this rebuild

| | Original | This rebuild |
|---|---|---|
| Runtime | PHP 8 + Apache | **Node.js 18+ / Express** |
| Database | MySQL / MariaDB (manual setup) | **libSQL** — a local SQLite file in dev, **Turso** in production |
| Install | Apache vhost, `install.php`, `config/db.php` | `npm install && npm start` |
| Hosting | LAMP server | **Vercel** (serverless) or any Node host |
| UI | Vanilla PHP templates | Server‑rendered **EJS** + modern design system |
| Auth | PHP sessions | `express-session` (SQLite store) + bcrypt + CSRF |

### New / enhanced features
- **52‑week heatmap** and a full **statistics** page (per‑track bars, book completion, day‑streak activity calendar).
- **Achievements** page showing earned *and* locked badges with progress toward each.
- **Enhanced reader**: adjustable text size & font, focus mode, side‑by‑side dual translation, keyboard chapter navigation, per‑verse **highlighting**, and a chapter **notes** drawer.
- **Bookmarks** and a **⌘K / Ctrl‑K quick‑jump** palette (type "John 3" to jump anywhere).
- **Daily verse** on the dashboard.
- **Notes export** to Markdown or JSON.
- Installable **PWA** with offline caching of the app shell and previously read chapters.

---

## Quick start

```bash
cd readin52-node
npm install
npm start
```

Then open **http://localhost:8000**.

On first boot the app creates `data/readin52.db`, seeds the 52‑week plan + badges, and prints a default admin login:

```
email:    setup@localhost
password: ChangeMe52!
```

You'll be prompted to set your own name, email and password on first login.

### Configuration (optional)

Copy `.env.example` to `.env` to override defaults:

| Variable | Default | Purpose |
|---|---|---|
| `PORT` | `8000` | HTTP port |
| `SESSION_SECRET` | dev value | **Set a long random string in production** |
| `NODE_ENV` | `development` | `production` enables secure cookies (requires HTTPS) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | `setup@localhost` / `ChangeMe52!` | Initial admin (only used when no users exist) |

---

## Project structure

```
readin52-node/
├── server.js                 # Local launcher (node server.js)
├── api/index.js              # Vercel serverless entry (re-exports the app)
├── vercel.json               # Vercel routing + file bundling
├── src/
│   ├── app.js                # Express app (shared by both entries)
│   ├── db.js                 # libSQL client, schema, seeding, settings cache
│   ├── sessionStore.js       # libSQL-backed session store
│   ├── auth.js               # sessions, CSRF, login/rate‑limit, middleware
│   ├── data/                 # reading-plan.json, book + badge definitions
│   ├── services/             # progress, notes, badges, users, readingPlan (async)
│   └── routes/               # pages.js, api.js, admin.js
├── views/                    # EJS templates (+ partials, admin)
├── public/                   # css, js (app/reader/dashboard/notes/stats), icons, manifest, sw
└── data/                     # local SQLite file (gitignored, auto‑created in dev)
```

## API (all session‑authenticated)

```
GET    /api/week/:n                 Reading plan + progress for a week
GET    /api/chapter-progress?week=n  Chapter progress for a week
POST   /api/chapter-progress         Toggle a chapter { week, category, book, chapter }
POST   /api/progress                 Toggle a whole category { week, category }
GET    /api/stats                    User statistics
GET    /api/notes | /notes/:id | /notes/chapter?book=&chapter=
POST   /api/notes                    Create/update a note
DELETE /api/notes/:id                Delete a note
GET    /api/notes/export?format=md|json
GET/POST /api/highlights             Per‑verse highlights
GET/POST /api/bookmarks              Chapter bookmarks
```

Mutating requests require the `x-csrf-token` header (the browser client sends it automatically).

---

## Deployment — Vercel + Turso

The app is wired for **Vercel** (serverless) with a **Turso** database. Locally it uses a SQLite file; in production the same libSQL code talks to Turso — no code changes.

### 1. Create the Turso database
Install the Turso CLI (`curl -sSfL https://tur.so/install.sh | bash`), then:
```bash
turso auth signup            # or: turso auth login
turso db create readin52
turso db show readin52 --url        # -> DATABASE_URL  (libsql://readin52-you.turso.io)
turso db tokens create readin52     # -> DATABASE_AUTH_TOKEN
```
The app creates its own tables and seeds the plan on first boot — no manual schema step.

### 2. Deploy to Vercel
```bash
npm i -g vercel
vercel                # link/create the project
```
In the Vercel project's **Settings → Environment Variables**, add:

| Name | Value |
|---|---|
| `DATABASE_URL` | the `libsql://…` URL from step 1 |
| `DATABASE_AUTH_TOKEN` | the token from step 1 |
| `SESSION_SECRET` | a long random string |
| `NODE_ENV` | `production` |

Then `vercel --prod`. First visitor triggers the one-time seed; the default `setup@localhost / ChangeMe52!` admin is created (change it on first login).

> **On Turso sleeping:** free-tier databases archive after a period of inactivity and **auto-wake on the next query** (a brief cold start, data preserved) — unlike Supabase, there's no manual "restore" step. Paid tiers stay always-on.

### Other hosts
Because it's a standard Express app, it also runs on **Render, Railway, Fly.io, a VPS, or Docker** — point `DATABASE_URL` at Turso (or drop it to use a local SQLite file on a persistent volume) and run `npm start`.

---

## Credits

- Scripture via the [HelloAO Free Use Bible API](https://bible.helloao.org/) — MIT, no keys, no limits.
- Berean Standard Bible (BSB), public domain.
- Original ReadIn52 by the ASK Devotions community · MIT License.

*Soli Deo Gloria.*

import bcrypt from 'bcryptjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BADGES } from './data/badges.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, '..', 'data');

// Production: a Postgres connection string. Neon's Vercel integration injects
// POSTGRES_URL (pooled) + DATABASE_URL. We prefer POSTGRES_URL so a leftover
// non-Postgres DATABASE_URL (e.g. a Turso libsql:// URL) can't be picked up by mistake.
// Local dev: none set -> an embedded Postgres (PGlite) file under data/pgdata.
const PG_URL = process.env.POSTGRES_URL || process.env.POSTGRES_PRISMA_URL
  || process.env.DATABASE_URL || process.env.POSTGRES_URL_NON_POOLING
  || process.env.DATABASE_URL_UNPOOLED || '';

// Naive-UTC timestamp text (matches the format templates/date logic expect).
export const NOW = "to_char((now() AT TIME ZONE 'UTC'),'YYYY-MM-DD HH24:MI:SS')";

let backend = null;

async function makeBackend() {
  if (PG_URL) {
    const pg = (await import('pg')).default;
    const pool = new pg.Pool({
      connectionString: PG_URL,
      ssl: /localhost|127\.0\.0\.1/.test(PG_URL) ? false : { rejectUnauthorized: false },
      max: 3,
    });
    return {
      query: (sql, args) => pool.query(sql, args),
      exec: (sql) => pool.query(sql),
      tx: async (stmts) => {
        const c = await pool.connect();
        try { await c.query('BEGIN'); for (const s of stmts) await c.query(s.sql, s.args); await c.query('COMMIT'); }
        catch (e) { await c.query('ROLLBACK'); throw e; }
        finally { c.release(); }
      },
    };
  }
  if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });
  const { PGlite } = await import('@electric-sql/pglite');
  const pglite = new PGlite({ dataDir: path.join(DATA_DIR, 'pgdata') });
  await pglite.waitReady;
  return {
    query: async (sql, args) => { const r = await pglite.query(sql, args); return { rows: r.rows, rowCount: r.affectedRows ?? 0 }; },
    exec: (sql) => pglite.exec(sql),
    tx: async (stmts) => { await pglite.transaction(async (t) => { for (const s of stmts) await t.query(s.sql, s.args); }); },
  };
}

// Convert `?` placeholders to Postgres `$1, $2, ...`
function toPg(sql) { let i = 0; return sql.replace(/\?/g, () => `$${++i}`); }

export async function get(sql, ...args) { const r = await backend.query(toPg(sql), args); return r.rows[0] ?? null; }
export async function all(sql, ...args) { const r = await backend.query(toPg(sql), args); return r.rows; }
export async function run(sql, ...args) { const r = await backend.query(toPg(sql), args); return { changes: r.rowCount ?? 0 }; }
export async function insert(sql, ...args) { const r = await backend.query(toPg(sql), args); return r.rows[0] ? Number(r.rows[0].id) : null; }
export async function exec(sql) { await backend.exec(sql); }
export async function batch(statements) { if (statements.length) await backend.tx(statements.map((s) => ({ sql: toPg(s.sql), args: s.args || [] }))); }

// ---- Settings cache (sync reads for templates/config) ----
const settings = new Map();
export function getSetting(key, fallback = null) { return settings.has(key) ? settings.get(key) : fallback; }
export async function setSetting(key, value) {
  await run(`INSERT INTO settings (key, value) VALUES (?, ?)
             ON CONFLICT (key) DO UPDATE SET value = excluded.value`, key, String(value));
  settings.set(key, String(value));
}
async function loadSettings() {
  settings.clear();
  for (const row of await all('SELECT key, value FROM settings')) settings.set(row.key, row.value);
}

async function createSchema() {
  await exec(`
    CREATE TABLE IF NOT EXISTS users (
      id SERIAL PRIMARY KEY,
      email TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      name TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('admin','user')),
      preferred_translation TEXT DEFAULT 'eng_kjv',
      secondary_translation TEXT,
      theme TEXT DEFAULT 'auto' CHECK(theme IN ('light','dark','auto')),
      reader_font_size INTEGER DEFAULT 18,
      reader_font_family TEXT DEFAULT 'serif',
      must_change_password INTEGER DEFAULT 0,
      created_at TEXT DEFAULT ${NOW},
      updated_at TEXT DEFAULT ${NOW},
      last_login TEXT
    );
    CREATE TABLE IF NOT EXISTS chapter_progress (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      week_number INTEGER NOT NULL,
      category TEXT NOT NULL,
      book TEXT NOT NULL,
      chapter INTEGER NOT NULL,
      completed INTEGER DEFAULT 0,
      completed_at TEXT,
      UNIQUE (user_id, week_number, category, book, chapter)
    );
    CREATE INDEX IF NOT EXISTS idx_cp_user ON chapter_progress(user_id);
    CREATE INDEX IF NOT EXISTS idx_cp_week ON chapter_progress(user_id, week_number);
    CREATE TABLE IF NOT EXISTS notes (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      title TEXT NOT NULL, content TEXT NOT NULL,
      week_number INTEGER, category TEXT, book TEXT, chapter INTEGER,
      color TEXT DEFAULT 'default',
      created_at TEXT DEFAULT ${NOW},
      updated_at TEXT DEFAULT ${NOW}
    );
    CREATE INDEX IF NOT EXISTS idx_notes_user ON notes(user_id);
    CREATE TABLE IF NOT EXISTS highlights (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      book TEXT NOT NULL, chapter INTEGER NOT NULL, verse INTEGER NOT NULL,
      color TEXT DEFAULT 'yellow', created_at TEXT DEFAULT ${NOW},
      UNIQUE (user_id, book, chapter, verse)
    );
    CREATE INDEX IF NOT EXISTS idx_hl_user ON highlights(user_id, book, chapter);
    CREATE TABLE IF NOT EXISTS bookmarks (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      book TEXT NOT NULL, chapter INTEGER NOT NULL, label TEXT,
      created_at TEXT DEFAULT ${NOW},
      UNIQUE (user_id, book, chapter)
    );
    CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT);
    CREATE TABLE IF NOT EXISTS reading_categories (id TEXT PRIMARY KEY, name TEXT NOT NULL, color TEXT NOT NULL, sort_order INTEGER DEFAULT 0);
    CREATE TABLE IF NOT EXISTS reading_plan (
      id SERIAL PRIMARY KEY,
      week_number INTEGER NOT NULL, category_id TEXT NOT NULL, reference TEXT NOT NULL, passages TEXT NOT NULL,
      UNIQUE (week_number, category_id)
    );
    CREATE TABLE IF NOT EXISTS bible_translations (id TEXT PRIMARY KEY, name TEXT NOT NULL, language TEXT NOT NULL, direction TEXT DEFAULT 'ltr');
    CREATE TABLE IF NOT EXISTS badges (
      id TEXT PRIMARY KEY, name TEXT NOT NULL, description TEXT NOT NULL, icon TEXT NOT NULL,
      category TEXT NOT NULL, criteria TEXT NOT NULL, sort_order INTEGER DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS user_badges (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      badge_id TEXT NOT NULL REFERENCES badges(id) ON DELETE CASCADE,
      earned_at TEXT DEFAULT ${NOW},
      UNIQUE (user_id, badge_id)
    );
    CREATE TABLE IF NOT EXISTS password_resets (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL,
      used INTEGER DEFAULT 0, created_at TEXT DEFAULT ${NOW}
    );
    CREATE TABLE IF NOT EXISTS login_attempts (
      id SERIAL PRIMARY KEY, email TEXT NOT NULL, ip_address TEXT NOT NULL,
      attempted_at TEXT DEFAULT ${NOW}
    );
    CREATE INDEX IF NOT EXISTS idx_login ON login_attempts(email, attempted_at);
    CREATE TABLE IF NOT EXISTS sessions (sid TEXT PRIMARY KEY, sess TEXT NOT NULL, expire BIGINT NOT NULL);
  `);
}

async function seedSettings() {
  const defaults = {
    app_name: 'ReadIn52',
    app_tagline: 'Journey Through Scripture in 52 Weeks',
    default_translation: 'eng_kjv',
    registration_enabled: '1',
    github_repo_url: 'https://github.com/robinson-vidva/readin52',
    admin_email: 'seek@askdevotions.com',
    parent_site_name: '',
    parent_site_url: '',
  };
  await batch(Object.entries(defaults).map(([k, v]) => ({ sql: 'INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT DO NOTHING', args: [k, v] })));
}

async function seedReadingPlan() {
  const c = await get('SELECT COUNT(*)::int AS c FROM reading_plan');
  if (c.c > 0) return;
  const raw = fs.readFileSync(path.join(__dirname, 'data', 'reading-plan.json'), 'utf-8');
  const plan = JSON.parse(raw);
  const stmts = [];
  (plan.categories || []).forEach((cat, i) => stmts.push({ sql: 'INSERT INTO reading_categories (id, name, color, sort_order) VALUES (?, ?, ?, ?) ON CONFLICT DO NOTHING', args: [cat.id, cat.name, cat.color, i] }));
  (plan.availableTranslations || []).forEach((t) => stmts.push({ sql: 'INSERT INTO bible_translations (id, name, language, direction) VALUES (?, ?, ?, ?) ON CONFLICT DO NOTHING', args: [t.id, t.name, t.language, t.direction || 'ltr'] }));
  for (const week of plan.weeks || []) {
    for (const [catId, reading] of Object.entries(week.readings)) {
      stmts.push({ sql: 'INSERT INTO reading_plan (week_number, category_id, reference, passages) VALUES (?, ?, ?, ?) ON CONFLICT DO NOTHING', args: [week.week, catId, reading.reference, JSON.stringify(reading.passages)] });
    }
  }
  await batch(stmts);
}

async function seedBadges() {
  await batch(BADGES.map((b) => ({
    sql: `INSERT INTO badges (id, name, description, icon, category, criteria, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT DO NOTHING`,
    args: [b.id, b.name, b.description, b.icon, b.category, JSON.stringify(b.criteria), b.sort_order],
  })));
}

async function seedAdmin() {
  const email = process.env.ADMIN_EMAIL || 'setup@localhost';
  const password = process.env.ADMIN_PASSWORD || 'ChangeMe52!';
  const any = await get('SELECT COUNT(*)::int AS c FROM users');
  if (any.c > 0) return null;
  const hash = bcrypt.hashSync(password, 12);
  await run(`INSERT INTO users (email, password_hash, name, role, must_change_password)
             VALUES (?, ?, 'Administrator', 'admin', 1)`, email.toLowerCase(), hash);
  return { email, password };
}

let readyPromise = null;
async function _init() {
  backend = await makeBackend();
  await createSchema();
  await seedSettings();
  await seedReadingPlan();
  await seedBadges();
  const admin = await seedAdmin();
  await loadSettings();
  const { loadPlan } = await import('./services/readingPlan.js');
  await loadPlan();
  if (admin) {
    console.log('\n  ✅ Default admin created:');
    console.log(`     email:    ${admin.email}`);
    console.log(`     password: ${admin.password}`);
    console.log('     (You will be prompted to change these on first login.)\n');
  }
}

export function initDb() {
  if (!readyPromise) readyPromise = _init();
  return readyPromise;
}

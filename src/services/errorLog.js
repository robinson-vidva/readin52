// Built-in error monitoring: persists server errors to the DB for the admin
// "Errors" page. Best-effort and never throws (logging must not cause errors).
import { get, all, run } from '../db.js';

export async function log(err, req) {
  try {
    await run(
      'INSERT INTO error_logs (method, path, message, stack, user_id) VALUES (?, ?, ?, ?, ?)',
      req?.method || null,
      (req?.originalUrl || '').slice(0, 300) || null,
      String(err?.message || err).slice(0, 500),
      String(err?.stack || '').slice(0, 4000),
      req?.user?.id || null,
    );
    // keep the table bounded (most recent 500)
    await run(`DELETE FROM error_logs WHERE id NOT IN (SELECT id FROM error_logs ORDER BY id DESC LIMIT 500)`);
  } catch {}
}

export async function getRecent(limit = 100) {
  return await all('SELECT * FROM error_logs ORDER BY id DESC LIMIT ?', limit);
}

export async function count() {
  return (await get('SELECT COUNT(*)::int AS c FROM error_logs')).c;
}

export async function clearAll() {
  await run('DELETE FROM error_logs');
}

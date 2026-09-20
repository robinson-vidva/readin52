import { get, run } from './db.js';

// express-session store backed by libSQL (local file or Turso). The `sessions`
// table is created in db.js createSchema().
export function makeSessionStore(session) {
  const Store = session.Store;
  const DEFAULT_TTL = 1000 * 60 * 60 * 24 * 30;

  function expiry(sess) {
    const e = sess && sess.cookie && sess.cookie.expires;
    return e ? new Date(e).getTime() : Date.now() + DEFAULT_TTL;
  }

  class LibsqlStore extends Store {
    get(sid, cb) {
      get('SELECT sess, expire FROM sessions WHERE sid = ?', sid).then((row) => {
        if (!row) return cb(null, null);
        if (row.expire < Date.now()) { run('DELETE FROM sessions WHERE sid = ?', sid).catch(() => {}); return cb(null, null); }
        cb(null, JSON.parse(row.sess));
      }).catch(cb);
    }
    set(sid, sess, cb) {
      run(`INSERT INTO sessions (sid, sess, expire) VALUES (?, ?, ?)
           ON CONFLICT (sid) DO UPDATE SET sess = excluded.sess, expire = excluded.expire`,
        sid, JSON.stringify(sess), expiry(sess)).then(() => cb && cb(null)).catch((e) => cb && cb(e));
    }
    touch(sid, sess, cb) {
      run('UPDATE sessions SET expire = ? WHERE sid = ?', expiry(sess), sid).then(() => cb && cb(null)).catch((e) => cb && cb(e));
    }
    destroy(sid, cb) {
      run('DELETE FROM sessions WHERE sid = ?', sid).then(() => cb && cb(null)).catch((e) => cb && cb(e));
    }
  }

  // Periodic cleanup of expired sessions
  setInterval(() => { run('DELETE FROM sessions WHERE expire < ?', Date.now()).catch(() => {}); }, 1000 * 60 * 30).unref?.();

  return new LibsqlStore();
}

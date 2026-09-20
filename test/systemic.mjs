/* Systemic integration test for ReadIn52.
   Runs black-box against a running server: TEST_URL (default http://localhost:8150).
   Exercises public pages, auth, guards, every API, admin, and the new features. */

const BASE = process.env.TEST_URL || 'http://localhost:8150';
const results = [];
let passed = 0, failed = 0;
function check(name, cond, detail = '') {
  if (cond) { passed++; results.push(`  ✅ ${name}`); }
  else { failed++; results.push(`  ❌ ${name}${detail ? '  — ' + detail : ''}`); }
}

// ---- tiny cookie-jar fetch ----
function jar() {
  const cookies = {};
  return {
    cookies,
    async fetch(path, opts = {}) {
      opts.redirect = opts.redirect || 'manual';
      opts.headers = opts.headers || {};
      const ck = Object.entries(cookies).map(([k, v]) => `${k}=${v}`).join('; ');
      if (ck) opts.headers.Cookie = ck;
      const res = await fetch(BASE + path, opts);
      const setC = res.headers.getSetCookie ? res.headers.getSetCookie() : [];
      for (const c of setC) { const [kv] = c.split(';'); const i = kv.indexOf('='); cookies[kv.slice(0, i)] = kv.slice(i + 1); }
      return res;
    },
  };
}
async function csrfFrom(session, path) {
  const html = await (await session.fetch(path)).text();
  return (html.match(/name="csrf_token" value="([^"]+)"/) || html.match(/csrf: '([^']+)'/) || [])[1];
}
function form(obj) { return new URLSearchParams(obj).toString(); }
const FH = { 'Content-Type': 'application/x-www-form-urlencoded' };
async function apiGet(s, p) { return (await s.fetch(p)).json().catch(() => ({})); }
async function apiPost(s, p, body, csrf) { const r = await s.fetch(p, { method: 'POST', headers: { 'Content-Type': 'application/json', 'x-csrf-token': csrf }, body: JSON.stringify(body) }); return r.json().catch(() => ({ _status: r.status })); }

async function run() {
  const rnd = Date.now().toString(36);
  const userEmail = `sys.${rnd}@example.com`;

  // 1. Public pages
  for (const p of ['/', '/login', '/register', '/forgot-password', '/about', '/privacy', '/terms']) {
    const r = await jar().fetch(p);
    check(`public ${p} → 200`, r.status === 200, `got ${r.status}`);
  }

  // 2. Guards
  const anon = jar();
  check('guard /dashboard → redirect', (await anon.fetch('/dashboard')).status === 302);
  check('guard /api/stats → 401', (await anon.fetch('/api/stats')).status === 401);
  check('404 for unknown route', (await anon.fetch('/nope-' + rnd)).status === 404);

  // 3. Register + login
  const u = jar();
  let csrf = await csrfFrom(u, '/register');
  const reg = await u.fetch('/register', { method: 'POST', headers: FH, body: form({ csrf_token: csrf, name: 'Sys Tester', email: userEmail, password: 'TestPass123', password_confirm: 'TestPass123', accept_terms: '1' }) });
  check('register → 302', reg.status === 302, `got ${reg.status}`);
  csrf = await csrfFrom(u, '/login');
  const login = await u.fetch('/login', { method: 'POST', headers: FH, body: form({ csrf_token: csrf, email: userEmail, password: 'TestPass123' }) });
  check('login → 302', login.status === 302);
  csrf = await csrfFrom(u, '/dashboard'); // session csrf

  // 4. Authed pages
  for (const p of ['/dashboard', '/books', '/notes', '/stats', '/achievements', '/settings', '/reader/JHN/3', '/reader/PSA/23']) {
    const r = await u.fetch(p);
    check(`authed ${p} → 200`, r.status === 200, `got ${r.status}`);
  }

  // 5. CSRF rejection
  const noCsrf = await u.fetch('/api/progress', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ week: 1, category: 'poetry' }) });
  check('CSRF rejected without token → 403', noCsrf.status === 403);

  // 6. Reading plan + progress APIs
  const week = await apiGet(u, '/api/week/1');
  check('GET /api/week/1', week.success && week.week && week.week.readings.poetry, JSON.stringify(week).slice(0, 80));
  const chap = await apiPost(u, '/api/chapter-progress', { week: 1, category: 'history', book: 'GEN', chapter: 1 }, csrf);
  check('toggle chapter-progress', chap.success && chap.weekCounts && chap.overallStats, JSON.stringify(chap).slice(0, 80));
  const cat = await apiPost(u, '/api/progress', { week: 1, category: 'poetry' }, csrf);
  check('toggle category (awards badge)', cat.success && Array.isArray(cat.newBadges), JSON.stringify(cat).slice(0, 80));
  const stats = await apiGet(u, '/api/stats');
  check('GET /api/stats', stats.success && stats.stats && stats.chapterStats.total_chapters === 1189);

  // 7. Notes CRUD + export
  const created = await apiPost(u, '/api/notes', { title: 'T', content: 'hello', color: 'green', book: 'JHN', chapter: 3, verse: 16 }, csrf);
  check('create verse note', created.success && created.id, JSON.stringify(created));
  const notesList = await apiGet(u, '/api/notes');
  check('list notes contains it', notesList.notes.some((n) => n.id === created.id));
  const oneNote = await apiGet(u, '/api/notes/' + created.id);
  check('get note by id + has verse', oneNote.success && oneNote.note.verse === 16);
  const upd = await apiPost(u, '/api/notes', { note_id: created.id, title: 'T2', content: 'updated', book: 'JHN', chapter: 3, verse: 16 }, csrf);
  check('update note', upd.success);
  const chNotes = await apiGet(u, '/api/notes/chapter?book=JHN&chapter=3');
  check('notes for chapter', chNotes.success && chNotes.notes.length >= 1);
  const md = await (await u.fetch('/api/notes/export?format=md')).text();
  check('notes export md', md.includes('# ReadIn52 Notes') && md.includes('T2'));
  const del = await u.fetch('/api/notes/' + created.id, { method: 'DELETE', headers: { 'x-csrf-token': csrf } });
  check('delete note → success', (await del.json()).success);

  // 8. Highlights + bookmarks
  const hlSet = await apiPost(u, '/api/highlights', { book: 'JHN', chapter: 3, verse: 16, color: 'yellow' }, csrf);
  check('set highlight', hlSet.success && hlSet.color === 'yellow');
  const hlGet = await apiGet(u, '/api/highlights?book=JHN&chapter=3');
  check('get highlights', hlGet.highlights['16'] === 'yellow');
  const hlClear = await apiPost(u, '/api/highlights', { book: 'JHN', chapter: 3, verse: 16, color: 'yellow' }, csrf);
  check('toggle highlight off', hlClear.success && hlClear.color === null);
  const bm = await apiPost(u, '/api/bookmarks', { book: 'JHN', chapter: 3 }, csrf);
  check('add bookmark', bm.success && bm.bookmarked === true);
  const bmOff = await apiPost(u, '/api/bookmarks', { book: 'JHN', chapter: 3 }, csrf);
  check('remove bookmark', bmOff.success && bmOff.bookmarked === false);

  // 9. Cross-references (study feature)
  const xr = await apiGet(u, '/api/cross-references?book=JHN&chapter=3&verse=16');
  check('cross-refs for JHN 3:16', xr.success && xr.refs.length >= 10 && xr.refs[0].ref);
  const xrCh = await apiGet(u, '/api/cross-references/chapter?book=JHN&chapter=3');
  check('cross-refs verses-in-chapter', xrCh.success && xrCh.verses.length > 20);

  // 10. Translations count (50+ requirement)
  const readerHtml = await (await u.fetch('/reader/JHN/3')).text();
  const optCount = (readerHtml.match(/<option /g) || []).length;
  const langGroups = (readerHtml.match(/<optgroup /g) || []).length;
  check('translations 100+ in reader', optCount >= 200, `options=${optCount} (2 selects)`); // 2 selects × ~100+
  check('translations grouped by language', langGroups >= 40, `optgroups=${langGroups}`);

  // 11. Admin flow (seeded admin, must-change first)
  const admin = jar();
  csrf = await csrfFrom(admin, '/login');
  await admin.fetch('/login', { method: 'POST', headers: FH, body: form({ csrf_token: csrf, email: process.env.ADMIN_EMAIL || 'setup@localhost', password: process.env.ADMIN_PASSWORD || 'ChangeMe52!' }) });
  const setupPage = await admin.fetch('/setup-credentials');
  if (setupPage.status === 200) {
    csrf = await csrfFrom(admin, '/setup-credentials');
    await admin.fetch('/setup-credentials', { method: 'POST', headers: FH, body: form({ csrf_token: csrf, name: 'Admin', email: `admin.${rnd}@example.com`, password: 'AdminPass123', password_confirm: 'AdminPass123' }) });
  }
  for (const p of ['/admin', '/admin/users', '/admin/reading-plan', '/admin/settings', '/admin/user-progress']) {
    const r = await admin.fetch(p);
    check(`admin ${p} → 200`, r.status === 200, `got ${r.status}`);
  }
  // regular user blocked from admin
  check('non-admin blocked from /admin (403)', (await u.fetch('/admin')).status === 403);

  // Summary
  console.log('\n=== ReadIn52 systemic test ===\n');
  console.log(results.join('\n'));
  console.log(`\n${passed} passed, ${failed} failed  (${passed + failed} checks)\n`);
  process.exit(failed ? 1 : 0);
}
run().catch((e) => { console.error('Test crashed:', e); process.exit(2); });

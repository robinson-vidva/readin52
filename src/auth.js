import crypto from 'node:crypto';
import bcrypt from 'bcryptjs';
import { get, run } from './db.js';
import * as Users from './services/users.js';

const RATE_LIMIT = 5;
const RATE_WINDOW_MIN = 15;

async function recentAttempts(email, ip) {
  const row = await get(`SELECT COUNT(*)::int AS c FROM login_attempts
    WHERE (email = ? OR ip_address = ?)
    AND attempted_at > to_char((now() AT TIME ZONE 'UTC') - interval '${RATE_WINDOW_MIN} minutes','YYYY-MM-DD HH24:MI:SS')`, email, ip);
  return row.c;
}
async function logAttempt(email, ip) { await run('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)', email, ip); }
async function clearAttempts(email) { await run('DELETE FROM login_attempts WHERE email = ?', email); }

export async function login(email, password, ip) {
  email = (email || '').trim().toLowerCase();
  if (await recentAttempts(email, ip) >= RATE_LIMIT) {
    return { success: false, error: 'Too many attempts. Please try again in 15 minutes.' };
  }
  const user = await Users.findByEmail(email);
  if (!user || !bcrypt.compareSync(password, user.password_hash)) {
    await logAttempt(email, ip);
    return { success: false, error: 'Invalid email or password.' };
  }
  await clearAttempts(email);
  await Users.setLastLogin(user.id);
  return { success: true, user };
}

export async function register(name, email, password) {
  name = (name || '').trim();
  email = (email || '').trim().toLowerCase();
  if (name.length < 2) return { success: false, error: 'Please enter your name.' };
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return { success: false, error: 'Please enter a valid email address.' };
  if (password.length < 6) return { success: false, error: 'Password must be at least 6 characters.' };
  const id = await Users.createUser(name, email, password, 'user', 0);
  if (!id) return { success: false, error: 'An account with this email already exists.' };
  return { success: true, id };
}

// ---- CSRF (session-based, synchronous) ----
export function csrfToken(req) {
  if (!req.session.csrf) req.session.csrf = crypto.randomBytes(24).toString('hex');
  return req.session.csrf;
}
export function verifyCsrf(req) {
  const token = req.body?.csrf_token || req.get('x-csrf-token') || (req.body && req.body._csrf);
  if (!token || !req.session.csrf) return false;
  const a = Buffer.from(String(token));
  const b = Buffer.from(String(req.session.csrf));
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}

// ---- Middleware ----
export async function attachUser(req, res, next) {
  try {
    req.user = req.session.userId ? await Users.findById(req.session.userId) : null;
    res.locals.currentUser = req.user ? Users.safeUser(req.user) : null;
    res.locals.csrfToken = csrfToken(req);
    next();
  } catch (e) { next(e); }
}

export function requireAuth(req, res, next) {
  if (!req.user) return res.redirect('/login');
  if (req.user.must_change_password && req.path !== '/setup-credentials' && !req.path.startsWith('/api')) {
    return res.redirect('/setup-credentials');
  }
  next();
}

export function requireAdmin(req, res, next) {
  if (!req.user) return res.redirect('/login');
  if (req.user.role !== 'admin') return res.status(403).render('error', { title: 'Forbidden', message: 'Admin access required.' });
  next();
}

export function csrfGuard(req, res, next) {
  if (['POST', 'PUT', 'DELETE'].includes(req.method) && !verifyCsrf(req)) {
    if (req.accepts('json') && !req.accepts('html')) return res.status(403).json({ success: false, error: 'Invalid CSRF token' });
    return res.status(403).render('error', { title: 'Invalid request', message: 'Your session expired or the request was invalid. Please go back and try again.' });
  }
  next();
}

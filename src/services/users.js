import bcrypt from 'bcryptjs';
import crypto from 'node:crypto';
import { get, all, run, insert, NOW } from '../db.js';

export async function findByEmail(email) {
  return await get('SELECT * FROM users WHERE email = ?', email);
}

export async function findById(id) {
  return await get('SELECT * FROM users WHERE id = ?', id);
}

export async function createUser(name, email, password, role = 'user', mustChange = 0) {
  const hash = bcrypt.hashSync(password, 12);
  try {
    return await insert(`INSERT INTO users (name, email, password_hash, role, must_change_password)
      VALUES (?, ?, ?, ?, ?) RETURNING id`, name, email.toLowerCase(), hash, role, mustChange ? 1 : 0);
  } catch (e) {
    if (e.code === '23505' || /unique|duplicate/i.test(e.message)) return null;
    throw e;
  }
}

const ALLOWED = ['name', 'email', 'role', 'preferred_translation', 'secondary_translation', 'theme', 'reader_font_size', 'reader_font_family', 'reminder_email'];

export async function updateUser(id, data) {
  const fields = [];
  const values = [];
  for (const [k, v] of Object.entries(data)) {
    if (!ALLOWED.includes(k)) continue;
    fields.push(`${k} = ?`);
    values.push(k === 'email' && v ? String(v).toLowerCase() : v);
  }
  if (!fields.length) return false;
  fields.push(`updated_at = ${NOW}`);
  values.push(id);
  return (await run(`UPDATE users SET ${fields.join(', ')} WHERE id = ?`, ...values)).changes > 0;
}

export async function updatePassword(id, password) {
  const hash = bcrypt.hashSync(password, 12);
  return (await run(`UPDATE users SET password_hash = ?, updated_at = ${NOW} WHERE id = ?`, hash, id)).changes > 0;
}

export async function verifyPassword(id, password) {
  const u = await findById(id);
  return u ? bcrypt.compareSync(password, u.password_hash) : false;
}

export async function deleteUser(id) {
  return (await run('DELETE FROM users WHERE id = ?', id)).changes > 0;
}

export async function listUsers() {
  return await all('SELECT id, name, email, role, preferred_translation, created_at, last_login FROM users ORDER BY created_at DESC');
}

export async function countUsers() {
  return (await get('SELECT COUNT(*)::int AS c FROM users')).c;
}

export async function setLastLogin(id) {
  await run(`UPDATE users SET last_login = ${NOW} WHERE id = ?`, id);
}

export async function mustChangePassword(id) {
  const u = await findById(id);
  return !!(u && u.must_change_password);
}

export async function clearMustChangePassword(id) {
  await run('UPDATE users SET must_change_password = 0 WHERE id = ?', id);
}

// ---- Password reset tokens ----
export async function createPasswordResetToken(email) {
  const user = await findByEmail(email.toLowerCase());
  if (!user) return null;
  const token = crypto.randomBytes(32).toString('hex');
  const expires = new Date(Date.now() + 3600 * 1000).toISOString().slice(0, 19).replace('T', ' ');
  await run('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)', user.id, token, expires);
  return { user, token };
}

export async function validatePasswordResetToken(token) {
  return await get(`SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > ${NOW}`, token);
}

export async function resetPasswordWithToken(token, password) {
  const row = await validatePasswordResetToken(token);
  if (!row) return false;
  await updatePassword(row.user_id, password);
  await run('UPDATE password_resets SET used = 1 WHERE id = ?', row.id);
  return true;
}

// ---- Email change verification ----
export async function createEmailVerificationToken(userId, newEmail) {
  newEmail = newEmail.toLowerCase();
  const existing = await findByEmail(newEmail);
  if (existing && existing.id !== userId) return null; // email already in use
  const token = crypto.randomBytes(32).toString('hex');
  const expires = new Date(Date.now() + 3600 * 1000).toISOString().slice(0, 19).replace('T', ' ');
  await run('INSERT INTO email_verifications (user_id, new_email, token, expires_at) VALUES (?, ?, ?, ?)',
    userId, newEmail, token, expires);
  return { token, newEmail };
}

export async function completeEmailChange(token) {
  const row = await get(`SELECT * FROM email_verifications WHERE token=? AND used=0 AND expires_at > ${NOW}`, token);
  if (!row) return null;
  // Guard against the address being taken since the token was issued
  const taken = await findByEmail(row.new_email);
  if (taken && taken.id !== row.user_id) return null;
  await updateUser(row.user_id, { email: row.new_email });
  await run('UPDATE email_verifications SET used = 1 WHERE id = ?', row.id);
  return { user_id: row.user_id, new_email: row.new_email };
}

export async function getUsersWithReminders() {
  return await all('SELECT id, name, email, preferred_translation FROM users WHERE reminder_email = 1');
}

// One-click unsubscribe token (no login needed), signed with SESSION_SECRET.
export function unsubToken(userId) {
  const secret = process.env.SESSION_SECRET || 'readin52-dev-secret-change-me';
  return crypto.createHmac('sha256', secret).update('reminders:' + userId).digest('hex').slice(0, 32);
}
export function verifyUnsub(userId, token) {
  return token === unsubToken(userId);
}
export async function disableReminders(userId) {
  await run('UPDATE users SET reminder_email = 0 WHERE id = ?', userId);
}

export function safeUser(u) {
  if (!u) return null;
  const { password_hash, ...rest } = u;
  return rest;
}

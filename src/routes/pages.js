import express from 'express';
import * as Auth from '../auth.js';
import * as Users from '../services/users.js';
import * as Progress from '../services/progress.js';
import * as Notes from '../services/notes.js';
import * as Badges from '../services/badges.js';
import * as Plan from '../services/readingPlan.js';
import * as Email from '../services/email.js';
import * as Turnstile from '../services/turnstile.js';
import * as Search from '../services/search.js';
import { BOOK_NAMES, BOOK_CHAPTERS, OLD_TESTAMENT, NEW_TESTAMENT } from '../data/books.js';

const router = express.Router();
const { requireAuth, csrfGuard } = Auth;

// Wrap async handlers so rejected promises reach Express's error handler.
const a = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);

// Public base URL for building links in emails (works behind Vercel's proxy).
const baseUrl = (req) => process.env.APP_URL || `${req.protocol}://${req.get('host')}`;

// ---------- Public ----------
router.get('/', (req, res) => {
  if (req.user) return res.redirect('/dashboard');
  res.render('home', { title: res.locals.app.appName });
});

router.get('/login', (req, res) => {
  if (req.user) return res.redirect('/dashboard');
  res.render('login', { title: 'Sign In', error: null, email: '' });
});

router.post('/login', csrfGuard, a(async (req, res) => {
  const { email, password } = req.body;
  if (!(await Turnstile.verify(req.body['cf-turnstile-response'], req.ip))) {
    return res.render('login', { title: 'Sign In', error: 'Human verification failed. Please try again.', email: email || '' });
  }
  const result = await Auth.login(email, password, req.ip);
  if (!result.success) return res.render('login', { title: 'Sign In', error: result.error, email });
  req.session.userId = result.user.id;
  if (result.user.must_change_password) return res.redirect('/setup-credentials');
  req.flash('success', 'Welcome back!');
  res.redirect('/dashboard');
}));

router.get('/register', (req, res) => {
  if (req.user) return res.redirect('/dashboard');
  if (!res.locals.app.registrationEnabled) { req.flash('error', 'Registration is currently disabled.'); return res.redirect('/login'); }
  res.render('register', { title: 'Create Account', error: null, name: '', email: '' });
});

router.post('/register', csrfGuard, a(async (req, res) => {
  if (!res.locals.app.registrationEnabled) return res.redirect('/login');
  const { name, email, password, password_confirm, accept_terms } = req.body;
  const render = (error) => res.render('register', { title: 'Create Account', error, name, email });
  if (!(await Turnstile.verify(req.body['cf-turnstile-response'], req.ip))) return render('Human verification failed. Please try again.');
  if (!accept_terms) return render('You must accept the Terms & Conditions.');
  if (password !== password_confirm) return render('Passwords do not match.');
  const result = await Auth.register(name, email, password);
  if (!result.success) return render(result.error);
  if (Email.isConfigured()) {
    Email.sendWelcome(email.toLowerCase(), name, `${baseUrl(req)}/login`, res.locals.app.appName).catch(() => {});
  }
  req.flash('success', 'Account created! Please sign in.');
  res.redirect('/login');
}));

router.get('/logout', (req, res) => { req.session.destroy(() => res.redirect('/')); });

router.get('/forgot-password', (req, res) => {
  if (req.user) return res.redirect('/dashboard');
  res.render('forgot-password', { title: 'Reset Password', error: null, success: null });
});

router.post('/forgot-password', csrfGuard, a(async (req, res) => {
  const email = (req.body.email || '').trim();
  if (!(await Turnstile.verify(req.body['cf-turnstile-response'], req.ip))) {
    return res.render('forgot-password', { title: 'Reset Password', error: 'Human verification failed. Please try again.', success: null });
  }
  // Always show the same message to prevent email enumeration.
  const success = 'If an account exists with this email, you will receive a reset link shortly.';
  let devLink = null;
  if (email) {
    const data = await Users.createPasswordResetToken(email.toLowerCase());
    if (data) {
      const link = `${baseUrl(req)}/reset-password?token=${data.token}`;
      if (Email.isConfigured()) {
        await Email.sendPasswordReset(data.user.email, data.user.name, link, res.locals.app.appName);
      } else {
        devLink = `/reset-password?token=${data.token}`; // local dev fallback when email isn't configured
      }
    }
  }
  res.render('forgot-password', { title: 'Reset Password', error: null, success, devLink });
}));

router.get('/reset-password', a(async (req, res) => {
  if (req.user) return res.redirect('/dashboard');
  const token = req.query.token || '';
  const valid = !!(await Users.validatePasswordResetToken(token));
  res.render('reset-password', { title: 'Reset Password', validToken: valid, token, error: null, success: null });
}));

router.post('/reset-password', csrfGuard, a(async (req, res) => {
  const { token, password, password_confirm } = req.body;
  const render = (opts) => res.render('reset-password', { title: 'Reset Password', token, error: null, success: null, validToken: true, ...opts });
  if (password.length < 6) return render({ error: 'Password must be at least 6 characters.' });
  if (password !== password_confirm) return render({ error: 'Passwords do not match.' });
  if (await Users.resetPasswordWithToken(token, password)) {
    return res.render('reset-password', { title: 'Reset Password', validToken: false, token, error: null, success: 'Your password has been reset. You can now sign in.' });
  }
  res.render('reset-password', { title: 'Reset Password', validToken: false, token, error: 'This reset link is invalid or has expired.', success: null });
}));

router.get('/verify-email', a(async (req, res) => {
  const result = await Users.completeEmailChange(req.query.token || '');
  if (result) req.flash('success', `Your email has been updated to ${result.new_email}.`);
  else req.flash('error', 'This verification link is invalid or has expired.');
  res.redirect(req.user ? '/settings' : '/login');
}));

// One-click unsubscribe from reminder emails (no login required)
router.get('/unsubscribe/reminders', a(async (req, res) => {
  const u = parseInt(req.query.u, 10);
  if (u && Users.verifyUnsub(u, req.query.t || '')) {
    await Users.disableReminders(u);
    return res.render('error', { title: 'Reminders turned off', message: 'You will no longer receive daily reminder emails. You can re-enable them in Settings any time.' });
  }
  res.status(400).render('error', { title: 'Invalid link', message: 'This unsubscribe link is invalid or has expired.' });
}));

// Daily reminder cron (invoked by Vercel Cron; protected by CRON_SECRET)
router.get('/cron/reminders', a(async (req, res) => {
  const secret = process.env.CRON_SECRET;
  if (secret && req.get('authorization') !== `Bearer ${secret}`) return res.status(401).json({ error: 'unauthorized' });
  if (!Email.isConfigured()) return res.json({ ok: true, skipped: 'email not configured' });
  const users = await Users.getUsersWithReminders();
  const base = baseUrl(req);
  let sent = 0;
  for (const u of users) {
    const wk = await Progress.getCurrentWeek(u.id);
    const week = Plan.getWeek(wk);
    let line = `Week ${wk}`;
    if (week) {
      const refs = ['poetry', 'history', 'prophecy', 'gospels'].map((c) => week.readings[c] && week.readings[c].reference).filter(Boolean).join(' · ');
      if (refs) line += ' — ' + refs;
    }
    const unsub = `${base}/unsubscribe/reminders?u=${u.id}&t=${Users.unsubToken(u.id)}`;
    const r = await Email.sendReminder(u.email, u.name, `${base}/dashboard`, line, unsub, res.locals.app.appName);
    if (r.success) sent++;
  }
  res.json({ ok: true, users: users.length, sent });
}));

router.get('/about', (req, res) => res.render('about', { title: 'About' }));
router.get('/privacy', (req, res) => res.render('privacy', { title: 'Privacy Policy' }));
router.get('/terms', (req, res) => res.render('terms', { title: 'Terms & Conditions' }));

// ---------- Setup credentials ----------
router.get('/setup-credentials', (req, res) => {
  if (!req.user) return res.redirect('/login');
  if (!req.user.must_change_password) return res.redirect('/dashboard');
  res.render('setup-credentials', { title: 'Set Up Your Account', error: null });
});

router.post('/setup-credentials', csrfGuard, a(async (req, res) => {
  if (!req.user) return res.redirect('/login');
  const { name, email, password, password_confirm } = req.body;
  const render = (error) => res.render('setup-credentials', { title: 'Set Up Your Account', error });
  if (!name || name.trim().length < 2) return render('Please enter your name.');
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return render('Please enter a valid email.');
  if (password.length < 6) return render('Password must be at least 6 characters.');
  if (password !== password_confirm) return render('Passwords do not match.');
  const existing = await Users.findByEmail(email.toLowerCase());
  if (existing && existing.id !== req.user.id) return render('This email is already in use.');
  await Users.updateUser(req.user.id, { name: name.trim(), email });
  await Users.updatePassword(req.user.id, password);
  await Users.clearMustChangePassword(req.user.id);
  req.flash('success', `Welcome to ${res.locals.app.appName}!`);
  res.redirect('/dashboard');
}));

// ---------- Authenticated ----------
router.get('/dashboard', requireAuth, a(async (req, res) => {
  const [stats, weekly, badgeCount, noteCount, chapterStats] = await Promise.all([
    Progress.getStats(req.user.id),
    Progress.getWeeklyCompletion(req.user.id),
    Badges.getUserBadgeCount(req.user.id),
    Notes.countNotes(req.user.id),
    Progress.getChapterStats(req.user.id),
  ]);
  res.render('dashboard', {
    title: 'Dashboard', stats, weekly, weeks: Plan.getWeeks(), categories: Plan.getCategories(),
    badgeCount, noteCount, chapterStats, bookNames: BOOK_NAMES,
    pageScripts: ['bible-api.js', 'dashboard.js'],
  });
}));

router.get('/stats', requireAuth, a(async (req, res) => {
  const [stats, weekly, bookStats, readingDates, chapterStats] = await Promise.all([
    Progress.getStats(req.user.id),
    Progress.getWeeklyCompletion(req.user.id),
    Progress.getBookCompletionStats(req.user.id),
    Progress.getReadingDates(req.user.id),
    Progress.getChapterStats(req.user.id),
  ]);
  res.render('stats', {
    title: 'Statistics', stats, weekly, bookStats, readingDates, chapterStats,
    categories: Plan.getCategories(), bookNames: BOOK_NAMES,
    bookOrder: [...OLD_TESTAMENT, ...NEW_TESTAMENT], pageScripts: ['stats.js'],
  });
}));

router.get('/achievements', requireAuth, a(async (req, res) => {
  const badges = await Badges.getBadgesWithProgress(req.user.id);
  res.render('achievements', { title: 'Achievements', badges, earnedCount: badges.filter((b) => b.earned).length, total: badges.length });
}));

router.get('/books', requireAuth, a(async (req, res) => {
  const bookStats = await Progress.getBookCompletionStats(req.user.id);
  res.render('books', {
    title: 'Books', bookNames: BOOK_NAMES, bookChapters: BOOK_CHAPTERS,
    oldTestament: OLD_TESTAMENT, newTestament: NEW_TESTAMENT, bookStats,
  });
}));

router.get('/notes', requireAuth, a(async (req, res) => {
  res.render('notes', {
    title: 'Notes', notes: await Notes.getAllNotes(req.user.id),
    bookNames: BOOK_NAMES, categories: Plan.getCategoryMap(), pageScripts: ['notes.js'],
  });
}));

router.get('/search', requireAuth, (req, res) => {
  const q = String(req.query.q || '').slice(0, 120);
  const found = q ? Search.search(q, { limit: 80 }) : { query: '', total: 0, results: [], translation: 'KJV', words: [] };
  res.render('search', { title: q ? `Search: ${q}` : 'Search', q, found });
});

router.get('/reader/:book/:chapter', requireAuth, (req, res) => {
  const book = req.params.book.toUpperCase();
  const chapter = parseInt(req.params.chapter, 10) || 1;
  if (!BOOK_NAMES[book]) return res.status(404).render('error', { title: 'Not found', message: 'Unknown book.' });
  res.render('reader', {
    title: `${BOOK_NAMES[book]} ${chapter}`, book, chapter, bookName: BOOK_NAMES[book],
    totalChapters: BOOK_CHAPTERS[book] || 1, translations: Plan.getTranslations(),
    bookNames: BOOK_NAMES, bookChapters: BOOK_CHAPTERS, pageScripts: ['bible-api.js', 'reader.js'],
  });
});

router.get('/settings', requireAuth, (req, res) => {
  res.render('settings', { title: 'Settings', translations: Plan.getTranslations(), messages: {} });
});

router.post('/settings', csrfGuard, requireAuth, a(async (req, res) => {
  const action = req.body.action || 'save_preferences';
  const uid = req.user.id;
  const messages = {};
  if (action === 'update_name') {
    const name = (req.body.name || '').trim();
    if (!name) messages.nameError = 'Name is required.';
    else { await Users.updateUser(uid, { name }); messages.nameSuccess = 'Name updated.'; }
  } else if (action === 'change_password') {
    const { current_password, new_password, confirm_password } = req.body;
    if (!(await Users.verifyPassword(uid, current_password))) messages.passwordError = 'Current password is incorrect.';
    else if (new_password !== confirm_password) messages.passwordError = 'New passwords do not match.';
    else if (new_password.length < 6) messages.passwordError = 'Password must be at least 6 characters.';
    else { await Users.updatePassword(uid, new_password); messages.passwordSuccess = 'Password changed.'; }
  } else if (action === 'change_email') {
    const email = (req.body.new_email || '').trim().toLowerCase();
    const existing = await Users.findByEmail(email);
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) messages.emailError = 'Please enter a valid email.';
    else if (email === req.user.email) messages.emailError = 'That is already your email.';
    else if (!(await Users.verifyPassword(uid, req.body.password))) messages.emailError = 'Incorrect password.';
    else if (existing && existing.id !== uid) messages.emailError = 'This email is already in use.';
    else if (Email.isConfigured()) {
      const data = await Users.createEmailVerificationToken(uid, email);
      if (!data) messages.emailError = 'This email is already in use.';
      else {
        const r = await Email.sendEmailVerification(email, req.user.name, `${baseUrl(req)}/verify-email?token=${data.token}`, res.locals.app.appName);
        messages[r.success ? 'emailSuccess' : 'emailError'] = r.success
          ? `Verification email sent to ${email}. Check your inbox to confirm the change.`
          : 'Could not send verification email. Please try again.';
      }
    } else {
      await Users.updateUser(uid, { email }); // dev fallback when email isn't configured
      messages.emailSuccess = 'Email updated.';
    }
  } else {
    const theme = ['light', 'dark', 'auto'].includes(req.body.theme) ? req.body.theme : 'auto';
    let secondary = req.body.secondary_translation || null;
    const primary = req.body.preferred_translation || 'eng_kjv';
    if (!secondary || secondary === primary) secondary = null;
    const fontSize = Math.min(28, Math.max(14, parseInt(req.body.reader_font_size, 10) || 18));
    const fontFamily = ['serif', 'sans'].includes(req.body.reader_font_family) ? req.body.reader_font_family : 'serif';
    const reminder = req.body.reminder_email ? 1 : 0;
    await Users.updateUser(uid, { preferred_translation: primary, secondary_translation: secondary, theme, reader_font_size: fontSize, reader_font_family: fontFamily, reminder_email: reminder });
    messages.prefsSuccess = 'Preferences saved.';
  }
  req.user = await Users.findById(uid);
  res.locals.currentUser = Users.safeUser(req.user);
  res.render('settings', { title: 'Settings', translations: Plan.getTranslations(), messages });
}));

router.post('/settings/reset-progress', csrfGuard, requireAuth, a(async (req, res) => {
  if (!(await Users.verifyPassword(req.user.id, req.body.password))) req.flash('error', 'Incorrect password.');
  else { await Progress.deleteAllProgress(req.user.id); req.flash('success', 'Your reading progress has been reset.'); }
  res.redirect('/settings');
}));

router.post('/settings/delete-account', csrfGuard, requireAuth, a(async (req, res) => {
  if (!(await Users.verifyPassword(req.user.id, req.body.password))) { req.flash('error', 'Incorrect password.'); return res.redirect('/settings'); }
  await Users.deleteUser(req.user.id);
  req.session.destroy(() => res.redirect('/'));
}));

export default router;

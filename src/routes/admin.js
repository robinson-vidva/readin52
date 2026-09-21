import express from 'express';
import { requireAdmin, csrfGuard } from '../auth.js';
import { run, setSetting } from '../db.js';
import * as Users from '../services/users.js';
import * as Progress from '../services/progress.js';
import * as Plan from '../services/readingPlan.js';
import * as ErrorLog from '../services/errorLog.js';

const router = express.Router();
const a = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);
router.use(requireAdmin);

router.get('/', a(async (req, res) => {
  res.render('admin/dashboard', {
    title: 'Admin', adminPage: 'dashboard',
    globalStats: await Progress.getGlobalStats(),
    userCount: await Users.countUsers(),
    users: (await Users.listUsers()).slice(0, 8),
  });
}));

router.get('/users', a(async (req, res) => {
  res.render('admin/users', { title: 'Manage Users', adminPage: 'users', users: await Users.listUsers(), translations: Plan.getTranslations() });
}));

router.post('/users', csrfGuard, a(async (req, res) => {
  const action = req.body.action;
  const userId = parseInt(req.body.user_id, 10);
  if (action === 'update' && userId) {
    await Users.updateUser(userId, {
      name: (req.body.name || '').trim(), email: (req.body.email || '').trim(),
      role: req.body.role === 'admin' ? 'admin' : 'user',
      preferred_translation: req.body.preferred_translation || 'eng_kjv',
    });
    if (req.body.new_password && req.body.new_password.length >= 6) await Users.updatePassword(userId, req.body.new_password);
    req.flash('success', 'User updated.');
  } else if (action === 'delete' && userId) {
    if (userId === req.user.id) req.flash('error', 'You cannot delete your own account.');
    else { await Users.deleteUser(userId); req.flash('success', 'User deleted.'); }
  }
  res.redirect('/admin/users');
}));

router.get('/reading-plan', (req, res) => {
  const selected = parseInt(req.query.week, 10) || 1;
  res.render('admin/reading-plan', {
    title: 'Reading Plan', adminPage: 'reading-plan',
    weeks: Plan.getWeeks(), categories: Plan.getCategories(),
    selectedWeek: selected, week: Plan.getWeek(selected), error: null, success: null,
  });
});

router.post('/reading-plan', csrfGuard, a(async (req, res) => {
  const week = parseInt(req.body.week, 10);
  const readings = req.body.readings || {};
  let error = null, success = null;
  if (week >= 1 && week <= 52) {
    try {
      for (const [catId, reading] of Object.entries(readings)) {
        const passages = JSON.parse(reading.passages || '[]');
        await Plan.updateReading(week, catId, reading.reference || '', passages);
      }
      success = `Week ${week} updated.`;
    } catch (e) { error = 'Invalid JSON in passages: ' + e.message; }
  }
  res.render('admin/reading-plan', {
    title: 'Reading Plan', adminPage: 'reading-plan', weeks: Plan.getWeeks(), categories: Plan.getCategories(),
    selectedWeek: week, week: Plan.getWeek(week), error, success,
  });
}));

router.get('/reading-plan/export', (req, res) => {
  res.setHeader('Content-Type', 'application/json');
  res.setHeader('Content-Disposition', `attachment; filename="reading-plan-${new Date().toISOString().slice(0, 10)}.json"`);
  res.send(Plan.exportPlan());
});

router.post('/reading-plan/import', csrfGuard, a(async (req, res) => {
  const result = await Plan.importPlan(req.body.json_data || '');
  req.flash(result.success ? 'success' : 'error', result.success ? 'Reading plan imported.' : result.error);
  res.redirect('/admin/reading-plan');
}));

router.get('/settings', (req, res) => {
  res.render('admin/settings', { title: 'App Settings', adminPage: 'settings', messages: {} });
});

router.post('/settings', csrfGuard, a(async (req, res) => {
  const action = req.body.action || 'general';
  const messages = {};
  if (action === 'clear_progress') {
    if (!(await Users.verifyPassword(req.user.id, req.body.confirm_password))) messages.error = 'Incorrect password. Action cancelled.';
    else { await run('DELETE FROM chapter_progress'); messages.success = 'All reading progress has been cleared for every user.'; }
  } else if (action === 'general') {
    await setSetting('app_name', (req.body.app_name || 'ReadIn52').trim());
    await setSetting('app_tagline', (req.body.app_tagline || '').trim());
    await setSetting('default_translation', req.body.default_translation || 'eng_kjv');
    await setSetting('registration_enabled', req.body.registration_enabled ? '1' : '0');
    await setSetting('parent_site_name', (req.body.parent_site_name || '').trim());
    await setSetting('parent_site_url', (req.body.parent_site_url || '').trim());
    await setSetting('admin_email', (req.body.admin_email || '').trim());
    await setSetting('github_repo_url', (req.body.github_repo_url || '').trim());
    messages.success = 'Settings saved.';
    res.locals.app = Plan.appConfig();
  }
  res.render('admin/settings', { title: 'App Settings', adminPage: 'settings', messages });
}));

router.get('/errors', a(async (req, res) => {
  res.render('admin/errors', {
    title: 'Error Log', adminPage: 'errors',
    errors: await ErrorLog.getRecent(100),
    total: await ErrorLog.count(),
    sentry: !!process.env.SENTRY_DSN,
  });
}));

router.post('/errors/clear', csrfGuard, a(async (req, res) => {
  await ErrorLog.clearAll();
  req.flash('success', 'Error log cleared.');
  res.redirect('/admin/errors');
}));

router.get('/user-progress', a(async (req, res) => {
  const list = await Users.listUsers();
  const users = [];
  for (const u of list) users.push({ ...u, stats: await Progress.getStats(u.id) });
  res.render('admin/user-progress', { title: 'User Progress', adminPage: 'user-progress', users });
}));

export default router;

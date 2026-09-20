import express from 'express';
import { get, all, run } from '../db.js';
import * as Auth from '../auth.js';
import * as Progress from '../services/progress.js';
import * as Badges from '../services/badges.js';
import * as Notes from '../services/notes.js';
import * as Plan from '../services/readingPlan.js';
import * as CrossRefs from '../services/crossRefs.js';
import { BOOK_NAMES } from '../data/books.js';

const router = express.Router();
const a = (fn) => (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);

router.use((req, res, next) => {
  if (!req.user) return res.status(401).json({ success: false, error: 'Not authenticated' });
  next();
});
router.use((req, res, next) => {
  if (['POST', 'PUT', 'DELETE'].includes(req.method) && !Auth.verifyCsrf(req)) {
    return res.status(403).json({ success: false, error: 'Invalid CSRF token' });
  }
  next();
});

// ---- Reading plan week ----
router.get('/week/:n', a(async (req, res) => {
  const n = parseInt(req.params.n, 10);
  const week = Plan.getWeekWithDetails(n);
  if (!week) return res.json({ success: false, error: 'Week not found' });
  const [progress, counts] = await Promise.all([
    Progress.getWeekChapterProgress(req.user.id, n),
    Progress.getWeekChapterCounts(req.user.id, n),
  ]);
  res.json({ success: true, week, progress, counts });
}));

// ---- Chapter progress ----
router.get('/chapter-progress', a(async (req, res) => {
  const week = parseInt(req.query.week, 10);
  if (!week) return res.json({ success: false, error: 'Week required' });
  const [progress, counts] = await Promise.all([
    Progress.getWeekChapterProgress(req.user.id, week),
    Progress.getWeekChapterCounts(req.user.id, week),
  ]);
  res.json({ success: true, progress, counts });
}));

router.post('/chapter-progress', a(async (req, res) => {
  const { week, category, book, chapter } = req.body;
  const w = parseInt(week, 10);
  const result = await Progress.toggleChapter(req.user.id, w, category, book, parseInt(chapter, 10));
  if (result.success) {
    result.newBadges = await Badges.checkAndAwardBadges(req.user.id);
    result.weekCounts = await Progress.getWeekChapterCounts(req.user.id, w);
    result.overallStats = await Progress.getChapterStats(req.user.id);
    result.categoryComplete = await Progress.isCategoryComplete(req.user.id, w, category);
  }
  res.json(result);
}));

// ---- Whole-category toggle ----
router.post('/progress', a(async (req, res) => {
  const week = parseInt(req.body.week, 10);
  const category = req.body.category;
  const result = await Progress.toggleCategory(req.user.id, week, category);
  if (result.success) {
    result.newBadges = await Badges.checkAndAwardBadges(req.user.id);
    result.overallStats = await Progress.getChapterStats(req.user.id);
    result.stats = await Progress.getStats(req.user.id);
  }
  res.json(result);
}));

// ---- Cross-references (study) ----
router.get('/cross-references', (req, res) => {
  const book = String(req.query.book || '').toUpperCase();
  const chapter = parseInt(req.query.chapter, 10);
  const verse = parseInt(req.query.verse, 10);
  if (!book || !chapter || !verse) return res.json({ success: false, error: 'book, chapter, verse required' });
  res.json({ success: true, refs: CrossRefs.getCrossRefs(book, chapter, verse) });
});

// Which verses in a chapter have cross-references (to mark them in the reader)
router.get('/cross-references/chapter', (req, res) => {
  const book = String(req.query.book || '').toUpperCase();
  const chapter = parseInt(req.query.chapter, 10);
  if (!book || !chapter) return res.json({ success: false, error: 'book, chapter required' });
  res.json({ success: true, verses: CrossRefs.versesWithRefs(book, chapter) });
});

// ---- Stats ----
router.get('/stats', a(async (req, res) => {
  res.json({ success: true, stats: await Progress.getStats(req.user.id), chapterStats: await Progress.getChapterStats(req.user.id) });
}));

// ---- Notes ----
router.get('/notes', a(async (req, res) => res.json({ success: true, notes: await Notes.getAllNotes(req.user.id) })));

router.get('/notes/export', a(async (req, res) => {
  const notes = await Notes.getAllNotes(req.user.id);
  if (req.query.format === 'json') {
    res.setHeader('Content-Disposition', `attachment; filename="readin52-notes-${today()}.json"`);
    return res.json(notes);
  }
  let md = `# ReadIn52 Notes\n\nExported ${new Date().toLocaleString()}\n\n`;
  for (const n of notes) {
    const ref = n.book ? `${BOOK_NAMES[n.book] || n.book}${n.chapter ? ' ' + n.chapter : ''}` : (n.week_number ? `Week ${n.week_number}` : '');
    md += `## ${n.title}\n`;
    if (ref) md += `*${ref}*\n\n`;
    md += `${n.content}\n\n---\n\n`;
  }
  res.setHeader('Content-Type', 'text/markdown; charset=utf-8');
  res.setHeader('Content-Disposition', `attachment; filename="readin52-notes-${today()}.md"`);
  res.send(md);
}));

router.get('/notes/chapter', a(async (req, res) => {
  const { book, chapter } = req.query;
  if (!book || !chapter) return res.json({ success: false, error: 'Book and chapter required' });
  res.json({ success: true, notes: await Notes.getNotesForChapter(req.user.id, book, parseInt(chapter, 10)) });
}));

router.get('/notes/:id', a(async (req, res) => {
  const note = await Notes.getNote(parseInt(req.params.id, 10), req.user.id);
  res.json(note ? { success: true, note } : { success: false, error: 'Note not found' });
}));

router.post('/notes', a(async (req, res) => {
  const id = req.body.note_id ? Number(req.body.note_id) : null;
  if (id) { await Notes.updateNote(id, req.user.id, req.body); return res.json({ success: true, message: 'Note updated', id }); }
  const newId = await Notes.createNote(req.user.id, req.body);
  res.json({ success: true, message: 'Note created', id: newId });
}));

router.delete('/notes/:id', a(async (req, res) => {
  res.json({ success: await Notes.deleteNote(parseInt(req.params.id, 10), req.user.id) });
}));

// ---- Highlights ----
router.get('/highlights', a(async (req, res) => {
  const { book, chapter } = req.query;
  const rows = await all('SELECT verse, color FROM highlights WHERE user_id=? AND book=? AND chapter=?', req.user.id, book, parseInt(chapter, 10));
  const map = {};
  for (const r of rows) map[r.verse] = r.color;
  res.json({ success: true, highlights: map });
}));

router.post('/highlights', a(async (req, res) => {
  const { book, chapter, verse, color } = req.body;
  const ch = parseInt(chapter, 10); const v = parseInt(verse, 10);
  const existing = await get('SELECT color FROM highlights WHERE user_id=? AND book=? AND chapter=? AND verse=?', req.user.id, book, ch, v);
  if (!color || (existing && existing.color === color)) {
    await run('DELETE FROM highlights WHERE user_id=? AND book=? AND chapter=? AND verse=?', req.user.id, book, ch, v);
    return res.json({ success: true, color: null });
  }
  await run(`INSERT INTO highlights (user_id, book, chapter, verse, color) VALUES (?, ?, ?, ?, ?)
    ON CONFLICT (user_id, book, chapter, verse) DO UPDATE SET color=excluded.color`, req.user.id, book, ch, v, color);
  res.json({ success: true, color });
}));

// ---- Bookmarks ----
router.get('/bookmarks', a(async (req, res) => {
  res.json({ success: true, bookmarks: await all('SELECT * FROM bookmarks WHERE user_id=? ORDER BY created_at DESC', req.user.id) });
}));

router.post('/bookmarks', a(async (req, res) => {
  const { book, chapter } = req.body;
  const ch = parseInt(chapter, 10);
  const existing = await get('SELECT id FROM bookmarks WHERE user_id=? AND book=? AND chapter=?', req.user.id, book, ch);
  if (existing) { await run('DELETE FROM bookmarks WHERE id=?', existing.id); return res.json({ success: true, bookmarked: false }); }
  await run('INSERT INTO bookmarks (user_id, book, chapter, label) VALUES (?, ?, ?, ?)', req.user.id, book, ch, `${BOOK_NAMES[book] || book} ${ch}`);
  res.json({ success: true, bookmarked: true });
}));

function today() { return new Date().toISOString().slice(0, 10); }

export default router;

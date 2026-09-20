import { all, run, getSetting } from '../db.js';
import { BOOK_NAMES, BOOK_CHAPTERS } from '../data/books.js';

export const CATEGORY_IDS = ['poetry', 'history', 'prophecy', 'gospels'];

// In-memory cache of the (mostly static) reading plan so lookups stay synchronous.
let _categories = [];
let _translations = [];
let _weeks = {};          // week_number -> { week, readings }
let _totalChapters = 0;
let _bookTotals = {};

export async function loadPlan() {
  _categories = await all('SELECT * FROM reading_categories ORDER BY sort_order ASC');
  _translations = await all('SELECT * FROM bible_translations ORDER BY language, name');
  const rows = await all('SELECT week_number, category_id, reference, passages FROM reading_plan');
  _weeks = {};
  for (const r of rows) {
    if (!_weeks[r.week_number]) _weeks[r.week_number] = { week: r.week_number, readings: {} };
    _weeks[r.week_number].readings[r.category_id] = { reference: r.reference, passages: JSON.parse(r.passages) };
  }
  recomputeTotals();
}

function recomputeTotals() {
  _totalChapters = 0;
  _bookTotals = {};
  for (const w of getWeeks()) {
    for (const cat of CATEGORY_IDS) {
      const r = w.readings[cat];
      if (!r) continue;
      for (const p of r.passages) {
        _totalChapters += p.chapters.length;
        _bookTotals[p.book] = (_bookTotals[p.book] || 0) + p.chapters.length;
      }
    }
  }
}

export function getCategories() { return _categories; }
export function getCategoryMap() { const m = {}; for (const c of _categories) m[c.id] = c; return m; }
export function getTranslations() { return _translations; }
export function getWeek(n) { return _weeks[n] || null; }
export function getWeeks() { return Object.values(_weeks).sort((a, b) => a.week - b.week); }
export function getTotalChaptersInPlan() { return _totalChapters; }
export function getBookChapterTotalsInPlan() { return _bookTotals; }

export function countWeekChapters(week) {
  let total = 0;
  for (const cat of CATEGORY_IDS) {
    const r = week.readings[cat];
    if (r) for (const p of r.passages) total += p.chapters.length;
  }
  return total;
}

export function getWeekWithDetails(weekNumber) {
  const week = getWeek(weekNumber);
  if (!week) return null;
  const out = { week: week.week, readings: {} };
  for (const cat of CATEGORY_IDS) {
    const reading = week.readings[cat];
    if (!reading) continue;
    out.readings[cat] = {
      reference: reading.reference,
      passages: reading.passages.map((p) => ({
        book: p.book, bookName: BOOK_NAMES[p.book] || p.book,
        chapters: p.chapters, totalChapters: BOOK_CHAPTERS[p.book] || 1,
      })),
    };
  }
  return out;
}

export async function updateReading(week, categoryId, reference, passages) {
  await run(`INSERT INTO reading_plan (week_number, category_id, reference, passages) VALUES (?, ?, ?, ?)
     ON CONFLICT (week_number, category_id) DO UPDATE SET reference = excluded.reference, passages = excluded.passages`,
    week, categoryId, reference, JSON.stringify(passages));
  await loadPlan();
  return true;
}

export function exportPlan() {
  return JSON.stringify({
    appName: getSetting('app_name', 'ReadIn52'),
    appTagline: getSetting('app_tagline', ''),
    defaultTranslation: getSetting('default_translation', 'eng_kjv'),
    availableTranslations: getTranslations(),
    categories: getCategories().map((c) => ({ id: c.id, name: c.name, color: c.color })),
    weeks: getWeeks().map((w) => ({ week: w.week, readings: w.readings })),
  }, null, 2);
}

export async function importPlan(json) {
  let data;
  try { data = JSON.parse(json); } catch { return { success: false, error: 'Invalid JSON file.' }; }
  if (!data.weeks || !Array.isArray(data.weeks)) return { success: false, error: 'Missing "weeks" array.' };
  try {
    for (const week of data.weeks) {
      for (const [catId, reading] of Object.entries(week.readings)) {
        await run(`INSERT INTO reading_plan (week_number, category_id, reference, passages) VALUES (?, ?, ?, ?)
          ON CONFLICT (week_number, category_id) DO UPDATE SET reference = excluded.reference, passages = excluded.passages`,
          week.week, catId, reading.reference, JSON.stringify(reading.passages));
      }
    }
    await loadPlan();
  } catch (e) { return { success: false, error: e.message }; }
  return { success: true };
}

export function appConfig() {
  return {
    appName: getSetting('app_name', 'ReadIn52'),
    appTagline: getSetting('app_tagline', 'Journey Through Scripture in 52 Weeks'),
    defaultTranslation: getSetting('default_translation', 'eng_kjv'),
    registrationEnabled: getSetting('registration_enabled', '1') === '1',
    githubUrl: getSetting('github_repo_url', ''),
    adminEmail: getSetting('admin_email', ''),
    parentSiteName: getSetting('parent_site_name', ''),
    parentSiteUrl: getSetting('parent_site_url', ''),
  };
}

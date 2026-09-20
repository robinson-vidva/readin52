import { get, all, run, batch, NOW } from '../db.js';
import {
  CATEGORY_IDS, getWeek, getWeeks, countWeekChapters,
  getTotalChaptersInPlan, getBookChapterTotalsInPlan,
} from './readingPlan.js';

const TOTAL_READINGS = 208; // 52 weeks * 4 categories

function nowIso() { return new Date().toISOString().slice(0, 19).replace('T', ' '); }

// ---- Chapter-level progress ----
export async function getWeekChapterProgress(userId, weekNumber) {
  const rows = await all(`SELECT category, book, chapter, completed, completed_at
    FROM chapter_progress WHERE user_id = ? AND week_number = ?`, userId, weekNumber);
  const progress = {};
  for (const cat of CATEGORY_IDS) progress[cat] = {};
  for (const r of rows) {
    progress[r.category][`${r.book}_${r.chapter}`] = {
      book: r.book, chapter: r.chapter, completed: !!r.completed, completed_at: r.completed_at,
    };
  }
  return progress;
}

export async function setChapter(userId, weekNumber, category, book, chapter, completed) {
  if (weekNumber < 1 || weekNumber > 52 || !CATEGORY_IDS.includes(category)) return { success: false, error: 'Invalid parameters' };
  await run(`INSERT INTO chapter_progress (user_id, week_number, category, book, chapter, completed, completed_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON CONFLICT (user_id, week_number, category, book, chapter)
     DO UPDATE SET completed = excluded.completed, completed_at = excluded.completed_at`,
    userId, weekNumber, category, book, chapter, completed ? 1 : 0, completed ? nowIso() : null);
  return { success: true, completed: !!completed };
}

export async function toggleChapter(userId, weekNumber, category, book, chapter) {
  const cur = await get(`SELECT completed FROM chapter_progress
    WHERE user_id=? AND week_number=? AND category=? AND book=? AND chapter=?`,
    userId, weekNumber, category, book, chapter);
  const next = !(cur && cur.completed);
  return setChapter(userId, weekNumber, category, book, chapter, next);
}

export async function setCategory(userId, weekNumber, category, completed) {
  const week = getWeek(weekNumber);
  if (!week || !week.readings[category]) return { success: false, error: 'Invalid week/category' };
  const stmts = [];
  for (const p of week.readings[category].passages) {
    for (const ch of p.chapters) {
      stmts.push({
        sql: `INSERT INTO chapter_progress (user_id, week_number, category, book, chapter, completed, completed_at)
              VALUES (?, ?, ?, ?, ?, ?, ?)
              ON CONFLICT (user_id, week_number, category, book, chapter)
              DO UPDATE SET completed = excluded.completed, completed_at = excluded.completed_at`,
        args: [userId, weekNumber, category, p.book, ch, completed ? 1 : 0, completed ? nowIso() : null],
      });
    }
  }
  await batch(stmts);
  return { success: true, completed };
}

export async function toggleCategory(userId, weekNumber, category) {
  const complete = await isCategoryComplete(userId, weekNumber, category);
  return setCategory(userId, weekNumber, category, !complete);
}

export async function isCategoryComplete(userId, weekNumber, category) {
  const week = getWeek(weekNumber);
  if (!week || !week.readings[category]) return false;
  let total = 0;
  for (const p of week.readings[category].passages) total += p.chapters.length;
  if (total === 0) return false;
  const row = await get(`SELECT COUNT(*)::int AS c FROM chapter_progress
    WHERE user_id=? AND week_number=? AND category=? AND completed=1`, userId, weekNumber, category);
  return row.c >= total;
}

export async function getWeekChapterCounts(userId, weekNumber) {
  const week = getWeek(weekNumber);
  if (!week) return { total: 0, completed: 0 };
  const total = countWeekChapters(week);
  const row = await get(`SELECT COUNT(*)::int AS c FROM chapter_progress WHERE user_id=? AND week_number=? AND completed=1`, userId, weekNumber);
  return { total, completed: row.c };
}

export async function getChapterStats(userId) {
  const total = getTotalChaptersInPlan();
  const row = await get('SELECT COUNT(*)::int AS c FROM chapter_progress WHERE user_id=? AND completed=1', userId);
  const completed = row.c;
  return { total_chapters: total, completed_chapters: completed, percentage: total ? Math.round((completed / total) * 1000) / 10 : 0 };
}

// Category completion matrix in one query.
async function completionMatrix(userId) {
  const rows = await all(`SELECT week_number, category, COUNT(*)::int AS done
    FROM chapter_progress WHERE user_id=? AND completed=1 GROUP BY week_number, category`, userId);
  const doneMap = {};
  for (const r of rows) doneMap[`${r.week_number}:${r.category}`] = r.done;
  const matrix = {};
  for (const week of getWeeks()) {
    matrix[week.week] = {};
    for (const cat of CATEGORY_IDS) {
      const reading = week.readings[cat];
      if (!reading) { matrix[week.week][cat] = false; continue; }
      const need = reading.passages.reduce((s, p) => s + p.chapters.length, 0);
      matrix[week.week][cat] = need > 0 && (doneMap[`${week.week}:${cat}`] || 0) >= need;
    }
  }
  return matrix;
}

export async function getStats(userId) {
  const matrix = await completionMatrix(userId);
  const byCategory = Object.fromEntries(CATEGORY_IDS.map((c) => [c, 0]));
  let totalCompleted = 0;
  const completeWeeks = [];
  for (let w = 1; w <= 52; w++) {
    let all4 = true;
    for (const cat of CATEGORY_IDS) {
      if (matrix[w] && matrix[w][cat]) { totalCompleted++; byCategory[cat]++; } else { all4 = false; }
    }
    if (all4) completeWeeks.push(w);
  }
  let streak = 0;
  if (completeWeeks.length) {
    const sorted = [...completeWeeks].sort((a, b) => b - a);
    let expected = sorted[0];
    for (const w of sorted) { if (w === expected) { streak++; expected--; } else break; }
  }
  return {
    total_completed: totalCompleted,
    total_readings: TOTAL_READINGS,
    percentage: Math.round((totalCompleted / TOTAL_READINGS) * 1000) / 10,
    by_category: byCategory,
    streak,
    complete_weeks: completeWeeks.length,
    day_streak: await calculateDayStreak(userId),
    current_week: await getCurrentWeek(userId),
  };
}

export async function getCurrentWeek(userId) {
  const rows = await all(`SELECT week_number, COUNT(*)::int AS done FROM chapter_progress
    WHERE user_id=? AND completed=1 GROUP BY week_number`, userId);
  const doneByWeek = {};
  for (const r of rows) doneByWeek[r.week_number] = r.done;
  for (let w = 1; w <= 52; w++) {
    const week = getWeek(w);
    if (!week) continue;
    if ((doneByWeek[w] || 0) < countWeekChapters(week)) return w;
  }
  return 52;
}

export async function getWeeklyCompletion(userId) {
  const matrix = await completionMatrix(userId);
  const chapterRows = await all(`SELECT week_number, COUNT(*)::int AS done FROM chapter_progress
    WHERE user_id=? AND completed=1 GROUP BY week_number`, userId);
  const doneByWeek = {};
  for (const r of chapterRows) doneByWeek[r.week_number] = r.done;
  const out = [];
  for (const week of getWeeks()) {
    const catsDone = {};
    CATEGORY_IDS.forEach((c) => { catsDone[c] = !!(matrix[week.week] && matrix[week.week][c]); });
    const cats = CATEGORY_IDS.filter((c) => catsDone[c]).length;
    out.push({ week: week.week, categories: cats, categoriesDone: catsDone, chaptersTotal: countWeekChapters(week), chaptersDone: doneByWeek[week.week] || 0 });
  }
  return out;
}

export async function getReadingDates(userId) {
  const rows = await all(`SELECT DISTINCT substr(completed_at,1,10) AS d FROM chapter_progress
    WHERE user_id=? AND completed=1 AND completed_at IS NOT NULL ORDER BY d DESC`, userId);
  return rows.map((r) => r.d);
}

export async function calculateDayStreak(userId) {
  const dates = await getReadingDates(userId);
  if (!dates.length) return 0;
  const today = new Date().toISOString().slice(0, 10);
  const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
  if (dates[0] !== today && dates[0] !== yesterday) return 0;
  let streak = 0;
  let expected = dates[0];
  for (const d of dates) {
    if (d === expected) { streak++; expected = new Date(new Date(expected).getTime() - 86400000).toISOString().slice(0, 10); }
    else break;
  }
  return streak;
}

export async function getBookCompletionStats(userId) {
  const totals = getBookChapterTotalsInPlan();
  const rows = await all(`SELECT book, COUNT(DISTINCT chapter)::int AS done FROM chapter_progress
    WHERE user_id=? AND completed=1 GROUP BY book`, userId);
  const done = {};
  for (const r of rows) done[r.book] = r.done;
  const stats = {};
  for (const [book, total] of Object.entries(totals)) {
    const d = done[book] || 0;
    stats[book] = { completed: d, total, percentage: total ? Math.round((d / total) * 1000) / 10 : 0, isComplete: d >= total };
  }
  return stats;
}

export async function isBookComplete(userId, book) {
  const totals = getBookChapterTotalsInPlan();
  const total = totals[book] || 0;
  if (!total) return false;
  const row = await get(`SELECT COUNT(DISTINCT chapter)::int AS c FROM chapter_progress WHERE user_id=? AND book=? AND completed=1`, userId, book);
  return row.c >= total;
}

export async function deleteAllProgress(userId) {
  await run('DELETE FROM chapter_progress WHERE user_id=?', userId);
  return true;
}

export async function getGlobalStats() {
  const users = await all('SELECT id FROM users');
  let activeReaders = 0, completedPlan = 0, totalCompleted = 0;
  for (const u of users) {
    const s = await getStats(u.id);
    if (s.total_completed > 0) activeReaders++;
    if (s.total_completed >= TOTAL_READINGS) completedPlan++;
    totalCompleted += s.total_completed;
  }
  return { total_completed: totalCompleted, active_readers: activeReaders, completed_plan: completedPlan };
}

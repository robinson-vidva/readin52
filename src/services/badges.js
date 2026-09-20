import { get, all, run } from '../db.js';
import { getStats, getChapterStats, isBookComplete, isCategoryComplete } from './progress.js';

export async function getAllBadges() {
  const rows = await all('SELECT * FROM badges ORDER BY sort_order ASC');
  return rows.map((b) => ({ ...b, criteria: JSON.parse(b.criteria) }));
}

export async function getUserBadges(userId) {
  const rows = await all(`SELECT b.*, ub.earned_at FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = ? ORDER BY ub.earned_at DESC`, userId);
  return rows.map((b) => ({ ...b, criteria: JSON.parse(b.criteria) }));
}

export async function getUserBadgeCount(userId) {
  return (await get('SELECT COUNT(*)::int AS c FROM user_badges WHERE user_id=?', userId)).c;
}

async function userHasBadge(userId, badgeId) {
  return !!(await get('SELECT 1 AS x FROM user_badges WHERE user_id=? AND badge_id=?', userId, badgeId));
}

async function award(userId, badgeId) {
  if (await userHasBadge(userId, badgeId)) return false;
  try { await run('INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)', userId, badgeId); return true; }
  catch { return false; }
}

async function categoryFullyComplete(userId, category) {
  for (let w = 1; w <= 52; w++) if (!(await isCategoryComplete(userId, w, category))) return false;
  return true;
}

async function meetsCriteria(userId, criteria, stats) {
  switch (criteria.type) {
    case 'book': return isBookComplete(userId, criteria.book);
    case 'category': return categoryFullyComplete(userId, criteria.category);
    case 'readings': return stats.total_completed >= criteria.count;
    case 'week_complete': return stats.complete_weeks >= criteria.count;
    case 'consecutive_weeks': return stats.streak >= criteria.count;
    case 'percentage': return stats.percentage >= criteria.value;
    case 'streak_days': return stats.day_streak >= criteria.count;
    default: return false;
  }
}

export async function checkAndAwardBadges(userId) {
  const stats = await getStats(userId);
  const badges = await getAllBadges();
  const earned = new Set((await getUserBadges(userId)).map((b) => b.id));
  const awarded = [];
  for (const badge of badges) {
    if (earned.has(badge.id)) continue;
    if (await meetsCriteria(userId, badge.criteria, stats) && await award(userId, badge.id)) {
      awarded.push({ id: badge.id, name: badge.name, icon: badge.icon, description: badge.description });
    }
  }
  return awarded;
}

export async function getBadgesWithProgress(userId) {
  const stats = await getStats(userId);
  const chapterStats = await getChapterStats(userId);
  const earnedAt = new Map((await getUserBadges(userId)).map((b) => [b.id, b.earned_at]));
  const badges = await getAllBadges();
  return badges.map((b) => {
    const isEarned = earnedAt.has(b.id);
    const progress = isEarned ? 1 : progressHint(b.criteria, stats, chapterStats);
    return { ...b, earned: isEarned, earned_at: earnedAt.get(b.id) || null, progress: Math.max(0, Math.min(1, progress)) };
  });
}

function progressHint(criteria, stats, chapterStats) {
  switch (criteria.type) {
    case 'readings': return stats.total_completed / criteria.count;
    case 'week_complete': return stats.complete_weeks / criteria.count;
    case 'consecutive_weeks': return stats.streak / criteria.count;
    case 'percentage': return stats.percentage / criteria.value;
    case 'streak_days': return stats.day_streak / criteria.count;
    default: return chapterStats.percentage / 100;
  }
}

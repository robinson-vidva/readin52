// Achievement badge definitions. criteria drives automatic awarding (see services/badges.js).
export const BADGES = [
  // Book completion
  { id: 'genesis_journey', name: 'Genesis Journey', description: 'Complete all Genesis chapters', icon: '📖', category: 'book', criteria: { type: 'book', book: 'GEN' }, sort_order: 1 },
  { id: 'exodus_explorer', name: 'Exodus Explorer', description: 'Complete all Exodus chapters', icon: '🏔️', category: 'book', criteria: { type: 'book', book: 'EXO' }, sort_order: 2 },
  { id: 'psalms_singer', name: 'Psalms Singer', description: 'Complete all Psalms chapters', icon: '🎵', category: 'book', criteria: { type: 'book', book: 'PSA' }, sort_order: 3 },
  { id: 'proverbs_wise', name: 'Wisdom Seeker', description: 'Complete all Proverbs chapters', icon: '🦉', category: 'book', criteria: { type: 'book', book: 'PRO' }, sort_order: 4 },
  { id: 'isaiah_prophet', name: "Prophet's Voice", description: 'Complete all Isaiah chapters', icon: '📜', category: 'book', criteria: { type: 'book', book: 'ISA' }, sort_order: 5 },
  { id: 'matthew_disciple', name: "Matthew's Path", description: 'Complete all Matthew chapters', icon: '✝️', category: 'book', criteria: { type: 'book', book: 'MAT' }, sort_order: 6 },
  { id: 'john_beloved', name: 'Beloved Disciple', description: 'Complete all John chapters', icon: '❤️', category: 'book', criteria: { type: 'book', book: 'JHN' }, sort_order: 7 },
  { id: 'romans_theologian', name: 'Romans Scholar', description: 'Complete all Romans chapters', icon: '⚖️', category: 'book', criteria: { type: 'book', book: 'ROM' }, sort_order: 8 },
  { id: 'revelation_seer', name: 'Revelation Seer', description: 'Complete all Revelation chapters', icon: '👁️', category: 'book', criteria: { type: 'book', book: 'REV' }, sort_order: 9 },

  // Category completion
  { id: 'poetry_master', name: 'Poetry Master', description: 'Complete all Psalms & Wisdom readings', icon: '📚', category: 'book', criteria: { type: 'category', category: 'poetry' }, sort_order: 10 },
  { id: 'history_scholar', name: 'History Scholar', description: 'Complete all Law & History readings', icon: '🏛️', category: 'book', criteria: { type: 'category', category: 'history' }, sort_order: 11 },
  { id: 'prophecy_student', name: 'Prophecy Student', description: 'Complete all Prophetic readings', icon: '🔮', category: 'book', criteria: { type: 'category', category: 'prophecy' }, sort_order: 12 },
  { id: 'gospel_bearer', name: 'Gospel Bearer', description: 'Complete all Gospel & Letters readings', icon: '✨', category: 'book', criteria: { type: 'category', category: 'gospels' }, sort_order: 13 },

  // Engagement
  { id: 'first_steps', name: 'First Steps', description: 'Complete your first reading', icon: '👣', category: 'engagement', criteria: { type: 'readings', count: 1 }, sort_order: 20 },
  { id: 'getting_started', name: 'Getting Started', description: 'Complete 10 readings', icon: '🌱', category: 'engagement', criteria: { type: 'readings', count: 10 }, sort_order: 21 },
  { id: 'dedicated_reader', name: 'Dedicated Reader', description: 'Complete 50 readings', icon: '📖', category: 'engagement', criteria: { type: 'readings', count: 50 }, sort_order: 22 },
  { id: 'faithful_student', name: 'Faithful Student', description: 'Complete 100 readings', icon: '🎓', category: 'engagement', criteria: { type: 'readings', count: 100 }, sort_order: 23 },
  { id: 'bible_scholar', name: 'Bible Scholar', description: 'Complete all 208 readings', icon: '🏆', category: 'engagement', criteria: { type: 'readings', count: 208 }, sort_order: 24 },

  // Weekly
  { id: 'week_warrior', name: 'Week Warrior', description: 'Complete all 4 readings in one week', icon: '⚔️', category: 'engagement', criteria: { type: 'week_complete', count: 1 }, sort_order: 30 },
  { id: 'month_of_faith', name: 'Month of Faith', description: 'Complete 4 consecutive weeks', icon: '📅', category: 'engagement', criteria: { type: 'consecutive_weeks', count: 4 }, sort_order: 31 },
  { id: 'quarter_champion', name: 'Quarter Champion', description: 'Complete 13 consecutive weeks', icon: '🏅', category: 'engagement', criteria: { type: 'consecutive_weeks', count: 13 }, sort_order: 32 },

  // Milestones
  { id: 'halfway_there', name: 'Halfway There', description: 'Reach 50% completion', icon: '🎯', category: 'milestone', criteria: { type: 'percentage', value: 50 }, sort_order: 40 },
  { id: 'almost_done', name: 'Almost Done', description: 'Reach 90% completion', icon: '🚀', category: 'milestone', criteria: { type: 'percentage', value: 90 }, sort_order: 41 },
  { id: 'finisher', name: 'Finisher', description: 'Complete the entire Bible in 52 weeks', icon: '👑', category: 'milestone', criteria: { type: 'percentage', value: 100 }, sort_order: 42 },

  // Streaks
  { id: 'on_fire', name: 'On Fire', description: '7-day reading streak', icon: '🔥', category: 'streak', criteria: { type: 'streak_days', count: 7 }, sort_order: 50 },
  { id: 'consistent', name: 'Consistent', description: '30-day reading streak', icon: '💪', category: 'streak', criteria: { type: 'streak_days', count: 30 }, sort_order: 51 },
  { id: 'devoted', name: 'Devoted', description: '100-day reading streak', icon: '🌟', category: 'streak', criteria: { type: 'streak_days', count: 100 }, sort_order: 52 },
];

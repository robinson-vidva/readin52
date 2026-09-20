import { get, all, run, insert, NOW } from '../db.js';

const NULLABLE = ['week_number', 'category', 'book', 'chapter'];

function clean(data) {
  const out = {
    title: (data.title || '').trim() || 'Untitled Note',
    content: (data.content || '').trim(),
    color: data.color || 'default',
  };
  for (const f of NULLABLE) {
    const v = data[f];
    out[f] = (v === '' || v === undefined || v === null) ? null
      : (f === 'week_number' || f === 'chapter') ? parseInt(v, 10) : v;
  }
  return out;
}

export async function createNote(userId, data) {
  const n = clean(data);
  return await insert(`INSERT INTO notes (user_id, title, content, color, week_number, category, book, chapter)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id`,
    userId, n.title, n.content, n.color, n.week_number, n.category, n.book, n.chapter);
}

export async function updateNote(id, userId, data) {
  const n = clean(data);
  const info = await run(`UPDATE notes SET title=?, content=?, color=?, week_number=?, category=?, book=?, chapter=?,
    updated_at=${NOW} WHERE id=? AND user_id=?`,
    n.title, n.content, n.color, n.week_number, n.category, n.book, n.chapter, id, userId);
  return info.changes > 0;
}

export async function deleteNote(id, userId) {
  return (await run('DELETE FROM notes WHERE id=? AND user_id=?', id, userId)).changes > 0;
}

export async function getNote(id, userId) {
  return await get('SELECT * FROM notes WHERE id=? AND user_id=?', id, userId);
}

export async function getAllNotes(userId) {
  return await all('SELECT * FROM notes WHERE user_id=? ORDER BY updated_at DESC', userId);
}

export async function getNotesForChapter(userId, book, chapter) {
  return await all('SELECT * FROM notes WHERE user_id=? AND book=? AND chapter=? ORDER BY updated_at DESC', userId, book, chapter);
}

export async function countNotes(userId) {
  return (await get('SELECT COUNT(*)::int AS c FROM notes WHERE user_id=?', userId)).c;
}

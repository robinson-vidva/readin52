// Full-text Scripture search over a bundled KJV index (public domain).
// Loaded lazily on first search and kept in memory for the process lifetime.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BOOK_NAMES, BOOK_ORDER } from '../data/books.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ORDER = {};
BOOK_ORDER.forEach((c, i) => { ORDER[c] = i; });

let DATA = null;
function load() {
  if (DATA) return DATA;
  const raw = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'data', 'kjv-index.json'), 'utf-8'));
  const verses = raw.verses; // [ [book, chapter, verse, text], ... ]
  DATA = {
    shortName: raw.shortName || 'KJV',
    verses,
    lower: verses.map((v) => v[3].toLowerCase()),
    canon: verses.map((v) => (ORDER[v[0]] ?? 99) * 1e6 + v[1] * 1e3 + v[2]),
  };
  return DATA;
}

export function search(query, { limit = 60 } = {}) {
  const q = String(query || '').trim();
  if (q.length < 2) return { query: q, total: 0, results: [], translation: 'KJV' };
  const d = load();
  const phrase = q.toLowerCase().replace(/["']/g, '').trim();
  const words = phrase.split(/\s+/).filter(Boolean);
  const matches = [];
  for (let i = 0; i < d.verses.length; i++) {
    const t = d.lower[i];
    let score = 0;
    if (t.includes(phrase)) score = 3;                 // contiguous phrase / substring
    else if (words.length > 1 && words.every((w) => t.includes(w))) score = 1; // all words, any order
    else continue;
    matches.push({ i, score, canon: d.canon[i] });
  }
  matches.sort((a, b) => b.score - a.score || a.canon - b.canon);
  const total = matches.length;
  const results = matches.slice(0, limit).map((m) => {
    const [b, c, v, text] = d.verses[m.i];
    return { book: b, bookName: BOOK_NAMES[b] || b, chapter: c, verse: v, text };
  });
  return { query: q, total, results, translation: d.shortName, words };
}

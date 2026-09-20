// Cross-reference lookup, backed by the OpenBible.info cross-reference dataset
// (CC-BY). Loaded once per process; lookups are O(1) by verse.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BOOK_NAMES } from '../data/books.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

let DATA = null;
function load() {
  if (DATA) return DATA;
  try {
    DATA = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'data', 'cross-references.json'), 'utf-8'));
  } catch {
    DATA = {};
  }
  return DATA;
}

// Returns related passages for a verse: [{ book, bookName, chapter, verse, verseEnd, ref }]
export function getCrossRefs(book, chapter, verse) {
  const data = load();
  const key = `${book}.${chapter}.${verse}`;
  const rows = data[key];
  if (!rows) return [];
  return rows.map(([b, c, v, ve]) => ({
    book: b,
    bookName: BOOK_NAMES[b] || b,
    chapter: c,
    verse: v,
    verseEnd: ve,
    ref: `${BOOK_NAMES[b] || b} ${c}:${v}${ve && ve !== v ? '-' + ve : ''}`,
  }));
}

// Which verses in a chapter have cross-references (so the reader can mark them).
export function versesWithRefs(book, chapter) {
  const data = load();
  const prefix = `${book}.${chapter}.`;
  const out = [];
  for (const key in data) {
    if (key.startsWith(prefix)) out.push(parseInt(key.slice(prefix.length), 10));
  }
  return out;
}

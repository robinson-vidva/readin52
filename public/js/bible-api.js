/* ReadIn52 Bible API client — fetches from HelloAO with LRU localStorage cache */
const BibleAPI = (function () {
  const BASE_URL = 'https://bible.helloao.org/api';
  const CACHE_KEY = 'readin52_bible_cache';
  const MAX_CACHE = 60;

  const BOOK_NAMES = Object.fromEntries(Object.entries(window.READIN52_BOOKS || {}).map(([k, v]) => [k, v[0]]));
  const BOOK_CHAPTERS = Object.fromEntries(Object.entries(window.READIN52_BOOKS || {}).map(([k, v]) => [k, v[1]]));

  function getCache() { try { return JSON.parse(localStorage.getItem(CACHE_KEY)) || { entries: [], data: {} }; } catch { return { entries: [], data: {} }; } }
  function saveCache(c) {
    try { localStorage.setItem(CACHE_KEY, JSON.stringify(c)); }
    catch {
      c.entries = c.entries.slice(-Math.floor(MAX_CACHE / 2));
      for (const k in c.data) if (!c.entries.includes(k)) delete c.data[k];
      try { localStorage.setItem(CACHE_KEY, JSON.stringify(c)); } catch {}
    }
  }
  function getCached(key) {
    const c = getCache();
    if (c.data[key]) { c.entries = c.entries.filter((k) => k !== key); c.entries.push(key); saveCache(c); return c.data[key]; }
    return null;
  }
  function cacheChapter(key, data) {
    const c = getCache();
    while (c.entries.length >= MAX_CACHE) delete c.data[c.entries.shift()];
    c.entries.push(key); c.data[key] = data; saveCache(c);
  }

  async function getChapter(translation, book, chapter) {
    const key = `${translation}:${book}:${chapter}`;
    const cached = getCached(key);
    if (cached) return cached;
    try {
      const res = await fetch(`${BASE_URL}/${translation}/${book}/${chapter}.json`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const api = await res.json();
      const verses = [];
      if (api.chapter && api.chapter.content) {
        for (const item of api.chapter.content) {
          if (item.type === 'verse' && item.content) {
            let text = '';
            for (const c of item.content) text += (typeof c === 'string') ? c : (c.text || '');
            verses.push({ verse: item.number, text: text.trim() });
          }
        }
      }
      const result = { translation, book, bookName: BOOK_NAMES[book] || book, chapter, totalChapters: BOOK_CHAPTERS[book] || 1, verses };
      cacheChapter(key, result);
      return result;
    } catch (e) {
      return { error: true, message: e.message };
    }
  }

  // ---- Commentaries (per-verse, cached) ----
  const CM_KEY = 'readin52_commentary_cache';
  function cmGet() { try { return JSON.parse(localStorage.getItem(CM_KEY)) || { entries: [], data: {} }; } catch { return { entries: [], data: {} }; } }
  function cmSave(c) { try { localStorage.setItem(CM_KEY, JSON.stringify(c)); } catch { c.entries = c.entries.slice(-15); for (const k in c.data) if (!c.entries.includes(k)) delete c.data[k]; try { localStorage.setItem(CM_KEY, JSON.stringify(c)); } catch {} } }

  async function getCommentary(id, book, chapter) {
    const key = `${id}:${book}:${chapter}`;
    const c = cmGet();
    if (c.data[key]) return c.data[key];
    try {
      const res = await fetch(`${BASE_URL}/c/${id}/${book}/${chapter}.json`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const api = await res.json();
      const blocks = []; // { number, text } — number = first verse a block covers
      const items = (api.chapter && api.chapter.content) || [];
      for (const item of items) {
        if (item.type === 'verse' && item.number) {
          let text = '';
          for (const p of (item.content || [])) text += (typeof p === 'string' ? p : (p && p.text ? p.text : '')) + ' ';
          if (text.trim()) blocks.push({ number: item.number, text: text.trim() });
        }
      }
      blocks.sort((a, b) => a.number - b.number);
      const result = { blocks };
      while (c.entries.length >= 15) delete c.data[c.entries.shift()];
      c.entries.push(key); c.data[key] = result; cmSave(c);
      return result;
    } catch (e) { return { error: true, message: e.message }; }
  }

  return { getChapter, getCommentary, getBookName: (b) => BOOK_NAMES[b] || b, getTotalChapters: (b) => BOOK_CHAPTERS[b] || 1, BOOK_NAMES, BOOK_CHAPTERS };
})();
window.BibleAPI = BibleAPI;

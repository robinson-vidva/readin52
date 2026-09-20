/* ReadIn52 reader — chapter loading, dual translation, highlights, notes, bookmarks */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const el = $('#reader');
  if (!el) return;
  const U = window.READIN52.user || {};
  const BOOKS = window.READIN52_BOOKS;

  const state = {
    book: el.dataset.book,
    chapter: parseInt(el.dataset.chapter, 10),
    total: parseInt(el.dataset.total, 10),
    translation: localStorage.getItem('r52-tr') || U.translation || 'eng_kjv',
    secondary: U.secondary || '',
    dual: localStorage.getItem('r52-dual') === '1',
    fontSize: parseInt(localStorage.getItem('r52-fs') || U.fontSize || 18, 10),
    fontFamily: localStorage.getItem('r52-ff') || U.fontFamily || 'serif',
  };

  const content = $('#readerContent');
  const trSel = $('#rTranslation');
  const secSel = $('#rSecondary');
  const bookBtn = $('#rBookBtn');

  trSel.value = state.translation;
  if (state.secondary) secSel.value = state.secondary;
  if (state.dual && state.secondary) { secSel.hidden = false; }

  applyType();

  function applyType() {
    content.style.setProperty('--reader-size', state.fontSize + 'px');
    document.querySelectorAll('.verses').forEach((v) => v.classList.toggle('sans', state.fontFamily === 'sans'));
  }

  function setUrl() {
    history.replaceState({}, '', `/reader/${state.book}/${state.chapter}`);
    bookBtn.textContent = `${BibleAPI.getBookName(state.book)} ${state.chapter} ▾`;
    document.title = `${BibleAPI.getBookName(state.book)} ${state.chapter} · ReadIn52`;
  }

  async function load() {
    content.innerHTML = '<div class="reader-loading">Loading…</div>';
    state.total = BibleAPI.getTotalChapters(state.book);
    setUrl();
    const [primary, highlights, secondary] = await Promise.all([
      BibleAPI.getChapter(state.translation, state.book, state.chapter),
      window.api(`/api/highlights?book=${state.book}&chapter=${state.chapter}`),
      (state.dual && state.secondary) ? BibleAPI.getChapter(state.secondary, state.book, state.chapter) : Promise.resolve(null),
    ]);
    if (primary.error) { content.innerHTML = `<div class="reader-loading">Could not load this chapter. ${primary.message || ''}</div>`; return; }

    const hl = (highlights && highlights.highlights) || {};
    const title = `<h2 class="chapter-title">${primary.bookName} ${state.chapter}</h2>`;

    if (secondary && !secondary.error) {
      content.className = 'reader-page dual';
      content.innerHTML = title + `<div class="dual-cols">
        <div class="col"><h4>${trName(state.translation)}</h4><div class="verses" data-col="1">${renderVerses(primary, hl)}</div></div>
        <div class="col"><h4>${trName(state.secondary)}</h4><div class="verses" data-col="2">${renderVerses(secondary, {})}</div></div>
      </div>`;
    } else {
      content.className = 'reader-page';
      content.innerHTML = title + `<div class="verses">${renderVerses(primary, hl)}</div>`;
    }
    applyType();
    bindVerses();
    updateNav();
    refreshNoteCount();
    refreshBookmark();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function esc(s) { return String(s).replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])); }
  function renderVerses(data, hl) {
    return data.verses.map((v) => {
      const cls = hl[v.verse] ? ` hl-${hl[v.verse]}` : '';
      return `<span class="verse${cls}" data-verse="${v.verse}"><span class="vn">${v.verse}</span>${esc(v.text)} </span>`;
    }).join('');
  }
  function trName(id) { const o = trSel.querySelector(`option[value="${id}"]`); return o ? o.textContent : id; }

  // ---- Verse highlight popover ----
  const pop = $('#hlPopover');
  let popVerse = null;
  function bindVerses() {
    content.querySelectorAll('.verses[data-col="1"] .verse, .reader-page > .verses .verse').forEach((v) => {
      v.addEventListener('click', (e) => {
        popVerse = v;
        pop.hidden = false;
        const r = v.getBoundingClientRect();
        pop.style.left = Math.min(r.left, window.innerWidth - 220) + 'px';
        pop.style.top = (window.scrollY + r.bottom + 6) + 'px';
        e.stopPropagation();
      });
    });
  }
  pop.querySelectorAll('.hl-swatch').forEach((sw) => {
    sw.addEventListener('click', async () => {
      if (!popVerse) return;
      const color = sw.dataset.color;
      const verse = popVerse.dataset.verse;
      const res = await window.api('/api/highlights', 'POST', { book: state.book, chapter: state.chapter, verse, color });
      popVerse.className = 'verse' + (res.color ? ` hl-${res.color}` : '');
      pop.hidden = true;
    });
  });
  document.addEventListener('click', (e) => { if (!pop.contains(e.target) && pop && !e.target.classList.contains('verse') && !e.target.classList.contains('vn')) pop.hidden = true; });

  // ---- Navigation ----
  function updateNav() {
    $('#rPrev').disabled = state.chapter <= 1;
    $('#rNext').disabled = state.chapter >= state.total;
    $('#rProgressMark').textContent = `${state.chapter} / ${state.total}`;
  }
  $('#rPrev').addEventListener('click', () => { if (state.chapter > 1) { state.chapter--; load(); } });
  $('#rNext').addEventListener('click', () => { if (state.chapter < state.total) { state.chapter++; load(); } });
  document.addEventListener('keydown', (e) => {
    if (e.target.matches('input,textarea')) return;
    if (e.key === 'ArrowLeft' && state.chapter > 1) { state.chapter--; load(); }
    if (e.key === 'ArrowRight' && state.chapter < state.total) { state.chapter++; load(); }
  });

  // ---- Translations ----
  trSel.addEventListener('change', () => { state.translation = trSel.value; localStorage.setItem('r52-tr', state.translation); load(); });
  secSel.addEventListener('change', () => { state.secondary = secSel.value; localStorage.setItem('r52-sec', state.secondary); load(); });
  $('#rDualBtn').addEventListener('click', () => {
    state.dual = !state.dual;
    if (state.dual && !state.secondary) { state.secondary = secSel.value; }
    secSel.hidden = !state.dual;
    localStorage.setItem('r52-dual', state.dual ? '1' : '0');
    load();
  });

  // ---- Type controls ----
  $('#rFontUp').addEventListener('click', () => { state.fontSize = Math.min(30, state.fontSize + 2); localStorage.setItem('r52-fs', state.fontSize); applyType(); });
  $('#rFontDown').addEventListener('click', () => { state.fontSize = Math.max(14, state.fontSize - 2); localStorage.setItem('r52-fs', state.fontSize); applyType(); });
  $('#rFontFamily').addEventListener('click', () => { state.fontFamily = state.fontFamily === 'serif' ? 'sans' : 'serif'; localStorage.setItem('r52-ff', state.fontFamily); applyType(); });

  // ---- Focus mode ----
  $('#rFocus').addEventListener('click', () => document.body.classList.toggle('focus-mode'));

  // ---- Bookmark ----
  const bmBtn = $('#rBookmark');
  async function refreshBookmark() {
    const res = await window.api('/api/bookmarks');
    const on = (res.bookmarks || []).some((b) => b.book === state.book && b.chapter === state.chapter);
    bmBtn.textContent = on ? '★' : '☆';
    bmBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
  }
  bmBtn.addEventListener('click', async () => {
    const res = await window.api('/api/bookmarks', 'POST', { book: state.book, chapter: state.chapter });
    bmBtn.textContent = res.bookmarked ? '★' : '☆';
    window.toast(res.bookmarked ? 'Bookmarked' : 'Bookmark removed');
  });

  // ---- Book picker ----
  const picker = $('#bookPicker');
  const bookList = $('#bookList');
  function buildPicker(filter = '') {
    bookList.innerHTML = '';
    for (const [code, [name, chapters]] of Object.entries(BOOKS)) {
      if (filter && !name.toLowerCase().includes(filter.toLowerCase())) continue;
      const wrap = document.createElement('div');
      wrap.className = 'picker-book';
      let chaps = '';
      for (let i = 1; i <= chapters; i++) chaps += `<a href="#" data-book="${code}" data-ch="${i}">${i}</a>`;
      wrap.innerHTML = `<div class="pb-name">${name}</div><div class="picker-chaps">${chaps}</div>`;
      bookList.appendChild(wrap);
    }
    bookList.querySelectorAll('a').forEach((a) => a.addEventListener('click', (e) => {
      e.preventDefault();
      state.book = a.dataset.book; state.chapter = parseInt(a.dataset.ch, 10);
      picker.hidden = true; load();
    }));
  }
  bookBtn.addEventListener('click', () => { picker.hidden = false; buildPicker(); $('#bookSearch').value = ''; setTimeout(() => $('#bookSearch').focus(), 20); });
  $('#bookPickerClose').addEventListener('click', () => picker.hidden = true);
  picker.addEventListener('click', (e) => { if (e.target === picker) picker.hidden = true; });
  $('#bookSearch').addEventListener('input', (e) => buildPicker(e.target.value));

  // ---- Notes drawer ----
  const drawer = $('#notesDrawer');
  const ndList = $('#ndList');
  const ndForm = $('#ndForm');
  let ndColor = 'default';
  async function refreshNoteCount() {
    const res = await window.api(`/api/notes/chapter?book=${state.book}&chapter=${state.chapter}`);
    const n = (res.notes || []).length;
    $('#rNoteCount').textContent = n ? n : '';
  }
  async function openNotes() {
    $('#ndRef').textContent = `${BibleAPI.getBookName(state.book)} ${state.chapter}`;
    drawer.hidden = false;
    const res = await window.api(`/api/notes/chapter?book=${state.book}&chapter=${state.chapter}`);
    renderNotes(res.notes || []);
  }
  function renderNotes(notes) {
    ndList.innerHTML = notes.length ? '' : '<p class="muted">No notes for this chapter yet.</p>';
    notes.forEach((n) => {
      const d = document.createElement('div');
      d.className = 'nd-note c-' + n.color;
      d.style.borderLeftColor = 'var(--hl-' + (n.color === 'default' ? 'yellow' : n.color) + ')';
      d.innerHTML = `<h4>${escAttr(n.title)}</h4><p>${escAttr(n.content)}</p>
        <div class="nd-note-tools"><button class="icon-btn nd-edit">✎</button><button class="icon-btn nd-del">🗑</button></div>`;
      d.querySelector('.nd-edit').addEventListener('click', () => fillForm(n));
      d.querySelector('.nd-del').addEventListener('click', async () => {
        if (!confirm('Delete this note?')) return;
        await window.api('/api/notes/' + n.id, 'DELETE', {});
        openNotes(); refreshNoteCount();
      });
      ndList.appendChild(d);
    });
  }
  function escAttr(s) { return String(s).replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])); }
  function fillForm(n) {
    $('#ndNoteId').value = n.id; $('#ndTitle').value = n.title; $('#ndContent').value = n.content;
    selColor(n.color); $('#ndCancel').hidden = false;
  }
  function selColor(c) {
    ndColor = c;
    $('#ndColors').querySelectorAll('.color-dot').forEach((b) => b.classList.toggle('sel', b.dataset.color === c));
  }
  $('#ndColors').querySelectorAll('.color-dot').forEach((b) => b.addEventListener('click', () => selColor(b.dataset.color)));
  $('#rNotesBtn').addEventListener('click', openNotes);
  $('#ndClose').addEventListener('click', () => drawer.hidden = true);
  $('#ndCancel').addEventListener('click', () => resetForm());
  function resetForm() { ndForm.reset(); $('#ndNoteId').value = ''; selColor('default'); $('#ndCancel').hidden = true; }
  ndForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = {
      note_id: $('#ndNoteId').value || '', title: $('#ndTitle').value, content: $('#ndContent').value,
      color: ndColor, book: state.book, chapter: state.chapter,
    };
    await window.api('/api/notes', 'POST', body);
    resetForm(); openNotes(); refreshNoteCount();
    window.toast('Note saved');
  });

  load();
})();

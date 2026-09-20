/* ReadIn52 study reader — continuous reading, cross-references, verse actions */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const el = $('#reader');
  if (!el) return;
  const U = window.READIN52.user || {};
  const BOOKS = window.READIN52_BOOKS;
  const ORDER = Object.keys(BOOKS);

  const state = {
    book: el.dataset.book,
    chapter: parseInt(el.dataset.chapter, 10),
    translation: localStorage.getItem('r52-tr') || U.translation || 'eng_kjv',
    secondary: U.secondary || '',
    dual: localStorage.getItem('r52-dual') === '1',
    fontSize: parseInt(localStorage.getItem('r52-fs') || U.fontSize || 19, 10),
    fontFamily: localStorage.getItem('r52-ff') || U.fontFamily || 'serif',
  };

  const content = $('#readerContent');
  const trSel = $('#rTranslation');
  const secSel = $('#rSecondary');
  let loading = false;
  let loaded = [];        // ordered [{book, chapter}]
  let atEnd = false;

  trSel.value = state.translation;
  if (state.secondary) secSel.value = state.secondary;
  if (state.dual && state.secondary) secSel.hidden = false;
  applyType();

  function esc(s) { return String(s).replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])); }
  function applyType() { content.style.setProperty('--reader-size', state.fontSize + 'px'); content.classList.toggle('sans', state.fontFamily === 'sans'); }
  function nextOf(b, c) { const t = BibleAPI.getTotalChapters(b); if (c < t) return { book: b, chapter: c + 1 }; const i = ORDER.indexOf(b); return i < ORDER.length - 1 ? { book: ORDER[i + 1], chapter: 1 } : null; }
  function prevOf(b, c) { if (c > 1) return { book: b, chapter: c - 1 }; const i = ORDER.indexOf(b); return i > 0 ? { book: ORDER[i - 1], chapter: BibleAPI.getTotalChapters(ORDER[i - 1]) } : null; }

  // ---- Chapter rendering ----
  function versesHtml(data, hl) {
    return data.verses.map((v) => {
      const cls = hl[v.verse] ? ` hl-${hl[v.verse]}` : '';
      return `<span class="verse${cls}" data-book="${data.book}" data-chapter="${data.chapter}" data-verse="${v.verse}"><span class="vn">${v.verse}</span>${esc(v.text)} </span>`;
    }).join('');
  }

  async function buildBlock(book, chapter) {
    const [primary, hlRes, refsRes, secondary] = await Promise.all([
      BibleAPI.getChapter(state.translation, book, chapter),
      window.api(`/api/highlights?book=${book}&chapter=${chapter}`),
      window.api(`/api/cross-references/chapter?book=${book}&chapter=${chapter}`),
      (state.dual && state.secondary) ? BibleAPI.getChapter(state.secondary, book, chapter) : Promise.resolve(null),
    ]);
    const block = document.createElement('section');
    block.className = 'chapter-block';
    block.dataset.book = book; block.dataset.chapter = chapter;
    if (primary.error) { block.innerHTML = `<div class="reader-loading">Could not load ${BibleAPI.getBookName(book)} ${chapter}.</div>`; return block; }

    const hl = (hlRes && hlRes.highlights) || {};
    const refSet = new Set((refsRes && refsRes.verses) || []);
    const title = `<h2 class="chapter-title">${primary.bookName} ${chapter}</h2>`;

    if (secondary && !secondary.error) {
      block.innerHTML = title + `<div class="dual-cols">
        <div class="col"><h4>${trName(state.translation)}</h4><div class="verses" data-col="1">${versesHtml(primary, hl)}</div></div>
        <div class="col"><h4>${trName(state.secondary)}</h4><div class="verses">${versesHtml(secondary, {})}</div></div></div>`;
    } else {
      block.innerHTML = title + `<div class="verses">${versesHtml(primary, hl)}</div>`;
    }
    // mark verses that have cross-references (primary column only)
    const primaryVerses = secondary ? block.querySelectorAll('.col:first-child .verse') : block.querySelectorAll('.verse');
    primaryVerses.forEach((v) => { if (refSet.has(parseInt(v.dataset.verse, 10))) v.classList.add('has-ref'); });
    return block;
  }

  function trName(id) { const o = trSel.querySelector(`option[value="${id}"]`); return o ? o.textContent : id; }

  async function loadInitial() {
    content.innerHTML = '';
    loaded = [];
    atEnd = false;
    const block = await buildBlock(state.book, state.chapter);
    content.appendChild(block);
    loaded.push({ book: state.book, chapter: state.chapter });
    applyType();
    updateChrome();
    window.scrollTo({ top: 0 });
  }

  async function loadNext() {
    if (loading || atEnd) return;
    const last = loaded[loaded.length - 1];
    const nx = nextOf(last.book, last.chapter);
    if (!nx) { atEnd = true; $('#readerEnd').hidden = false; return; }
    loading = true;
    const block = await buildBlock(nx.book, nx.chapter);
    content.appendChild(block);
    loaded.push(nx);
    applyType();
    loading = false;
  }

  // ---- Continuous scroll ----
  const observer = new IntersectionObserver((entries) => {
    if (entries.some((e) => e.isIntersecting)) loadNext();
  }, { rootMargin: '600px' });
  observer.observe($('#loadSentinel'));

  // ---- Scroll spy: keep title + URL on the chapter in view ----
  let spyRaf = null;
  window.addEventListener('scroll', () => {
    if (spyRaf) return;
    spyRaf = requestAnimationFrame(() => {
      spyRaf = null;
      const blocks = content.querySelectorAll('.chapter-block');
      let cur = null;
      blocks.forEach((b) => { if (b.getBoundingClientRect().top < 120) cur = b; });
      if (cur && cur.dataset.book) {
        const b = cur.dataset.book, c = cur.dataset.chapter;
        if (b !== state.book || +c !== state.chapter) {
          state.book = b; state.chapter = +c;
          $('#rTitleText').textContent = `${BibleAPI.getBookName(b)} ${c}`;
          document.title = `${BibleAPI.getBookName(b)} ${c} · ReadIn52`;
          history.replaceState({}, '', `/reader/${b}/${c}`);
          refreshBookmark(); refreshNoteCount();
        }
      }
    });
  }, { passive: true });

  function updateChrome() {
    $('#rTitleText').textContent = `${BibleAPI.getBookName(state.book)} ${state.chapter}`;
    document.title = `${BibleAPI.getBookName(state.book)} ${state.chapter} · ReadIn52`;
    refreshBookmark(); refreshNoteCount();
  }

  function jumpTo(book, chapter) {
    state.book = book; state.chapter = chapter;
    history.replaceState({}, '', `/reader/${book}/${chapter}`);
    closeStudy();
    loadInitial();
  }

  // ---- Study panel ----
  const panel = $('#studyPanel');
  const fab = $('#studyFab');
  let activeVerse = null; // { book, chapter, verse, text, el }

  content.addEventListener('click', (e) => {
    const v = e.target.closest('.verse');
    if (!v || v.closest('.col:nth-child(2)')) return; // ignore secondary column
    openStudy(v);
  });

  function openStudy(vEl) {
    const book = vEl.dataset.book, chapter = +vEl.dataset.chapter, verse = +vEl.dataset.verse;
    const text = vEl.textContent.replace(/^\s*\d+\s*/, '').trim();
    activeVerse = { book, chapter, verse, text, el: vEl };
    document.querySelectorAll('.verse.active').forEach((x) => x.classList.remove('active'));
    vEl.classList.add('active');

    $('#spRef').textContent = `${BibleAPI.getBookName(book)} ${chapter}:${verse}`;
    $('#spVerse').textContent = text;
    panel.hidden = false; fab.hidden = true;
    document.body.classList.add('study-open');
    $('#spNoteForm').hidden = true;
    loadRefs(book, chapter, verse);
    loadCommentary(book, chapter, verse);
    loadVerseNotes(book, chapter, verse);
    markHighlightState(vEl);
  }
  function closeStudy() {
    panel.hidden = true;
    document.body.classList.remove('study-open');
    document.querySelectorAll('.verse.active').forEach((x) => x.classList.remove('active'));
    if (activeVerse) fab.hidden = false;
  }
  $('#spClose').addEventListener('click', closeStudy);
  fab.addEventListener('click', () => { if (activeVerse) { panel.hidden = false; fab.hidden = true; document.body.classList.add('study-open'); } });

  // Cross references with inline text
  async function loadRefs(book, chapter, verse) {
    const box = $('#spRefs');
    box.innerHTML = '<p class="muted small">Finding related passages…</p>';
    const res = await window.api(`/api/cross-references?book=${book}&chapter=${chapter}&verse=${verse}`);
    if (!res.success || !res.refs.length) { box.innerHTML = '<p class="muted small">No cross-references for this verse.</p>'; return; }
    box.innerHTML = '';
    for (const r of res.refs) {
      const item = document.createElement('div');
      item.className = 'xref';
      item.innerHTML = `<a class="xref-ref" href="/reader/${r.book}/${r.chapter}" data-b="${r.book}" data-c="${r.chapter}">${r.ref}</a><div class="xref-text muted">…</div>`;
      item.querySelector('.xref-ref').addEventListener('click', (e) => { e.preventDefault(); jumpTo(r.book, r.chapter); });
      box.appendChild(item);
      // fetch the referenced text (cached)
      BibleAPI.getChapter(state.translation, r.book, r.chapter).then((data) => {
        if (data.error) return;
        const parts = [];
        for (let v = r.verse; v <= (r.verseEnd || r.verse); v++) {
          const found = data.verses.find((x) => x.verse === v);
          if (found) parts.push(found.text);
        }
        item.querySelector('.xref-text').textContent = parts.join(' ') || '';
      });
    }
  }

  // ---- Highlights ----
  function markHighlightState(vEl) {
    const cur = (vEl.className.match(/hl-(\w+)/) || [])[1] || '';
    $('#spHighlight').querySelectorAll('.hl-swatch').forEach((s) => s.classList.toggle('sel', s.dataset.color === cur));
  }
  $('#spHighlight').querySelectorAll('.hl-swatch').forEach((sw) => sw.addEventListener('click', async () => {
    if (!activeVerse) return;
    const res = await window.api('/api/highlights', 'POST', { book: activeVerse.book, chapter: activeVerse.chapter, verse: activeVerse.verse, color: sw.dataset.color });
    document.querySelectorAll(`.verse[data-book="${activeVerse.book}"][data-chapter="${activeVerse.chapter}"][data-verse="${activeVerse.verse}"]`)
      .forEach((x) => {
        const hasRef = x.classList.contains('has-ref');
        const isActive = x.classList.contains('active');
        x.className = 'verse' + (hasRef ? ' has-ref' : '') + (isActive ? ' active' : '') + (res.color ? ` hl-${res.color}` : '');
      });
    markHighlightState(activeVerse.el);
  }));

  // ---- Copy ----
  $('#spCopyBtn').addEventListener('click', async () => {
    if (!activeVerse) return;
    const txt = `"${activeVerse.text}" — ${BibleAPI.getBookName(activeVerse.book)} ${activeVerse.chapter}:${activeVerse.verse}`;
    try { await navigator.clipboard.writeText(txt); window.toast('Verse copied'); } catch { window.toast('Copy failed'); }
  });

  // ---- Verse notes ----
  const noteForm = $('#spNoteForm');
  $('#spNoteBtn').addEventListener('click', () => {
    noteForm.hidden = !noteForm.hidden;
    if (!noteForm.hidden) {
      $('#spNoteId').value = '';
      $('#spNoteTitle').value = `${BibleAPI.getBookName(activeVerse.book)} ${activeVerse.chapter}:${activeVerse.verse}`;
      $('#spNoteContent').value = '';
      setTimeout(() => $('#spNoteContent').focus(), 20);
    }
  });
  $('#spNoteCancel').addEventListener('click', () => { noteForm.hidden = true; });
  noteForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    await window.api('/api/notes', 'POST', {
      note_id: $('#spNoteId').value || '',
      title: $('#spNoteTitle').value, content: $('#spNoteContent').value,
      book: activeVerse.book, chapter: activeVerse.chapter, verse: activeVerse.verse,
    });
    noteForm.hidden = true;
    window.toast('Note saved');
    loadVerseNotes(activeVerse.book, activeVerse.chapter, activeVerse.verse);
    refreshNoteCount();
  });
  async function loadVerseNotes(book, chapter, verse) {
    const box = $('#spNotes');
    const res = await window.api(`/api/notes/chapter?book=${book}&chapter=${chapter}`);
    const notes = (res.notes || []).filter((n) => !n.verse || n.verse === verse);
    if (!notes.length) { box.innerHTML = ''; return; }
    box.innerHTML = '<h4 class="sp-title">Your notes</h4>' + notes.map((n) => `
      <div class="sp-note c-${n.color}" data-id="${n.id}">
        <strong>${esc(n.title)}</strong><p>${esc(n.content)}</p>
        <button class="icon-btn sp-note-del" data-id="${n.id}">🗑 delete</button>
      </div>`).join('');
    box.querySelectorAll('.sp-note-del').forEach((b) => b.addEventListener('click', async () => {
      await window.api('/api/notes/' + b.dataset.id, 'DELETE', {});
      loadVerseNotes(book, chapter, verse); refreshNoteCount();
    }));
  }

  // ---- Commentary ----
  let commentaryId = localStorage.getItem('r52-cm') || '';
  const cmSel = $('#spCommentarySel');
  if (cmSel) {
    cmSel.value = commentaryId;
    cmSel.addEventListener('change', () => {
      commentaryId = cmSel.value; localStorage.setItem('r52-cm', commentaryId);
      if (activeVerse) loadCommentary(activeVerse.book, activeVerse.chapter, activeVerse.verse);
    });
  }
  async function loadCommentary(book, chapter, verse) {
    const box = $('#spCommentary');
    if (!commentaryId) { box.innerHTML = '<p class="muted small">Choose a commentary to see notes on this verse.</p>'; return; }
    box.innerHTML = '<p class="muted small">Loading commentary…</p>';
    const data = await BibleAPI.getCommentary(commentaryId, book, chapter);
    if (data.error || !data.blocks) { box.innerHTML = '<p class="muted small">Commentary unavailable for this passage.</p>'; return; }
    // exact verse, else the passage block that covers this verse (largest starting verse ≤ target)
    const covering = data.blocks.filter((b) => b.number <= verse).pop() || data.blocks[0];
    if (!covering) { box.innerHTML = '<p class="muted small">No commentary for this chapter.</p>'; return; }
    const scope = covering.number === verse ? '' : `<span class="cm-scope">on verse ${covering.number}${covering.number < verse ? '+' : ''}</span>`;
    box.innerHTML = `${scope}<div class="cm-text">${esc(covering.text)}</div>`;
  }

  // ---- Share as image ----
  $('#spShareBtn').addEventListener('click', async () => {
    if (!activeVerse) return;
    const blob = await makeVerseCard(activeVerse.text, `${BibleAPI.getBookName(activeVerse.book)} ${activeVerse.chapter}:${activeVerse.verse}`);
    if (!blob) return;
    const file = new File([blob], 'readin52-verse.png', { type: 'image/png' });
    if (navigator.canShare && navigator.canShare({ files: [file] })) {
      try { await navigator.share({ files: [file], title: 'ReadIn52' }); return; } catch {}
    }
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `${activeVerse.book}-${activeVerse.chapter}-${activeVerse.verse}.png`; a.click();
    URL.revokeObjectURL(url);
    window.toast('Verse image saved');
  });
  function makeVerseCard(text, ref) {
    return new Promise((resolve) => {
      const W = 1080, H = 1080, c = document.createElement('canvas'); c.width = W; c.height = H; const g = c.getContext('2d');
      const grad = g.createLinearGradient(0, 0, W, H); grad.addColorStop(0, '#b0603a'); grad.addColorStop(1, '#5b3a29');
      g.fillStyle = grad; g.fillRect(0, 0, W, H);
      g.fillStyle = 'rgba(255,255,255,0.06)'; roundRect(g, 64, 64, W - 128, H - 128, 44); g.fill();
      g.textAlign = 'center';
      const size = text.length > 260 ? 34 : text.length > 140 ? 42 : 50;
      g.fillStyle = '#fdf6ee';
      wrapText(g, '“' + text + '”', W / 2, H / 2 - 40, W - 280, size * 1.4, `600 ${size}px Georgia, serif`);
      g.font = '700 40px Inter, Arial, sans-serif'; g.fillStyle = '#ffe6cf'; g.fillText(ref.toUpperCase(), W / 2, H - 230);
      g.font = '600 32px Inter, Arial, sans-serif'; g.fillStyle = 'rgba(255,255,255,0.9)'; g.fillText('ReadIn52', W / 2, H - 150);
      g.font = '400 26px Inter, Arial, sans-serif'; g.fillStyle = 'rgba(255,255,255,0.6)'; g.fillText('readin52.askdevotions.com', W / 2, H - 105);
      c.toBlob(resolve, 'image/png');
    });
  }
  function roundRect(g, x, y, w, h, r) { g.beginPath(); g.moveTo(x + r, y); g.arcTo(x + w, y, x + w, y + h, r); g.arcTo(x + w, y + h, x, y + h, r); g.arcTo(x, y + h, x, y, r); g.arcTo(x, y, x + w, y, r); g.closePath(); }
  function wrapText(g, text, x, y, maxW, lh, font) {
    g.font = font; const words = text.split(' '); let line = ''; const lines = [];
    for (const w of words) { const t = line + w + ' '; if (g.measureText(t).width > maxW && line) { lines.push(line.trim()); line = w + ' '; } else line = t; }
    lines.push(line.trim());
    const startY = y - (lines.length - 1) * lh / 2;
    lines.forEach((l, i) => g.fillText(l, x, startY + i * lh));
  }

  // ---- Toolbar controls ----
  trSel.addEventListener('change', () => { state.translation = trSel.value; localStorage.setItem('r52-tr', state.translation); loadInitial(); });
  secSel.addEventListener('change', () => { state.secondary = secSel.value; localStorage.setItem('r52-sec', state.secondary); loadInitial(); });
  $('#rDualBtn').addEventListener('click', () => {
    state.dual = !state.dual;
    if (state.dual && !state.secondary) state.secondary = secSel.value;
    secSel.hidden = !state.dual;
    localStorage.setItem('r52-dual', state.dual ? '1' : '0');
    loadInitial();
  });
  $('#rFontUp').addEventListener('click', () => { state.fontSize = Math.min(30, state.fontSize + 2); localStorage.setItem('r52-fs', state.fontSize); applyType(); });
  $('#rFontDown').addEventListener('click', () => { state.fontSize = Math.max(14, state.fontSize - 2); localStorage.setItem('r52-fs', state.fontSize); applyType(); });
  $('#rFontFamily').addEventListener('click', () => { state.fontFamily = state.fontFamily === 'serif' ? 'sans' : 'serif'; localStorage.setItem('r52-ff', state.fontFamily); applyType(); });
  $('#rFocus').addEventListener('click', () => document.body.classList.toggle('focus-mode'));

  document.addEventListener('keydown', (e) => {
    if (e.target.matches('input,textarea')) return;
    if (e.key === 'Escape') closeStudy();
    if (e.key === 'ArrowRight') { const nx = nextOf(state.book, state.chapter); if (nx) jumpTo(nx.book, nx.chapter); }
    if (e.key === 'ArrowLeft') { const pv = prevOf(state.book, state.chapter); if (pv) jumpTo(pv.book, pv.chapter); }
  });

  // ---- Bookmark ----
  const bmBtn = $('#rBookmark');
  async function refreshBookmark() {
    const res = await window.api('/api/bookmarks');
    const on = (res.bookmarks || []).some((b) => b.book === state.book && b.chapter === state.chapter);
    bmBtn.textContent = on ? '★' : '☆';
  }
  bmBtn.addEventListener('click', async () => {
    const res = await window.api('/api/bookmarks', 'POST', { book: state.book, chapter: state.chapter });
    bmBtn.textContent = res.bookmarked ? '★' : '☆';
    window.toast(res.bookmarked ? 'Bookmarked' : 'Bookmark removed');
  });
  async function refreshNoteCount() {
    const res = await window.api(`/api/notes/chapter?book=${state.book}&chapter=${state.chapter}`);
    $('#rNoteCount').textContent = (res.notes || []).length || '';
  }
  $('#rNotesBtn').addEventListener('click', () => { location.href = '/notes'; });

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
      for (let i = 1; i <= chapters; i++) chaps += `<a href="#" data-b="${code}" data-c="${i}">${i}</a>`;
      wrap.innerHTML = `<div class="pb-name">${name}</div><div class="picker-chaps">${chaps}</div>`;
      bookList.appendChild(wrap);
    }
    bookList.querySelectorAll('a').forEach((aEl) => aEl.addEventListener('click', (e) => { e.preventDefault(); picker.hidden = true; jumpTo(aEl.dataset.b, +aEl.dataset.c); }));
  }
  $('#rBookBtn').addEventListener('click', () => { picker.hidden = false; buildPicker(); $('#bookSearch').value = ''; setTimeout(() => $('#bookSearch').focus(), 20); });
  $('#bookPickerClose').addEventListener('click', () => picker.hidden = true);
  picker.addEventListener('click', (e) => { if (e.target === picker) picker.hidden = true; });
  $('#bookSearch').addEventListener('input', (e) => buildPicker(e.target.value));

  loadInitial();
})();

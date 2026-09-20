/* ReadIn52 dashboard — week detail panel, chapter toggling, daily verse */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const cats = JSON.parse($('#catData').textContent);
  const catMap = {}; cats.forEach((c) => catMap[c.id] = c);
  const detail = $('#weekDetail');
  const body = $('#weekDetailBody');
  let currentWeekData = null;

  async function openWeek(week) {
    detail.hidden = false;
    $('#weekDetailTitle').textContent = 'Week ' + week;
    body.innerHTML = '<p class="muted">Loading…</p>';
    detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
    const res = await window.api('/api/week/' + week);
    if (!res.success) { body.innerHTML = '<p class="muted">Could not load week.</p>'; return; }
    currentWeekData = res;
    render(week, res);
  }

  function render(week, res) {
    body.innerHTML = '';
    ['poetry', 'history', 'prophecy', 'gospels'].forEach((cid) => {
      const reading = res.week.readings[cid];
      if (!reading) return;
      const cat = catMap[cid];
      const prog = res.progress[cid] || {};
      const card = document.createElement('div');
      card.className = 'wd-cat';
      card.style.setProperty('--c', cat.color);

      let chips = '';
      let firstBook = null, firstCh = null;
      reading.passages.forEach((p) => {
        if (!firstBook) { firstBook = p.book; firstCh = p.chapters[0]; }
        p.chapters.forEach((ch) => {
          const done = prog[`${p.book}_${ch}`] && prog[`${p.book}_${ch}`].completed;
          chips += `<button class="ch-chip ${done ? 'done' : ''}" data-book="${p.book}" data-ch="${ch}" data-cat="${cid}">
            ${done ? '✓' : ''} ${BibleAPI ? BibleAPI.getBookName(p.book) : p.book} ${ch}</button>`;
        });
      });

      card.innerHTML = `<h3>${cat.name}</h3>
        <div class="wd-ref">${reading.reference}</div>
        <div class="wd-chips">${chips}</div>
        <a class="btn btn-ghost btn-sm" href="/reader/${firstBook}/${firstCh}">Open in reader →</a>`;
      body.appendChild(card);

      card.querySelectorAll('.ch-chip').forEach((chip) => {
        chip.addEventListener('click', () => toggleChapter(week, chip));
      });
    });
  }

  async function toggleChapter(week, chip) {
    chip.disabled = true;
    const res = await window.api('/api/chapter-progress', 'POST', {
      week, category: chip.dataset.cat, book: chip.dataset.book, chapter: chip.dataset.ch,
    });
    chip.disabled = false;
    if (!res.success) { window.toast('Could not save. Try again.'); return; }
    chip.classList.toggle('done', res.completed);
    chip.textContent = (res.completed ? '✓ ' : '') + (BibleAPI ? BibleAPI.getBookName(chip.dataset.book) : chip.dataset.book) + ' ' + chip.dataset.ch;
    if (res.newBadges && res.newBadges.length) window.badgeToast(res.newBadges);
    updateGridCell(week);
  }

  async function updateGridCell(week) {
    // Recompute this week's category completion from the fresh progress
    const res = await window.api('/api/week/' + week);
    if (!res.success) return;
    const cell = document.querySelector(`.week-cell[data-week="${week}"]`);
    if (!cell) return;
    let doneCount = 0;
    ['poetry', 'history', 'prophecy', 'gospels'].forEach((cid) => {
      const reading = res.week.readings[cid];
      const bar = cell.querySelector(`.wc-bar[data-cat="${cid}"]`);
      if (!reading || !bar) return;
      let need = 0, have = 0;
      reading.passages.forEach((p) => p.chapters.forEach((ch) => {
        need++;
        if (res.progress[cid] && res.progress[cid][`${p.book}_${ch}`] && res.progress[cid][`${p.book}_${ch}`].completed) have++;
      }));
      const complete = need > 0 && have >= need;
      bar.dataset.on = complete ? 1 : 0;
      if (complete) doneCount++;
    });
    cell.classList.toggle('full', doneCount === 4);
    cell.style.setProperty('--fill', doneCount / 4);
  }

  document.querySelectorAll('.week-cell').forEach((c) => c.addEventListener('click', () => openWeek(parseInt(c.dataset.week, 10))));
  $('#weekDetailClose').addEventListener('click', () => detail.hidden = true);
  const cont = $('#continueBtn');
  if (cont) cont.addEventListener('click', (e) => { e.preventDefault(); openWeek(parseInt(cont.dataset.week, 10)); });

  // ---- Daily verse ----
  const VERSES = [
    ['JHN', 3, 16], ['PSA', 23, 1], ['PRO', 3, 5], ['ROM', 8, 28], ['PHP', 4, 13], ['ISA', 40, 31],
    ['JOS', 1, 9], ['JER', 29, 11], ['MAT', 6, 33], ['PSA', 46, 10], ['ROM', 12, 2], ['GAL', 5, 22],
    ['PSA', 119, 105], ['HEB', 11, 1], ['1CO', 13, 4], ['PSA', 37, 4], ['MAT', 11, 28], ['ISA', 41, 10],
    ['PRO', 16, 3], ['PSA', 121, 1], ['2TI', 1, 7], ['LAM', 3, 22], ['PHP', 4, 6], ['JHN', 14, 6],
    ['PSA', 90, 12], ['ROM', 15, 13], ['COL', 3, 23], ['1PE', 5, 7], ['PSA', 34, 8], ['MAT', 5, 16],
  ];
  async function dailyVerse() {
    const box = $('#dailyVerseText'); const cite = $('#dailyVerseRef');
    if (!box || !window.BibleAPI) return;
    const day = Math.floor(Date.now() / 86400000);
    const [book, chapter, verse] = VERSES[day % VERSES.length];
    const tr = window.READIN52.user.translation || 'eng_kjv';
    const data = await BibleAPI.getChapter(tr, book, chapter);
    if (data.error) { box.textContent = 'Verse unavailable offline.'; return; }
    const v = data.verses.find((x) => x.verse === verse) || data.verses[0];
    box.classList.remove('verse-loading');
    box.textContent = '“' + v.text + '”';
    cite.textContent = `${data.bookName} ${chapter}:${v.verse}`;
    cite.innerHTML = `<a href="/reader/${book}/${chapter}">${data.bookName} ${chapter}:${v.verse}</a>`;
  }
  dailyVerse();
})();

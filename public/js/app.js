/* ReadIn52 core client — theme, nav, menus, toasts, quick-jump palette */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  // ---- Theme toggle ----
  function resolvedTheme() {
    const pref = document.documentElement.getAttribute('data-theme-pref') || 'auto';
    return pref === 'auto'
      ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : pref;
  }
  const themeToggle = $('#themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const next = resolvedTheme() === 'dark' ? 'light' : 'dark';
      localStorage.setItem('readin52-theme', next);
      document.documentElement.setAttribute('data-theme', next);
      document.documentElement.setAttribute('data-theme-pref', next);
    });
  }
  matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if ((localStorage.getItem('readin52-theme') || 'auto') === 'auto') {
      document.documentElement.setAttribute('data-theme', resolvedTheme());
    }
  });

  // ---- User menu ----
  const userMenu = $('#userMenu');
  if (userMenu) {
    $('#userMenuBtn').addEventListener('click', (e) => { e.stopPropagation(); userMenu.classList.toggle('open'); });
    document.addEventListener('click', () => userMenu.classList.remove('open'));
  }
  // ---- Export menu (notes) ----
  $$('.menu#exportMenu, #exportMenu').forEach((m) => {
    const btn = m.querySelector('#exportBtn');
    if (btn) { btn.addEventListener('click', (e) => { e.stopPropagation(); m.classList.toggle('open'); }); document.addEventListener('click', () => m.classList.remove('open')); }
  });

  // ---- Mobile nav ----
  const navToggle = $('#navToggle');
  if (navToggle) navToggle.addEventListener('click', () => $('.main-nav')?.classList.toggle('open'));

  // ---- Flash dismiss ----
  $$('.flash-close').forEach((b) => b.addEventListener('click', (e) => e.target.closest('.flash').remove()));

  // ---- Toasts ----
  window.toast = function (message, kind) {
    const stack = $('#toastStack'); if (!stack) return;
    const el = document.createElement('div');
    el.className = 'toast' + (kind ? ' ' + kind : '');
    el.textContent = message;
    stack.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3200);
  };
  window.badgeToast = function (badges) {
    (badges || []).forEach((b) => window.toast(`${b.icon} Badge unlocked: ${b.name}`, 'badge'));
  };

  // ---- API helper ----
  window.api = async function (url, method, body) {
    const opts = { method: method || 'GET', headers: {} };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.headers['x-csrf-token'] = window.READIN52.csrf; opts.body = JSON.stringify(body); }
    const res = await fetch(url, opts);
    return res.json();
  };

  // ---- Quick-jump palette ----
  const BOOKS = { GEN:['Genesis',50],EXO:['Exodus',40],LEV:['Leviticus',27],NUM:['Numbers',36],DEU:['Deuteronomy',34],JOS:['Joshua',24],JDG:['Judges',21],RUT:['Ruth',4],'1SA':['1 Samuel',31],'2SA':['2 Samuel',24],'1KI':['1 Kings',22],'2KI':['2 Kings',25],'1CH':['1 Chronicles',29],'2CH':['2 Chronicles',36],EZR:['Ezra',10],NEH:['Nehemiah',13],EST:['Esther',10],JOB:['Job',42],PSA:['Psalms',150],PRO:['Proverbs',31],ECC:['Ecclesiastes',12],SNG:['Song of Solomon',8],ISA:['Isaiah',66],JER:['Jeremiah',52],LAM:['Lamentations',5],EZK:['Ezekiel',48],DAN:['Daniel',12],HOS:['Hosea',14],JOL:['Joel',3],AMO:['Amos',9],OBA:['Obadiah',1],JON:['Jonah',4],MIC:['Micah',7],NAM:['Nahum',3],HAB:['Habakkuk',3],ZEP:['Zephaniah',3],HAG:['Haggai',2],ZEC:['Zechariah',14],MAL:['Malachi',4],MAT:['Matthew',28],MRK:['Mark',16],LUK:['Luke',24],JHN:['John',21],ACT:['Acts',28],ROM:['Romans',16],'1CO':['1 Corinthians',16],'2CO':['2 Corinthians',13],GAL:['Galatians',6],EPH:['Ephesians',6],PHP:['Philippians',4],COL:['Colossians',4],'1TH':['1 Thessalonians',5],'2TH':['2 Thessalonians',3],'1TI':['1 Timothy',6],'2TI':['2 Timothy',4],TIT:['Titus',3],PHM:['Philemon',1],HEB:['Hebrews',13],JAS:['James',5],'1PE':['1 Peter',5],'2PE':['2 Peter',3],'1JN':['1 John',5],'2JN':['2 John',1],'3JN':['3 John',1],JUD:['Jude',1],REV:['Revelation',22] };
  window.READIN52_BOOKS = BOOKS;

  const backdrop = $('#paletteBackdrop');
  if (backdrop) {
    const input = $('#paletteInput');
    const results = $('#paletteResults');
    let active = 0, items = [];

    function parseQuery(q) {
      q = q.trim().toLowerCase();
      const m = q.match(/^(\d?\s*[a-z ]+?)\s*(\d+)?$/i);
      let namePart = q, chapter = null;
      if (m) { namePart = (m[1] || '').trim(); chapter = m[2] ? parseInt(m[2], 10) : null; }
      const matches = [];
      for (const [code, [name, chapters]] of Object.entries(BOOKS)) {
        if (!namePart || name.toLowerCase().includes(namePart) || code.toLowerCase().startsWith(namePart.replace(/\s/g, ''))) {
          const ch = chapter && chapter <= chapters ? chapter : 1;
          matches.push({ code, name, ref: chapter ? `chapter ${ch}` : `1–${chapters}`, chapter: ch });
        }
      }
      return matches.slice(0, 8);
    }
    function render() {
      results.innerHTML = '';
      items.forEach((it, i) => {
        const li = document.createElement('li');
        li.className = i === active ? 'active' : '';
        li.innerHTML = `<span>${it.name} ${it.chapter}</span><span class="pr-ref">${it.code}</span>`;
        li.addEventListener('click', () => go(it));
        results.appendChild(li);
      });
    }
    function go(it) { if (it) location.href = `/reader/${it.code}/${it.chapter}`; }
    function open() { backdrop.hidden = false; input.value = ''; items = parseQuery(''); active = 0; render(); setTimeout(() => input.focus(), 20); }
    function close() { backdrop.hidden = true; }

    input.addEventListener('input', () => { items = parseQuery(input.value); active = 0; render(); });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { active = Math.min(active + 1, items.length - 1); render(); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { active = Math.max(active - 1, 0); render(); e.preventDefault(); }
      else if (e.key === 'Enter') { go(items[active]); }
      else if (e.key === 'Escape') { close(); }
    });
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) close(); });
    $('#quickJumpBtn')?.addEventListener('click', open);
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); backdrop.hidden ? open() : close(); }
    });
  }

  // ---- Service worker ----
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }
})();

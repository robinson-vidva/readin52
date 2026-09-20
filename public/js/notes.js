/* ReadIn52 notes page — filter, create/edit/delete */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const modal = $('#noteModal');
  let color = 'default';

  // Filter
  $$('#noteFilter .seg-btn').forEach((btn) => btn.addEventListener('click', () => {
    $$('#noteFilter .seg-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    const c = btn.dataset.color;
    $$('.note-card').forEach((card) => { card.style.display = (c === 'all' || card.dataset.color === c) ? '' : 'none'; });
  }));

  function selColor(c) {
    color = c;
    $('#noteColors').querySelectorAll('.color-dot').forEach((b) => b.classList.toggle('sel', b.dataset.color === c));
  }
  $('#noteColors').querySelectorAll('.color-dot').forEach((b) => b.addEventListener('click', () => selColor(b.dataset.color)));

  function open(note) {
    modal.hidden = false;
    $('#noteModalTitle').textContent = note ? 'Edit note' : 'New note';
    $('#noteId').value = note ? note.id : '';
    $('#noteTitleInput').value = note ? note.title : '';
    $('#noteContentInput').value = note ? note.content : '';
    $('#noteBookInput').value = note ? note.book : '';
    $('#noteChapterInput').value = note ? note.chapter : '';
    selColor(note ? note.color : 'default');
    setTimeout(() => $('#noteTitleInput').focus(), 20);
  }
  function close() { modal.hidden = true; }

  ['#newNoteBtn', '#newNoteBtnEmpty'].forEach((sel) => { const b = $(sel); if (b) b.addEventListener('click', () => open(null)); });
  $('#noteModalClose').addEventListener('click', close);
  modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

  $$('.note-edit').forEach((btn) => btn.addEventListener('click', () => {
    const card = btn.closest('.note-card');
    open({
      id: card.dataset.id, title: card.dataset.title, content: card.dataset.content,
      color: card.dataset.color, book: card.dataset.bookcode, chapter: card.dataset.chapter,
    });
  }));
  $$('.note-del').forEach((btn) => btn.addEventListener('click', async () => {
    if (!confirm('Delete this note?')) return;
    const card = btn.closest('.note-card');
    await window.api('/api/notes/' + card.dataset.id, 'DELETE', {});
    card.remove();
  }));

  $('#noteForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = {
      note_id: $('#noteId').value || '',
      title: $('#noteTitleInput').value,
      content: $('#noteContentInput').value,
      color,
      book: ($('#noteBookInput').value || '').toUpperCase(),
      chapter: $('#noteChapterInput').value || '',
    };
    const res = await window.api('/api/notes', 'POST', body);
    if (res.success) { window.toast('Note saved'); location.reload(); }
    else window.toast('Could not save note');
  });
})();

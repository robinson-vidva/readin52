/* ReadIn52 stats — GitHub-style reading activity heatmap (last 26 weeks) */
(function () {
  const host = document.getElementById('activityHeatmap');
  if (!host) return;
  let dates = [];
  try { dates = JSON.parse(host.dataset.dates || '[]'); } catch {}
  const set = new Set(dates);

  const WEEKS = 26;
  const today = new Date();
  // Start on the Sunday WEEKS ago
  const start = new Date(today);
  start.setDate(start.getDate() - (WEEKS * 7 - 1));
  start.setDate(start.getDate() - start.getDay());

  host.innerHTML = '';
  for (let w = 0; w < WEEKS + 1; w++) {
    const col = document.createElement('div');
    col.className = 'ah-week';
    for (let d = 0; d < 7; d++) {
      const day = new Date(start);
      day.setDate(start.getDate() + w * 7 + d);
      if (day > today) break;
      const iso = day.toISOString().slice(0, 10);
      const cell = document.createElement('div');
      cell.className = 'ah-day' + (set.has(iso) ? ' on' : '');
      cell.title = iso + (set.has(iso) ? ' · read' : '');
      col.appendChild(cell);
    }
    host.appendChild(col);
  }
})();

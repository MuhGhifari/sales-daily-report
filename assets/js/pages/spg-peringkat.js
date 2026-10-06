(function () {
  'use strict';
  const user = App.init({ roles: ['spg'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);

  $('sub').textContent = 'Tim ' + D.team(user.teamId).name + ' · berdasarkan % target masing-masing';

  function render(period) {
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.p === period));
    const lb = D.leaderboard(user.teamId, period, D.TODAY);
    $('list').innerHTML = lb.map(r => {
      const isMe = r.user.id === user.id;
      return `<div class="${isMe ? 'me' : ''}">
        <div class="muted" style="width:28px">${r.rank}</div>
        <div class="grow"><b>${App.esc(r.user.name)}${isMe ? ' (kamu)' : ''}</b><span>${App.esc(r.user.store)}</span></div>
        <div class="val"><b>${App.pct(r.pct)}</b></div>
      </div>`;
    }).join('') || '<p class="empty">Belum ada data.</p>';
  }

  $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b) render(b.dataset.p); });
  render('month');
})();

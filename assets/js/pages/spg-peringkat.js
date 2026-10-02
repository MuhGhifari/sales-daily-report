(function () {
  'use strict';
  const user = App.init({ roles: ['spg'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const LABEL = { day: 'hari ini', week: 'minggu ini', month: 'bulan ini' };

  $('sub').textContent = `Tim ${D.team(user.teamId).name} · ${App.dateLong(D.TODAY)}`;

  function render(period) {
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.p === period));
    const lb = D.leaderboard(user.teamId, period, D.TODAY);
    const me = lb.find(r => r.user.id === user.id);

    $('me').innerHTML = me ? `
      <div class="card me-card">
        <span class="rank-no">#${me.rank}</span>
        <div class="grow"><b>Posisi kamu ${LABEL[period]}</b><div class="small" style="opacity:.8">dari ${lb.length} SPG · ${me.level.label}</div></div>
        <div class="v">${App.pct(me.pct)}</div>
      </div>` : '';

    const medals = ['#B8901A', '#7A8699', '#A0612A'];
    const top = lb.slice(0, 3);
    const order = [top[1], top[0], top[2]].filter(Boolean);
    $('podium').innerHTML = order.map(r => `
      <div class="p p${r.rank} ${r.user.id === user.id ? 'me' : ''}">
        <div class="medal" style="color:${medals[r.rank - 1]}">${App.icon(r.rank === 1 ? 'crown' : 'award')}<span>${r.rank}</span></div>
        <div class="n">${App.esc(r.user.name.split(' ')[0])}</div>
        <div class="v">${App.pct(r.pct)}</div>
      </div>`).join('');

    $('list').innerHTML = lb.map(r => `
      <div class="item ${r.user.id === user.id ? 'me' : ''}">
        <span class="rank-no ${r.rank === 1 ? 'r1' : ''}">${r.rank}</span>
        <div class="grow"><b>${App.esc(r.user.name)}${r.user.id === user.id ? ' (kamu)' : ''}</b><span class="small muted">${App.esc(r.user.store)} · ${App.levelBadge(r.level)}</span></div>
        <div class="score">${App.pct(r.pct)}</div>
      </div>`).join('') || '<div class="empty">Belum ada data.</div>';
  }

  $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b) render(b.dataset.p); });
  render('month');
})();

(function () {
  'use strict';
  const user = App.init({ roles: ['supervisor'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  let period = 'month';

  function render() {
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.p === period));
    const a = D.areaSummary(user.areaId, period, D.TODAY);
    $('title').textContent = 'Area ' + a.area.name;
    $('sub').textContent = `${a.teams.length} tim · ${a.size} SPG · ${App.dateLong(D.TODAY)}`;

    let pace = '';
    if (a.expected != null) {
      const diff = a.pct - a.expected;
      pace = diff >= 0 ? `<span class="ok">${App.pct1(diff)} di atas laju</span>` : `<span class="warn">${App.pct1(-diff)} di bawah laju</span>`;
    }
    $('stats').innerHTML = `
      <div class="stat"><div class="l">Penjualan</div><div class="v">${App.rpShort(a.actual)}</div><div class="s">dari ${App.rpShort(a.target)}</div></div>
      <div class="stat"><div class="l">Pencapaian</div><div class="v">${App.pct1(a.pct)}</div><div class="s">${pace}</div></div>
      <div class="stat"><div class="l">Mulai shift hari ini</div><div class="v">${a.reported}/${a.size}</div></div>`;

    const [cf, ct] = D.chartRange(period, D.TODAY);
    App.trendChart($('trend'), D.dailySeries(a.teams.flatMap(t => t.lb.map(r => r.user.id)), a.teams.map(t => t.team.id), cf, ct));

    $('teams').innerHTML = `
      <thead><tr><th>#</th><th>Tim</th><th>Team Leader</th><th class="num">Penjualan</th><th class="num">Target</th><th class="num">%</th><th class="num">Lapor</th></tr></thead>
      <tbody>${a.teams.map((t, i) => `<tr class="click" data-id="${t.team.id}">
        <td class="muted">${i + 1}</td>
        <td>${App.esc(t.team.name)}<small>${t.size} SPG</small></td>
        <td>${App.esc(D.user(t.team.leaderId).name)}</td>
        <td class="num">${App.rpShort(t.actual)}</td>
        <td class="num">${App.rpShort(t.target)}</td>
        <td class="num"><b>${App.pct1(t.pct)}</b></td>
        <td class="num">${t.reported}/${t.size}</td>
      </tr>`).join('')}</tbody>`;
    $('teams').querySelectorAll('tr.click').forEach(tr => tr.addEventListener('click', () => {
      location.href = '../leader/dashboard.html?tim=' + tr.dataset.id;
    }));
  }

  $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b) { period = b.dataset.p; render(); } });
  render();
})();

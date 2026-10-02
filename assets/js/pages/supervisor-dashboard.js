(function () {
  'use strict';
  const user = App.init({ roles: ['supervisor'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const LABEL = { day: 'Hari Ini', week: 'Minggu Ini', month: 'Bulan Ini' };
  let period = 'month';

  function render() {
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.p === period));
    const a = D.areaSummary(user.areaId, period, D.TODAY);
    const lv = D.level(a.pct);
    const best = a.teams[0];
    $('title').textContent = 'Area ' + a.area.name;
    $('sub').textContent = `${a.teams.length} tim · ${a.size} SPG · ${App.dateLong(D.TODAY)}`;

    let pace = '';
    if (a.expected != null) {
      const diff = a.pct - a.expected;
      pace = diff >= 0 ? `<span class="pill ok">▲ ${App.pct1(diff)} di atas laju</span>` : `<span class="pill warn">▼ ${App.pct1(-diff)} di bawah laju</span>`;
    }
    $('kpis').innerHTML = `
      <div class="card kpi"><div class="l">Penjualan ${LABEL[period]}</div><div class="v">${App.rpShort(a.actual)}</div><div class="small muted">dari target ${App.rpShort(a.target)}</div></div>
      <div class="card kpi"><div class="l">Pencapaian Area</div><div class="v">${App.pct1(a.pct)} ${lv.emoji}</div>${App.bar(a.pct, lv.color)}${pace}</div>
      <div class="card kpi"><div class="l">Tim Terbaik</div><div class="v" style="font-size:20px">🏆 ${best ? App.esc(best.team.name) : '–'}</div><div class="small muted">${best ? App.pct1(best.pct) + ' dari target' : ''}</div></div>
      <div class="card kpi"><div class="l">Lapor Hari Ini</div><div class="v">${a.reported} / ${a.size}</div><div class="small muted">${a.size - a.reported} SPG belum lapor</div></div>`;

    $('teams').innerHTML = `
      <thead><tr><th>#</th><th>Tim</th><th>Team Leader</th><th class="num">SPG</th><th class="num">Penjualan</th><th class="num">Target</th><th>Pencapaian</th><th class="num">Lapor hari ini</th></tr></thead>
      <tbody>${a.teams.map((t, i) => {
        const l = D.level(t.pct);
        return `<tr class="click" data-id="${t.team.id}">
          <td><span class="rank-no ${i === 0 ? 'r1' : ''}">${i + 1}</span></td>
          <td><b>${App.esc(t.team.name)}</b></td>
          <td>${App.esc(D.user(t.team.leaderId).name)}</td>
          <td class="num">${t.size}</td>
          <td class="num">${App.rpShort(t.actual)}</td>
          <td class="num">${App.rpShort(t.target)}</td>
          <td><span class="mini-bar"><span style="width:${Math.min(t.pct, 100)}%;background:${l.color}"></span></span><b>${App.pct1(t.pct)}</b></td>
          <td class="num"><span class="pill ${t.reported === t.size ? 'ok' : 'warn'}">${t.reported}/${t.size}</span></td>
        </tr>`;
      }).join('')}</tbody>`;
    $('teams').querySelectorAll('tr.click').forEach(tr => tr.addEventListener('click', () => {
      location.href = '../leader/dashboard.html?tim=' + tr.dataset.id;
    }));

    $('top').innerHTML = `
      <thead><tr><th>#</th><th>SPG</th><th>Tim</th><th>Pencapaian</th><th>Level</th></tr></thead>
      <tbody>${a.topSpgs.slice(0, 10).map((r, i) => `<tr>
        <td><span class="rank-no ${i === 0 ? 'r1' : ''}">${i + 1}</span></td>
        <td><b>${App.esc(r.user.name)}</b><small>${App.esc(r.user.store)}</small></td>
        <td>${App.esc(D.team(r.user.teamId).name)}</td>
        <td><b>${App.pct(r.pct)}</b></td>
        <td>${r.level.emoji} ${r.level.name}</td>
      </tr>`).join('')}</tbody>`;
  }

  $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b) { period = b.dataset.p; render(); } });
  render();
})();

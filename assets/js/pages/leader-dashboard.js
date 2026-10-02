(function () {
  'use strict';
  const user = App.init({ roles: ['leader', 'supervisor'], teamPicker: true });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const teamId = App.teamId(user);
  const team = D.team(teamId);
  const LABEL = { day: 'Hari Ini', week: 'Minggu Ini', month: 'Bulan Ini' };
  let period = 'month';

  $('title').textContent = 'Tim ' + team.name;

  function pace(s) {
    if (s.expected == null) return '';
    const diff = s.pct - s.expected;
    return diff >= 0
      ? `<span class="pill ok">▲ ${App.pct1(diff)} di atas laju</span>`
      : `<span class="pill warn">▼ ${App.pct1(-diff)} di bawah laju</span>`;
  }

  function render() {
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.p === period));
    const s = D.teamSummary(teamId, period, D.TODAY);
    const lv = D.level(s.pct);
    $('sub').textContent = `${s.size} SPG aktif · ${App.dateLong(D.TODAY)}`;

    $('kpis').innerHTML = `
      <div class="card kpi"><div class="l">Penjualan ${LABEL[period]}</div><div class="v">${App.rpShort(s.actual)}</div><div class="small muted">dari target ${App.rpShort(s.target)}</div></div>
      <div class="card kpi"><div class="l">Pencapaian Tim</div><div class="v">${App.pct1(s.pct)} ${lv.emoji}</div>${App.bar(s.pct, lv.color)}${pace(s)}</div>
      <div class="card kpi"><div class="l">Lapor Hari Ini</div><div class="v">${s.working ? `${s.reported} / ${s.size}` : 'Libur'}</div><div class="small muted">${s.missing.length ? s.missing.length + ' SPG belum lapor' : s.working ? 'Semua sudah lapor ✓' : 'Hari ini bukan hari kerja'}</div></div>`;

    $('missing').innerHTML = !s.working ? '' : !s.missing.length
      ? `<h2>Belum Lapor Hari Ini</h2><p class="muted" style="margin:0">✓ Semua SPG sudah mengirim laporan hari ini.</p>`
      : `<h2>Belum Lapor Hari Ini <span class="pill bad">${s.missing.length}</span></h2>
        ${s.missing.map(u => {
          const text = encodeURIComponent(`Halo ${u.name.split(' ')[0]}, jangan lupa isi laporan penjualan hari ini ya 🙏`);
          const wa = u.phone ? `https://wa.me/62${u.phone.replace(/\D/g, '').replace(/^0/, '')}?text=${text}` : '';
          return `<div class="row" style="padding:8px 0;border-bottom:1px solid var(--sky)">
            <div><b>${App.esc(u.name)}</b><div class="small muted">${App.esc(u.store)}</div></div>
            ${wa ? `<a class="btn small" href="${wa}" target="_blank" rel="noopener">Ingatkan WA</a>` : ''}
          </div>`;
        }).join('')}`;
    $('missing').hidden = !s.working;

    $('spgs').innerHTML = `
      <thead><tr><th>#</th><th>SPG</th><th class="num">Penjualan</th><th class="num">Target</th><th>Pencapaian</th><th>Level</th><th class="num">Streak</th><th>Hari ini</th></tr></thead>
      <tbody>${s.lb.map(r => `
        <tr class="click" data-id="${r.user.id}">
          <td><span class="rank-no ${r.rank === 1 ? 'r1' : ''}">${r.rank}</span></td>
          <td><b>${App.esc(r.user.name)}</b><small>${App.esc(r.user.store)}</small></td>
          <td class="num">${App.num(r.actual)}</td>
          <td class="num">${App.num(Math.round(r.target / 1000) * 1000)}</td>
          <td><span class="mini-bar"><span style="width:${Math.min(r.pct, 100)}%;background:${r.level.color}"></span></span><b>${App.pct(r.pct)}</b></td>
          <td>${r.level.emoji} ${r.level.name}</td>
          <td class="num">${D.streak(r.user.id, D.TODAY) ? '🔥 ' + D.streak(r.user.id, D.TODAY) : '–'}</td>
          <td>${!s.working ? '<span class="pill off">Libur</span>' : D.getReport(r.user.id, D.TODAY) ? '<span class="pill ok">✓ Lapor</span>' : '<span class="pill bad">Belum</span>'}</td>
        </tr>`).join('')}</tbody>`;
    $('spgs').querySelectorAll('tr.click').forEach(tr => tr.addEventListener('click', () => {
      location.href = 'laporan.html?spg=' + encodeURIComponent(tr.dataset.id);
    }));

    $('products').innerHTML = s.products.length ? `
      <thead><tr><th>Produk</th><th class="num">Qty</th><th class="num">Penjualan (Rp)</th></tr></thead>
      <tbody>${s.products.map(p => `<tr><td>${App.esc(p.product.name)}</td><td class="num">${App.num(p.qty)}</td><td class="num">${App.num(p.amount)}</td></tr>`).join('')}</tbody>`
      : '<tbody><tr><td class="empty">Belum ada penjualan.</td></tr></tbody>';

    $('period-note').textContent = `${App.dateMid(s.from)} – ${App.dateMid(s.to)}`;
  }

  $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b) { period = b.dataset.p; render(); } });

  $('export').addEventListener('click', () => {
    const [from, to] = D.range(period, D.TODAY);
    const end = to < D.TODAY ? to : D.TODAY;
    const ids = D.spgsOf(teamId, true).map(u => u.id);
    const rows = [['Tanggal', 'SPG', 'Toko', 'Produk', 'Qty', 'Harga', 'Subtotal']];
    D.listReports({ userIds: ids, from, to: end }).reverse().forEach(r => {
      const u = D.user(r.userId);
      if (r.noSales) rows.push([r.date, u.name, u.store, 'Tidak ada penjualan', 0, 0, 0]);
      r.items.forEach(i => rows.push([r.date, u.name, u.store, D.product(i.productId).name, i.qty, i.price, i.qty * i.price]));
    });
    App.downloadCsv(`laporan-${team.name.toLowerCase().replace(/\s+/g, '-')}-${from}-sd-${end}.csv`, rows);
  });

  render();
})();

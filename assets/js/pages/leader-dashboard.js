(function () {
  'use strict';
  const user = App.init({ roles: ['leader', 'supervisor'], teamPicker: true });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const teamId = App.teamId(user);
  const team = D.team(teamId);
  let period = 'month';

  $('title').textContent = 'Tim ' + team.name;

  function shiftStatus(id, working) {
    const sh = D.getShift(id, D.TODAY);
    if (sh) return sh.end ? `<span class="muted">Selesai ${sh.end}</span>` : `<span class="ok">Aktif sejak ${sh.start}</span>`;
    if (D.getReport(id, D.TODAY)) return 'Sudah lapor';
    return working ? '<span class="bad">Belum mulai</span>' : '<span class="muted">Libur</span>';
  }

  function render() {
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.p === period));
    const s = D.teamSummary(teamId, period, D.TODAY);
    $('sub').textContent = `${s.size} SPG · ${App.dateLong(D.TODAY)}`;

    let pace = '';
    if (s.expected != null) {
      const diff = s.pct - s.expected;
      pace = diff >= 0 ? `<span class="ok">${App.pct1(diff)} di atas laju</span>` : `<span class="warn">${App.pct1(-diff)} di bawah laju</span>`;
    }
    $('stats').innerHTML = `
      <div class="stat"><div class="l">Penjualan</div><div class="v">${App.rpShort(s.actual)}</div><div class="s">dari ${App.rpShort(s.target)}</div></div>
      <div class="stat"><div class="l">Pencapaian</div><div class="v">${App.pct1(s.pct)}</div><div class="s">${pace}</div></div>
      <div class="stat"><div class="l">Mulai shift hari ini</div><div class="v">${s.working ? `${s.reported}/${s.size}` : '-'}</div><div class="s">${s.working ? '' : 'Hari libur'}</div></div>`;

    const [cf, ct] = D.chartRange(period, D.TODAY);
    App.trendChart($('trend'), D.dailySeries(s.lb.map(r => r.user.id), [teamId], cf, ct));

    $('missingWrap').hidden = !s.missing.length;
    $('missing').innerHTML = s.missing.map(u => {
      const text = encodeURIComponent(`Halo ${u.name.split(' ')[0]}, jangan lupa mulai shift dan catat setiap penjualan hari ini ya. Terima kasih.`);
      const wa = u.phone ? `https://wa.me/62${u.phone.replace(/\D/g, '').replace(/^0/, '')}?text=${text}` : '';
      return `<div>
        <div class="grow"><b>${App.esc(u.name)}</b><span>${App.esc(u.store)}</span></div>
        ${wa ? `<a href="${wa}" target="_blank" rel="noopener">Ingatkan via WhatsApp</a>` : ''}
      </div>`;
    }).join('');

    $('spgs').innerHTML = `
      <thead><tr><th>#</th><th>SPG</th><th class="num">Penjualan</th><th class="num">Target</th><th class="num">%</th><th>Shift hari ini</th></tr></thead>
      <tbody>${s.lb.map(r => `
        <tr class="click" data-id="${r.user.id}">
          <td class="muted">${r.rank}</td>
          <td>${App.esc(r.user.name)}<small>${App.esc(r.user.store)}</small></td>
          <td class="num">${App.num(r.actual)}</td>
          <td class="num">${App.num(Math.round(r.target / 1000) * 1000)}</td>
          <td class="num"><b>${App.pct(r.pct)}</b></td>
          <td>${shiftStatus(r.user.id, s.working)}</td>
        </tr>`).join('')}</tbody>`;
    $('spgs').querySelectorAll('tr.click').forEach(tr => tr.addEventListener('click', () => {
      location.href = 'laporan.html?spg=' + encodeURIComponent(tr.dataset.id);
    }));

    $('products').innerHTML = s.products.length ? `
      <thead><tr><th>Produk</th><th class="num">Qty</th><th class="num">Penjualan</th></tr></thead>
      <tbody>${s.products.map(p => `<tr><td>${App.esc(p.product.name)}</td><td class="num">${App.num(p.qty)}</td><td class="num">${App.num(p.amount)}</td></tr>`).join('')}</tbody>`
      : '<tbody><tr><td class="empty">Belum ada penjualan.</td></tr></tbody>';
  }

  $('tabs').addEventListener('click', e => { const b = e.target.closest('button'); if (b) { period = b.dataset.p; render(); } });

  $('export').addEventListener('click', () => {
    const [from, to] = D.range(period, D.TODAY);
    const end = to < D.TODAY ? to : D.TODAY;
    const rows = [['Tanggal', 'Waktu', 'SPG', 'Toko', 'Produk', 'Qty', 'Harga', 'Subtotal']];
    D.listReports({ userIds: D.spgsOf(teamId, true).map(u => u.id), from, to: end }).reverse().forEach(r => {
      const u = D.user(r.userId);
      if (r.noSales) rows.push([r.date, '', u.name, u.store, 'Tidak ada penjualan', 0, 0, 0]);
      D.getTransactions(r).forEach(t => rows.push([r.date, t.time, u.name, u.store, D.product(t.productId).name, t.qty, t.price, t.qty * t.price]));
    });
    App.downloadCsv(`laporan-${team.name.toLowerCase().replace(/\s+/g, '-')}-${from}-sd-${end}.csv`, rows);
  });

  render();
})();

(function () {
  'use strict';
  const user = App.init({ roles: ['leader', 'supervisor'], teamPicker: true });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const teamId = App.teamId(user);
  const spgs = D.spgsOf(teamId, true);

  $('sub').textContent = 'Tim ' + D.team(teamId).name;
  const pre = App.param('spg');
  $('spg').innerHTML = `<option value="">Semua SPG</option>` + spgs.map(u =>
    `<option value="${u.id}" ${u.id === pre ? 'selected' : ''}>${App.esc(u.name)}${u.active ? '' : ' (nonaktif)'}</option>`).join('');
  $('from').value = D.monthStart(D.TODAY);
  $('to').value = D.TODAY;
  $('from').max = $('to').max = D.TODAY;

  function render() {
    const ids = $('spg').value ? [$('spg').value] : spgs.map(u => u.id);
    let from = $('from').value || D.DATA_START, to = $('to').value || D.TODAY;
    if (from > to) [from, to] = [to, from];
    const list = D.listReports({ userIds: ids, from, to });
    const total = list.reduce((a, r) => a + r.total, 0);
    $('summary').innerHTML = `<b>${list.length}</b> laporan · Total <b>${App.rp(total)}</b>`;

    if (!list.length) { $('table').innerHTML = '<tbody><tr><td class="empty">Tidak ada laporan pada filter ini.</td></tr></tbody>'; return; }

    $('table').innerHTML = `
      <thead><tr><th>Tanggal</th><th>SPG</th><th class="num">Produk</th><th class="num">Total (Rp)</th><th>Status</th><th></th></tr></thead>
      <tbody>${list.map(r => {
        const u = D.user(r.userId);
        const key = r.userId + '|' + r.date;
        const lockedByTime = r.date < D.addDays(D.TODAY, -D.getSettings(teamId).editDays);
        const status = !lockedByTime ? '<span class="pill ok">SPG bisa ubah</span>'
          : r.unlocked ? '<span class="pill warn">Dibuka Leader</span>' : `<span class="pill off">${App.icon('lock')} Terkunci</span>`;
        const lockBtn = lockedByTime
          ? `<button class="btn small ghost" data-lock="${key}">${r.unlocked ? 'Kunci lagi' : 'Buka kunci'}</button>` : '';
        return `<tr class="click" data-key="${key}">
            <td>${App.dateShort(r.date)}</td>
            <td><b>${App.esc(u.name)}</b><small>${App.esc(u.store)}</small></td>
            <td class="num">${r.noSales ? '–' : r.items.length}</td>
            <td class="num">${App.num(r.total)}</td>
            <td>${status}</td>
            <td class="num"><a class="btn small ghost" href="../spg/laporan.html?spg=${encodeURIComponent(r.userId)}&tanggal=${r.date}">Ubah</a> ${lockBtn}</td>
          </tr>
          <tr class="detail" data-detail="${key}" hidden><td colspan="6">
            ${r.noSales ? '<i>Tidak ada penjualan.</i>' : `<table>
              <thead><tr><th>Produk</th><th class="num">Qty</th><th class="num">Harga</th><th class="num">Subtotal</th></tr></thead>
              <tbody>${r.items.map(i => `<tr><td>${App.esc(D.product(i.productId).name)}</td><td class="num">${i.qty}</td><td class="num">${App.num(i.price)}</td><td class="num">${App.num(i.qty * i.price)}</td></tr>`).join('')}</tbody>
            </table>`}
            ${r.notes ? `<p class="small"><b>Catatan:</b> ${App.esc(r.notes)}</p>` : ''}
          </td></tr>`;
      }).join('')}</tbody>`;
  }

  $('table').addEventListener('click', e => {
    const lock = e.target.closest('[data-lock]');
    if (lock) {
      const [uid, date] = lock.dataset.lock.split('|');
      const r = D.getReport(uid, date);
      D.setUnlocked(uid, date, !r.unlocked);
      App.toast(r.unlocked ? 'Laporan dibuka, SPG bisa mengubah' : 'Laporan dikunci lagi');
      render();
      return;
    }
    if (e.target.closest('a')) return;
    const tr = e.target.closest('tr.click');
    if (tr) { const d = $('table').querySelector(`[data-detail="${tr.dataset.key}"]`); d.hidden = !d.hidden; }
  });
  ['spg', 'from', 'to'].forEach(id => $(id).addEventListener('change', render));
  render();
})();

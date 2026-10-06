/* Penjualan: record each sale as it happens during a shift. The day's report is built from these. */
(function () {
  'use strict';
  const user = App.init({ roles: ['spg', 'leader', 'supervisor'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const isSpg = user.role === 'spg';

  const owner = isSpg ? user : D.user(App.param('spg'));
  if (!owner || !D.canView(user, owner)) {
    document.querySelector('main').innerHTML = '<p class="empty">Laporan tidak ditemukan.</p>';
    return;
  }
  let date = App.param('tanggal') || D.TODAY;
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || date > D.TODAY) date = D.TODAY;
  const isToday = date === D.TODAY;
  const editDays = D.getSettings(owner.teamId).editDays;

  $('sub').textContent = (isSpg ? '' : owner.name + ' · ') + App.dateLong(date);
  if (!isToday && isSpg) $('back').hidden = false;

  /* ----- Product entry ----- */
  const products = D.products();
  $('product').innerHTML = '<option value=""></option>' +
    products.map(p => `<option value="${p.id}">${App.esc(p.name)}</option>`).join('');
  App.combobox($('product'));

  const qty = () => Math.max(1, Math.floor(+$('qty').value || 1));
  const updateSubtotal = () => { $('subtotal').textContent = App.rp(qty() * Math.max(0, +$('price').value || 0)); };
  $('product').addEventListener('change', () => {
    const p = D.product($('product').value);
    $('price').value = p ? p.price : '';
    updateSubtotal();
  });
  $('minus').addEventListener('click', () => { $('qty').value = Math.max(1, qty() - 1); updateSubtotal(); });
  $('plus').addEventListener('click', () => { $('qty').value = qty() + 1; updateSubtotal(); });
  ['qty', 'price'].forEach(id => $(id).addEventListener('input', updateSubtotal));

  $('entry').addEventListener('submit', e => {
    e.preventDefault();
    const productId = $('product').value;
    if (!productId) { App.toast('Pilih produk dulu.'); $('product')._combo.focus(); return; }
    const price = Math.round(+$('price').value);
    if (!(price > 0)) { App.toast('Isi harga yang benar.'); $('price').focus(); return; }

    const before = isSpg && isToday ? D.level(D.progress(owner.id, 'day', date).pct) : null;
    const q = qty();
    D.addSale(owner.id, date, { productId, qty: q, price }, user.id);
    let msg = `Tersimpan: ${q} × ${D.product(productId).name}`;
    if (before) {
      const after = D.level(D.progress(owner.id, 'day', date).pct);
      if (after.min > before.min) msg = `Tersimpan. Selamat, kamu mencapai ${after.label}!`;
    }
    App.toast(msg);

    // Ready for the next sale
    $('product').value = '';
    $('product').dispatchEvent(new Event('change'));
    $('qty').value = 1;
    updateSubtotal();
    render();
  });

  /* ----- Shift controls ----- */
  $('startShift').addEventListener('click', () => { D.startShift(owner.id); App.toast('Shift dimulai.'); render(); });
  $('endShift').addEventListener('click', () => {
    if (!confirm('Akhiri shift sekarang?')) return;
    D.endShift(owner.id, user.id);
    App.toast('Shift selesai.');
    render();
  });

  $('list').addEventListener('click', e => {
    const b = e.target.closest('[data-del]');
    if (!b || !confirm('Hapus transaksi ini?')) return;
    D.removeSale(owner.id, date, b.dataset.del, user.id);
    render();
  });

  /* ----- Render ----- */
  function render() {
    const shift = D.getShift(owner.id, date);
    const report = D.getReport(owner.id, date);
    const tx = D.getTransactions(report).slice().reverse(); // newest first
    const canEdit = D.canEdit(user, owner.id, date);
    const needsShift = isSpg && isToday && !shift;
    const canAdd = canEdit && !needsShift;

    $('startWrap').hidden = !needsShift;
    const shiftText = shift ? (shift.end ? `Shift ${shift.start}–${shift.end}` : `Shift berjalan sejak ${shift.start}`) : '';
    $('shift').textContent = shiftText;
    $('shift').hidden = !shiftText;
    $('endShift').hidden = !(isSpg && isToday && shift && !shift.end);

    $('entry').hidden = !canAdd;
    $('locked').hidden = canEdit;
    $('locked').textContent = `Laporan ini terkunci karena sudah lebih dari ${editDays} hari.`;
    $('late').hidden = !(canAdd && (!isToday || (shift && shift.end)));

    $('total').textContent = App.rp(report ? report.total : 0);
    $('count').textContent = report && report.noSales ? 'Tidak ada penjualan' : `${tx.length} transaksi`;
    $('list').innerHTML = tx.map(t => `
      <div>
        <div class="muted" style="width:48px">${t.time || '–'}</div>
        <div class="grow"><b>${App.esc(D.product(t.productId).name)}</b><span>${t.qty} × ${App.rp(t.price)}</span></div>
        <div class="val">${App.rp(t.qty * t.price)}</div>${canEdit && report.transactions ? App.iconBtn('trash', 'Hapus transaksi', `data-del="${t.id}"`, 'danger') : ''}
      </div>`).join('') || (needsShift ? '' : '<p class="empty">Belum ada penjualan.</p>');
  }

  updateSubtotal();
  render();
})();

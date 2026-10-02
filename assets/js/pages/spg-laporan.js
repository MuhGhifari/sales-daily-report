(function () {
  'use strict';
  const user = App.init({ roles: ['spg', 'leader', 'supervisor'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const isSpg = user.role === 'spg';

  const owner = isSpg ? user : D.user(App.param('spg'));
  if (!owner || !D.canView(user, owner)) {
    document.querySelector('main').innerHTML = '<div class="card empty">Laporan tidak ditemukan.</div>';
    return;
  }

  let date = App.param('tanggal') || D.TODAY;
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || date > D.TODAY) date = D.TODAY;
  const report = D.getReport(owner.id, date);
  const editable = D.canEdit(user, owner.id, date);
  const editDays = D.getSettings(owner.teamId).editDays;

  $('title').textContent = report ? 'Ubah Laporan' : 'Isi Laporan';
  $('sub').textContent = (isSpg ? '' : owner.name + ' · ') + App.dateLong(date);
  $('store').value = owner.store || '';

  const go = d => {
    const q = new URLSearchParams({ tanggal: d });
    if (!isSpg) q.set('spg', owner.id);
    location.search = '?' + q;
  };

  /* ----- Date field ----- */
  if (isSpg) {
    const options = [];
    for (let i = 0; i <= editDays; i++) options.push(D.addDays(D.TODAY, -i));
    if (!options.includes(date)) options.push(date);
    $('dateField').innerHTML = `<select class="input" id="date">${options.map(d =>
      `<option value="${d}" ${d === date ? 'selected' : ''}>${App.dateLong(d)}${d === D.TODAY ? ' (hari ini)' : ''}${D.isLocked(owner.id, d) ? ' 🔒' : ''}</option>`).join('')}</select>`;
  } else {
    $('dateField').innerHTML = `<input type="date" class="input" id="date" max="${D.TODAY}" value="${date}">`;
  }
  $('date').addEventListener('change', e => e.target.value && go(e.target.value));

  if (!D.isWorkingDay(owner.teamId, date)) {
    $('notes-top').innerHTML = `<div class="notice">Tanggal ini hari libur tim. Laporan tetap bisa dikirim.</div>`;
  }
  if (!editable) {
    $('notes-top').innerHTML = `<div class="notice">🔒 Laporan ini terkunci karena sudah lebih dari ${editDays} hari. Minta Team Leader membuka kunci jika perlu diubah.</div>`;
  }

  /* ----- Product lines ----- */
  const products = D.products();
  const linesEl = $('lines');

  function productOptions(selected) {
    const list = products.slice();
    if (selected && !list.some(p => p.id === selected)) list.push(D.product(selected)); // keep inactive product on old reports
    return list.map(p => `<option value="${p.id}" ${p.id === selected ? 'selected' : ''}>${App.esc(p.name)}</option>`).join('');
  }

  function addLine(item) {
    const p = D.product(item.productId);
    const el = document.createElement('div');
    el.className = 'line';
    el.innerHTML = `
      <div class="line-top">
        <select class="input" aria-label="Produk">${productOptions(item.productId)}</select>
        <button type="button" class="del" aria-label="Hapus produk">×</button>
      </div>
      <div class="line-nums">
        <div><label>Qty</label><input class="input qty" type="number" inputmode="numeric" min="0" step="1" value="${item.qty}"></div>
        <div><label>Harga satuan (Rp)</label><input class="input price" type="number" inputmode="numeric" min="0" step="500" value="${item.price != null ? item.price : p.price}"></div>
        <div class="line-sub"></div>
      </div>`;
    el.querySelector('select').addEventListener('change', e => { el.querySelector('.price').value = D.product(e.target.value).price; recalc(); });
    el.querySelector('.del').addEventListener('click', () => { el.remove(); recalc(); });
    el.querySelectorAll('input').forEach(i => i.addEventListener('input', recalc));
    linesEl.append(el);
    recalc();
  }

  function readItems() {
    return [...linesEl.querySelectorAll('.line')].map(l => ({
      productId: l.querySelector('select').value,
      qty: Math.max(0, Math.floor(+l.querySelector('.qty').value || 0)),
      price: Math.max(0, +l.querySelector('.price').value || 0),
    }));
  }

  function recalc() {
    let total = 0;
    linesEl.querySelectorAll('.line').forEach(l => {
      const sub = Math.max(0, Math.floor(+l.querySelector('.qty').value || 0)) * Math.max(0, +l.querySelector('.price').value || 0);
      l.querySelector('.line-sub').textContent = App.rp(sub);
      total += sub;
    });
    $('total').textContent = App.rp($('noSales').checked ? 0 : total);
  }

  const startItems = report && report.items.length ? report.items : [{ productId: products[0].id, qty: 1 }];
  startItems.forEach(addLine);
  $('noSales').checked = !!(report && report.noSales);
  $('notes').value = report ? report.notes : '';
  const syncNoSales = () => { linesEl.classList.toggle('lines-off', $('noSales').checked); $('add').disabled = $('noSales').checked; recalc(); };
  $('noSales').addEventListener('change', syncNoSales);
  syncNoSales();
  $('add').addEventListener('click', () => {
    const used = readItems().map(i => i.productId);
    const next = products.find(p => !used.includes(p.id)) || products[0];
    addLine({ productId: next.id, qty: 1 });
  });

  if (!editable) {
    document.querySelectorAll('#form input, #form select:not(#date), #form button').forEach(el => { el.disabled = true; });
    $('add').hidden = true;
    $('submit').hidden = true;
    document.querySelectorAll('.del').forEach(b => { b.hidden = true; });
  }
  $('submit').textContent = report ? 'Simpan Perubahan' : 'Kirim Laporan';

  /* ----- Submit ----- */
  $('form').addEventListener('submit', e => {
    e.preventDefault();
    if (!editable) return;
    const noSales = $('noSales').checked;
    const items = readItems().filter(i => i.qty > 0);
    if (!noSales && !items.length) { App.toast('Tambahkan minimal 1 produk, atau centang "Tidak ada penjualan".'); return; }

    const trackLevel = isSpg && date === D.TODAY && D.isWorkingDay(owner.teamId, date);
    const before = trackLevel ? D.level(D.progress(owner.id, 'day', date).pct) : null;
    D.saveReport({ userId: owner.id, date, items, noSales, notes: $('notes').value.trim() }, user.id);

    if (isSpg) {
      let msg = report ? 'Laporan diperbarui ✓' : 'Laporan terkirim ✓';
      if (trackLevel) {
        const after = D.level(D.progress(owner.id, 'day', date).pct);
        if (after.min > before.min) msg = `Selamat! Kamu mencapai ${after.emoji} ${after.label} hari ini! 🎉`;
      }
      try { sessionStorage.setItem('lspg-celebrate', msg); } catch (err) { /* ignore */ }
      location.href = 'beranda.html';
    } else {
      location.href = '../leader/laporan.html?spg=' + encodeURIComponent(owner.id);
    }
  });
})();

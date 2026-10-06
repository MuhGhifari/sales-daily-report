(function () {
  'use strict';
  const user = App.init({ roles: ['admin'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const form = $('form');

  function render() {
    const list = D.products(true);
    $('count').textContent = `${list.filter(p => p.active).length} aktif dari ${list.length}`;
    $('table').innerHTML = `
      <thead><tr><th></th><th>Produk</th><th>SKU</th><th class="num">Harga (Rp)</th><th>Status</th><th></th></tr></thead>
      <tbody>${list.map(p => `<tr>
        <td style="width:52px">${App.productImg(p)}</td>
        <td><b>${App.esc(p.name)}</b></td>
        <td>${App.esc(p.sku)}</td>
        <td class="num">${App.num(p.price)}</td>
        <td>${p.active ? '<span class="pill ok">Aktif</span>' : '<span class="pill">Nonaktif</span>'}</td>
        <td class="num">${App.iconBtn('pencil', 'Ubah produk', `data-id="${p.id}"`)}</td>
      </tr>`).join('')}</tbody>`;
    App.tableTools($('table'), { placeholder: 'Cari produk atau SKU...' });
  }

  let image = '';
  const showImage = () => { $('imgPreview').innerHTML = App.productImg({ image }, 'lg'); };
  $('pickImg').addEventListener('click', async () => {
    const photo = await App.pickImage('contain', 320);
    if (photo) { image = photo; showImage(); }
  });

  function fill(p) {
    form.id.value = p ? p.id : '';
    form.name.value = p ? p.name : '';
    form.sku.value = p ? p.sku : '';
    form.price.value = p ? p.price : '';
    image = p ? p.image || '' : '';
    showImage();
    form.active.checked = p ? p.active : true;
    $('formTitle').textContent = p ? 'Ubah Produk' : 'Tambah Produk';
    $('cancel').hidden = !p;
  }

  $('table').addEventListener('click', e => {
    const b = e.target.closest('button[data-id]');
    if (!b) return;
    fill(D.product(b.dataset.id));
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.name.focus();
  });
  $('cancel').addEventListener('click', () => fill(null));

  form.addEventListener('submit', e => {
    e.preventDefault();
    const price = Math.round(+form.price.value);
    if (!form.name.value.trim() || !(price > 0)) { App.toast('Isi nama produk dan harga yang benar.'); return; }
    D.saveProduct({ id: form.id.value || undefined, name: form.name.value.trim(), sku: form.sku.value.trim(), price, active: form.active.checked, image: image || 'assets/products/placeholder.svg' });
    App.toast('Produk disimpan.');
    fill(null);
    render();
  });

  fill(null);
  render();
})();

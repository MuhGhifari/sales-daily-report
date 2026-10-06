(function () {
  'use strict';
  const existing = Data.currentUser();
  if (existing) { location.replace(App.home(existing)); return; }

  if (App.BRAND_LOGO) document.getElementById('mark').outerHTML = App.logoImg();

  const form = document.getElementById('form');
  const err = document.getElementById('err');

  form.addEventListener('submit', e => {
    e.preventDefault();
    const u = Data.login(form.username.value, form.password.value);
    if (!u) {
      err.textContent = 'Username atau password salah.';
      err.hidden = false;
      form.password.value = '';
      form.password.focus();
      return;
    }
    location.href = App.home(u);
  });

  // Demo accounts: click to fill the form
  document.querySelectorAll('[data-user]').forEach(row => row.addEventListener('click', () => {
    form.username.value = row.dataset.user;
    form.password.value = row.dataset.pass;
    err.hidden = true;
    form.querySelector('button').focus();
  }));

  document.getElementById('reset').addEventListener('click', e => {
    e.preventDefault();
    if (!confirm('Kembalikan semua data demo ke awal? Laporan, target, dan pengaturan yang diubah akan hilang.')) return;
    Data.reset();
    location.reload();
  });
})();

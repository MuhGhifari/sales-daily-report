(function () {
  'use strict';
  const existing = Data.currentUser();
  if (existing) { location.replace(App.home(existing)); return; }

  if (App.BRAND_LOGO) document.getElementById('mark').outerHTML = App.logoImg();

  const form = document.getElementById('form');

  // Show / hide password
  const pwBtn = document.getElementById('pwToggle');
  pwBtn.addEventListener('click', () => {
    const show = form.password.type === 'password';
    form.password.type = show ? 'text' : 'password';
    pwBtn.setAttribute('aria-pressed', show);
    pwBtn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    pwBtn.title = pwBtn.getAttribute('aria-label');
    pwBtn.querySelector('.eye').hidden = show;
    pwBtn.querySelector('.eye-off').hidden = !show;
    form.password.focus();
  });
  const err = document.getElementById('err');

  form.addEventListener('submit', e => {
    e.preventDefault();
    const u = Data.login(form.phone.value, form.password.value);
    if (!u) {
      err.textContent = 'Nomor HP atau password salah.';
      err.hidden = false;
      form.password.value = '';
      form.password.focus();
      return;
    }
    location.href = App.home(u);
  });

  // Demo accounts: click to fill the form
  document.querySelectorAll('[data-user]').forEach(row => row.addEventListener('click', () => {
    form.phone.value = row.dataset.user;
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

(function () {
  'use strict';
  const user = App.init({ roles: ['leader', 'supervisor'], teamPicker: true });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const teamId = App.teamId(user);

  $('sub').textContent = 'Tim ' + D.team(teamId).name;

  function render() {
    const list = D.spgsOf(teamId, true).sort((a, b) => (b.active - a.active) || a.name.localeCompare(b.name));
    $('count').textContent = `${list.filter(u => u.active).length} aktif`;
    $('table').innerHTML = `
      <thead><tr><th>Nama</th><th>Username</th><th>WhatsApp</th><th>Toko</th><th>Status</th><th></th></tr></thead>
      <tbody>${list.map(u => `<tr>
        <td>${App.person(u.name)}</td>
        <td>${App.esc(u.username)}</td>
        <td>${App.esc(u.phone)}</td>
        <td>${App.esc(u.store)}</td>
        <td>${u.active ? '<span class="pill ok">Aktif</span>' : '<span class="pill">Nonaktif</span>'}</td>
        <td class="num">${App.iconBtn(u.active ? 'userX' : 'userCheck', u.active ? 'Nonaktifkan SPG' : 'Aktifkan SPG', `data-id="${u.id}"`, u.active ? 'danger' : 'primary')}</td>
      </tr>`).join('')}</tbody>`;
  }

  $('table').addEventListener('click', e => {
    const b = e.target.closest('button[data-id]');
    if (!b) return;
    const u = D.user(b.dataset.id);
    if (u.active && !confirm(`Nonaktifkan ${u.name}? SPG ini tidak bisa login dan tidak masuk peringkat.`)) return;
    D.setActive(u.id, !u.active);
    App.toast(`${u.name} ${u.active ? 'diaktifkan' : 'dinonaktifkan'}`);
    render();
  });

  $('form').addEventListener('submit', e => {
    e.preventDefault();
    const f = e.target;
    const error = D.addSpg({ name: f.name.value, username: f.username.value, phone: f.phone.value, store: f.store.value, teamId });
    if (error) { $('err').textContent = error; $('err').hidden = false; return; }
    $('err').hidden = true;
    App.toast(`${f.name.value} ditambahkan. Password awal: spg123`);
    f.reset();
    render();
  });

  render();
})();

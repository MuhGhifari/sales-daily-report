(function () {
  'use strict';
  const user = App.init({ roles: ['spg'] });
  if (!user) return;
  const D = Data, today = D.TODAY, $ = id => document.getElementById(id);

  $('hello').textContent = 'Halo, ' + user.name.split(' ')[0];
  $('sub').textContent = App.dateLong(today);

  // Message left by the report page after a submit
  try {
    const msg = sessionStorage.getItem('lspg-celebrate');
    if (msg) { $('celebrate').innerHTML = `<div class="banner">${App.esc(msg)}</div>`; sessionStorage.removeItem('lspg-celebrate'); }
  } catch (e) { /* ignore */ }

  /* ----- Today ----- */
  const day = D.progress(user.id, 'day', today);
  const lv = D.level(day.pct);
  const report = D.getReport(user.id, today);
  const shift = D.getShift(user.id, today);

  if (!D.isWorkingDay(user.teamId, today) && !shift) {
    $('today').innerHTML = `<p class="muted">Hari ini libur.</p>`;
  } else {
    const next = D.nextLevel(day.pct);
    const nextText = next ? `${lv.label} · ${App.rpK(next.min / 100 * day.target - day.actual)} lagi ke ${next.name}` : lv.label;
    let action;
    if (!shift) {
      action = `<p class="next">Shift belum dimulai</p><button type="button" class="btn block" id="start">Mulai shift</button>`;
    } else if (!shift.end) {
      action = `<p class="next">${report ? nextText : 'Belum ada penjualan'} · shift sejak ${shift.start}</p>
        <a class="btn block" href="laporan.html">Catat penjualan</a>`;
    } else {
      action = `<p class="next">Shift ${shift.start}–${shift.end} selesai</p><a href="laporan.html">Lihat penjualan hari ini</a>`;
    }
    $('today').innerHTML = `
      ${App.ring(day.pct, `<div class="pct">${App.pct(day.pct)}</div><div class="lbl">target hari ini</div>`, `${App.pct(day.pct)} dari target hari ini`)}
      <div class="amount">${App.rp(day.actual)} <span class="muted">dari ${App.rpK(day.target)}</span></div>
      ${action}`;
    const start = $('start');
    if (start) start.addEventListener('click', () => { D.startShift(user.id); location.href = 'laporan.html'; });
  }

  /* ----- Rank & streak ----- */
  const lb = D.leaderboard(user.teamId, 'month', today);
  const me = lb.find(r => r.user.id === user.id);
  const st = D.streak(user.id, today);
  $('rank').innerHTML = `
    <a href="peringkat.html">
      <div class="grow"><b>Peringkat tim</b><span>Bulan ini, berdasarkan % target</span></div>
      <div class="val"><b>#${me ? me.rank : '-'}</b> <span>dari ${lb.length}</span></div>
    </a>
    <div>
      <div class="grow"><b>Streak</b><span>Hari berturut-turut capai 100%</span></div>
      <div class="val"><b>${st} hari</b></div>
    </div>`;

  /* ----- Week & month ----- */
  function progressRow(title, p) {
    return `<div class="progress">
      <div class="top"><span>${title}</span><b>${App.pct(p.pct)}</b></div>
      ${App.bar(p.pct)}
      <div class="small muted" style="margin-top:6px">${App.rpShort(p.actual)} dari ${App.rpShort(p.target)}</div>
    </div>`;
  }
  $('periods').innerHTML =
    progressRow('Minggu ini', D.progress(user.id, 'week', today)) +
    progressRow('Bulan ini', D.progress(user.id, 'month', today));

  App.animateRings();
})();

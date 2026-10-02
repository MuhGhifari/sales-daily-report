(function () {
  'use strict';
  const user = App.init({ roles: ['spg'] });
  if (!user) return;
  const D = Data, today = D.TODAY, $ = id => document.getElementById(id);

  $('hello').textContent = `Halo, ${user.name.split(' ')[0]}`;
  $('sub').textContent = `${App.dateLong(today)} · ${user.store}`;

  // Celebration message left by the report page after a submit
  try {
    const msg = sessionStorage.getItem('lspg-celebrate');
    if (msg) { $('celebrate').innerHTML = `<div class="banner">${App.icon('sparkles')}<span>${App.esc(msg)}</span></div>`; sessionStorage.removeItem('lspg-celebrate'); }
  } catch (e) { /* ignore */ }

  /* ----- Today: big ring ----- */
  const day = D.progress(user.id, 'day', today);
  const lv = D.level(day.pct);
  const report = D.getReport(user.id, today);

  if (!D.isWorkingDay(user.teamId, today)) {
    $('today').innerHTML = `<div class="title">Hari Ini</div><div class="big">${App.icon('coffee')} Hari ini libur</div><p class="muted">Selamat beristirahat!</p>`;
  } else {
    const center = `<div class="pct" style="color:${lv.color}">${App.pct(day.pct)}</div>
      <div class="sub"><b>${App.rp(day.actual)}</b><br>dari ${App.rpK(day.target)}</div>`;
    let goal;
    if (!report) {
      goal = `<p class="goal">Kamu belum mengirim laporan hari ini.</p>
        <a class="btn block" href="laporan.html" style="margin-top:12px">${App.icon('edit')} Isi Laporan Hari Ini</a>`;
    } else {
      const next = D.nextLevel(day.pct);
      goal = next
        ? `<p class="goal">${App.rpK(next.min / 100 * day.target - day.actual)} lagi untuk mencapai ${App.levelBadge(next)}</p>`
        : `<p class="goal">Luar biasa, kamu di level tertinggi hari ini.</p>`;
      goal += `<p class="small" style="margin:8px 0 0"><a href="laporan.html">Ubah laporan hari ini</a></p>`;
    }
    const ladder = D.LEVELS.slice().reverse().filter(l => l.min > 0)
      .map(l => `<span class="${l === lv ? 'on' : ''}">${App.levelIcon(l)}${l.min}%</span>`).join('');
    $('today').innerHTML = `
      <div class="title">Target Hari Ini</div>
      ${App.ring(day.pct, lv.color, center, `${App.pct(day.pct)} dari target hari ini`)}
      <div class="level" style="background:${lv.color}1A;color:${lv.color === '#C9A227' ? '#8A6D00' : lv.color}">${App.levelIcon(lv)}${lv.label}</div>
      ${goal}
      <div class="levels" aria-label="Tingkat level">${ladder}</div>`;
  }

  /* ----- Rank & streak ----- */
  const lb = D.leaderboard(user.teamId, 'month', today);
  const me = lb.find(r => r.user.id === user.id);
  const above = me && lb[me.rank - 2];
  let rankText = '';
  if (me && above) {
    const need = Math.ceil(((above.pct - me.pct) / 100 * me.target + 1) / 1000) * 1000;
    rankText = `<div class="small"><b>${App.rp(need)}</b> lagi untuk naik ke #${above.rank}</div>`;
  } else if (me) {
    rankText = `<div class="small"><b>Peringkat teratas tim</b></div>`;
  }
  $('rank').innerHTML = me ? `${App.icon('trophy', { cls: 'ic' })}<div class="v">#${me.rank}</div>
    <div class="small muted">dari ${lb.length} SPG · bulan ini</div>${rankText}` : '';

  const st = D.streak(user.id, today);
  $('streak').innerHTML = `${App.icon('flame', { cls: 'ic' })}<div class="v">${st} hari</div>
    <div class="small muted">berturut-turut capai 100% target harian</div>
    <div class="small"><b>${st ? 'Pertahankan' : 'Mulai hari ini'}</b></div>`;

  /* ----- Week & month ----- */
  function periodCard(title, p, rangeText) {
    const l = D.level(p.pct);
    const rest = p.remaining <= 0
      ? `<div class="hint">${App.icon('check')} Target tercapai</div>`
      : `<div class="hint">Sisa ${App.rpK(p.remaining)} dalam ${p.restDays} hari kerja, rata-rata ${App.rpK(p.perDay)}/hari</div>`;
    return `<div class="row"><h2 style="margin:0">${title}</h2><span class="lvl">${App.levelIcon(l)}<b>${App.pct(p.pct)}</b></span></div>
      <div class="big" style="margin-top:6px">${App.rp(p.actual)} <span class="small muted">/ ${App.rpK(p.target)}</span></div>
      ${App.bar(p.pct, l.color)}
      <div class="small muted">${rangeText}</div>${rest}`;
  }
  const w = D.progress(user.id, 'week', today);
  $('week').innerHTML = periodCard('Minggu Ini', w, `${App.dateShort(w.from)} – ${App.dateShort(w.to)}`);
  const m = D.progress(user.id, 'month', today);
  const wdDone = D.workingDays(user.teamId, m.from, today).length;
  $('month').innerHTML = periodCard('Bulan Ini', m, `${App.monthName(D.monthKey(today))} · hari kerja ke-${wdDone} dari ${D.workingDaysInMonth(user.teamId, D.monthKey(today))}`);

  App.animateRings();
})();

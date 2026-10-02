(function () {
  'use strict';
  const user = App.init({ roles: ['spg'] });
  if (!user) return;
  const D = Data, $ = id => document.getElementById(id);
  const firstMonth = D.monthKey(D.DATA_START), lastMonth = D.monthKey(D.TODAY);
  let mk = App.param('bulan') || lastMonth;
  if (mk < firstMonth || mk > lastMonth) mk = lastMonth;

  function render() {
    $('month').textContent = App.monthName(mk);
    $('prev').disabled = mk <= firstMonth;
    $('next').disabled = mk >= lastMonth;

    const end = D.monthEnd(mk + '-01') < D.TODAY ? D.monthEnd(mk + '-01') : D.TODAY;
    const days = D.eachDay(mk + '-01', end).reverse();
    let workDays = 0, sent = 0, total = 0;

    $('list').innerHTML = days.map(d => {
      const working = D.isWorkingDay(user.teamId, d);
      const r = D.getReport(user.id, d);
      if (working) workDays++;
      if (r) { sent++; total += r.total; }

      let status, amount = '';
      if (r) {
        const t = D.dailyTarget(user.id, d);
        const p = t ? r.total / t * 100 : 0;
        const lv = D.level(p);
        status = D.isLocked(user.id, d) ? '<span class="pill off">🔒 Terkunci</span>' : '<span class="pill ok">Terkirim</span>';
        amount = `${App.rp(r.total)}<div class="small muted">${t ? `${lv.emoji} ${App.pct(p)}` : r.noSales ? 'Tidak ada penjualan' : ''}</div>`;
      } else if (!working) {
        status = '<span class="pill off">Libur</span>';
      } else {
        status = d === D.TODAY ? '<span class="pill warn">Belum dikirim</span>' : '<span class="pill bad">Tidak lapor</span>';
      }
      const clickable = r || (working && !D.isLocked(user.id, d));
      const tag = clickable ? 'a' : 'div';
      return `<${tag} class="day" ${clickable ? `href="laporan.html?tanggal=${d}"` : ''}>
        <div class="grow"><b>${App.dateShort(d)}</b><div>${status}</div></div>
        <div class="amt">${amount}</div>
      </${tag}>`;
    }).join('');

    $('summary').innerHTML = `<b>${sent}</b> dari ${workDays} hari kerja dilaporkan · Total <b>${App.rp(total)}</b>`;
  }

  $('prev').addEventListener('click', () => { mk = D.addMonths(mk, -1); render(); });
  $('next').addEventListener('click', () => { mk = D.addMonths(mk, 1); render(); });
  render();
})();

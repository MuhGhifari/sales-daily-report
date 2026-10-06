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
    let workDays = 0, sent = 0, total = 0;

    $('list').innerHTML = D.eachDay(mk + '-01', end).reverse().map(d => {
      const working = D.isWorkingDay(user.teamId, d);
      const r = D.getReport(user.id, d);
      if (working) workDays++;
      if (r) { sent++; total += r.total; }

      let status, value = '';
      if (r) {
        const t = D.dailyTarget(user.id, d);
        status = D.isLocked(user.id, d) ? `<span title="Terkunci" aria-label="Terkunci">${App.icon('lock')}</span>` : `<span class="ok" title="Terkirim" aria-label="Terkirim">${App.icon('check')}</span>`;
        value = `<b>${App.rp(r.total)}</b>${t ? `<span>${App.pct(r.total / t * 100)} dari target</span>` : ''}`;
      } else if (!working) {
        status = 'Libur';
      } else {
        status = d === D.TODAY ? '<span class="warn">Belum dikirim</span>' : '<span class="bad">Tidak lapor</span>';
      }
      const clickable = r || (working && !D.isLocked(user.id, d));
      const tag = clickable ? 'a' : 'div';
      return `<${tag} ${clickable ? `href="laporan.html?tanggal=${d}"` : ''}>
        <div class="grow"><b>${App.dateShort(d)}</b><span>${status}</span></div>
        <div class="val">${value}</div>
      </${tag}>`;
    }).join('');

    App.trendChart($('trend'), D.dailySeries([user.id], [user.teamId], mk + '-01', end));
    $('summary').textContent = `${sent} dari ${workDays} hari kerja dilaporkan · Total ${App.rp(total)}`;
  }

  $('prev').addEventListener('click', () => { mk = D.addMonths(mk, -1); render(); });
  $('next').addEventListener('click', () => { mk = D.addMonths(mk, 1); render(); });
  render();
})();

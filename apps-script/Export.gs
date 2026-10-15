/**
 * Daily sales recap per SPG for a date range, exported as a new Google Sheet (same layout as the team's recap):
 * day names over the dates, one row per SPG with that day's sales, OFF on days off, red when a working day has no
 * report, then Total Achievement, Total Target (monthly targets of the months in the range) and Ach, best first.
 * The file is made in the script owner's Drive (folder "Laporan SPG – Export") and shared "anyone with the link
 * can view"; the reply gives its address. Team Leader: own team; Supervisor: teams in their area; Admin: any team or all.
 */
const DAY_NAMES_ = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const MAX_EXPORT_DAYS_ = 62;

function dow_(date) {
  const [y, m, d] = date.split('-').map(Number);
  return new Date(Date.UTC(y, m - 1, d)).getUTCDay();
}

/** The recap as plain data (no Google calls, so it can be tested): { teams, days, offDays, rows }. */
function dailyRecap_(me, req) {
  if (['admin', 'supervisor', 'leader'].indexOf(me.role) < 0) fail_('Kamu tidak punya akses ke laporan ini.', 403);
  const from = req.from, to = req.to;
  if (!isDate_(from) || !isDate_(to) || from > to) fail_('Pilih tanggal mulai dan selesai yang benar.');
  const days = [];
  for (let d = from; d <= to; d = addDays_(d, 1)) {
    days.push(d);
    if (days.length > MAX_EXPORT_DAYS_) fail_('Rentang tanggal maksimal ' + MAX_EXPORT_DAYS_ + ' hari.');
  }
  const allowed = teamIds_(me);
  const teamIds = req.teamId ? [req.teamId] : allowed;
  if (teamIds.some(t => allowed.indexOf(t) < 0)) fail_('Kamu tidak punya akses ke tim ini.', 403);
  const teams = rows_('Teams').filter(t => teamIds.indexOf(t.id) >= 0);
  if (!teams.length) fail_('Belum ada tim.');

  // Working day per team (holidays override the weekly pattern), as in the app
  const working = {};
  teams.forEach(t => {
    const st = settingsFor_(t.id), hol = {};
    rows_('Holidays').filter(h => h.team_id === t.id).forEach(h => { hol[h.date] = !!h.working; });
    working[t.id] = d => (d in hol ? hol[d] : st.workingDays.indexOf(dow_(d)) >= 0);
  });

  const spgs = rows_('Users').filter(u => u.role === 'spg' && u.active && teamIds.indexOf(u.team_id) >= 0);
  const ids = {};
  spgs.forEach(u => { ids[u.id] = true; });
  const amount = {}, noSales = {}, target = {};
  rows_('Sales').forEach(s => {
    if (s.deleted || !ids[s.user_id] || s.date < from || s.date > to) return;
    const k = s.user_id + '|' + s.date;
    amount[k] = (amount[k] || 0) + (s.subtotal || s.qty * s.price || 0);
  });
  rows_('DayReports').forEach(r => { if (r.no_sales && ids[r.user_id]) noSales[r.user_id + '|' + r.date] = true; });
  const months = {};
  days.forEach(d => { months[d.slice(0, 7)] = true; });
  rows_('Targets').forEach(t => { if (ids[t.user_id] && months[t.month]) target[t.user_id] = (target[t.user_id] || 0) + (t.monthly || 0); });

  const today = today_();
  const teamName = {};
  teams.forEach(t => { teamName[t.id] = t.name; });
  const rows = spgs.map(u => {
    let total = 0;
    // Each day: a number (sales), 'OFF' (day off without sales), 'MISSING' (working day, nothing reported) or '' (not yet)
    const cells = days.map(d => {
      const k = u.id + '|' + d;
      if (amount[k]) { total += amount[k]; return amount[k]; }
      if (noSales[k]) return 0;
      if (d > today) return '';
      return working[u.team_id](d) ? 'MISSING' : 'OFF';
    });
    const t = target[u.id] || 0;
    return { name: u.name, team: teamName[u.team_id] || '', cells, total, target: t, ach: t ? total / t : null };
  }).sort((a, b) => (b.ach === null ? -1 : b.ach) - (a.ach === null ? -1 : a.ach) || b.total - a.total || a.name.localeCompare(b.name));

  // A day is marked in the header when it is a day off for every team in the recap
  const offDays = days.map(d => teams.every(t => !working[t.id](d)));
  return { teams, days, offDays, rows };
}

function actExportDaily_(req, me) {
  const r = dailyRecap_(me, req);
  const multi = r.teams.length > 1;
  const label = multi ? 'Semua tim' : 'Tim ' + r.teams[0].name;
  const dmy = d => d.slice(8, 10) + '/' + d.slice(5, 7) + '/' + d.slice(0, 4);
  const ss = SpreadsheetApp.create('Rekap Harian SPG – ' + label + ' – ' + dmy(r.days[0]) + ' s.d. ' + dmy(r.days[r.days.length - 1]));
  ss.setSpreadsheetLocale('id_ID');
  const sh = ss.getSheets()[0];
  sh.setName('Rekap ' + r.days[0].slice(5).replace('-', '.') + '–' + r.days[r.days.length - 1].slice(5).replace('-', '.'));

  const lead = multi ? 2 : 1;                  // Nama NMA (+ Tim)
  const first = lead + 1, n = r.days.length;   // first day column, number of days
  const totalCol = first + n, cols = totalCol + 2;
  const head1 = [], head2 = [];
  for (let i = 0; i < lead; i++) head1.push('');
  head2.push('Nama NMA');
  if (multi) head2.push('Tim');
  r.days.forEach(d => { head1.push(DAY_NAMES_[dow_(d)]); head2.push(dmy(d)); });
  head1.push('', '', '');
  head2.push('Total Achievement', 'TOTAL TARGET', 'Ach');
  const body = r.rows.map(x => {
    const line = [x.name];
    if (multi) line.push(x.team);
    x.cells.forEach(c => line.push(c === 'MISSING' ? '' : c));
    line.push(x.total, x.target || '', x.ach === null ? '' : x.ach);
    return line;
  });
  sh.getRange(1, 1, 2, cols).setValues([head1, head2]);
  if (body.length) sh.getRange(3, 1, body.length, cols).setValues(body);

  // Look: blue header, day names above, OFF in yellow, missing reports in red, totals and target shaded
  sh.getRange(1, first, 1, n).setHorizontalAlignment('center');
  r.offDays.forEach((off, i) => { if (off) sh.getRange(1, first + i).setBackground('#ffc000'); });
  sh.getRange(2, 1, 1, cols).setBackground('#4472c4').setFontColor('#ffffff').setFontWeight('bold').setHorizontalAlignment('center').setVerticalAlignment('middle').setWrap(true);
  sh.getRange(2, totalCol).setBackground('#ffc000').setFontColor('#000000');
  sh.getRange(2, totalCol + 1).setBackground('#f4b183').setFontColor('#000000');
  sh.getRange(2, totalCol + 2).setBackground('#ffffff').setFontColor('#000000');
  if (body.length) {
    const rowsN = body.length;
    sh.getRange(3, 1, rowsN, lead).setFontWeight('bold');
    sh.getRange(3, first, rowsN, n + 2).setNumberFormat('#,##0').setHorizontalAlignment('right');
    sh.getRange(3, totalCol, rowsN, 1).setBackground('#ddebf7').setFontWeight('bold');
    sh.getRange(3, totalCol + 1, rowsN, 1).setBackground('#d9d9d9').setFontWeight('bold');
    sh.getRange(3, totalCol + 2, rowsN, 1).setNumberFormat('0%').setHorizontalAlignment('right');
    r.rows.forEach((x, i) => x.cells.forEach((c, j) => {
      if (c === 'OFF') sh.getRange(3 + i, first + j).setBackground('#ffff00').setHorizontalAlignment('left');
      else if (c === 'MISSING') sh.getRange(3 + i, first + j).setBackground('#ff0000');
    }));
    sh.getRange(2, 1, rowsN + 1, cols).setBorder(true, true, true, true, true, true, '#000000', SpreadsheetApp.BorderStyle.SOLID);
    sh.getRange(2, 1, rowsN + 1, cols).createFilter();
  }
  sh.setFrozenRows(2);
  sh.setFrozenColumns(lead);
  sh.setColumnWidth(1, 170);
  if (multi) sh.setColumnWidth(2, 130);
  for (let c = first; c < totalCol; c++) sh.setColumnWidth(c, 95);
  sh.setColumnWidths(totalCol, 2, 120);
  sh.setColumnWidth(totalCol + 2, 55);
  sh.setRowHeight(2, 36);
  SpreadsheetApp.flush();

  const file = DriveApp.getFileById(ss.getId());
  file.moveTo(exportFolder_());
  file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
  return { url: ss.getUrl(), name: ss.getName(), spgs: r.rows.length, days: r.days.length };
}

function exportFolder_() {
  const props = PropertiesService.getScriptProperties();
  const id = props.getProperty('EXPORT_FOLDER_ID');
  if (id) return DriveApp.getFolderById(id);
  const folder = DriveApp.createFolder('Laporan SPG – Export');
  props.setProperty('EXPORT_FOLDER_ID', folder.getId());
  return folder;
}

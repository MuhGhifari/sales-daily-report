/* Shared page helpers: login guard, top bar, navigation, formatting. */
(function () {
  'use strict';

  const root = document.body.dataset.root || '';

  const ROLE_LABEL = { spg: 'SPG', leader: 'Team Leader', supervisor: 'Supervisor', admin: 'Admin' };
  const HOME = {
    spg: 'spg/beranda.html',
    leader: 'leader/dashboard.html',
    supervisor: 'supervisor/dashboard.html',
    admin: 'admin/produk.html',
  };
  const NAV = {
    spg: [['spg/beranda.html', 'home', 'Beranda'], ['spg/laporan.html', 'edit', 'Isi Laporan'], ['spg/peringkat.html', 'trophy', 'Peringkat'], ['spg/riwayat.html', 'calendar', 'Riwayat']],
    leader: [['leader/dashboard.html', 'Dashboard'], ['leader/laporan.html', 'Laporan'], ['leader/target.html', 'Target'], ['leader/tim.html', 'Kelola Tim'], ['leader/pengaturan.html', 'Pengaturan']],
    supervisor: [['supervisor/dashboard.html', 'Dashboard Area'], ['leader/dashboard.html', 'Dashboard Tim'], ['leader/laporan.html', 'Laporan'], ['leader/target.html', 'Target'], ['leader/tim.html', 'Kelola Tim'], ['leader/pengaturan.html', 'Pengaturan']],
    admin: [['admin/produk.html', 'Produk']],
  };

  /* ---------- Icons (Lucide, ISC license — inline SVG, inherit text color) ---------- */
  const ICONS = {
    home: '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
    edit: '<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.4 2.6a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4Z"/>',
    trophy: '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.7V17c0 .6-.5 1-1 1.2C7.9 18.8 7 20.2 7 22"/><path d="M14 14.7V17c0 .6.5 1 1 1.2 1.1.6 2 2 2 3.8"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
    calendar: '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    chart: '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
    flame: '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.4-.5-2-1-3-1.1-2.1-.2-4.1 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.2.4-2.3 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
    award: '<circle cx="12" cy="8" r="6"/><path d="M15.5 12.9 17 22l-5-3-5 3 1.5-9.1"/>',
    gem: '<path d="M6 3h12l4 6-10 13L2 9Z"/><path d="M11 3 8 9l4 13 4-13-3-6"/><path d="M2 9h20"/>',
    target: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
    crown: '<path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7Z"/><path d="M5 20h14"/>',
    up: '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
    down: '<path d="m22 17-8.5-8.5-5 5L2 7"/><path d="M16 17h6v-6"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    lock: '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    unlock: '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    plus: '<path d="M5 12h14M12 5v14"/>',
    left: '<path d="m15 18-6-6 6-6"/>',
    right: '<path d="m9 18 6-6-6-6"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    message: '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    sparkles: '<path d="M9.9 15.5a2 2 0 0 0-1.4-1.4l-6.1-1.6a.5.5 0 0 1 0-1l6.1-1.6a2 2 0 0 0 1.4-1.4l1.6-6.1a.5.5 0 0 1 1 0l1.6 6.1a2 2 0 0 0 1.4 1.4l6.1 1.6a.5.5 0 0 1 0 1l-6.1 1.6a2 2 0 0 0-1.4 1.4l-1.6 6.1a.5.5 0 0 1-1 0Z"/>',
    coffee: '<path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><path d="M6 2v2M10 2v2M14 2v2"/>',
  };
  function icon(name, opts) {
    opts = opts || {};
    const style = opts.color ? ` style="color:${opts.color}"` : '';
    return `<svg class="icon${opts.cls ? ' ' + opts.cls : ''}"${style} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ICONS[name] || ''}</svg>`;
  }
  const levelIcon = (lv, cls) => icon(lv.icon, { color: lv.iconColor, cls });
  const levelBadge = lv => `<span class="lvl">${levelIcon(lv)}${lv.name}</span>`;

  /* ---------- Formatting ---------- */
  const nf = new Intl.NumberFormat('id-ID');
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const num = n => nf.format(Math.round(n || 0));
  const rp = n => 'Rp ' + num(n);
  const rpK = n => rp(Math.round((n || 0) / 1000) * 1000); // targets rounded to the nearest thousand
  function rpShort(n) {
    if (n >= 1e9) return 'Rp ' + (n / 1e9).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' M';
    if (n >= 1e6) return 'Rp ' + (n / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
    return rp(n);
  }
  const pct = p => Math.round(p || 0) + '%';
  const pct1 = p => (p || 0).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
  const fmt = opts => { const f = new Intl.DateTimeFormat('id-ID', opts); return s => f.format(Data.parse(s)); };
  const dateLong = fmt({ weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const dateShort = fmt({ weekday: 'short', day: 'numeric', month: 'short' });
  const dateMid = fmt({ day: 'numeric', month: 'short', year: 'numeric' });
  const monthName = mk => fmt({ month: 'long', year: 'numeric' })(mk + '-01');
  const param = name => new URLSearchParams(location.search).get(name);
  const parseNum = s => +String(s || '').replace(/\D/g, '') || 0;

  function lsGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
  function lsSet(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* ignore */ } }

  /* ---------- Team for leader / supervisor pages ---------- */
  function teamId(user) {
    if (user.role === 'leader') return user.teamId;
    const list = Data.teams();
    let id = param('tim') || lsGet('lspg-team') || list[0].id;
    if (!list.some(t => t.id === id)) id = list[0].id;
    lsSet('lspg-team', id);
    return id;
  }

  /* ---------- Page chrome ---------- */
  function init(opts) {
    const user = Data.currentUser();
    if (!user || (opts.roles && !opts.roles.includes(user.role))) {
      location.replace(root + 'index.html');
      return null;
    }
    const here = location.pathname;
    const isOn = href => here.endsWith('/' + href);
    const t = user.teamId ? Data.team(user.teamId) : null;
    const where = user.role === 'supervisor' ? 'Area ' + Data.area(user.areaId).name : t ? 'Tim ' + t.name : '';

    const top = document.createElement('header');
    top.className = 'topbar';
    top.innerHTML = `
      <div class="topbar-inner">
        <a class="brand" href="${root + HOME[user.role]}"><span class="brand-dot">${icon('chart')}</span>Laporan SPG</a>
        <div class="user">
          <div class="who"><b>${esc(user.name)}</b><span>${ROLE_LABEL[user.role]}${where ? ' · ' + esc(where) : ''}</span></div>
          <button class="logout" type="button" aria-label="Keluar">${icon('logout')}<span>Keluar</span></button>
        </div>
      </div>`;
    top.querySelector('.logout').addEventListener('click', () => { Data.logout(); location.href = root + 'index.html'; });
    document.body.prepend(top);

    const items = NAV[user.role];
    if (user.role === 'spg') {
      document.body.classList.add('spg');
      const nav = document.createElement('nav');
      nav.className = 'bottomnav';
      nav.innerHTML = items.map(([href, ic, label]) =>
        `<a href="${root + href}" class="${isOn(href) ? 'on' : ''}" ${isOn(href) ? 'aria-current="page"' : ''}>${icon(ic, { cls: 'ic' })}${label}</a>`).join('');
      document.body.append(nav);
    } else {
      const nav = document.createElement('nav');
      nav.className = 'subnav';
      nav.innerHTML = `<div class="subnav-inner">${items.map(([href, label]) =>
        `<a href="${root + href}" class="${isOn(href) ? 'on' : ''}">${label}</a>`).join('')}</div>`;
      top.after(nav);
    }

    if (opts.teamPicker && user.role === 'supervisor') {
      const current = teamId(user);
      const box = document.createElement('div');
      box.className = 'team-pick';
      box.innerHTML = `<label for="teamPick">Tim</label>
        <select id="teamPick" class="input">${Data.teams().map(x =>
          `<option value="${x.id}" ${x.id === current ? 'selected' : ''}>${esc(x.name)}</option>`).join('')}</select>`;
      box.querySelector('select').addEventListener('change', e => {
        lsSet('lspg-team', e.target.value);
        location.search = '?tim=' + e.target.value;
      });
      document.querySelector('main').prepend(box);
    }
    return user;
  }

  function toast(msg) {
    let el = document.querySelector('.toast');
    if (!el) { el = document.createElement('div'); el.className = 'toast'; el.setAttribute('role', 'status'); document.body.append(el); }
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2400);
  }

  /* ---------- Big progress ring (SVG) ---------- */
  function ring(p, color, centerHtml, label) {
    const r = 92, c = 2 * Math.PI * r;
    const lap1 = Math.min(p, 100) / 100;
    const lap2 = Math.min(Math.max(p - 100, 0), 100) / 100;
    const arc = (frac, extra) => frac > 0
      ? `<circle class="arc" cx="110" cy="110" r="${r}" stroke="${color}" stroke-width="18" stroke-dasharray="${c}" stroke-dashoffset="${c}" data-off="${c * (1 - frac)}" ${extra || ''}/>`
      : '';
    return `<div class="ring" role="img" aria-label="${esc(label || pct(p))}">
      <svg viewBox="0 0 220 220" aria-hidden="true">
        <circle cx="110" cy="110" r="${r}" stroke="#E9EEF9" stroke-width="18" fill="none"/>
        ${arc(lap1)}${arc(lap2, 'style="filter:brightness(.6)"')}
      </svg>
      <div class="center">${centerHtml}</div>
    </div>`;
  }
  function animateRings() {
    requestAnimationFrame(() => requestAnimationFrame(() => {
      document.querySelectorAll('.ring .arc').forEach(a => { a.style.strokeDashoffset = a.dataset.off; });
    }));
  }

  function bar(p, color) {
    return `<div class="bar"><span style="width:${Math.min(Math.max(p, 0), 100)}%;background:${color || ''}"></span></div>`;
  }

  function downloadCsv(filename, rows) {
    const csv = rows.map(r => r.map(v => {
      const s = String(v == null ? '' : v);
      return /[",\n;]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }).join(',')).join('\n');
    const url = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    const a = Object.assign(document.createElement('a'), { href: url, download: filename });
    document.body.append(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  window.App = {
    root, HOME, ROLE_LABEL, init, teamId, toast, ring, animateRings, bar, downloadCsv, icon, levelIcon, levelBadge,
    esc, num, rp, rpK, rpShort, pct, pct1, dateLong, dateShort, dateMid, monthName, param, parseNum,
    home: user => root + HOME[user.role],
  };
})();

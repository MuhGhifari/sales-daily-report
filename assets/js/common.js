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
    spg: [['spg/beranda.html', '🏠', 'Beranda'], ['spg/laporan.html', '✏️', 'Isi Laporan'], ['spg/peringkat.html', '🏆', 'Peringkat'], ['spg/riwayat.html', '📅', 'Riwayat']],
    leader: [['leader/dashboard.html', 'Dashboard'], ['leader/laporan.html', 'Laporan'], ['leader/target.html', 'Target'], ['leader/tim.html', 'Kelola Tim'], ['leader/pengaturan.html', 'Pengaturan']],
    supervisor: [['supervisor/dashboard.html', 'Dashboard Area'], ['leader/dashboard.html', 'Dashboard Tim'], ['leader/laporan.html', 'Laporan'], ['leader/target.html', 'Target'], ['leader/tim.html', 'Kelola Tim'], ['leader/pengaturan.html', 'Pengaturan']],
    admin: [['admin/produk.html', 'Produk']],
  };

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
        <a class="brand" href="${root + HOME[user.role]}"><span class="brand-dot">📊</span>Laporan SPG</a>
        <div class="user">
          <div class="who"><b>${esc(user.name)}</b><span>${ROLE_LABEL[user.role]}${where ? ' · ' + esc(where) : ''}</span></div>
          <button class="logout" type="button">Keluar</button>
        </div>
      </div>`;
    top.querySelector('.logout').addEventListener('click', () => { Data.logout(); location.href = root + 'index.html'; });
    document.body.prepend(top);

    const items = NAV[user.role];
    if (user.role === 'spg') {
      document.body.classList.add('spg');
      const nav = document.createElement('nav');
      nav.className = 'bottomnav';
      nav.innerHTML = items.map(([href, icon, label]) =>
        `<a href="${root + href}" class="${isOn(href) ? 'on' : ''}"><span class="ic">${icon}</span>${label}</a>`).join('');
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
    root, HOME, ROLE_LABEL, init, teamId, toast, ring, animateRings, bar, downloadCsv,
    esc, num, rp, rpK, rpShort, pct, pct1, dateLong, dateShort, dateMid, monthName, param, parseNum,
    home: user => root + HOME[user.role],
  };
})();

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? html_escape($title) . ' — ' : '' ?>e-Recruitment RPG</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
<style>
:root {
  --bg: #f5f7f4; --surface: #ffffff; --surface-2: #ecefe9; --surface-3: #e3e7dd;
  --border: #d6dbcf; --border-strong: #c3cabb;
  --text: #1b211c; --text-muted: #59635b; --text-faint: #8a938a;
  --accent: #1f6f5c; --accent-ink: #124034; --accent-soft: #e3f0ea; --accent-contrast: #ffffff;
  --good: #2f7d4f; --good-soft: #e2f0e5;
  --warn: #8f6410; --warn-soft: #f6ecd6;
  --crit: #b23b3b; --crit-soft: #f6e1df;
  --info: #3a6ea5; --info-soft: #e2ecf5;
  --hold: #7c6316; --hold-soft: #f3ecd3;
  --talent: #6b4fa0; --talent-soft: #ece4f6;
  --shadow-sm: 0 1px 2px rgba(20,30,25,.06);
  --shadow: 0 1px 2px rgba(20,30,25,.06), 0 10px 26px rgba(20,30,25,.07);
  --radius: 10px;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #111512; --surface: #191d18; --surface-2: #20251f; --surface-3: #2a3028;
    --border: #333a32; --border-strong: #414a3f;
    --text: #e7ebe4; --text-muted: #9ba79c; --text-faint: #717d72;
    --accent: #4bb89d; --accent-ink: #bfeadd; --accent-soft: #183129; --accent-contrast: #07130f;
    --good: #5bb47e; --good-soft: #16301f;
    --warn: #d0a04e; --warn-soft: #332a16;
    --crit: #e08585; --crit-soft: #3a201f;
    --info: #7aa9d6; --info-soft: #182a38;
    --hold: #d8b866; --hold-soft: #322a15;
    --talent: #b6a0e0; --talent-soft: #241c33;
    --shadow-sm: 0 1px 2px rgba(0,0,0,.3);
    --shadow: 0 1px 2px rgba(0,0,0,.3), 0 12px 32px rgba(0,0,0,.38);
  }
}
:root[data-theme="dark"] {
  --bg: #111512; --surface: #191d18; --surface-2: #20251f; --surface-3: #2a3028;
  --border: #333a32; --border-strong: #414a3f;
  --text: #e7ebe4; --text-muted: #9ba79c; --text-faint: #717d72;
  --accent: #4bb89d; --accent-ink: #bfeadd; --accent-soft: #183129; --accent-contrast: #07130f;
  --good: #5bb47e; --good-soft: #16301f;
  --warn: #d0a04e; --warn-soft: #332a16;
  --crit: #e08585; --crit-soft: #3a201f;
  --info: #7aa9d6; --info-soft: #182a38;
  --hold: #d8b866; --hold-soft: #322a15;
  --talent: #b6a0e0; --talent-soft: #241c33;
  --shadow-sm: 0 1px 2px rgba(0,0,0,.3);
  --shadow: 0 1px 2px rgba(0,0,0,.3), 0 12px 32px rgba(0,0,0,.38);
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
h1, h2, h3, h4 {
  font-family: "Archivo", system-ui, sans-serif;
  color: var(--text);
  margin: 0 0 8px;
  line-height: 1.25;
}
h1 { font-size: 22px; font-weight: 700; }
h2 { font-size: 16px; font-weight: 600; }
h3 { font-size: 14px; font-weight: 600; }
p { margin: 0 0 10px; }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
.mono { font-family: "IBM Plex Mono", monospace; font-variant-numeric: tabular-nums; }
.eyebrow {
  font-family: "Archivo", sans-serif;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--text-faint);
}
.muted { color: var(--text-muted); font-size: 13px; }
.faint { color: var(--text-faint); }
code {
  font-family: "IBM Plex Mono", monospace;
  background: var(--surface-2);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12.5px;
}

/* Base Form Controls */
label { display: block; margin: 12px 0 4px; font-weight: 600; font-size: 13px; color: var(--text); }
input[type=text], input[type=password], input[type=email], input[type=number], input[type=date], input[type=datetime-local], select, textarea {
  width: 100%;
  padding: 8px 11px;
  border: 1px solid var(--border-strong);
  border-radius: 7px;
  font-size: 13.5px;
  font-family: inherit;
  background: var(--surface);
  color: var(--text);
  transition: border-color .15s, box-shadow .15s;
}
input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 20%, transparent);
}
textarea { resize: vertical; min-height: 70px; }

/* Buttons */
button, .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 14px;
  border: 1px solid transparent;
  border-radius: 7px;
  background: var(--accent);
  color: var(--accent-contrast);
  font-size: 13px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  text-decoration: none !important;
  transition: background-color .15s, opacity .15s, transform .05s;
}
button:hover, .btn:hover { background: var(--accent-ink); opacity: .96; }
button:active, .btn:active { transform: scale(0.99); }
.btn-sm, .btn--sm { padding: 4px 10px; font-size: 12px; border-radius: 6px; }
.btn-ghost, .btn--ghost {
  background: var(--surface);
  color: var(--text);
  border: 1px solid var(--border);
}
.btn-ghost:hover, .btn--ghost:hover { background: var(--surface-2); border-color: var(--border-strong); }
.btn-primary, .btn--primary { background: var(--accent); color: var(--accent-contrast); }
.btn-danger, .btn--danger { background: var(--crit); color: #fff; }
.btn-danger:hover, .btn--danger:hover { background: #8e2828; }
.btn[disabled], button[disabled] { opacity: .5; cursor: not-allowed; }

/* Card & Surface */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px 24px;
  box-shadow: var(--shadow-sm);
}

/* Badges & Tags */
.tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 999px;
  font-family: "Archivo", sans-serif;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.35;
  white-space: nowrap;
}
.tag.on, .tag.lulus, .tag.diterima, .tag--good { background: var(--good-soft); color: var(--good); }
.tag.off, .tag.tidak_lulus, .tag.ditolak, .tag.batal, .tag--crit { background: var(--crit-soft); color: var(--crit); }
.tag.warn, .tag.nego, .tag.review, .tag--warn { background: var(--warn-soft); color: var(--warn); }
.tag.info, .tag--info { background: var(--info-soft); color: var(--info); }
.tag.hold, .tag--hold { background: var(--hold-soft); color: var(--hold); }
.tag.talent, .tag--talent { background: var(--talent-soft); color: var(--talent); }
.tag.accent, .tag--accent { background: var(--accent-soft); color: var(--accent-ink); }

/* Alerts / Flash */
.flash {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-size: 13.5px;
  font-weight: 500;
}
.flash.err {
  background: var(--crit-soft);
  border: 1px solid color-mix(in srgb, var(--crit) 30%, transparent);
  color: var(--crit);
}
.flash.ok {
  background: var(--good-soft);
  border: 1px solid color-mix(in srgb, var(--good) 30%, transparent);
  color: var(--good);
}

/* Tables */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  margin: 10px 0;
}
th, td {
  text-align: left;
  padding: 10px 12px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
th {
  font-family: "Archivo", sans-serif;
  color: var(--text-faint);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 10.5px;
  letter-spacing: .06em;
  background: var(--surface-2);
}
tr:hover td { background-color: color-mix(in srgb, var(--surface-2) 40%, transparent); }

/* Dialog / Modal */
dialog {
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: var(--shadow);
  background: var(--surface);
  color: var(--text);
  padding: 24px;
  max-width: 540px;
  width: 92%;
}
dialog::backdrop {
  background: rgba(12, 18, 14, 0.45);
  backdrop-filter: blur(2px);
}

/* Layout Shell */
#app {
  display: grid;
  grid-template-columns: 240px 1fr;
  min-height: 100vh;
}
.sidebar {
  background: var(--surface);
  border-right: 1px solid var(--border);
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  padding: 18px 14px;
  display: flex;
  flex-direction: column;
  z-index: 40;
}
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 6px 16px;
  border-bottom: 1px solid var(--border);
  text-decoration: none !important;
}
.brand-mark {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--accent);
  color: var(--accent-contrast);
  display: grid;
  place-items: center;
  font-family: "Archivo", sans-serif;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: .02em;
  flex: none;
}
.brand-name {
  font-family: "Archivo", sans-serif;
  font-weight: 700;
  font-size: 14px;
  color: var(--text);
  line-height: 1.2;
}
.brand-sub {
  font-size: 10.5px;
  color: var(--text-faint);
  letter-spacing: .03em;
}
.nav-group {
  margin-top: 16px;
}
.nav-group > .eyebrow {
  padding: 0 8px 6px;
  display: block;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none !important;
  transition: all .15s ease;
  margin-bottom: 2px;
}
.nav-item:hover {
  background: var(--surface-2);
  color: var(--text);
}
.nav-item.active {
  background: var(--accent-soft);
  color: var(--accent-ink);
  font-weight: 600;
}
.nav-item svg {
  width: 17px;
  height: 17px;
  flex: none;
  opacity: .85;
}
.nav-item.active svg {
  opacity: 1;
  stroke: var(--accent);
}
.sidebar-footer {
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid var(--border);
}
.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 4px;
}
.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--surface-3);
  color: var(--text);
  font-weight: 700;
  font-size: 12px;
  display: grid;
  place-items: center;
  flex: none;
}
.user-info {
  flex: 1;
  min-width: 0;
  line-height: 1.25;
}
.user-name {
  font-weight: 600;
  font-size: 12.5px;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.user-role {
  font-size: 10.5px;
  color: var(--text-faint);
  font-family: "Archivo", sans-serif;
  text-transform: uppercase;
}

/* Main Area & Sticky Topbar */
.main {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  background: color-mix(in srgb, var(--surface) 92%, transparent);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 24px;
}
.topbar-title {
  font-family: "Archivo", sans-serif;
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}
.topbar-spacer { flex: 1; }
.env-pill {
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--warn);
  background: var(--warn-soft);
  border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);
  padding: 3px 8px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.view {
  padding: 24px 28px;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}
.view.wide {
  max-width: none;
}

/* Public / Solo wrapper (for login / form lamar) */
.wrap-public {
  max-width: 740px;
  margin: 5vh auto;
  padding: 0 20px;
}
.wrap-public.narrow {
  max-width: 440px;
}

@media (max-width: 880px) {
  #app { grid-template-columns: 1fr; }
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    transform: translateX(-100%);
    transition: transform .2s ease;
    box-shadow: var(--shadow);
  }
  .sidebar.open { transform: none; }
  .menu-btn { display: inline-flex !important; }
}
.menu-btn { display: none; }
</style>
</head>
<body>

<?php if ($this->session->userdata('logged_in')):
    $au = (array) $this->session->userdata('auth_user');
    $seg1 = $this->uri->segment(1);
    $initials = strtoupper(substr($au['nama'] ?? ($au['username'] ?? 'U'), 0, 2));
?>
<div id="app">
  <!-- Sidebar -->
  <aside class="sidebar" id="appSidebar">
    <a href="<?= site_url('dashboard') ?>" class="brand">
      <div class="brand-mark">RPG</div>
      <div>
        <div class="brand-name">e-Recruitment</div>
        <div class="brand-sub">Ratu Pertiwi Group</div>
      </div>
    </a>

    <!-- Nav Group: Operasional -->
    <div class="nav-group">
      <span class="eyebrow">Operasional</span>
      <a href="<?= site_url('dashboard') ?>" class="nav-item <?= in_array($seg1, array('', 'dashboard')) ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="<?= site_url('requisitions') ?>" class="nav-item <?= in_array($seg1, array('requisitions', 'pipeline')) ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>MPR & Pipeline</span>
      </a>
    </div>

    <!-- Nav Group: Intake & Pelamar -->
    <div class="nav-group">
      <span class="eyebrow">Intake & Pelamar</span>
      <a href="<?= site_url('postings') ?>" class="nav-item <?= $seg1 === 'postings' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        <span>Link Form Publik</span>
      </a>
      <a href="<?= site_url('manual') ?>" class="nav-item <?= $seg1 === 'manual' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Entry Manual</span>
      </a>
      <a href="<?= site_url('import') ?>" class="nav-item <?= $seg1 === 'import' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        <span>Import Portal</span>
      </a>
      <a href="<?= site_url('documents') ?>" class="nav-item <?= $seg1 === 'documents' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>Berkas & PDP</span>
      </a>
    </div>

    <!-- Nav Group: Konfigurasi -->
    <div class="nav-group">
      <span class="eyebrow">Konfigurasi</span>
      <a href="<?= site_url('users') ?>" class="nav-item <?= $seg1 === 'users' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Manajemen User</span>
      </a>
      <a href="<?= site_url('master') ?>" class="nav-item <?= $seg1 === 'master' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
        <span>Master Data</span>
      </a>
      <a href="<?= site_url('flowbuilder') ?>" class="nav-item <?= $seg1 === 'flowbuilder' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        <span>Flow Builder</span>
      </a>
    </div>

    <!-- Footer Profile -->
    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar"><?= html_escape($initials) ?></div>
        <div class="user-info">
          <div class="user-name" title="<?= html_escape($au['nama'] ?? '') ?>"><?= html_escape($au['nama'] ?? '') ?></div>
          <div class="user-role"><?= html_escape($au['kode_role'] ?? '') ?></div>
        </div>
        <a href="<?= site_url('auth/logout') ?>" class="btn-ghost btn-sm" title="Keluar dari sesi" style="padding:4px 8px">
          <svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main Container -->
  <div class="main">
    <header class="topbar">
      <button class="btn btn-sm btn-ghost menu-btn" onclick="document.getElementById('appSidebar').classList.toggle('open')" aria-label="Menu">
        <svg style="width:16px; height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div class="topbar-title"><?= isset($title) ? html_escape($title) : 'e-Recruitment RPG' ?></div>
      <div class="topbar-spacer"></div>
      <div class="env-pill">
        <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:currentColor"></span>
        DEV SQL 2008 R2
      </div>
      <a href="<?= site_url('requisitions/create') ?>" class="btn btn-sm btn-primary">
        <svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        <span>+ Buat MPR</span>
      </a>
    </header>

    <main class="view<?= ! empty($wide) ? ' wide' : '' ?>">
      <?php if ($this->session->flashdata('error')): ?>
        <div class="flash err">
          <svg style="width:16px; height:16px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <span><?= html_escape($this->session->flashdata('error')) ?></span>
        </div>
      <?php endif; ?>
      <?php if ($this->session->flashdata('ok')): ?>
        <div class="flash ok">
          <svg style="width:16px; height:16px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          <span><?= html_escape($this->session->flashdata('ok')) ?></span>
        </div>
      <?php endif; ?>

      <?php $this->load->view($_content); ?>
    </main>
  </div>
</div>
<?php else: ?>
  <!-- Publik / Guest View (Login / Form Pelamar) -->
  <div class="wrap-public<?= !empty($narrow) ? ' narrow' : '' ?>">
    <?php if ($this->session->flashdata('error')): ?>
      <div class="flash err">
        <svg style="width:16px; height:16px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span><?= html_escape($this->session->flashdata('error')) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('ok')): ?>
      <div class="flash ok">
        <svg style="width:16px; height:16px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        <span><?= html_escape($this->session->flashdata('ok')) ?></span>
      </div>
    <?php endif; ?>

    <?php $this->load->view($_content); ?>
  </div>
<?php endif; ?>

</body>
</html>

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Layout View: layouts/main.php -- Kerangka Template Utama Aplikasi E-Recruitment RPG
 *
 * Fungsi:
 * - Menyediakan struktur tata letak induk HTML5 (Header, Top Navigation Bar, Kontainer Konten, Footer).
 * - Menangani styling global berbasis design tokens CSS, integrasi Google Fonts, dan utilitas responsif.
 * - Merender menu navigasi dinamis berlandaskan hak akses (RBAC) pengguna aktif.
 */
?>
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
  white-space: normal;
  word-break: break-word;
  max-width: 100%;
}
.tag.on, .tag.lulus, .tag.diterima, .tag--good { background: var(--good-soft); color: var(--good); }
.tag.off, .tag.tidak_lulus, .tag.ditolak, .tag.batal, .tag--crit { background: var(--crit-soft); color: var(--crit); }
.tag.warn, .tag.nego, .tag.review, .tag--warn { background: var(--warn-soft); color: var(--warn); }
.tag.info, .tag--info { background: var(--info-soft); color: var(--info); }
.tag.hold, .tag--hold { background: var(--hold-soft); color: var(--hold); }
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
.flash.info {
  background: var(--info-soft);
  border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);
  color: var(--info);
}
.flash.warn {
  background: var(--warn-soft);
  border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);
  color: var(--warn);
}

/* Global Floating Toast */
#global-toast-container {
  position: fixed; top: 20px; right: 20px; z-index: 99999;
  display: flex; flex-direction: column; gap: 8px;
  pointer-events: none; max-width: 420px; width: calc(100% - 40px);
}
.g-toast {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 18px; border-radius: 10px;
  font-size: 13.5px; font-weight: 500; pointer-events: auto;
  box-shadow: 0 6px 24px rgba(0,0,0,.15);
  animation: toastIn .3s ease forwards;
  opacity: 0; transform: translateX(30px);
}
.g-toast.ok   { background: var(--good-soft); border: 1px solid color-mix(in srgb, var(--good) 30%, transparent); color: var(--good); }
.g-toast.err  { background: var(--crit-soft); border: 1px solid color-mix(in srgb, var(--crit) 30%, transparent); color: var(--crit); }
.g-toast.info { background: var(--info-soft); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent); color: var(--info); }
.g-toast.warn { background: var(--warn-soft); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent); color: var(--warn); }
.g-toast.removing { animation: toastOut .25s ease forwards; }
.g-toast .toast-icon { flex: none; width: 18px; height: 18px; }
.g-toast .toast-msg { flex: 1; line-height: 1.4; }
.g-toast .toast-close {
  flex: none; background: none; border: none; color: inherit; opacity: .5;
  cursor: pointer; padding: 2px; font-size: 16px; line-height: 1;
}
.g-toast .toast-close:hover { opacity: 1; }
@keyframes toastIn { to { opacity: 1; transform: translateX(0); } }
@keyframes toastOut { to { opacity: 0; transform: translateX(30px); } }

/* Global Anti-Overflow & Responsiveness */
* { box-sizing: border-box; }
html, body {
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  max-width: 100vw;
}

/* Force All Tables to Fit 1 Screen without horizontal scroll */
table {
  width: 100% !important;
  max-width: 100% !important;
  border-collapse: collapse;
  font-size: 13px;
  margin: 10px 0;
  table-layout: auto;
}
th, td {
  text-align: left;
  padding: 8px 10px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
  word-break: break-word;
  overflow-wrap: break-word;
}
th {
  font-family: "Archivo", sans-serif;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: .05em;
  background: var(--surface-2);
  vertical-align: middle;
}
.nowrap, th.nowrap, td.nowrap {
  white-space: nowrap !important;
}
tr:hover td { background-color: color-mix(in srgb, var(--surface-2) 40%, transparent); }

/* Responsive tables container */
div[style*="overflow-x:auto"],
div[style*="overflow-x: auto"],
.table-responsive-fit {
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch;
  width: 100% !important;
  max-width: 100% !important;
}

@media (max-width: 768px) {
  table {
    display: block;
    width: 100%;
  }
  thead {
    display: none;
  }
  tr {
    display: block;
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 12px;
    background: var(--surface);
    padding: 10px 12px;
  }
  td {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--surface-2);
    padding: 7px 0;
    font-size: 12.5px;
  }
  td:last-child {
    border-bottom: none;
  }
}

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
  height: 100vh;
  max-height: 100vh;
  overflow: hidden;
}
.sidebar {
  background: var(--surface);
  border-right: 1px solid var(--border);
  height: 100vh;
  max-height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 18px 14px;
  display: flex;
  flex-direction: column;
  z-index: 40;
  flex-shrink: 0;
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
.nav-item-collapsible {
  margin-bottom: 2px;
}
.nav-chevron-btn {
  background: none;
  border: none;
  padding: 4px 6px;
  color: inherit;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  opacity: .75;
  transition: all .15s ease;
}
.nav-chevron-btn:hover {
  opacity: 1;
  background: color-mix(in srgb, currentColor 12%, transparent);
}
.nav-chevron-icon {
  width: 13px;
  height: 13px;
  transition: transform .2s ease;
}
.nav-item-collapsible.open .nav-chevron-icon {
  transform: rotate(180deg);
}
.nav-submenu {
  margin-left: 18px;
  padding-left: 8px;
  border-left: 2px solid var(--border);
  margin-top: 2px;
  margin-bottom: 6px;
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.nav-submenu.collapsed {
  display: none !important;
}
.nav-subitem {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 5px 8px;
  border-radius: 6px;
  color: var(--text-muted);
  font-size: 12px;
  text-decoration: none !important;
  transition: all .15s ease;
}
.nav-subitem:hover {
  background: var(--surface-2);
  color: var(--text);
}
.nav-subitem.active {
  background: var(--accent-soft);
  color: var(--accent-ink);
  font-weight: 600;
}
.nav-subitem .sub-badge {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 10px;
  background: var(--surface-2);
  color: var(--text-faint);
}
.nav-subitem.active .sub-badge {
  background: var(--accent);
  color: var(--accent-contrast);
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
  height: 100vh;
  max-height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
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

<?php
  $seg1 = $this->uri->segment(1);
  $is_public_route = in_array($seg1, array('lamar', 'auth', 'berkas', 'onboarding'));
  if ($this->session->userdata('logged_in') && ! $is_public_route):
    $au = (array) $this->session->userdata('auth_user');
    $initials = strtoupper(substr($au['nama'] ?? ($au['username'] ?? 'U'), 0, 2));
    $is_user_dept = (($au['kode_role'] ?? '') === 'USER_DEPT');
    $brand_href = $is_user_dept ? site_url('requisitions') : site_url('dashboard');
?>
<div id="app">
  <!-- Sidebar -->
  <aside class="sidebar" id="appSidebar">
    <a href="<?= $brand_href ?>" class="brand">
      <div class="brand-mark">RPG</div>
      <div>
        <div class="brand-name">e-Recruitment</div>
        <div class="brand-sub">Ratu Pertiwi Group</div>
      </div>
    </a>

    <!-- Nav Group: Operasional -->
    <div class="nav-group">
      <span class="eyebrow"><?= $is_user_dept ? 'Menu Utama' : 'Operasional' ?></span>
      <?php if ( ! $is_user_dept): ?>
      <a href="<?= site_url('dashboard') ?>" class="nav-item <?= in_array($seg1, array('', 'dashboard')) ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        <span>Dashboard</span>
      </a>
      <?php endif; ?>
      <a href="<?= site_url('requisitions') ?>" class="nav-item <?= in_array($seg1, array('requisitions', 'pipeline')) ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>MPR & Pipeline</span>
      </a>
      <a href="<?= site_url('candidates') ?>" class="nav-item <?= $seg1 === 'candidates' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Daftar Pelamar</span>
      </a>
      <?php /* ponytail: Menu Report & Summary di-hide dari sidebar nav sesuai instruksi user, routing & controller tetap tersedia */ ?>
      <?php if (false): ?>
      <a href="<?= site_url('reports') ?>" class="nav-item <?= $seg1 === 'reports' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span>Report &amp; Summary</span>
      </a>
      <?php endif; ?>
    </div>

    <?php if ( ! $is_user_dept): ?>
    <!-- Nav Group: Intake & Pelamar -->
    <div class="nav-group">
      <span class="eyebrow">Intake & Pelamar</span>
      <a href="<?= site_url('postings') ?>" class="nav-item <?= in_array($seg1, array('postings', 'lamar')) ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        <span>Form Publik</span>
      </a>
      <a href="<?= site_url('manual') ?>" class="nav-item <?= $seg1 === 'manual' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Entry Manual</span>
      </a>
      <?php /* ponytail: Menu Import Portal di-hide sementara, aktifkan kembali saat format CSV/portal dibakukan */ ?>
      <?php if (false): ?>
      <a href="<?= site_url('import') ?>" class="nav-item <?= $seg1 === 'import' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        <span>Import Portal</span>
      </a>
      <?php endif; ?>
      <?php /* ponytail: Menu Berkas & PDP di-hide sementara sesuai instruksi user */ ?>
      <?php if (false): ?>
      <a href="<?= site_url('documents') ?>" class="nav-item <?= $seg1 === 'documents' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>Berkas & PDP</span>
      </a>
      <?php endif; ?>
    </div>

    <!-- Nav Group: Konfigurasi -->
    <div class="nav-group">
      <span class="eyebrow">Konfigurasi</span>
      <a href="<?= site_url('users') ?>" class="nav-item <?= $seg1 === 'users' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Manajemen User</span>
      </a>
      <div class="nav-item-collapsible <?= $seg1 === 'master' ? 'open' : '' ?>" id="masterNavGroup">
        <div class="nav-item <?= $seg1 === 'master' ? 'active' : '' ?>" style="display:flex; justify-content:space-between; align-items:center; padding-right:4px;">
          <a href="<?= site_url('master') ?>" style="display:flex; align-items:center; gap:10px; color:inherit; text-decoration:none; flex:1">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
            <span>Master Data</span>
          </a>
          <button type="button" class="nav-chevron-btn" onclick="toggleMasterSubmenu(event)" title="Buka / Tutup Sub Menu">
            <svg class="nav-chevron-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          </button>
        </div>
        <div class="nav-submenu <?= $seg1 === 'master' ? '' : 'collapsed' ?>" id="masterSubmenu">
          <?php
            $m_sub = $this->uri->segment(3) ?: ($seg1 === 'master' ? 'posisi' : '');
            $m_list = array(
              'posisi'     => 'Posisi',
              'departemen' => 'Departemen',
              'outlet'     => 'Outlet',
              'dokumen'     => 'Dokumen',
              'remark'      => 'Remark Alur',
            );
            foreach ($m_list as $mk => $ml):
          ?>
            <a href="<?= site_url('master/index/' . $mk) ?>" class="nav-subitem <?= ($seg1 === 'master' && $m_sub === $mk) ? 'active' : '' ?>">
              <span><?= $ml ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?= site_url('flowbuilder') ?>" class="nav-item <?= $seg1 === 'flowbuilder' ? 'active' : '' ?>">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        <span>Flow Builder</span>
      </a>
    </div>
    <?php endif; ?>

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

<script>
function toggleMasterSubmenu(e) {
  if (e) e.stopPropagation();
  var group = document.getElementById('masterNavGroup');
  var sub = document.getElementById('masterSubmenu');
  if (!group || !sub) return;
  var isCollapsed = sub.classList.contains('collapsed');
  if (isCollapsed) {
    sub.classList.remove('collapsed');
    group.classList.add('open');
  } else {
    sub.classList.add('collapsed');
    group.classList.remove('open');
  }
}

function toggleMenu(e, id) {
  e.stopPropagation();
  var target = document.getElementById(id);
  if (!target) return;
  var isShown = target.style.display === 'block';
  document.querySelectorAll('.mpr-dropdown').forEach(function(el) {
    el.style.display = 'none';
  });
  if (!isShown) {
    target.style.display = 'block';
  }
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.mpr-menu-btn') && !e.target.closest('.mpr-dropdown')) {
    document.querySelectorAll('.mpr-dropdown').forEach(function(el) {
      el.style.display = 'none';
    });
  }
});

/* ===== Auto-fade inline flash messages ===== */
(function() {
  var flashes = document.querySelectorAll('main .flash.ok, main .flash.err, .wrap-public .flash.ok, .wrap-public .flash.err');
  flashes.forEach(function(el) {
    setTimeout(function() {
      el.style.transition = 'opacity .4s ease, max-height .4s ease, margin .4s ease, padding .4s ease';
      el.style.opacity = '0';
      el.style.maxHeight = '0';
      el.style.marginBottom = '0';
      el.style.paddingTop = '0';
      el.style.paddingBottom = '0';
      el.style.overflow = 'hidden';
      setTimeout(function() { el.remove(); }, 450);
    }, 4000);
  });
})();
</script>

<!-- Global Toast Container -->
<div id="global-toast-container"></div>
<script>
var _toastIcons = {
  ok:   '<svg class="toast-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
  err:  '<svg class="toast-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
  info: '<svg class="toast-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
  warn: '<svg class="toast-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
};

/**
 * showToast(message, type, duration)
 * type: 'ok' | 'err' | 'info' | 'warn'  (default 'ok')
 * duration: ms before auto-dismiss (default 4000, 0 = sticky)
 */
function showToast(message, type, duration) {
  type = type || 'ok';
  duration = (duration === undefined || duration === null) ? 4000 : duration;
  var container = document.getElementById('global-toast-container');
  if (!container) return;

  var el = document.createElement('div');
  el.className = 'g-toast ' + type;
  el.innerHTML = (_toastIcons[type] || _toastIcons.info) +
    '<span class="toast-msg">' + message + '</span>' +
    '<button class="toast-close" onclick="dismissToast(this.parentNode)" title="Tutup">&times;</button>';
  container.appendChild(el);

  if (duration > 0) {
    setTimeout(function() { dismissToast(el); }, duration);
  }
  return el;
}

function dismissToast(el) {
  if (!el || el.classList.contains('removing')) return;
  el.classList.add('removing');
  setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 260);
}
</script>
</body>
</html>

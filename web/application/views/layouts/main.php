<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? html_escape($title) . ' — ' : '' ?>E-Recruitment RPG</title>
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
  --shadow-sm: 0 1px 2px rgba(20,30,25,.06);
  --shadow: 0 1px 2px rgba(20,30,25,.06), 0 10px 26px rgba(20,30,25,.07);
  --radius: 8px;
}
* { box-sizing: border-box; }
body {
  margin: 0;
  font-family: "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  font-size: 14.5px;
  line-height: 1.5;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
}
.wrap { max-width: 780px; margin: 4vh auto; padding: 0 20px; }
.wrap.wide { max-width: 1200px; }
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px 28px;
  box-shadow: var(--shadow-sm);
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
label { display: block; margin: 12px 0 4px; font-weight: 600; font-size: 13px; color: var(--text); }
input[type=text], input[type=password], input[type=email], input[type=number], input[type=date], input[type=datetime-local], select, textarea {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background: var(--surface);
  color: var(--text);
}
input:focus, select:focus, textarea:focus {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
  border-color: var(--accent);
}
button, .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  border: 0;
  border-radius: 6px;
  background: var(--accent);
  color: var(--accent-contrast);
  font-size: 14px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.15s ease;
}
button:hover, .btn:hover { opacity: 0.9; }
.btn-sm { padding: 4px 10px; font-size: 12.5px; border-radius: 5px; }
.btn-ghost {
  background: var(--surface-2);
  color: var(--text);
  border: 1px solid var(--border);
}
.btn-ghost:hover { background: var(--surface-3); }
.flash {
  padding: 10px 14px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-size: 13.5px;
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
ul { padding-left: 18px; }
code, .mono {
  font-family: "IBM Plex Mono", monospace;
  background: var(--surface-2);
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 12.5px;
}
.muted { color: var(--text-muted); font-size: 13px; }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
  margin: 12px 0;
}
th, td {
  text-align: left;
  padding: 9px 12px;
  border-bottom: 1px solid var(--border);
  vertical-align: top;
}
th {
  font-family: "Archivo", sans-serif;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: .05em;
  background: var(--surface-2);
}
.tag {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  line-height: 1.3;
}
.tag.on, .tag.lulus, .tag.diterima { background: var(--good-soft); color: var(--good); }
.tag.off, .tag.tidak_lulus, .tag.ditolak, .tag.batal { background: var(--crit-soft); color: var(--crit); }
.tag.warn, .tag.nego, .tag.review { background: var(--warn-soft); color: var(--warn); }
.tag.info { background: var(--info-soft); color: var(--info); }
form.inline { display: inline; margin: 0; }

dialog {
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: var(--shadow);
  background: var(--surface);
  color: var(--text);
  padding: 24px;
  max-width: 520px;
  width: 90%;
}
dialog::backdrop {
  background: rgba(15, 23, 20, 0.4);
  backdrop-filter: blur(2px);
}
</style>
</head>
<body>
<?php if ($this->session->userdata('logged_in')): $au = (array) $this->session->userdata('auth_user'); ?>
<nav style="background:var(--surface); border-bottom:1px solid var(--border); padding:10px 24px; font-size:13.5px; position:sticky; top:0; z-index:30">
	<div style="max-width:1200px; margin:0 auto; display:flex; gap:16px; flex-wrap:wrap; align-items:center">
		<a href="<?= site_url('dashboard') ?>" style="font-family:'Archivo'; font-weight:700; font-size:15px; color:var(--text); text-decoration:none; display:flex; align-items:center; gap:8px">
			<span style="display:inline-grid; place-items:center; width:26px; height:26px; border-radius:6px; background:var(--accent); color:var(--accent-contrast); font-size:12px">RPG</span>
			<span>e-Recruitment</span>
		</a>
		<a href="<?= site_url('dashboard') ?>">Dashboard</a>
		<a href="<?= site_url('requisitions') ?>">MPR</a>
		<a href="<?= site_url('postings') ?>">Link Form</a>
		<a href="<?= site_url('manual') ?>">Entry Manual</a>
		<a href="<?= site_url('import') ?>">Import</a>
		<a href="<?= site_url('documents') ?>">Berkas</a>
		<a href="<?= site_url('master') ?>">Master Data</a>
		<a href="<?= site_url('flowbuilder') ?>">Flow Builder</a>
		<span style="margin-left:auto; color:var(--text-muted); font-size:13px">
			<strong><?= html_escape($au['nama'] ?? '') ?></strong> (<?= html_escape($au['kode_role'] ?? '') ?>)
			&middot; <a href="<?= site_url('auth/logout') ?>" style="color:var(--crit)">keluar</a>
		</span>
	</div>
</nav>
<?php endif; ?>
<div class="wrap<?= ! empty($wide) ? ' wide' : '' ?>">
<?php if ($this->session->flashdata('error')): ?>
	<div class="flash err"><?= html_escape($this->session->flashdata('error')) ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('ok')): ?>
	<div class="flash ok"><?= html_escape($this->session->flashdata('ok')) ?></div>
<?php endif; ?>
<?php $this->load->view($_content); ?>
</div>
</body>
</html>

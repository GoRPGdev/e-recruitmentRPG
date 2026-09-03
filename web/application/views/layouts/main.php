<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? html_escape($title) . ' — ' : '' ?>E-Recruitment RPG</title>
<style>
/* Kerangka sementara. CSS final diambil dari docs/preview.html di Fase 1 lanjutan. */
* { box-sizing: border-box; }
body { margin: 0; font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
       background: #f4f5f7; color: #1c1e21; }
.wrap { max-width: 720px; margin: 6vh auto; padding: 0 20px; }
.card { background: #fff; border: 1px solid #e2e4e8; border-radius: 10px; padding: 28px 32px; }
h1 { margin: 0 0 4px; font-size: 22px; }
h2 { font-size: 15px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin: 24px 0 8px; }
label { display: block; margin: 14px 0 4px; font-weight: 600; font-size: 13px; }
input[type=text], input[type=password] { width: 100%; padding: 9px 11px; border: 1px solid #cbd0d8;
       border-radius: 7px; font-size: 15px; }
button { margin-top: 18px; padding: 10px 18px; border: 0; border-radius: 7px; background: #2f6feb;
       color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
button:hover { background: #245ad1; }
.flash { padding: 10px 14px; border-radius: 7px; margin-bottom: 16px; font-size: 14px; }
.flash.err { background: #fdecec; border: 1px solid #f5b5b5; color: #a12626; }
.flash.ok  { background: #eaf7ee; border: 1px solid #b6e0c4; color: #1f7a3d; }
ul { padding-left: 18px; } code { background: #eef0f3; padding: 1px 5px; border-radius: 4px; word-break: break-all; }
.muted { color: #6b7280; font-size: 13px; margin-top: 20px; }
a { color: #2f6feb; }
.wrap.wide { max-width: 1000px; }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; margin: 8px 0; }
th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e8eaee; vertical-align: top; }
th { color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: .04em; }
.tag { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
.tag.on { background: #eaf7ee; color: #1f7a3d; } .tag.off { background: #f1f2f4; color: #6b7280; }
.btn-sm { padding: 5px 12px; font-size: 13px; margin: 0; }
.btn-ghost { background: #eef0f3; color: #1c1e21; } .btn-ghost:hover { background: #e2e4e8; }
form.inline { display: inline; margin: 0; }
</style>
</head>
<body>
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

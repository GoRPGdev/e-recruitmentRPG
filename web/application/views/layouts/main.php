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
ul { padding-left: 18px; } code { background: #eef0f3; padding: 1px 5px; border-radius: 4px; }
.muted { color: #6b7280; font-size: 13px; margin-top: 20px; }
a { color: #2f6feb; }
</style>
</head>
<body>
<div class="wrap">
<?php if ($this->session->flashdata('error')): ?>
	<div class="flash err"><?= html_escape($this->session->flashdata('error')) ?></div>
<?php endif; ?>
<?php $this->load->view($_content); ?>
</div>
</body>
</html>

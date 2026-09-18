<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card" style="text-align:center; padding:32px 28px">
	<div style="width:44px; height:44px; border-radius:50%; background:var(--crit-soft); color:var(--crit); display:grid; place-items:center; margin:0 auto 16px; font-size:20px">
		&times;
	</div>
	<h1 style="font-size:18px">Tidak bisa masuk</h1>
	<p class="muted"><?= html_escape($pesan ?? 'Tautan masuk tidak valid. Silakan coba lagi dari menu E-Recruitment di Payroll.') ?></p>
</main>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div style="text-align:center; margin-bottom:24px">
	<div style="display:inline-flex; align-items:center; gap:12px; margin-bottom:8px">
		<div style="width:52px; height:52px; border-radius:12px; overflow:hidden; background:#000000; border:1px solid rgba(0,0,0,0.1); box-shadow:0 4px 14px rgba(0,0,0,0.18); flex:none; display:flex; align-items:center; justify-content:center">
			<img src="<?= base_url('assets/img/logo-sm.png') ?>" alt="Logo RPG" style="width:100%; height:100%; object-fit:contain; display:block">
		</div>
		<div style="text-align:left">
			<div style="font-family:'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:700; font-size:17.5px; color:var(--text); line-height:1.2">Ratu Pertiwi Group</div>
			<div style="font-size:12px; color:var(--text-faint)">Portal Karir &amp; Rekrutmen Resmi</div>
		</div>
	</div>
</div>

<main class="card" style="padding:32px; text-align:center">
	<div style="width:54px; height:54px; border-radius:50%; background:var(--accent-soft); color:var(--accent); display:grid; place-items:center; margin:0 auto 16px; font-size:26px">
		&#10003;
	</div>
	<h1 style="margin:0 0 8px; font-size:22px; font-weight:700">Lamaran Anda Berhasil Terkirim</h1>
	<p style="font-size:14px; color:var(--text); margin:0 0 16px; line-height:1.5">
		Terima kasih atas ketertarikan Anda untuk bergabung bersama Ratu Pertiwi Group.<br>
		Lamaran untuk posisi <strong><?= html_escape($posting['nama_posisi']) ?></strong> telah berhasil masuk ke sistem rekrutmen kami.
	</p>
	<p class="muted" style="font-size:12.5px; margin:0; line-height:1.5">
		Tim Rekrutmen RPG akan meninjau berkas kualifikasi Anda.<br>
		Pemberitahuan perkembangan seleksi atau undangan wawancara akan disampaikan melalui WhatsApp atau Email terdaftar.
	</p>
</main>

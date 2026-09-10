<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="card" style="padding:36px 32px; text-align:center; max-width:580px; margin:40px auto; border-top:4px solid var(--good)">
	<div style="width:58px; height:58px; border-radius:50%; background:var(--good-soft); color:var(--good); display:grid; place-items:center; margin:0 auto 16px; font-size:28px">
		✓
	</div>

	<div class="eyebrow" style="color:var(--good); font-weight:700; margin-bottom:4px">Data Formulir Berhasil Dikirim</div>
	<h1 style="font-size:22px; font-weight:700; color:var(--text); margin-bottom:8px">
		Terima Kasih, <?= html_escape($t['nama_lengkap']) ?>!
	</h1>

	<p class="muted" style="font-size:13.5px; line-height:1.6; margin-bottom:24px">
		Kelengkapan data formulir pelamar untuk posisi <strong><?= html_escape($t['nama_posisi']) ?></strong> telah berhasil kami terima dan tersimpan secara aman di sistem Ratu Pertiwi Group.
	</p>

	<div style="background:var(--surface-2); padding:16px 20px; border-radius:10px; text-align:left; font-size:13px; line-height:1.6; margin-bottom:24px; border:1px solid var(--border)">
		<div style="font-weight:700; margin-bottom:6px; color:var(--text)">Langkah Selanjutnya:</div>
		<ul style="margin:0; padding-left:18px; color:var(--text-muted)">
			<li>Tim HR dan rekruter kami akan meninjau data serta kelengkapan berkas yang telah Anda lengkapi.</li>
			<li>Pemberitahuan hasil seleksi dan jadwal tahapan berikutnya akan disampaikan melalui WhatsApp atau Email resmi.</li>
		</ul>
	</div>

	<p class="faint" style="font-size:12px; margin:0">
		Tautan ini sekarang sudah tidak berlaku lagi untuk menjaga keamanan data pribadi Anda.
	</p>
</div>

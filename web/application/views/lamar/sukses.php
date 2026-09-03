<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Lamaran terkirim &check;</h1>
	<p>Terima kasih. Lamaran Anda untuk posisi
	   <strong><?= html_escape($posting['nama_posisi']) ?></strong> sudah kami terima.</p>
	<p class="muted">
		Nomor lamaran: <code>#<?= (int) $res['id_lamaran'] ?></code><br>
		Tim HR akan menghubungi Anda melalui WhatsApp bila lolos tahap awal.
	</p>
</main>

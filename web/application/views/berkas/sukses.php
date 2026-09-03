<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Berkas terkirim &check;</h1>
	<p><strong><?= (int) $saved ?></strong> file berhasil diunggah untuk lamaran posisi
	   <strong><?= html_escape($t['nama_posisi']) ?></strong>.</p>
	<?php if ( ! empty($errs)): ?>
		<div class="flash err">
			Sebagian file tidak tersimpan:
			<ul><?php foreach ($errs as $e): ?><li><?= html_escape($e) ?></li><?php endforeach; ?></ul>
		</div>
	<?php endif; ?>
	<p class="muted">Tautan ini sudah tidak berlaku lagi. Terima kasih.</p>
</main>

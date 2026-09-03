<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Statistik Posting: <?= html_escape($posting['nama_posisi']) ?></h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('postings/form_settings/' . (int) $posting['id_posting']) ?>">&larr; link form</a>
	</p>
	<p class="muted">
		Angka funnel teratas diisi manual selama sebagian pelamar belum masuk sistem
		satu per satu (ERD §7.2). Submit lewat form sistem: <strong><?= (int) $posting['jumlah_submit'] ?></strong>.
	</p>

	<?= form_open(site_url('postings/stats/' . (int) $posting['id_posting'])) ?>
		<label>Jumlah pelamar masuk (portal)</label>
		<input type="text" name="jumlah_pelamar_masuk" value="<?= (int) ($stats['jumlah_pelamar_masuk'] ?? 0) ?>" inputmode="numeric">
		<label>CV sesuai</label>
		<input type="text" name="cv_sesuai" value="<?= (int) ($stats['cv_sesuai'] ?? 0) ?>" inputmode="numeric">
		<label>CV tidak sesuai</label>
		<input type="text" name="cv_tidak_sesuai" value="<?= (int) ($stats['cv_tidak_sesuai'] ?? 0) ?>" inputmode="numeric">
		<button type="submit">Simpan</button>
	<?= form_close() ?>

	<?php if ($stats && $stats['diperbarui_pada']): ?>
		<p class="muted">Terakhir diperbarui: <?= html_escape(substr($stats['diperbarui_pada'], 0, 16)) ?></p>
	<?php endif; ?>
</main>

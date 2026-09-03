<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Link Form: <?= html_escape($posting['nama_posisi']) ?></h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('postings') ?>">&larr; semua link</a>
	</p>

	<h2>Tautan publik</h2>
	<p><code><?= html_escape($public_url) ?></code></p>
	<p class="muted">Sebarkan link ini di job board / job desc. Slug menentukan posisi &mdash; kandidat tidak memilih.</p>

	<h2>Pengaturan</h2>
	<?= form_open(site_url('postings/form_settings/' . (int) $posting['id_posting'])) ?>
		<label style="font-weight:400">
			<input type="checkbox" name="form_aktif" value="1" <?= $posting['form_aktif'] ? 'checked' : '' ?>>
			Form aktif (menerima lamaran)
		</label>

		<label for="form_dibuka">Buka mulai (opsional)</label>
		<input type="text" id="form_dibuka" name="form_dibuka" placeholder="YYYY-MM-DD HH:MM"
		       value="<?= html_escape($posting['form_dibuka'] ? substr($posting['form_dibuka'], 0, 16) : '') ?>">

		<label for="form_ditutup">Tutup sampai (opsional)</label>
		<input type="text" id="form_ditutup" name="form_ditutup" placeholder="YYYY-MM-DD HH:MM"
		       value="<?= html_escape($posting['form_ditutup'] ? substr($posting['form_ditutup'], 0, 16) : '') ?>">

		<button type="submit">Simpan</button>
	<?= form_close() ?>

	<p class="muted">Total submit tercatat: <strong><?= (int) $posting['jumlah_submit'] ?></strong></p>
</main>

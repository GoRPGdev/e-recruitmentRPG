<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: import/index.php -- Formulir Import Massal Data Kandidat (CSV)
 *
 * Fungsi:
 * - Mengunggah file CSV berisikan data lamaran kandidat dari job fair atau sumber eksternal.
 * - Menangani parsing, validasi nomor WhatsApp, dan deduplikasi data secara otomatis.
 * - Menyajikan tinjauan awal (preview) sebelum data dieksekusi ke database.
 */
?>
<main class="card">
	<h1>Import File Pelamar</h1>
	<p class="muted" style="margin-top:0">
		Format CSV (Excel &rarr; Save As &rarr; CSV).
		Kolom wajib: <code>nama</code>, <code>no_wa</code>. Opsional:
		<code>email</code>, <code>pendidikan</code>, <code>kota</code>,
		<code>perusahaan_terakhir</code>, <code>gaji_diharapkan</code>.
	</p>

	<?= validation_errors('<div class="flash err">', '</div>') ?>

	<?php if ( ! $reqs): ?>
		<div class="flash err">Tidak ada requisition yang menerima lamaran.</div>
	<?php else: ?>
	<?= form_open_multipart(site_url('import')) ?>
		<label for="id_req">Requisition *</label>
		<select id="id_req" name="id_req" required>
			<option value="">- pilih -</option>
			<?php foreach ($reqs as $r): ?>
				<option value="<?= (int) $r['id_req'] ?>">
					<?= html_escape(($r['no_mpr'] ?: '#' . $r['id_req']) . ' — ' . $r['nama_posisi']) ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="file">File CSV *</label>
		<input type="file" id="file" name="file" accept=".csv" required>

		<button type="submit">Unggah &amp; preview</button>
	<?= form_close() ?>
	<?php endif; ?>

	<h2>Batch terakhir</h2>
	<?php if ( ! $recent): ?>
		<p class="muted">Belum ada.</p>
	<?php else: ?>
		<div style="overflow-x:auto"><table>
			<tr><th>#</th><th>File</th><th>Posisi</th><th>Status</th><th>Baris</th><th>OK</th><th>Dup</th><th>Gagal</th><th></th></tr>
			<?php foreach ($recent as $b): ?>
			<tr>
				<td><?= (int) $b['id_batch'] ?></td>
				<td><?= html_escape($b['nama_file']) ?></td>
				<td><?= html_escape($b['nama_posisi'] ?: '-') ?></td>
				<td><span class="tag <?= $b['status'] === 'Committed' ? 'on' : 'off' ?>"><?= html_escape($b['status']) ?></span></td>
				<td><?= (int) $b['jumlah_baris'] ?></td>
				<td><?= (int) $b['berhasil'] ?></td>
				<td><?= (int) $b['duplikat'] ?></td>
				<td><?= (int) $b['gagal'] ?></td>
				<td><a href="<?= site_url('import/preview/' . (int) $b['id_batch']) ?>">lihat</a></td>
			</tr>
			<?php endforeach; ?>
		</table></div>
	<?php endif; ?>
</main>

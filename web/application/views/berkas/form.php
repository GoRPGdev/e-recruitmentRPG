<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: berkas/form.php -- Formulir Unggah Dokumen Mandiri Pelamar
 *
 * Fungsi:
 * - Antarmuka publik yang diakses kandidat via token unik untuk melengkapi berkas yang diminta HR.
 * - Mengunggah dokumen pendukung secara aman ke direktori penyimpanan fisik di luar webroot.
 */
?>
<main class="card">
	<h1>Lengkapi berkas</h1>
	<p class="muted" style="margin-top:0">
		Halo <strong><?= html_escape($t['nama_lengkap']) ?></strong> &mdash;
		lamaran posisi <strong><?= html_escape($t['nama_posisi']) ?></strong>.
	</p>

	<?= form_open_multipart(site_url('berkas/' . $this->uri->segment(2))) ?>

	<p>Unggah dokumen yang diminta. Pilih jenis untuk tiap file.</p>

	<?php for ($i = 0; $i < 4; $i++): ?>
		<label for="f<?= $i ?>">Dokumen <?= $i + 1 ?><?= $i === 0 ? ' *' : ' (opsional)' ?></label>
		<div style="display:flex; gap:8px; flex-wrap:wrap">
			<select name="id_dokumen[<?= $i ?>]" style="flex:0 0 200px">
				<option value="">- jenis -</option>
				<?php foreach ($dokumen as $d): ?>
					<option value="<?= (int) $d['id_dokumen'] ?>"><?= html_escape($d['nama_dokumen']) ?></option>
				<?php endforeach; ?>
			</select>
			<input type="file" id="f<?= $i ?>" name="berkas[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="flex:1">
		</div>
	<?php endfor; ?>

	<button type="submit">Kirim berkas</button>
	<?= form_close() ?>

	<p class="muted">Tautan ini hanya bisa dipakai sekali. Simpan bukti setelah berhasil.</p>
</main>

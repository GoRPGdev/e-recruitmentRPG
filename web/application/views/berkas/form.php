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

	<?= form_open_multipart(site_url('berkas/' . $this->uri->segment(2)), array('id' => 'form-berkas')) ?>

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

	<div style="margin-top:16px">
		<button type="submit" id="btn-submit-berkas" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; font-weight:700">
			<span>Kirim berkas &rarr;</span>
		</button>
	</div>
	<?= form_close() ?>

	<p class="muted" style="margin-top:16px">Tautan ini hanya bisa dipakai sekali. Simpan bukti setelah berhasil.</p>
</main>

<style>
@keyframes erecSpin { 100% { transform: rotate(360deg); } }
.btn-submitting {
	opacity: 0.8 !important;
	cursor: wait !important;
	pointer-events: none !important;
	box-shadow: none !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var f = document.getElementById('form-berkas');
	var b = document.getElementById('btn-submit-berkas');
	if (f && b) {
		f.addEventListener('submit', function() {
			if (f.checkValidity && !f.checkValidity()) return;
			b.classList.add('btn-submitting');
			b.innerHTML = '<svg style="width:16px; height:16px; animation:erecSpin 0.9s linear infinite; flex:none" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"></circle></svg> <span>Sedang Mengunggah...</span>';
		});
	}
});
</script>
</main>

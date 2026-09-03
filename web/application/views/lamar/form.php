<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Lamar: <?= html_escape($posting['nama_posisi']) ?></h1>
	<p class="muted" style="margin-top:0">
		<?php if ($posting['departemen']): ?><?= html_escape($posting['departemen']) ?> &middot; <?php endif; ?>
		Anda melamar untuk posisi ini. Isi data di bawah dengan benar.
	</p>

	<?= validation_errors('<div class="flash err">', '</div>') ?>

	<?= form_open_multipart(site_url('lamar/' . $posting['url_slug'])) ?>

	<h2>Data diri</h2>
	<label for="nama_lengkap">Nama lengkap *</label>
	<input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= set_value('nama_lengkap') ?>" required>

	<label for="email">Email *</label>
	<input type="text" id="email" name="email" value="<?= set_value('email') ?>" required>

	<label for="no_wa">Nomor WhatsApp *</label>
	<input type="text" id="no_wa" name="no_wa" value="<?= set_value('no_wa') ?>" placeholder="08xx / +62xx" required>

	<label for="tempat_lahir">Tempat lahir</label>
	<input type="text" id="tempat_lahir" name="tempat_lahir" value="<?= set_value('tempat_lahir') ?>">

	<label for="tanggal_lahir">Tanggal lahir</label>
	<input type="text" id="tanggal_lahir" name="tanggal_lahir" value="<?= set_value('tanggal_lahir') ?>" placeholder="YYYY-MM-DD">

	<label for="jenis_kelamin">Jenis kelamin</label>
	<select id="jenis_kelamin" name="jenis_kelamin">
		<option value="">-</option>
		<option value="L" <?= set_select('jenis_kelamin', 'L') ?>>Laki-laki</option>
		<option value="P" <?= set_select('jenis_kelamin', 'P') ?>>Perempuan</option>
	</select>

	<label for="status_pernikahan">Status pernikahan</label>
	<select id="status_pernikahan" name="status_pernikahan">
		<option value="">-</option>
		<?php foreach (array('Belum_Menikah', 'Menikah', 'Cerai') as $s): ?>
			<option value="<?= $s ?>" <?= set_select('status_pernikahan', $s) ?>><?= str_replace('_', ' ', $s) ?></option>
		<?php endforeach; ?>
	</select>

	<label for="kota_domisili">Kota domisili</label>
	<input type="text" id="kota_domisili" name="kota_domisili" value="<?= set_value('kota_domisili') ?>">

	<label for="alamat_lengkap">Alamat lengkap</label>
	<input type="text" id="alamat_lengkap" name="alamat_lengkap" value="<?= set_value('alamat_lengkap') ?>">

	<h2>Pendidikan</h2>
	<label for="pendidikan_terakhir">Pendidikan terakhir</label>
	<input type="text" id="pendidikan_terakhir" name="pendidikan_terakhir" value="<?= set_value('pendidikan_terakhir') ?>" placeholder="SMA / D3 / S1 ...">

	<label for="nama_sekolah">Nama sekolah / universitas</label>
	<input type="text" id="nama_sekolah" name="nama_sekolah" value="<?= set_value('nama_sekolah') ?>">

	<label for="jurusan">Jurusan</label>
	<input type="text" id="jurusan" name="jurusan" value="<?= set_value('jurusan') ?>">

	<h2>Pekerjaan terakhir</h2>
	<label for="perusahaan_terakhir">Perusahaan terakhir</label>
	<input type="text" id="perusahaan_terakhir" name="perusahaan_terakhir" value="<?= set_value('perusahaan_terakhir') ?>">

	<label for="jabatan_terakhir">Jabatan terakhir</label>
	<input type="text" id="jabatan_terakhir" name="jabatan_terakhir" value="<?= set_value('jabatan_terakhir') ?>">

	<label for="periode_kerja">Periode kerja</label>
	<input type="text" id="periode_kerja" name="periode_kerja" value="<?= set_value('periode_kerja') ?>" placeholder="mis. 2021 - 2024">

	<label for="gaji_terakhir">Gaji terakhir (Rp)</label>
	<input type="text" id="gaji_terakhir" name="gaji_terakhir" value="<?= set_value('gaji_terakhir') ?>" inputmode="numeric">

	<label for="gaji_diharapkan">Gaji diharapkan (Rp)</label>
	<input type="text" id="gaji_diharapkan" name="gaji_diharapkan" value="<?= set_value('gaji_diharapkan') ?>" inputmode="numeric">

	<h2>Kontak darurat</h2>
	<label for="kd_nama">Nama</label>
	<input type="text" id="kd_nama" name="kd_nama" value="<?= set_value('kd_nama') ?>">

	<label for="kd_telp">Telepon</label>
	<input type="text" id="kd_telp" name="kd_telp" value="<?= set_value('kd_telp') ?>">

	<label for="kd_hub">Hubungan</label>
	<input type="text" id="kd_hub" name="kd_hub" value="<?= set_value('kd_hub') ?>" placeholder="Ayah / Ibu / Kakak ...">

	<h2>Berkas</h2>
	<label for="cv">CV (PDF / DOC / gambar, maks 5 MB) *</label>
	<input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>

	<h2>Persetujuan</h2>
	<label style="font-weight:400">
		<input type="checkbox" name="consent" value="1" <?= set_checkbox('consent', '1') ?>>
		Saya menyetujui data saya diproses untuk keperluan rekrutmen (versi <?= html_escape($this->config->item('erec_consent_versi')) ?>).
	</label>
	<label style="font-weight:400">
		<input type="checkbox" name="talent_pool" value="1" <?= set_checkbox('talent_pool', '1') ?>>
		Data saya boleh disimpan untuk lowongan lain (talent pool).
	</label>
	<label style="font-weight:400">
		<input type="checkbox" name="consent_kesehatan" value="1" id="ck" <?= set_checkbox('consent_kesehatan', '1') ?>>
		Saya bersedia mengisi riwayat kesehatan (opsional, consent terpisah).
	</label>
	<label for="riwayat_penyakit">Riwayat penyakit (jika bersedia)</label>
	<input type="text" id="riwayat_penyakit" name="riwayat_penyakit" value="<?= set_value('riwayat_penyakit') ?>">

	<button type="submit">Kirim lamaran</button>
	<?= form_close() ?>
</main>

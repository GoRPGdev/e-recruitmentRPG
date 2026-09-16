<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: lamar/form.php -- Formulir Aplikasi Pendaftaran Lowongan Publik Pelamar (Tahap 1)
 *
 * Fungsi:
 * - Antarmuka publik responsif bagi calon pelamar untuk mendaftar pada lowongan posisi aktif.
 * - Mengumpulkan identitas awal, informasi kontak, kualifikasi pendidikan, dan berkas CV fisik.
 * - Dilengkapi validasi keamanan client-side dan server-side serta pencegahan duplikasi data.
 */
?>

<!-- Header Publik / Identitas Karir RPG -->
<div style="text-align:center; margin-bottom:24px">
	<div style="display:inline-flex; align-items:center; gap:10px; margin-bottom:8px">
		<div style="width:36px; height:36px; border-radius:9px; background:var(--accent); color:var(--accent-contrast); display:grid; place-items:center; font-family:'Archivo',sans-serif; font-weight:700; font-size:15px">
			RPG
		</div>
		<div style="text-align:left">
			<div style="font-family:'Archivo',sans-serif; font-weight:700; font-size:16px; color:var(--text); line-height:1.2">Ratu Pertiwi Group</div>
			<div style="font-size:11.5px; color:var(--text-faint)">Portal Karir &amp; Rekrutmen Resmi</div>
		</div>
	</div>
</div>

<!-- ================= KARTU DETAIL LOWONGAN KERJA ================= -->
<div class="card" style="padding:24px 28px; margin-bottom:24px; border-top:3px solid var(--accent)">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px">
		<div>
			<div class="eyebrow" style="margin-bottom:4px; color:var(--accent)">Lowongan Pekerjaan</div>
			<h1 style="margin:0 0 6px; font-size:22px; font-weight:700; color:var(--text)">
				<?= html_escape($posting['judul_posting'] ?: $posting['nama_posisi']) ?>
			</h1>
			<div class="muted" style="font-size:13px; display:flex; gap:10px; flex-wrap:wrap; align-items:center">
				<?php if (!empty($posting['nama_posisi']) && $posting['judul_posting'] !== $posting['nama_posisi']): ?>
					<span>Posisi: <strong><?= html_escape($posting['nama_posisi']) ?></strong></span>
					<span>&bull;</span>
				<?php endif; ?>
				<?php if (!empty($posting['departemen'])): ?>
					<span>Departemen: <strong><?= html_escape($posting['departemen']) ?></strong></span>
					<span>&bull;</span>
				<?php endif; ?>
				<span>Status Lowongan: <span class="tag on" style="font-size:10.5px">Terbuka</span></span>
			</div>
		</div>
	</div>

	<!-- Badges Spesifikasi Pekerjaan -->
	<div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid var(--border)">
		<!-- Penempatan -->
		<span class="tag" style="background:var(--surface-2); font-size:11.5px; padding:4px 10px">
			<svg style="width:12px; height:12px; margin-right:3px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
			Penempatan: <strong><?= html_escape($posting['tipe_penempatan']) ?><?= !empty($posting['nama_outlet']) ? ' &mdash; ' . html_escape($posting['nama_outlet']) : '' ?><?= !empty($posting['region_outlet']) ? ' (' . html_escape($posting['region_outlet']) . ')' : '' ?></strong>
		</span>

		<!-- Status Karyawan -->
		<?php if (!empty($posting['status_karyawan'])): ?>
		<span class="tag" style="background:var(--surface-2); font-size:11.5px; padding:4px 10px">
			<svg style="width:12px; height:12px; margin-right:3px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
			Ikatan: <strong><?= html_escape($posting['status_karyawan']) ?></strong>
		</span>
		<?php endif; ?>

		<!-- Pendidikan Minimal -->
		<?php if (!empty($posting['pendidikan_minimal'])): ?>
		<span class="tag" style="background:var(--surface-2); font-size:11.5px; padding:4px 10px">
			<svg style="width:12px; height:12px; margin-right:3px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
			Pendidikan Min: <strong><?= html_escape($posting['pendidikan_minimal']) ?></strong>
		</span>
		<?php endif; ?>

		<!-- Pengalaman Minimal -->
		<?php if ($posting['pengalaman_minimal_tahun'] !== NULL): ?>
		<span class="tag" style="background:var(--surface-2); font-size:11.5px; padding:4px 10px">
			<svg style="width:12px; height:12px; margin-right:3px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			Pengalaman: <strong><?= (int) $posting['pengalaman_minimal_tahun'] > 0 ? (int) $posting['pengalaman_minimal_tahun'] . ' Tahun' : 'Fresh Graduate Terbuka' ?></strong>
		</span>
		<?php endif; ?>
	</div>

	<!-- Deskripsi Pekerjaan (Job Description) -->
	<?php if (!empty($posting['job_desc'])): ?>
	<div style="margin-bottom:18px">
		<h3 style="font-size:14px; font-weight:700; margin:0 0 6px; color:var(--text)">
			Deskripsi Pekerjaan:
		</h3>
		<div style="font-size:13.5px; line-height:1.6; color:var(--text); white-space:pre-line; background:var(--surface-2); padding:14px 16px; border-radius:8px">
			<?= html_escape($posting['job_desc']) ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Kualifikasi & Persyaratan (Requirements) -->
	<?php if (!empty($posting['kualifikasi'])): ?>
	<div>
		<h3 style="font-size:14px; font-weight:700; margin:0 0 6px; color:var(--text)">
			Kualifikasi &amp; Persyaratan:
		</h3>
		<div style="font-size:13.5px; line-height:1.6; color:var(--text); white-space:pre-line; background:var(--surface-2); padding:14px 16px; border-radius:8px">
			<?= html_escape($posting['kualifikasi']) ?>
		</div>
	</div>
	<?php endif; ?>
</div>

<!-- ================= FORMULIR PENDAFTARAN PELAMAR ================= -->
<div class="card" style="padding:24px 28px">
	<div style="margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border)">
		<h2 style="margin:0 0 4px; font-size:18px; font-weight:700">Formulir Lamaran Pekerjaan</h2>
		<p class="muted" style="margin:0; font-size:13px">
			Lengkapi seluruh data diri dan unggah berkas CV Anda untuk diproses oleh Tim Rekrutmen Ratu Pertiwi Group.
		</p>
	</div>

	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>

	<?= validation_errors('<div class="flash err" style="margin-bottom:14px">', '</div>') ?>

	<?= form_open_multipart(site_url('lamar/' . $posting['url_slug']), array('id' => 'formLamar', 'style' => 'display:flex; flex-direction:column; gap:20px')) ?>

	<!-- Section 1: Data Diri Utama -->
	<fieldset style="border:none; padding:0; margin:0">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			1. Data Pribadi
		</legend>

		<div style="display:grid; grid-template-columns:1fr; gap:12px">
			<div>
				<label for="nama_lengkap" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Lengkap (Sesuai KTP) <span style="color:var(--crit)">*</span></label>
				<input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= set_value('nama_lengkap') ?>" placeholder="Nama lengkap tanpa singkatan" required style="width:100%">
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
				<div>
					<label for="email" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Alamat Email Aktif <span style="color:var(--crit)">*</span></label>
					<input type="email" id="email" name="email" value="<?= set_value('email') ?>" placeholder="nama@email.com" required style="width:100%">
				</div>
				<div>
					<label for="no_wa" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor WhatsApp Aktif <span style="color:var(--crit)">*</span></label>
					<input type="text" id="no_wa" name="no_wa" value="<?= set_value('no_wa') ?>" placeholder="08xxxxxxxxxx" required style="width:100%">
					<span class="muted" style="font-size:11px">Pemberitahuan seleksi dikirimkan via WhatsApp</span>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
				<div>
					<label for="tempat_lahir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tempat Lahir</label>
					<input type="text" id="tempat_lahir" name="tempat_lahir" value="<?= set_value('tempat_lahir') ?>" placeholder="Kota tempat lahir" style="width:100%">
				</div>
				<div>
					<label for="tanggal_lahir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tanggal Lahir</label>
					<input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= set_value('tanggal_lahir') ?>" style="width:100%">
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
				<div>
					<label for="jenis_kelamin" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jenis Kelamin</label>
					<select id="jenis_kelamin" name="jenis_kelamin" style="width:100%">
						<option value="">-- Pilih Jenis Kelamin --</option>
						<option value="L" <?= set_select('jenis_kelamin', 'L') ?>>Laki-laki</option>
						<option value="P" <?= set_select('jenis_kelamin', 'P') ?>>Perempuan</option>
					</select>
				</div>
				<div>
					<label for="status_pernikahan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Status Pernikahan</label>
					<select id="status_pernikahan" name="status_pernikahan" style="width:100%">
						<option value="">-- Pilih Status --</option>
						<?php foreach (array('Belum_Menikah' => 'Belum Menikah', 'Menikah' => 'Menikah', 'Cerai' => 'Cerai') as $sk => $sl): ?>
							<option value="<?= $sk ?>" <?= set_select('status_pernikahan', $sk) ?>><?= $sl ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 2fr; gap:12px">
				<div>
					<label for="kota_domisili" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Kota Domisili Saat Ini</label>
					<input type="text" id="kota_domisili" name="kota_domisili" value="<?= set_value('kota_domisili') ?>" placeholder="Mis. Jakarta Selatan" style="width:100%">
				</div>
				<div>
					<label for="alamat_lengkap" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Alamat Tempat Tinggal</label>
					<input type="text" id="alamat_lengkap" name="alamat_lengkap" value="<?= set_value('alamat_lengkap') ?>" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan" style="width:100%">
				</div>
			</div>
		</div>
	</fieldset>

	<!-- Section 2: Pendidikan Terakhir -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:16px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			2. Riwayat Pendidikan
		</legend>

		<div style="display:grid; grid-template-columns:140px 1fr 1fr; gap:12px">
			<div>
				<label for="pendidikan_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jenjang</label>
				<input type="text" id="pendidikan_terakhir" name="pendidikan_terakhir" value="<?= set_value('pendidikan_terakhir') ?>" placeholder="SMA / D3 / S1" style="width:100%">
			</div>
			<div>
				<label for="nama_sekolah" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Institusi / Kampus</label>
				<input type="text" id="nama_sekolah" name="nama_sekolah" value="<?= set_value('nama_sekolah') ?>" placeholder="Nama sekolah / perguruan tinggi" style="width:100%">
			</div>
			<div>
				<label for="jurusan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jurusan / Program Studi</label>
				<input type="text" id="jurusan" name="jurusan" value="<?= set_value('jurusan') ?>" placeholder="Jurusan yang ditempuh" style="width:100%">
			</div>
		</div>
	</fieldset>

	<!-- Section 3: Pengalaman Kerja Terakhir -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:16px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			3. Pengalaman Kerja Terakhir (Opsional / Jika Ada)
		</legend>

		<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
			<div>
				<label for="perusahaan_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Perusahaan Terakhir</label>
				<input type="text" id="perusahaan_terakhir" name="perusahaan_terakhir" value="<?= set_value('perusahaan_terakhir') ?>" placeholder="Nama instansi / perusahaan" style="width:100%">
			</div>
			<div>
				<label for="jabatan_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jabatan / Posisi Terakhir</label>
				<input type="text" id="jabatan_terakhir" name="jabatan_terakhir" value="<?= set_value('jabatan_terakhir') ?>" placeholder="Jabatan terakhir yang diemban" style="width:100%">
			</div>
		</div>

		<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px">
			<div>
				<label for="periode_kerja" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Periode Bekerja</label>
				<input type="text" id="periode_kerja" name="periode_kerja" value="<?= set_value('periode_kerja') ?>" placeholder="Mis. 2021 - 2024" style="width:100%">
			</div>
			<div>
				<label for="gaji_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Gaji Terakhir (Rp)</label>
				<input type="text" id="gaji_terakhir" name="gaji_terakhir" value="<?= set_value('gaji_terakhir') ?>" inputmode="numeric" placeholder="Contoh: 5000000" style="width:100%">
			</div>
			<div>
				<label for="gaji_diharapkan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Gaji Diharapkan (Rp)</label>
				<input type="text" id="gaji_diharapkan" name="gaji_diharapkan" value="<?= set_value('gaji_diharapkan') ?>" inputmode="numeric" placeholder="Contoh: 6000000" style="width:100%">
			</div>
		</div>
	</fieldset>

	<!-- Section 4: Kontak Darurat -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:16px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			4. Kontak Darurat
		</legend>

		<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px">
			<div>
				<label for="kd_nama" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Kontak Darurat</label>
				<input type="text" id="kd_nama" name="kd_nama" value="<?= set_value('kd_nama') ?>" placeholder="Nama keluarga / kerabat" style="width:100%">
			</div>
			<div>
				<label for="kd_telp" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor Telepon Kontak</label>
				<input type="text" id="kd_telp" name="kd_telp" value="<?= set_value('kd_telp') ?>" placeholder="08xxxxxxxxxx" style="width:100%">
			</div>
			<div>
				<label for="kd_hub" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Hubungan Keluarga</label>
				<input type="text" id="kd_hub" name="kd_hub" value="<?= set_value('kd_hub') ?>" placeholder="Ayah / Ibu / Suami / Istri" style="width:100%">
			</div>
		</div>
	</fieldset>

	<!-- Section 5: Unggah Berkas CV -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:16px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			5. Berkas Curriculum Vitae (CV)
		</legend>

		<div style="background:var(--surface-2); border:1px dashed var(--border); border-radius:8px; padding:16px">
			<label for="cv" style="display:block; font-size:13px; font-weight:600; margin-bottom:6px">
				Unggah File CV Anda <span style="color:var(--crit)">*</span>
			</label>
			<input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required style="width:100%; font-size:13px">
			<div class="muted" style="font-size:11.5px; margin-top:6px">
				Format file yang didukung: PDF, DOC, DOCX, JPG, PNG. Ukuran file maksimal 5 MB.
			</div>
		</div>
	</fieldset>

	<!-- Section 6: Persetujuan Perlindungan Data Pribadi (PDP) -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:16px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			6. Pernyataan &amp; Persetujuan Data Pribadi (PDP)
		</legend>

		<div style="display:flex; flex-direction:column; gap:10px; background:var(--surface-2); padding:16px; border-radius:8px; font-size:12.5px; line-height:1.5">
			<label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-weight:500">
				<input type="checkbox" name="consent" value="1" <?= set_checkbox('consent', '1') ?> required style="margin-top:3px">
				<span>
					<strong>Pernyataan Kebenaran Data &amp; Persetujuan Pemrosesan Pelamar (Wajib) *</strong><br>
					Saya menyatakan bahwa seluruh data yang saya berikan adalah benar dan dapat dipertanggungjawabkan. Saya menyetujui data saya diproses oleh Ratu Pertiwi Group untuk keperluan evaluasi seleksi rekrutmen sesuai ketentuan perlindungan data pribadi (versi <?= html_escape($this->config->item('erec_consent_versi')) ?>).
				</span>
			</label>
		</div>
	</fieldset>

	<!-- Tombol Submit Lamaran -->
	<div style="display:flex; justify-content:flex-end; gap:12px; margin-top:10px; padding-top:16px; border-top:1px solid var(--border)">
		<button type="submit" id="btnSubmitLamar" class="btn btn-primary" style="padding:12px 32px; font-size:14px; font-weight:700; display:inline-flex; align-items:center; gap:8px; transition:all .2s ease">
			<span>Kirim Lamaran Sekarang &rarr;</span>
		</button>
	</div>

	<?= form_close() ?>
</div>

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
	var form = document.getElementById('formLamar');
	var btn = document.getElementById('btnSubmitLamar');
	if (form && btn) {
		form.addEventListener('submit', function(e) {
			if (form.checkValidity && !form.checkValidity()) {
				return;
			}
			btn.classList.add('btn-submitting');
			btn.innerHTML = '<svg style="width:16px; height:16px; animation:erecSpin 0.9s linear infinite; flex:none" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"></circle></svg> <span>Sedang Mengirim &amp; Mengunggah Berkas...</span>';
		});
	}
});
</script>

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: manual/form.php -- Formulir Tambah Pelamar Manual (Walk-in / Referral)
 *
 * Fungsi:
 * - Menginput data kandidat walk-in atau referral outlet secara lengkap (setara Form Publik).
 * - Berelasi langsung dengan salah satu MPR yang sedang dibuka/aktif.
 * - Dapat diakses langsung dari menu sidebar (/manual) atau dari dalam pipeline (/manual/{id_req}).
 */
$id_req = ! empty($req) ? (int) $req['id_req'] : 0;
$action_url = $id_req > 0 ? site_url('manual/' . $id_req) : site_url('manual');
$back_url = $id_req > 0 ? site_url('pipeline/index/' . $id_req) : site_url('requisitions');
?>

<div style="max-width:960px; margin:0 auto">
	<!-- Top Navigation & Title -->
	<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px; color:var(--accent)">Intake Pelamar Langsung / Manual</div>
			<h1 style="margin:0 0 4px; font-size:22px; font-weight:700">
				<?= $id_req > 0 ? 'Tambah Pelamar ke Pipeline' : 'Entry Manual Pelamar' ?>
			</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Input data kandidat walk-in, referensi internal, atau pelamar outlet secara lengkap sesuai standar data formasi.
			</p>
		</div>
		<a href="<?= $back_url ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px">
			<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
			<span><?= $id_req > 0 ? 'Kembali ke Pipeline' : 'Kembali ke Daftar MPR' ?></span>
		</a>
	</div>

	<!-- Flash Message & Error Validation -->
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:16px">
			<svg style="width:16px; height:16px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
			<span><?= html_escape($this->session->flashdata('error')) ?></span>
		</div>
	<?php endif; ?>

	<?= validation_errors('<div class="flash err" style="margin-bottom:16px">', '</div>') ?>

	<?php if (empty($req) && empty($open_reqs)): ?>
		<!-- Kondisi tidak ada MPR yang sedang buka penerimaan -->
		<div class="card" style="padding:36px 20px; text-align:center; border-radius:12px">
			<svg style="width:40px; height:40px; margin:0 auto 12px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
			<h3 style="margin:0 0 6px; font-size:16px; font-weight:700">Tidak ada lowongan / MPR yang sedang dibuka</h3>
			<p class="muted" style="margin:0 0 16px; font-size:13px">
				Entry manual hanya dapat dikaitkan dengan dokumen MPR yang sedang berstatus aktif menerima lamaran (Approved / Sourcing).
			</p>
			<a href="<?= site_url('requisitions') ?>" class="btn btn-sm btn-primary">Lihat Daftar MPR</a>
		</div>
	<?php else: ?>

		<!-- ================= INFORMASI TARGET LOWONGAN (MPR) ================= -->
		<?php if (! empty($req)): ?>
			<!-- Terkunci pada 1 MPR (dari pipeline) -->
			<div class="card" style="padding:16px 20px; margin-bottom:20px; background:var(--surface-2); border-left:4px solid var(--accent); border-radius:10px">
				<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px">
					<div>
						<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em; margin-bottom:2px">
							Target Lowongan &amp; Dokumen MPR Terkunci
						</div>
						<div style="font-size:16px; font-weight:700; color:var(--text); line-height:1.3">
							<?= html_escape($req['nama_posisi']) ?>
						</div>
						<div class="muted" style="font-size:12.5px; margin-top:4px; display:flex; gap:8px; flex-wrap:wrap; align-items:center">
							<span class="mono"><strong><?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?></strong></span>
							<span>&bull;</span>
							<span>Departemen: <strong><?= html_escape($req['departemen'] ?: '-') ?></strong></span>
							<span>&bull;</span>
							<span>Penempatan: <strong><?= html_escape($req['tipe_penempatan']) ?><?= $req['nama_outlet'] ? ' / ' . html_escape($req['nama_outlet']) : '' ?></strong></span>
						</div>
					</div>
					<div style="display:flex; gap:8px; align-items:center">
						<span class="tag on" style="font-size:11px; padding:3px 10px; font-weight:700">
							<?= html_escape(label_status_req($req['status_req'])) ?>
						</span>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- ================= FORMULIR LENGKAP PELAMAR ================= -->
		<div class="card" style="padding:24px 28px; border-radius:12px">
			<?= form_open_multipart($action_url, array('style' => 'display:flex; flex-direction:column; gap:22px')) ?>

			<?php if (! empty($req)): ?>
				<input type="hidden" name="id_req" value="<?= $id_req ?>">
			<?php else: ?>
				<!-- Dropdown Pemilihan MPR Aktif (Bila Dibuka Langsung Dari Menu Sidebar) -->
				<fieldset style="border:none; padding:0; margin:0; background:var(--surface-2); border-radius:8px; padding:16px; border:1px solid var(--border)">
					<legend style="font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:8px">
						Pilih Lowongan / Dokumen MPR yang Sedang Dibuka <span style="color:var(--crit)">*</span>
					</legend>
					<label for="id_req" style="display:block; font-size:12px; color:var(--text-muted); margin-bottom:6px">
						Pelamar manual akan otomatis dikaitkan ke formasi MPR dan alur seleksi posisi ini:
					</label>
					<select id="id_req" name="id_req" required style="width:100%; font-size:13px; padding:9px 10px; border-radius:6px">
						<option value="">-- Pilih Lowongan / MPR Aktif --</option>
						<?php foreach ($open_reqs as $or): ?>
							<option value="<?= (int) $or['id_req'] ?>" <?= set_select('id_req', $or['id_req']) ?>>
								<?= html_escape(($or['no_mpr'] ?: '#' . $or['id_req']) . ' — ' . $or['nama_posisi'] . ' (' . ($or['departemen'] ?: 'Umum') . ' - ' . $or['tipe_penempatan'] . ($or['nama_outlet'] ? '/' . $or['nama_outlet'] : '') . ')') ?>
							</option>
						<?php endforeach; ?>
					</select>
				</fieldset>
			<?php endif; ?>

			<!-- Section 1: Data Pribadi -->
			<fieldset style="border:none; padding:0; margin:0">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					1. Data Identitas Pribadi
				</legend>

				<div style="display:grid; grid-template-columns:1fr; gap:14px">
					<div>
						<label for="nama_lengkap" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">
							Nama Lengkap (Sesuai KTP) <span style="color:var(--crit)">*</span>
						</label>
						<input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= set_value('nama_lengkap') ?>" placeholder="Nama lengkap tanpa singkatan" required style="width:100%; font-size:13px; padding:8px 10px">
					</div>

					<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px">
						<div>
							<label for="email" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">
								Alamat Email Aktif <span style="color:var(--crit)">*</span>
							</label>
							<input type="email" id="email" name="email" value="<?= set_value('email') ?>" placeholder="nama@email.com" required style="width:100%; font-size:13px; padding:8px 10px">
						</div>
						<div>
							<label for="no_wa" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">
								Nomor WhatsApp Aktif <span style="color:var(--crit)">*</span>
							</label>
							<input type="text" id="no_wa" name="no_wa" value="<?= set_value('no_wa') ?>" placeholder="08xxxxxxxxxx / +62xx" required style="width:100%; font-size:13px; padding:8px 10px">
							<span class="muted" style="font-size:11px">Saluran utama kontak &amp; penjadwalan seleksi</span>
						</div>
					</div>

					<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px">
						<div>
							<label for="tempat_lahir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tempat Lahir</label>
							<input type="text" id="tempat_lahir" name="tempat_lahir" value="<?= set_value('tempat_lahir') ?>" placeholder="Kota tempat lahir" style="width:100%; font-size:13px; padding:8px 10px">
						</div>
						<div>
							<label for="tanggal_lahir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tanggal Lahir</label>
							<input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= set_value('tanggal_lahir') ?>" style="width:100%; font-size:13px; padding:8px 10px">
						</div>
					</div>

					<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px">
						<div>
							<label for="jenis_kelamin" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jenis Kelamin</label>
							<select id="jenis_kelamin" name="jenis_kelamin" style="width:100%; font-size:13px; padding:8px 10px">
								<option value="">-- Pilih Jenis Kelamin --</option>
								<option value="L" <?= set_select('jenis_kelamin', 'L') ?>>Laki-laki</option>
								<option value="P" <?= set_select('jenis_kelamin', 'P') ?>>Perempuan</option>
							</select>
						</div>
						<div>
							<label for="status_pernikahan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Status Pernikahan</label>
							<select id="status_pernikahan" name="status_pernikahan" style="width:100%; font-size:13px; padding:8px 10px">
								<option value="">-- Pilih Status --</option>
								<?php foreach (array('Belum_Menikah' => 'Belum Menikah', 'Menikah' => 'Menikah', 'Cerai' => 'Cerai') as $sk => $sl): ?>
									<option value="<?= $sk ?>" <?= set_select('status_pernikahan', $sk) ?>><?= $sl ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div style="display:grid; grid-template-columns:1fr 2fr; gap:14px">
						<div>
							<label for="kota_domisili" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Kota Domisili Saat Ini</label>
							<input type="text" id="kota_domisili" name="kota_domisili" value="<?= set_value('kota_domisili') ?>" placeholder="Mis. Jakarta Selatan" style="width:100%; font-size:13px; padding:8px 10px">
						</div>
						<div>
							<label for="alamat_lengkap" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Alamat Tempat Tinggal</label>
							<input type="text" id="alamat_lengkap" name="alamat_lengkap" value="<?= set_value('alamat_lengkap') ?>" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan" style="width:100%; font-size:13px; padding:8px 10px">
						</div>
					</div>
				</div>
			</fieldset>

			<!-- Section 2: Pendidikan Terakhir -->
			<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					2. Riwayat Pendidikan Terakhir
				</legend>

				<div style="display:grid; grid-template-columns:140px 1fr 1fr; gap:14px">
					<div>
						<label for="pendidikan_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jenjang</label>
						<input type="text" id="pendidikan_terakhir" name="pendidikan_terakhir" value="<?= set_value('pendidikan_terakhir') ?>" placeholder="SMA / D3 / S1" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="nama_sekolah" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Institusi / Kampus</label>
						<input type="text" id="nama_sekolah" name="nama_sekolah" value="<?= set_value('nama_sekolah') ?>" placeholder="Nama sekolah / universitas" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="jurusan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jurusan / Program Studi</label>
						<input type="text" id="jurusan" name="jurusan" value="<?= set_value('jurusan') ?>" placeholder="Jurusan yang ditempuh" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
				</div>
			</fieldset>

			<!-- Section 3: Pengalaman Kerja Terakhir -->
			<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					3. Pengalaman Kerja Terakhir (Opsional)
				</legend>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px">
					<div>
						<label for="perusahaan_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Perusahaan Terakhir</label>
						<input type="text" id="perusahaan_terakhir" name="perusahaan_terakhir" value="<?= set_value('perusahaan_terakhir') ?>" placeholder="Nama instansi / perusahaan" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="jabatan_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Jabatan / Posisi Terakhir</label>
						<input type="text" id="jabatan_terakhir" name="jabatan_terakhir" value="<?= set_value('jabatan_terakhir') ?>" placeholder="Jabatan terakhir yang diemban" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px">
					<div>
						<label for="periode_kerja" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Periode Bekerja</label>
						<input type="text" id="periode_kerja" name="periode_kerja" value="<?= set_value('periode_kerja') ?>" placeholder="Mis. 2021 - 2024" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="gaji_terakhir" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Gaji Terakhir (Rp)</label>
						<input type="text" id="gaji_terakhir" name="gaji_terakhir" value="<?= set_value('gaji_terakhir') ?>" inputmode="numeric" placeholder="Contoh: 5000000" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="gaji_diharapkan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Gaji Diharapkan (Rp)</label>
						<input type="text" id="gaji_diharapkan" name="gaji_diharapkan" value="<?= set_value('gaji_diharapkan') ?>" inputmode="numeric" placeholder="Contoh: 6000000" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
				</div>
			</fieldset>

			<!-- Section 4: Kontak Darurat -->
			<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					4. Kontak Darurat
				</legend>

				<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px">
					<div>
						<label for="kd_nama" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Kontak Darurat</label>
						<input type="text" id="kd_nama" name="kd_nama" value="<?= set_value('kd_nama') ?>" placeholder="Nama keluarga / kerabat" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="kd_telp" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor Telepon Kontak</label>
						<input type="text" id="kd_telp" name="kd_telp" value="<?= set_value('kd_telp') ?>" placeholder="08xxxxxxxxxx" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="kd_hub" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Hubungan Keluarga</label>
						<input type="text" id="kd_hub" name="kd_hub" value="<?= set_value('kd_hub') ?>" placeholder="Ayah / Ibu / Pasangan" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
				</div>
			</fieldset>

			<!-- Section 5: Berkas CV (Opsional untuk Entry Manual) -->
			<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					5. Berkas Curriculum Vitae (CV)
				</legend>

				<div style="background:var(--surface-2); border:1px dashed var(--border); border-radius:8px; padding:16px">
					<label for="cv" style="display:block; font-size:13px; font-weight:600; margin-bottom:6px">
						Unggah Berkas CV Pelamar (Opsional)
					</label>
					<input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="width:100%; font-size:13px">
					<div class="muted" style="font-size:11.5px; margin-top:6px">
						Format yang didukung: PDF, DOC, DOCX, JPG, PNG. Ukuran file maksimal 5 MB. (Jika berkas fisik belum discan, dapat disusulkan kemudian pada detail kandidat).
					</div>
				</div>
			</fieldset>

			<!-- Section 6: Catatan Intake Internal HR -->
			<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					6. Catatan Operasional HR (Internal)
				</legend>

				<div style="display:grid; grid-template-columns:220px 1fr; gap:14px">
					<div>
						<label for="tanggal_join" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Rencana Tanggal Join</label>
						<input type="date" id="tanggal_join" name="tanggal_join" value="<?= set_value('tanggal_join') ?>" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
					<div>
						<label for="catatan_internal" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Keterangan Sumber / Rekomendasi</label>
						<input type="text" id="catatan_internal" name="catatan_internal" value="<?= set_value('catatan_internal') ?>" placeholder="Contoh: Walk-in di outlet cabang A, referensi dari karyawan B" style="width:100%; font-size:13px; padding:8px 10px">
					</div>
				</div>
			</fieldset>

			<!-- Section 7: Persetujuan PDP & Kebijakan Data -->
			<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
				<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px">
					<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
					7. Pernyataan &amp; Persetujuan Data Pribadi (PDP)
				</legend>

				<div style="display:flex; flex-direction:column; gap:10px; background:var(--surface-2); padding:16px; border-radius:8px; font-size:12.5px; line-height:1.5">
					<label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-weight:500">
						<input type="checkbox" name="consent" value="1" <?= set_checkbox('consent', '1', TRUE) ?> required style="margin-top:3px">
						<span>
							<strong>Pernyataan Kebenaran Data &amp; Persetujuan PDP (Wajib) *</strong><br>
							Kandidat telah menyatakan dan menyetujui bahwa seluruh informasi yang diberikan adalah benar dan bersedia diproses dalam seleksi rekrutmen RPG sesuai ketentuan perlindungan data pribadi (versi <?= html_escape($this->config->item('erec_consent_versi')) ?>).
						</span>
					</label>
				</div>
			</fieldset>

			<!-- Tombol Action Submit & Batal -->
			<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:8px; padding-top:18px; border-top:1px solid var(--border)">
				<a href="<?= $back_url ?>" class="btn btn-ghost" style="padding:10px 20px; font-size:13.5px">
					Batal
				</a>
				<button type="submit" class="btn btn-primary" style="padding:10px 28px; font-size:14px; font-weight:700; display:inline-flex; align-items:center; gap:8px">
					<svg style="width:16px; height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
					<span>Simpan &amp; Masukkan ke Pipeline</span>
				</button>
			</div>

			<?= form_close() ?>
		</div>
	<?php endif; ?>
</div>

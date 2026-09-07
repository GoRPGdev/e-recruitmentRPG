<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- Header Identitas Karir RPG -->
<div style="text-align:center; margin-bottom:24px">
	<div style="display:inline-flex; align-items:center; gap:10px; margin-bottom:8px">
		<div style="width:38px; height:38px; border-radius:10px; background:var(--accent); color:var(--accent-contrast); display:grid; place-items:center; font-family:'Archivo',sans-serif; font-weight:700; font-size:16px">
			RPG
		</div>
		<div style="text-align:left">
			<div style="font-family:'Archivo',sans-serif; font-weight:700; font-size:16.5px; color:var(--text); line-height:1.2">Ratu Pertiwi Group</div>
			<div style="font-size:11.5px; color:var(--text-faint)">Formulir Kelengkapan Onboarding Karyawan Baru</div>
		</div>
	</div>
</div>

<!-- ================= KARTU INFO KANDIDAT & POSISI ================= -->
<div class="card" style="padding:22px 26px; margin-bottom:22px; border-top:3px solid var(--accent)">
	<div class="eyebrow" style="margin-bottom:3px; color:var(--accent); font-weight:700">Pemberkasan Onboarding</div>
	<h1 style="margin:0 0 6px; font-size:20px; font-weight:700; color:var(--text)">
		Selamat Datang, <?= html_escape($t['nama_lengkap']) ?>!
	</h1>
	<p class="muted" style="margin:0; font-size:13px; line-height:1.5">
		Anda telah diterima untuk bergabung di Ratu Pertiwi Group pada posisi <strong><?= html_escape($t['nama_posisi']) ?></strong>
		<?= !empty($t['nama_departemen']) ? ' (' . html_escape($t['nama_departemen']) . ')' : '' ?>
		<?= !empty($t['nama_outlet']) ? ' &mdash; ' . html_escape($t['nama_outlet']) : '' ?>.
		Silakan melengkapi formulir onboarding di bawah ini untuk keperluan administrasi kepegawaian dan payroll.
	</p>
</div>

<!-- ================= KARTU DATA FORM 1 (PRE-FILLED & READ-ONLY) ================= -->
<div class="card" style="padding:20px 24px; margin-bottom:22px; background:var(--surface-2); border:1px solid var(--border)">
	<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px">
		<h2 style="margin:0; font-size:14px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--good)"></span>
			Data Pribadi Terverifikasi (Form Awal)
		</h2>
		<span class="tag on" style="font-size:10.5px">Otomatis Terisi</span>
	</div>
	<p class="muted" style="font-size:12px; margin-bottom:12px">
		Data di bawah ini diambil langsung dari pendaftaran Anda sebelumnya dan tidak perlu diisi ulang.
	</p>

	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; font-size:12.5px">
		<div>
			<span class="muted" style="display:block; font-size:11px">Nama Lengkap</span>
			<strong style="color:var(--text)"><?= html_escape($t['nama_lengkap']) ?></strong>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Nomor WhatsApp</span>
			<span class="mono" style="color:var(--text); font-weight:600"><?= html_escape($t['no_wa_normal'] ?: '-') ?></span>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Email Aktif</span>
			<strong style="color:var(--text)"><?= html_escape($t['email'] ?: '-') ?></strong>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Tempat &amp; Tanggal Lahir</span>
			<strong style="color:var(--text)"><?= html_escape($t['tempat_lahir'] ?: '-') ?>, <?= !empty($t['tanggal_lahir']) ? html_escape(substr($t['tanggal_lahir'], 0, 10)) : '-' ?></strong>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Jenis Kelamin</span>
			<strong style="color:var(--text)"><?= $t['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($t['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></strong>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Status Pernikahan</span>
			<strong style="color:var(--text)"><?= html_escape($t['status_pernikahan'] ?: '-') ?></strong>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Pendidikan Terakhir</span>
			<strong style="color:var(--text)"><?= html_escape($t['pendidikan_terakhir'] ?: '-') ?> (<?= html_escape($t['nama_sekolah'] ?: '-') ?>)</strong>
		</div>
		<div>
			<span class="muted" style="display:block; font-size:11px">Kontak Darurat</span>
			<strong style="color:var(--text)"><?= html_escape($t['kontak_darurat_nama'] ?: '-') ?> (<?= html_escape($t['kontak_darurat_hub'] ?: '-') ?> - <?= html_escape($t['kontak_darurat_telp'] ?: '-') ?>)</strong>
		</div>
	</div>
</div>

<!-- ================= FORMULIR ONBOARDING LANJUTAN ================= -->
<div class="card" style="padding:24px 28px; margin-bottom:30px">
	<div style="margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--border)">
		<h2 style="margin:0 0 4px; font-size:17px; font-weight:700">Formulir Kelengkapan Berkas &amp; Data Onboarding</h2>
		<p class="muted" style="margin:0; font-size:13px">
			Lengkapi data identitas resmi, rekening payroll, riwayat pengalaman kerja, dan susunan keluarga Anda.
		</p>
	</div>

	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:16px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>

	<?= form_open(site_url('onboarding/' . $this->uri->segment(2)), array('id' => 'form-onboarding', 'style' => 'display:flex; flex-direction:column; gap:24px')) ?>

	<!-- 1. IDENTITAS RESMI & DEMOGRAFI LANJUTAN -->
	<fieldset style="border:none; padding:0; margin:0">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			1. Data Identitas Resmi &amp; Tambahan
		</legend>

		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px">
			<div>
				<label for="nama_panggilan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Panggilan</label>
				<input type="text" id="nama_panggilan" name="nama_panggilan" value="<?= set_value('nama_panggilan', $t['nama_panggilan'] ?? '') ?>" placeholder="Nama akrab sehari-hari" style="width:100%">
			</div>

			<div>
				<label for="nik" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor Induk Kependudukan (NIK KTP) <span style="color:var(--crit)">*</span></label>
				<input type="text" id="nik" name="nik" value="<?= set_value('nik', $t['nik'] ?? '') ?>" placeholder="16 digit NIK sesuai KTP" maxlength="20" required style="width:100%">
			</div>

			<div>
				<label for="npwp" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor NPWP</label>
				<input type="text" id="npwp" name="npwp" value="<?= set_value('npwp', $t['npwp'] ?? '') ?>" placeholder="Nomor Pokok Wajib Pajak (opsional)" style="width:100%">
			</div>

			<div>
				<label for="no_sim" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor SIM (A / C)</label>
				<input type="text" id="no_sim" name="no_sim" value="<?= set_value('no_sim', $t['no_sim'] ?? '') ?>" placeholder="Misal: SIM C 1234xxxx (opsional)" style="width:100%">
			</div>

			<div>
				<label for="agama" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Agama</label>
				<select id="agama" name="agama" style="width:100%">
					<option value="">-- Pilih Agama --</option>
					<?php foreach (array('Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya') as $ag): ?>
						<option value="<?= $ag ?>" <?= (set_value('agama', $t['agama'] ?? '') === $ag) ? 'selected' : '' ?>><?= $ag ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div>
				<label for="gol_darah" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Golongan Darah</label>
				<select id="gol_darah" name="gol_darah" style="width:100%">
					<option value="">-- Pilih Gol. Darah --</option>
					<?php foreach (array('A', 'B', 'AB', 'O') as $gd): ?>
						<option value="<?= $gd ?>" <?= (set_value('gol_darah', $t['gol_darah'] ?? '') === $gd) ? 'selected' : '' ?>><?= $gd ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div>
				<label for="tinggi_badan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tinggi Badan (cm)</label>
				<input type="number" id="tinggi_badan" name="tinggi_badan" value="<?= set_value('tinggi_badan', $t['tinggi_badan'] ?? '') ?>" placeholder="Mis. 170" min="100" max="250" style="width:100%">
			</div>

			<div>
				<label for="berat_badan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Berat Badan (kg)</label>
				<input type="number" id="berat_badan" name="berat_badan" value="<?= set_value('berat_badan', $t['berat_badan'] ?? '') ?>" placeholder="Mis. 65" min="30" max="200" style="width:100%">
			</div>
		</div>
	</fieldset>

	<!-- 2. DATA REKENING BANK (PAYROLL) -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			2. Data Rekening Bank untuk Payroll / Gaji
		</legend>

		<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:12px">
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px">
				<div>
					<label for="nama_bank" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Bank <span style="color:var(--crit)">*</span></label>
					<input type="text" id="nama_bank" name="nama_bank" value="<?= set_value('nama_bank', $t['nama_bank'] ?? 'BCA') ?>" placeholder="Misal: BCA / Mandiri / BRI / BNI" required style="width:100%">
				</div>
				<div>
					<label for="no_rekening" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor Rekening Bank <span style="color:var(--crit)">*</span></label>
					<input type="text" id="no_rekening" name="no_rekening" value="<?= set_value('no_rekening', $t['no_rekening'] ?? '') ?>" placeholder="Nomor rekening tanpa spasi / tanda minus" required style="width:100%">
				</div>
				<div>
					<label for="nama_pemilik_bank" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Pemilik Rekening (Sesuai Buku Tabungan) <span style="color:var(--crit)">*</span></label>
					<input type="text" id="nama_pemilik_bank" name="nama_pemilik_bank" value="<?= set_value('nama_pemilik_bank', $t['nama_pemilik_bank'] ?? $t['nama_lengkap']) ?>" placeholder="Nama pemilik rekening" required style="width:100%">
				</div>
			</div>
			<div class="muted" style="font-size:11.5px; margin-top:8px">
				Rekening wajib atas nama pribadi kandidat untuk kelancaran transfer penggajian bulanan.
			</div>
		</div>
	</fieldset>

	<!-- 3. RIWAYAT PENGALAMAN KERJA (MULTI-ITEM DINAMIS) -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px">
			<legend style="font-size:14px; font-weight:700; color:var(--text); margin:0; display:flex; align-items:center; gap:6px">
				<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
				3. Riwayat Pengalaman Kerja Lengkap
			</legend>
			<button type="button" class="btn btn-sm btn-ghost" onclick="addExperienceRow()" style="font-size:12px; font-weight:600">
				+ Tambah Riwayat Pekerjaan
			</button>
		</div>

		<p class="muted" style="font-size:12.5px; margin-bottom:12px">
			Tambahkan riwayat perusahaan tempat Anda pernah bekerja sebelumnya (urutkan dari yang terbaru).
		</p>

		<div id="experience-container" style="display:flex; flex-direction:column; gap:12px">
			<?php
			$exp_init = !empty($experiences) ? $experiences : array(
				array(
					'nama_perusahaan' => $t['perusahaan_terakhir'] ?? '',
					'posisi_jabatan'  => $t['jabatan_terakhir'] ?? '',
					'periode_kerja'   => $t['periode_kerja'] ?? '',
					'gaji_terakhir'   => $t['gaji_terakhir'] ?? '',
					'alasan_keluar'   => '',
					'deskripsi_tugas' => '',
				)
			);
			?>

			<?php foreach ($exp_init as $idx => $e): ?>
				<div class="exp-card" style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px; position:relative">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
						<strong style="font-size:13px; color:var(--text)" class="exp-num">Pekerjaan #<?= $idx + 1 ?></strong>
						<button type="button" class="btn-sm btn-ghost" onclick="removeExpCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11.5px" <?= count($exp_init) === 1 ? 'hidden' : '' ?>>
							✕ Hapus Baris
						</button>
					</div>

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:10px">
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Perusahaan / Instansi</label>
							<input type="text" name="exp[<?= $idx ?>][nama_perusahaan]" value="<?= html_escape($e['nama_perusahaan'] ?? '') ?>" placeholder="PT Contoh Nama Perusahaan" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Jabatan / Posisi</label>
							<input type="text" name="exp[<?= $idx ?>][posisi_jabatan]" value="<?= html_escape($e['posisi_jabatan'] ?? '') ?>" placeholder="Misal: Staff Admin / Kasir / SPV" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Periode Bekerja</label>
							<input type="text" name="exp[<?= $idx ?>][periode_kerja]" value="<?= html_escape($e['periode_kerja'] ?? '') ?>" placeholder="Misal: 2021 - 2023" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Gaji Terakhir (Rp)</label>
							<input type="text" name="exp[<?= $idx ?>][gaji_terakhir]" value="<?= html_escape($e['gaji_terakhir'] ?? '') ?>" placeholder="Contoh: 5000000" style="width:100%; font-size:12.5px">
						</div>
					</div>

					<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Alasan Berhenti / Pindah</label>
							<input type="text" name="exp[<?= $idx ?>][alasan_keluar]" value="<?= html_escape($e['alasan_keluar'] ?? '') ?>" placeholder="Misal: Habis kontrak / Pengembangan karir" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Uraian Singkat Tanggung Jawab</label>
							<input type="text" name="exp[<?= $idx ?>][deskripsi_tugas]" value="<?= html_escape($e['deskripsi_tugas'] ?? '') ?>" placeholder="Uraian pekerjaan utama" style="width:100%; font-size:12.5px">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</fieldset>

	<!-- 4. SUSUNAN ANGGOTA KELUARGA (MULTI-ITEM DINAMIS) -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px">
			<legend style="font-size:14px; font-weight:700; color:var(--text); margin:0; display:flex; align-items:center; gap:6px">
				<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
				4. Susunan Anggota Keluarga
			</legend>
			<button type="button" class="btn btn-sm btn-ghost" onclick="addFamilyRow()" style="font-size:12px; font-weight:600">
				+ Tambah Anggota Keluarga
			</button>
		</div>

		<p class="muted" style="font-size:12.5px; margin-bottom:12px">
			Cantumkan keluarga inti (Ayah, Ibu, Suami/Istri, Anak, atau Saudara Kandung).
		</p>

		<div id="family-container" style="display:flex; flex-direction:column; gap:10px">
			<?php
			$fam_init = !empty($families) ? $families : array(
				array('hubungan' => 'Ayah', 'nama_lengkap' => '', 'jenis_kelamin' => 'L', 'usia' => '', 'pendidikan' => '', 'pekerjaan' => '', 'no_telp' => ''),
				array('hubungan' => 'Ibu',  'nama_lengkap' => '', 'jenis_kelamin' => 'P', 'usia' => '', 'pendidikan' => '', 'pekerjaan' => '', 'no_telp' => ''),
			);
			?>

			<?php foreach ($fam_init as $fidx => $f): ?>
				<div class="fam-card" style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px; position:relative">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
						<strong style="font-size:12.5px; color:var(--text)" class="fam-num">Anggota Keluarga #<?= $fidx + 1 ?></strong>
						<button type="button" class="btn-sm btn-ghost" onclick="removeFamCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11px">
							✕ Hapus
						</button>
					</div>

					<div style="display:grid; grid-template-columns:130px 1.5fr 70px 60px 100px 1fr 110px; gap:8px; align-items:center">
						<div>
							<select name="fam[<?= $fidx ?>][hubungan]" style="width:100%; font-size:12px; padding:6px 4px">
								<?php foreach (array('Ayah', 'Ibu', 'Suami', 'Istri', 'Anak', 'Kakak', 'Adik') as $hub): ?>
									<option value="<?= $hub ?>" <?= ($f['hubungan'] ?? '') === $hub ? 'selected' : '' ?>><?= $hub ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<input type="text" name="fam[<?= $fidx ?>][nama_lengkap]" value="<?= html_escape($f['nama_lengkap'] ?? '') ?>" placeholder="Nama lengkap" style="width:100%; font-size:12px; padding:6px">
						</div>
						<div>
							<select name="fam[<?= $fidx ?>][jenis_kelamin]" style="width:100%; font-size:12px; padding:6px 4px">
								<option value="L" <?= ($f['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>L</option>
								<option value="P" <?= ($f['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>P</option>
							</select>
						</div>
						<div>
							<input type="number" name="fam[<?= $fidx ?>][usia]" value="<?= html_escape($f['usia'] ?? '') ?>" placeholder="Usia" style="width:100%; font-size:12px; padding:6px">
						</div>
						<div>
							<input type="text" name="fam[<?= $fidx ?>][pendidikan]" value="<?= html_escape($f['pendidikan'] ?? '') ?>" placeholder="Pendidikan" style="width:100%; font-size:12px; padding:6px">
						</div>
						<div>
							<input type="text" name="fam[<?= $fidx ?>][pekerjaan]" value="<?= html_escape($f['pekerjaan'] ?? '') ?>" placeholder="Pekerjaan / Usaha" style="width:100%; font-size:12px; padding:6px">
						</div>
						<div>
							<input type="text" name="fam[<?= $fidx ?>][no_telp]" value="<?= html_escape($f['no_telp'] ?? '') ?>" placeholder="No. Telp" style="width:100%; font-size:12px; padding:6px">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</fieldset>

	<!-- 5. DATA KESEHATAN KHUSUS (UU PDP) -->
	<fieldset style="border:none; padding:0; margin:0; border-top:1px solid var(--border); padding-top:18px">
		<legend style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:10px; display:flex; align-items:center; gap:6px">
			<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--accent)"></span>
			5. Keterangan Riwayat Kesehatan Khusus (Opsional)
		</legend>

		<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px">
			<label for="riwayat_penyakit" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">
				Apakah Anda memiliki riwayat penyakit berat, rawat inap Rumah Sakit, atau alergi tertentu?
			</label>
			<textarea id="riwayat_penyakit" name="riwayat_penyakit" rows="2" placeholder="Tuliskan keterangan bila ada (mis. riwayat asma, tindakan operasi, alergi obat). Kosongkan jika tidak ada." style="width:100%; font-size:13px; margin-bottom:10px"><?= set_value('riwayat_penyakit', $t['riwayat_penyakit'] ?? '') ?></textarea>

			<label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer; font-size:12px; color:var(--text-muted)">
				<input type="checkbox" name="consent_kesehatan" value="1" <?= set_checkbox('consent_kesehatan', '1', !empty($t['consent_kesehatan'])) ?> style="margin-top:2px">
				<span>
					Saya bersedia memberikan informasi riwayat kesehatan ini secara sukarela untuk keperluan penyesuaian lingkungan kerja dan tanggap darurat medis perusahaan sesuai UU Pelindungan Data Pribadi (UU PDP No. 27/2022).
				</span>
			</label>
		</div>
	</fieldset>

	<!-- 6. PERNYATAAN KEBENARAN DATA -->
	<div style="background:var(--surface-2); border-radius:8px; padding:16px; font-size:12.5px; line-height:1.5; border:1px solid var(--border)">
		<label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-weight:600">
			<input type="checkbox" required style="margin-top:3px">
			<span>
				Pernyataan Keabsahan Data *<br>
				<span style="font-weight:400; color:var(--text-muted)">
					Dengan ini saya menyatakan bahwa seluruh keterangan dan data yang saya sampaikan dalam formulir onboarding ini adalah benar dan sesuai dengan kondisi sebenarnya. Apabila di kemudian hari ditemukan ketidaksesuaian atau pemalsuan data, saya bersedia menerima sanksi sesuai ketentuan peraturan perusahaan Ratu Pertiwi Group.
				</span>
			</span>
		</label>
	</div>

	<!-- TOMBOL SUBMIT -->
	<div style="display:flex; justify-content:flex-end; gap:12px; padding-top:14px; border-top:1px solid var(--border)">
		<button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:14px; font-weight:700">
			Kirim Data Onboarding &rarr;
		</button>
	</div>

	<?= form_close() ?>
</div>

<script>
var expCount = <?= count($exp_init) ?>;
var famCount = <?= count($fam_init) ?>;

function addExperienceRow() {
	var c = document.getElementById('experience-container');
	var idx = expCount++;
	var div = document.createElement('div');
	div.className = 'exp-card';
	div.style = 'background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px; position:relative';
	div.innerHTML = `
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
			<strong style="font-size:13px; color:var(--text)" class="exp-num">Pekerjaan #${c.children.length + 1}</strong>
			<button type="button" class="btn-sm btn-ghost" onclick="removeExpCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11.5px">
				✕ Hapus Baris
			</button>
		</div>
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:10px">
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Perusahaan / Instansi</label>
				<input type="text" name="exp[${idx}][nama_perusahaan]" placeholder="PT Contoh Nama Perusahaan" style="width:100%; font-size:12.5px" required>
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Jabatan / Posisi</label>
				<input type="text" name="exp[${idx}][posisi_jabatan]" placeholder="Misal: Staff / Kasir / SPV" style="width:100%; font-size:12.5px" required>
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Periode Bekerja</label>
				<input type="text" name="exp[${idx}][periode_kerja]" placeholder="Misal: 2021 - 2023" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Gaji Terakhir (Rp)</label>
				<input type="text" name="exp[${idx}][gaji_terakhir]" placeholder="Contoh: 5000000" style="width:100%; font-size:12.5px">
			</div>
		</div>
		<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Alasan Berhenti / Pindah</label>
				<input type="text" name="exp[${idx}][alasan_keluar]" placeholder="Misal: Habis kontrak" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Uraian Singkat Tanggung Jawab</label>
				<input type="text" name="exp[${idx}][deskripsi_tugas]" placeholder="Uraian pekerjaan utama" style="width:100%; font-size:12.5px">
			</div>
		</div>
	`;
	c.appendChild(div);
	renumberExp();
}

function removeExpCard(btn) {
	var c = document.getElementById('experience-container');
	if (c.children.length <= 1) return;
	btn.closest('.exp-card').remove();
	renumberExp();
}

function renumberExp() {
	var c = document.getElementById('experience-container');
	var items = c.querySelectorAll('.exp-card');
	items.forEach(function(el, i) {
		el.querySelector('.exp-num').textContent = 'Pekerjaan #' + (i + 1);
		var delBtn = el.querySelector('button');
		if (delBtn) delBtn.hidden = (items.length === 1);
	});
}

function addFamilyRow() {
	var c = document.getElementById('family-container');
	var idx = famCount++;
	var div = document.createElement('div');
	div.className = 'fam-card';
	div.style = 'background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px; position:relative';
	div.innerHTML = `
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
			<strong style="font-size:12.5px; color:var(--text)" class="fam-num">Anggota Keluarga #${c.children.length + 1}</strong>
			<button type="button" class="btn-sm btn-ghost" onclick="removeFamCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11px">
				✕ Hapus
			</button>
		</div>
		<div style="display:grid; grid-template-columns:130px 1.5fr 70px 60px 100px 1fr 110px; gap:8px; align-items:center">
			<div>
				<select name="fam[${idx}][hubungan]" style="width:100%; font-size:12px; padding:6px 4px">
					<option value="Ayah">Ayah</option>
					<option value="Ibu">Ibu</option>
					<option value="Suami">Suami</option>
					<option value="Istri">Istri</option>
					<option value="Anak">Anak</option>
					<option value="Kakak">Kakak</option>
					<option value="Adik">Adik</option>
				</select>
			</div>
			<div>
				<input type="text" name="fam[${idx}][nama_lengkap]" placeholder="Nama lengkap" style="width:100%; font-size:12px; padding:6px" required>
			</div>
			<div>
				<select name="fam[${idx}][jenis_kelamin]" style="width:100%; font-size:12px; padding:6px 4px">
					<option value="L">L</option>
					<option value="P">P</option>
				</select>
			</div>
			<div>
				<input type="number" name="fam[${idx}][usia]" placeholder="Usia" style="width:100%; font-size:12px; padding:6px">
			</div>
			<div>
				<input type="text" name="fam[${idx}][pendidikan]" placeholder="Pendidikan" style="width:100%; font-size:12px; padding:6px">
			</div>
			<div>
				<input type="text" name="fam[${idx}][pekerjaan]" placeholder="Pekerjaan / Usaha" style="width:100%; font-size:12px; padding:6px">
			</div>
			<div>
				<input type="text" name="fam[${idx}][no_telp]" placeholder="No. Telp" style="width:100%; font-size:12px; padding:6px">
			</div>
		</div>
	`;
	c.appendChild(div);
	renumberFam();
}

function removeFamCard(btn) {
	btn.closest('.fam-card').remove();
	renumberFam();
}

function renumberFam() {
	var c = document.getElementById('family-container');
	var items = c.querySelectorAll('.fam-card');
	items.forEach(function(el, i) {
		el.querySelector('.fam-num').textContent = 'Anggota Keluarga #' + (i + 1);
	});
}
</script>

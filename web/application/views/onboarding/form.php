<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: onboarding/form.php -- Formulir Mandiri Kelengkapan Data Pelamar & Onboarding (Section A - J)
 *
 * Fungsi:
 * - Antarmuka pengisian data komprehensif calon karyawan (identitas, keluarga, riwayat kerja, kesehatan, rekening payroll, kuesioner).
 * - Dilengkapi fitur simpan otomatis (auto-save progress) berbasis token unik yang dapat dilanjutkan kapan saja.
 * - Mengunggah berkas pas foto dan data pendukung onboarding dengan proteksi kepatuhan privasi data.
 */
?>

<!-- Header Identitas Karir RPG -->
<div style="text-align:center; margin-bottom:20px">
	<div style="display:inline-flex; align-items:center; gap:10px; margin-bottom:6px">
		<div style="width:38px; height:38px; border-radius:10px; background:var(--accent); color:var(--accent-contrast); display:grid; place-items:center; font-family:'Archivo',sans-serif; font-weight:700; font-size:16px">
			RPG
		</div>
		<div style="text-align:left">
			<div style="font-family:'Archivo',sans-serif; font-weight:700; font-size:16.5px; color:var(--text); line-height:1.2">Ratu Pertiwi Group</div>
			<div style="font-size:11.5px; color:var(--text-faint)">Formulir Kelengkapan Data Pelamar &amp; Onboarding</div>
		</div>
	</div>
</div>

<!-- ================= KARTU INFO KANDIDAT & POSISI ================= -->
<div class="card" style="padding:18px 24px; margin-bottom:20px; border-top:3px solid var(--accent)">
	<div class="eyebrow" style="margin-bottom:3px; color:var(--accent); font-weight:700">Tahap Pengisian Form Pelamar Lanjutan</div>
	<h1 style="margin:0 0 6px; font-size:19px; font-weight:700; color:var(--text)">
		Halo, <?= html_escape($t['nama_lengkap']) ?>!
	</h1>
	<p class="muted" style="margin:0; font-size:13px; line-height:1.5">
		Terima kasih atas minat Anda melamar di Ratu Pertiwi Group untuk posisi <strong><?= html_escape($t['nama_posisi']) ?></strong>
		<?= !empty($t['nama_departemen']) ? ' (' . html_escape($t['nama_departemen']) . ')' : '' ?>
		<?= !empty($t['nama_outlet']) ? ' &mdash; ' . html_escape($t['nama_outlet']) : '' ?>.
		Silakan lengkapi tahapan data di bawah ini secara bertahap (per Section) sesuai berkas fisik standar pelamar RPG.
	</p>
</div>

<!-- ================= STEP WIZARD PROGRESS BAR ================= -->
<div class="card" style="padding:16px 20px; margin-bottom:20px; background:var(--surface)">
	<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
			<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
		<span id="wizard-step-label" style="font-size:12.5px; font-weight:700; color:var(--accent); text-transform:uppercase; letter-spacing:0.5px">
			Langkah 1 dari 8: Data Pribadi &amp; Kontak
		</span>
				<span id="autosave-indicator" style="font-size:11px; color:var(--text-muted); display:inline-flex; align-items:center; gap:5px; background:var(--surface-2); padding:3px 10px; border-radius:12px; border:1px solid var(--border); transition:all 0.2s">
					<span id="autosave-dot" style="width:6px; height:6px; border-radius:50%; background:var(--good); display:inline-block"></span>
					<span id="autosave-text">Tersimpan otomatis</span>
				</span>
			</div>
		<span id="wizard-step-percent" class="mono" style="font-size:12px; font-weight:700; color:var(--text)">
			12%
		</span>
	</div>
	<!-- Progress Track -->
	<div style="width:100%; height:8px; background:var(--border); border-radius:999px; overflow:hidden">
		<div id="wizard-progress-bar" style="width:12.5%; height:100%; background:var(--accent); transition:width 0.3s ease"></div>
	</div>

	<!-- Step Navigation Indicator (Mobile Wrap, No Horizontal Scroll) -->
	<div style="display:flex; gap:6px; flex-wrap:wrap; padding-top:14px; margin-top:10px; border-top:1px solid var(--border)" id="wizard-nav-chips">
		<button type="button" class="step-chip active" onclick="goToStep(1)">1. Data Pribadi</button>
		<button type="button" class="step-chip" onclick="goToStep(2)">2. Keluarga</button>
		<button type="button" class="step-chip" onclick="goToStep(3)">3. Pendidikan &amp; Pelatihan</button>
		<button type="button" class="step-chip" onclick="goToStep(4)">4. Pengalaman Kerja</button>
		<button type="button" class="step-chip" onclick="goToStep(5)">5. Keahlian &amp; Bahasa</button>
		<button type="button" class="step-chip" onclick="goToStep(6)">6. Referensi Kerja</button>
		<button type="button" class="step-chip" onclick="goToStep(7)">7. Minat &amp; Konsep Diri</button>
		<button type="button" class="step-chip" onclick="goToStep(8)">8. Info Umum &amp; Final</button>
	</div>
</div>

<style>
.step-chip {
	padding: 5px 10px;
	border-radius: 6px;
	border: 1px solid var(--border);
	background: var(--surface-2);
	color: var(--text-muted);
	font-size: 11px;
	font-weight: 600;
	white-space: nowrap;
	cursor: pointer;
	transition: all 0.2s;
}
.step-chip:hover {
	background: var(--surface);
	color: var(--text);
}
.step-chip.active {
	background: var(--accent);
	color: var(--accent-contrast);
	border-color: var(--accent);
}
.step-chip.completed {
	border-color: var(--good);
	color: var(--good);
	background: rgba(34, 197, 94, 0.08);
}
.wizard-step {
	display: none;
}
.wizard-step.active {
	display: block;
	animation: fadeInStep 0.25s ease-in-out;
}
@keyframes fadeInStep {
	from { opacity: 0; transform: translateY(6px); }
	to { opacity: 1; transform: translateY(0); }
}
.wizard-btn-bar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	margin-top: 24px;
	padding-top: 16px;
	border-top: 1px solid var(--border);
}
</style>

<!-- ================= FORMULIR UTAMA ONBOARDING (MULTI-STEP) ================= -->
<div class="card" style="padding:24px 28px; margin-bottom:30px">
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:16px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>
	<?php if ($this->session->flashdata('success')): ?>
		<div class="flash ok" style="margin-bottom:16px; background:rgba(34, 197, 94, 0.12); color:#15803d; border:1px solid rgba(34, 197, 94, 0.3); padding:12px 16px; border-radius:6px; font-size:13px; display:flex; align-items:center; gap:8px">
			<span style="font-size:16px">✓</span>
			<span><?= html_escape($this->session->flashdata('success')) ?></span>
		</div>
	<?php endif; ?>

	<?= form_open_multipart(site_url('onboarding/' . $this->uri->segment(2)), array('id' => 'form-onboarding', 'style' => 'display:flex; flex-direction:column; gap:0')) ?>
	<input type="hidden" name="action_mode" id="action_mode" value="final">
	<input type="hidden" name="current_step" id="current_step" value="1">

	<!-- =========================================================================
	     STEP 1: SECTION I — DATA PRIBADI & KONTAK DARURAT
	     ========================================================================= -->
	<div class="wizard-step active" id="step-1">
		<div style="margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div class="eyebrow" style="color:var(--accent); font-weight:700">Section I: Data Pribadi &amp; Kontak</div>
			<h2 style="margin:2px 0; font-size:16px; font-weight:700">Identitas Resmi &amp; Kontak Darurat</h2>
			<p class="muted" style="margin:0; font-size:12.5px">
				Data di bawah ini mencakup data awal terverifikasi serta butir identitas fisik lengkap (KTP, NPWP, SIM, agama, fisik, kontak darurat).
			</p>
		</div>

		<!-- Data Awal Read-Only -->
		<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px 16px; margin-bottom:18px">
			<div style="font-size:11.5px; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase">
				Data Pendaftaran Awal (Terverifikasi)
			</div>
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; font-size:12px">
				<div><span class="muted" style="font-size:10.5px; display:block">Nama Lengkap</span><strong><?= html_escape($t['nama_lengkap']) ?></strong></div>
				<div><span class="muted" style="font-size:10.5px; display:block">No. WhatsApp</span><span class="mono"><?= html_escape($t['no_wa_normal'] ?: '-') ?></span></div>
				<div><span class="muted" style="font-size:10.5px; display:block">Email</span><strong><?= html_escape($t['email'] ?: '-') ?></strong></div>
				<div><span class="muted" style="font-size:10.5px; display:block">Tempat, Tgl Lahir</span><strong><?= html_escape($t['tempat_lahir'] ?: '-') ?>, <?= !empty($t['tanggal_lahir']) ? html_escape(substr($t['tanggal_lahir'], 0, 10)) : '-' ?></strong></div>
				<div><span class="muted" style="font-size:10.5px; display:block">Jenis Kelamin</span><strong><?= $t['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($t['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></strong></div>
				<div><span class="muted" style="font-size:10.5px; display:block">Status Pernikahan</span><strong><?= html_escape($t['status_pernikahan'] ?: '-') ?></strong></div>
			</div>
		</div>

		<!-- Upload Pas Foto Kandidat (Sesuai Box Foto Form Standar RPG) -->
		<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:18px">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px">
				<div>
					<strong style="font-size:13px; color:var(--text)">Pas Foto Pelamar</strong>
					<span class="muted" style="font-size:11.5px; margin-left:6px">(Format JPG/PNG, ukuran 3x4 / 4x6, maks. 3 MB)</span>
				</div>
				<?php if (!empty($t['foto_path']) && is_file($t['foto_path'])): ?>
					<span class="tag on" style="font-size:11px">Foto Sudah Tersimpan</span>
				<?php endif; ?>
			</div>

			<div style="display:flex; gap:18px; align-items:center; flex-wrap:wrap">
				<!-- Preview Box Pas Foto -->
				<div id="foto-preview-box" style="width:105px; height:140px; border:2px dashed var(--border); border-radius:8px; display:flex; align-items:center; justify-content:center; background:var(--surface); overflow:hidden; position:relative; flex-shrink:0">
					<?php if (!empty($t['foto_path']) && is_file($t['foto_path'])): ?>
						<img id="foto-preview-img" src="<?= site_url('onboarding/photo/' . $this->uri->segment(2)) ?>" alt="Pas Foto" style="width:100%; height:100%; object-fit:cover">
					<?php else: ?>
						<img id="foto-preview-img" src="" alt="Preview Foto" style="width:100%; height:100%; object-fit:cover; display:none">
						<div id="foto-placeholder" style="text-align:center; padding:10px; color:var(--text-muted)">
							<div style="font-size:20px; line-height:1; font-weight:700; color:var(--text-faint)">FOTO</div>
							<div style="font-size:10px; font-weight:600; margin-top:4px">FOTO 3x4 / 4x6</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Input Upload & Keterangan -->
				<div style="flex:1; min-width:220px">
					<label for="pas_foto" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:6px">
						Pilih Berkas Pas Foto
					</label>
					<input type="file" id="pas_foto" name="pas_foto" accept="image/jpeg,image/png,image/jpg" onchange="previewPasFoto(this)" style="font-size:12px; width:100%; padding:6px; background:var(--surface); border:1px solid var(--border); border-radius:6px">
					<div class="muted" style="font-size:11.5px; margin-top:6px; line-height:1.4">
						Gunakan foto formal/semi-formal dengan latar belakang polos. Foto ini akan dicantumkan pada berkas fisik lamaran Anda.
					</div>
				</div>
			</div>
		</div>

		<!-- Field Input Section I -->
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px">
			<div>
				<label for="nama_panggilan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Panggilan</label>
				<input type="text" id="nama_panggilan" name="nama_panggilan" value="<?= set_value('nama_panggilan', $t['nama_panggilan'] ?? '') ?>" placeholder="Nama akrab sehari-hari" style="width:100%">
			</div>

			<div>
				<label for="nik" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor Induk Kependudukan (NIK KTP) <span style="color:var(--crit)">*</span></label>
				<input type="text" id="nik" name="nik" value="<?= set_value('nik', $t['nik'] ?? '') ?>" placeholder="16 digit NIK KTP" maxlength="20" required style="width:100%">
			</div>

			<div>
				<label for="npwp" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nomor NPWP</label>
				<input type="text" id="npwp" name="npwp" value="<?= set_value('npwp', $t['npwp'] ?? '') ?>" placeholder="Nomor NPWP (opsional)" style="width:100%">
			</div>

			<div>
					<label for="no_sim" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Kepemilikan SIM</label>
					<select id="no_sim" name="no_sim" style="width:100%">
						<?php foreach (array('Tidak', 'SIM A', 'SIM BI', 'SIM BII', 'SIM C') as $sim_opt): ?>
							<option value="<?= $sim_opt ?>" <?= (set_value('no_sim', $t['no_sim'] ?? 'Tidak') === $sim_opt) ? 'selected' : '' ?>><?= $sim_opt ?></option>
						<?php endforeach; ?>
					</select>
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

			<?php
				$stt_std = array('Rumah Sendiri', 'Milik Orang Tua', 'Sewa / Kontrak', 'Kost');
				$cur_stt = set_value('status_tempat_tinggal', $t['status_tempat_tinggal'] ?? '');
				$is_lainnya = !empty($cur_stt) && !in_array($cur_stt, $stt_std, TRUE);
				$sel_stt = $is_lainnya ? 'Lainnya' : $cur_stt;
				$txt_lainnya = set_value('status_tempat_tinggal_lainnya', $is_lainnya ? $cur_stt : '');
				?>
				<div style="grid-column: 1 / -1">
					<label for="status_tempat_tinggal" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Status Tempat Tinggal Saat Ini</label>
					<select id="status_tempat_tinggal" name="status_tempat_tinggal" style="width:100%" onchange="toggleTempatTinggalLainnya(this.value)">
						<option value="">-- Pilih Status Tempat Tinggal --</option>
						<?php foreach (array('Rumah Sendiri', 'Milik Orang Tua', 'Sewa / Kontrak', 'Kost', 'Lainnya') as $stt): ?>
							<option value="<?= $stt ?>" <?= ($sel_stt === $stt) ? 'selected' : '' ?>><?= $stt ?></option>
						<?php endforeach; ?>
					</select>
					<div id="wrap_tempat_tinggal_lainnya" style="margin-top:8px; display:<?= ($sel_stt === 'Lainnya') ? 'block' : 'none' ?>">
						<label for="status_tempat_tinggal_lainnya" style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">
							Keterangan Status Tempat Tinggal Lainnya <span style="color:var(--crit)">*</span>
						</label>
						<input type="text" id="status_tempat_tinggal_lainnya" name="status_tempat_tinggal_lainnya" value="<?= html_escape($txt_lainnya) ?>" placeholder="Sebutkan (misal: Rumah Dinas / Ikut Saudara / Asrama)" style="width:100%; font-size:12.5px">
					</div>
				</div>
		</div>

		<!-- Kontak Darurat -->
		<div style="margin-top:16px; padding:14px; background:var(--surface-2); border-radius:8px; border:1px solid var(--border)">
			<div style="font-size:12.5px; font-weight:700; color:var(--text); margin-bottom:4px">Kontak Darurat (Emergency Contact)</div>
			<div style="font-size:11.5px; color:var(--text-muted); margin-bottom:8px">Pihak yang dapat dihubungi segera bila terjadi keadaan darurat saat Anda bekerja.</div>
			<div style="font-size:12.5px; color:var(--text)">
				<strong><?= html_escape($t['kontak_darurat_nama'] ?: '-') ?></strong>
				(Hubungan: <?= html_escape($t['kontak_darurat_hub'] ?: '-') ?> &mdash; Telp: <?= html_escape($t['kontak_darurat_telp'] ?: '-') ?>)
			</div>
		</div>

		<div class="wizard-btn-bar"><div></div>
			<button type="button" class="btn btn-primary" onclick="nextStep(1)">
				Selanjutnya: Susunan Keluarga &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 2: SECTION II — SUSUNAN KELUARGA
	     ========================================================================= -->
	<div class="wizard-step" id="step-2">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:8px">
			<div>
				<div class="eyebrow" style="color:var(--accent); font-weight:700">Section II: Susunan Keluarga</div>
				<h2 style="margin:2px 0; font-size:16px; font-weight:700">Data Keluarga Inti</h2>
				<p class="muted" style="margin:0; font-size:12.5px">
					Cantumkan anggota keluarga inti (Ayah, Ibu, Pasangan/Suami/Istri, Anak, atau Saudara Kandung).
				</p>
			</div>
			<button type="button" class="btn btn-sm btn-ghost" onclick="addFamilyRow()" style="font-size:12px; font-weight:600">
				+ Tambah Anggota Keluarga
			</button>
		</div>

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

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px">
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Hubungan Keluarga</label>
								<select name="fam[<?= $fidx ?>][hubungan]" style="width:100%; font-size:12.5px">
								<?php foreach (array('Ayah', 'Ibu', 'Suami', 'Istri', 'Anak', 'Kakak', 'Adik') as $hub): ?>
									<option value="<?= $hub ?>" <?= ($f['hubungan'] ?? '') === $hub ? 'selected' : '' ?>><?= $hub ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Lengkap</label>
								<input type="text" name="fam[<?= $fidx ?>][nama_lengkap]" value="<?= html_escape($f['nama_lengkap'] ?? '') ?>" placeholder="Nama lengkap anggota keluarga" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Jenis Kelamin</label>
								<select name="fam[<?= $fidx ?>][jenis_kelamin]" style="width:100%; font-size:12.5px">
								<option value="L" <?= ($f['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>L</option>
								<option value="P" <?= ($f['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>P</option>
							</select>
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Usia (Tahun)</label>
								<input type="number" name="fam[<?= $fidx ?>][usia]" value="<?= html_escape($f['usia'] ?? '') ?>" placeholder="Misal: 45" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Pendidikan Terakhir</label>
								<input type="text" name="fam[<?= $fidx ?>][pendidikan]" value="<?= html_escape($f['pendidikan'] ?? '') ?>" placeholder="Misal: SMA / S1" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Pekerjaan / Usaha</label>
								<input type="text" name="fam[<?= $fidx ?>][pekerjaan]" value="<?= html_escape($f['pekerjaan'] ?? '') ?>" placeholder="Misal: Wirausaha / Pensiunan / IRT" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nomor Telepon / WA</label>
								<input type="text" name="fam[<?= $fidx ?>][no_telp]" value="<?= html_escape($f['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx" style="width:100%; font-size:12.5px">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(2)">
					&larr; Sebelumnya
				</button>
			<button type="button" class="btn btn-primary" onclick="nextStep(2)">
				Selanjutnya: Pendidikan &amp; Pelatihan &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 3: SECTION III — PENDIDIKAN FORMAL & PELATIHAN NON-FORMAL
	     ========================================================================= -->
	<div class="wizard-step" id="step-3">
		<div style="margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div class="eyebrow" style="color:var(--accent); font-weight:700">Section III: Riwayat Pendidikan &amp; Pelatihan</div>
			<h2 style="margin:2px 0; font-size:16px; font-weight:700">Pendidikan Formal &amp; Kursus / Non-Formal</h2>
			<p class="muted" style="margin:0; font-size:12.5px">
				Pendidikan formal terakhir Anda dan sertifikasi kursus / pelatihan kerja yang pernah diikuti.
			</p>
		</div>

		<!-- Pendidikan Formal Terakhir (Read-Only Awal) -->
		<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px 16px; margin-bottom:18px">
			<div style="font-size:11.5px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase">
				Pendidikan Formal Terakhir
			</div>
			<div style="font-size:13px; color:var(--text)">
				<strong><?= html_escape($t['pendidikan_terakhir'] ?: '-') ?></strong> &mdash;
				<?= html_escape($t['nama_sekolah'] ?: '-') ?>
				<?= !empty($t['jurusan']) ? '(' . html_escape($t['jurusan']) . ')' : '' ?>
			</div>
		</div>

		<!-- Pendidikan Non-Formal / Pelatihan Multi-Item -->
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px">
			<div style="font-size:13px; font-weight:700; color:var(--text)">
				Pelatihan / Kursus / Workshop / Sertifikasi (Non-Formal)
			</div>
			<button type="button" class="btn btn-sm btn-ghost" onclick="addTrainingRow()" style="font-size:12px; font-weight:600">
				+ Tambah Pelatihan
			</button>
		</div>

		<div id="training-container" style="display:flex; flex-direction:column; gap:10px">
			<?php
			$trn_init = !empty($trainings) ? $trainings : array(
				array('nama_pelatihan' => '', 'penyelenggara' => '', 'tahun' => '', 'keterangan' => '')
			);
			?>

			<?php foreach ($trn_init as $tidx => $tr): ?>
				<div class="trn-card" style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px; position:relative">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
						<strong style="font-size:12.5px; color:var(--text)" class="trn-num">Pelatihan #<?= $tidx + 1 ?></strong>
						<button type="button" class="btn-sm btn-ghost" onclick="removeTrnCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11px" <?= count($trn_init) === 1 ? 'hidden' : '' ?>>
							✕ Hapus
						</button>
					</div>

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px">
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Kursus / Pelatihan</label>
							<input type="text" name="trn[<?= $tidx ?>][nama_pelatihan]" value="<?= html_escape($tr['nama_pelatihan'] ?? '') ?>" placeholder="Misal: Barista / Brevet Pajak / Excel" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Lembaga Penyelenggara</label>
							<input type="text" name="trn[<?= $tidx ?>][penyelenggara]" value="<?= html_escape($tr['penyelenggara'] ?? '') ?>" placeholder="Instansi Penyelenggara" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Tahun</label>
							<input type="text" name="trn[<?= $tidx ?>][tahun]" value="<?= html_escape($tr['tahun'] ?? '') ?>" placeholder="Mis. 2023" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Keterangan / No. Sertifikat</label>
							<input type="text" name="trn[<?= $tidx ?>][keterangan]" value="<?= html_escape($tr['keterangan'] ?? '') ?>" placeholder="Bersertifikat / Nilai" style="width:100%; font-size:12.5px">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(3)">
					&larr; Sebelumnya
				</button>
			<button type="button" class="btn btn-primary" onclick="nextStep(3)">
				Selanjutnya: Pengalaman Kerja &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 4: SECTION IV — PENGALAMAN KERJA TERPERINCI
	     ========================================================================= -->
	<div class="wizard-step" id="step-4">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:8px">
			<div>
				<div class="eyebrow" style="color:var(--accent); font-weight:700">Section IV: Pengalaman Kerja</div>
				<h2 style="margin:2px 0; font-size:16px; font-weight:700">Riwayat Pengalaman Kerja Lengkap</h2>
				<p class="muted" style="margin:0; font-size:12.5px">
					Cantumkan riwayat perusahaan tempat Anda pernah bekerja (urutkan dari pekerjaan terakhir).
				</p>
			</div>
			<button type="button" class="btn btn-sm btn-ghost" onclick="addExperienceRow()" style="font-size:12px; font-weight:600">
				+ Tambah Pekerjaan
			</button>
		</div>

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
							<input type="text" name="exp[<?= $idx ?>][posisi_jabatan]" value="<?= html_escape($e['posisi_jabatan'] ?? '') ?>" placeholder="Misal: Staff / Kasir / SPV" style="width:100%; font-size:12.5px">
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

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px">
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Alasan Berhenti / Pindah</label>
							<input type="text" name="exp[<?= $idx ?>][alasan_keluar]" value="<?= html_escape($e['alasan_keluar'] ?? '') ?>" placeholder="Misal: Habis kontrak / Pengembangan karir" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Uraian Tanggung Jawab / Prestasi</label>
							<input type="text" name="exp[<?= $idx ?>][deskripsi_tugas]" value="<?= html_escape($e['deskripsi_tugas'] ?? '') ?>" placeholder="Uraian pekerjaan utama" style="width:100%; font-size:12.5px">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(4)">
					&larr; Sebelumnya
				</button>
			<button type="button" class="btn btn-primary" onclick="nextStep(4)">
				Selanjutnya: Keahlian &amp; Bahasa &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 5: SECTION V — KEAHLIAN KOMPUTER & PENGUASAAN BAHASA
	     ========================================================================= -->
	<div class="wizard-step" id="step-5">
		<div style="margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div class="eyebrow" style="color:var(--accent); font-weight:700">Section V: Keahlian &amp; Bahasa</div>
			<h2 style="margin:2px 0; font-size:16px; font-weight:700">Keahlian Komputer &amp; Penguasaan Bahasa</h2>
			<p class="muted" style="margin:0; font-size:12.5px">
				Informasi kecakapan aplikasi pendukung pekerjaan serta bahasa asing atau daerah yang Anda kuasai.
			</p>
		</div>

		<div style="display:flex; flex-direction:column; gap:16px">
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px">
				<label for="keahlian_komputer" style="display:block; font-size:13px; font-weight:700; margin-bottom:4px; color:var(--text)">
					Keahlian Komputer &amp; Aplikasi Lunak
				</label>
				<p class="muted" style="font-size:12px; margin:0 0 8px">
					Sebutkan program komputer, software POS kasir, spreadsheet, desain, atau aplikasi yang Anda kuasai.
				</p>
				<input type="text" id="keahlian_komputer" name="keahlian_komputer" value="<?= set_value('keahlian_komputer', $t['keahlian_komputer'] ?? '') ?>" placeholder="Misal: MS Excel (Rumus VLOOKUP/Pivot), MS Word, POS Kasir, Canva, Accurate/ERP" style="width:100%; font-size:13px">
			</div>

			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px">
				<label for="bahasa_asing" style="display:block; font-size:13px; font-weight:700; margin-bottom:4px; color:var(--text)">
					Penguasaan Bahasa Asing &amp; Bahasa Daerah
				</label>
				<p class="muted" style="font-size:12px; margin:0 0 8px">
					Sebutkan bahasa yang dikuasai beserta tingkat kemampuannya (Aktif / Pasif).
				</p>
				<input type="text" id="bahasa_asing" name="bahasa_asing" value="<?= set_value('bahasa_asing', $t['bahasa_asing'] ?? '') ?>" placeholder="Misal: Bahasa Inggris (Aktif Lisan & Tulisan), Bahasa Mandarin (Pasif), Bahasa Jawa" style="width:100%; font-size:13px">
			</div>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(5)">
					&larr; Sebelumnya
				</button>
			<button type="button" class="btn btn-primary" onclick="nextStep(5)">
				Selanjutnya: Referensi Kerja &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 6: SECTION VI — REFERENSI KERJA PROFESIONAL
	     ========================================================================= -->
	<div class="wizard-step" id="step-6">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:8px">
			<div>
				<div class="eyebrow" style="color:var(--accent); font-weight:700">Section VI: Referensi Kerja</div>
				<h2 style="margin:2px 0; font-size:16px; font-weight:700">Referensi Profesional / Rekomendasi Kerja</h2>
				<p class="muted" style="margin:0; font-size:12.5px">
					Cantumkan atasan langsung, HRD, atau rekan profesional yang dapat dihubungi untuk konfirmasi rekam jejak Anda.
				</p>
			</div>
			<button type="button" class="btn btn-sm btn-ghost" onclick="addReferenceRow()" style="font-size:12px; font-weight:600">
				+ Tambah Referensi
			</button>
		</div>

		<div id="reference-container" style="display:flex; flex-direction:column; gap:10px">
			<?php
			$ref_init = !empty($references) ? $references : array(
				array('nama_referensi' => '', 'perusahaan' => '', 'jabatan' => '', 'no_telp' => '', 'hubungan' => '')
			);
			?>

			<?php foreach ($ref_init as $ridx => $rf): ?>
				<div class="ref-card" style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px; position:relative">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
						<strong style="font-size:12.5px; color:var(--text)" class="ref-num">Referensi #<?= $ridx + 1 ?></strong>
						<button type="button" class="btn-sm btn-ghost" onclick="removeRefCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11px" <?= count($ref_init) === 1 ? 'hidden' : '' ?>>
							✕ Hapus
						</button>
					</div>

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px">
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Lengkap</label>
							<input type="text" name="ref[<?= $ridx ?>][nama_referensi]" value="<?= html_escape($rf['nama_referensi'] ?? '') ?>" placeholder="Nama pemberi referensi" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Perusahaan / Instansi</label>
							<input type="text" name="ref[<?= $ridx ?>][perusahaan]" value="<?= html_escape($rf['perusahaan'] ?? '') ?>" placeholder="Nama instansi" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Jabatan</label>
							<input type="text" name="ref[<?= $ridx ?>][jabatan]" value="<?= html_escape($rf['jabatan'] ?? '') ?>" placeholder="Mis. Branch Manager / SPV" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nomor Telepon / WA</label>
							<input type="text" name="ref[<?= $ridx ?>][no_telp]" value="<?= html_escape($rf['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx" style="width:100%; font-size:12.5px">
						</div>
						<div>
							<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Hubungan Kerja</label>
							<input type="text" name="ref[<?= $ridx ?>][hubungan]" value="<?= html_escape($rf['hubungan'] ?? '') ?>" placeholder="Mis. Atasan Langsung / Rekan Kerja" style="width:100%; font-size:12.5px">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(6)">
					&larr; Sebelumnya
				</button>
			<button type="button" class="btn btn-primary" onclick="nextStep(6)">
				Selanjutnya: Minat &amp; Konsep Diri &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 7: SECTION VII — MINAT & KONSEP PRIBADI LENGKAP
	     ========================================================================= -->
	<div class="wizard-step" id="step-7">
		<div style="margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div class="eyebrow" style="color:var(--accent); font-weight:700">Section VII: Minat &amp; Konsep Pribadi</div>
			<h2 style="margin:2px 0; font-size:16px; font-weight:700">Evaluasi Minat, Sasaran Karir &amp; Konsep Diri</h2>
			<p class="muted" style="margin:0; font-size:12.5px">
				Jawab seluruh butir evaluasi diri di bawah ini secara jujur dan komprehensif sebagai pertimbangan kesesuaian kultur dan peran di RPG.
			</p>
		</div>

		<?php $q = $questionnaire ?? array(); ?>

		<div style="display:flex; flex-direction:column; gap:16px">
			<!-- Butir 1: Motivasi Melamar -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_alasan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					1. Apa motivasi / alasan utama Anda melamar di Ratu Pertiwi Group? <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_alasan" name="quest[alasan_melamar]" rows="2" placeholder="Uraikan motivasi dan tujuan karir Anda di RPG..." required style="width:100%; font-size:13px"><?= set_value('quest[alasan_melamar]', $q['alasan_melamar'] ?? '') ?></textarea>
			</div>

			<!-- Butir 2: Kecocokan Posisi -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_cocok" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					2. Mengapa Anda merasa cocok untuk menduduki posisi yang Anda lamar saat ini? <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_cocok" name="quest[alasan_cocok_posisi]" rows="2" placeholder="Jelaskan keterampilan, pengalaman, atau minat yang membuat Anda yakin berhasil di posisi ini..." required style="width:100%; font-size:13px"><?= set_value('quest[alasan_cocok_posisi]', $q['alasan_cocok_posisi'] ?? '') ?></textarea>
			</div>

			<!-- Butir 3: Pengetahuan tentang RPG -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_pengetahuan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					3. Apa yang Anda ketahui mengenai lini bisnis, produk, dan brand unit usaha Ratu Pertiwi Group? <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_pengetahuan" name="quest[pengetahuan_rpg]" rows="2" placeholder="Tuliskan pemahaman Anda tentang bidang usaha dan unit bisnis RPG..." required style="width:100%; font-size:13px"><?= set_value('quest[pengetahuan_rpg]', $q['pengetahuan_rpg'] ?? '') ?></textarea>
			</div>

			<!-- Butir 4 & 5: Kelebihan & Kekurangan Diri (Grid) -->
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px">
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_kelebihan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						4. Sebutkan kelebihan / kekuatan diri yang mendukung pekerjaan ini! <span style="color:var(--crit)">*</span>
					</label>
					<textarea id="q_kelebihan" name="quest[kelebihan_diri]" rows="3" placeholder="Sebutkan karakter, sikap positif, atau kompetensi utama Anda..." required style="width:100%; font-size:13px"><?= set_value('quest[kelebihan_diri]', $q['kelebihan_diri'] ?? '') ?></textarea>
				</div>
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_kekurangan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						5. Sebutkan kekurangan diri Anda dan langkah konkret mengatasinya! <span style="color:var(--crit)">*</span>
					</label>
					<textarea id="q_kekurangan" name="quest[kekurangan_diri]" rows="3" placeholder="Sebutkan kelemahan Anda dan bagaimana Anda mengatasinya..." required style="width:100%; font-size:13px"><?= set_value('quest[kekurangan_diri]', $q['kekurangan_diri'] ?? '') ?></textarea>
				</div>
			</div>

			<!-- Butir 6: Prestasi Terbesar -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_prestasi" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					6. Apa pencapaian atau prestasi paling membanggakan dalam karir / pendidikan Anda? <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_prestasi" name="quest[prestasi_terbesar]" rows="2" placeholder="Ceritakan pencapaian terbaik yang berhasil Anda raih..." required style="width:100%; font-size:13px"><?= set_value('quest[prestasi_terbesar]', $q['prestasi_terbesar'] ?? '') ?></textarea>
			</div>

			<!-- Butir 7: Rencana Karir 3-5 Tahun ke Depan -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_karir" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					7. Bagaimana rencana dan sasaran karir Anda dalam 3 sampai 5 tahun ke depan? <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_karir" name="quest[rencana_karir_5thn]" rows="2" placeholder="Jelaskan aspirasi perkembangan profesional yang ingin Anda capai di RPG..." required style="width:100%; font-size:13px"><?= set_value('quest[rencana_karir_5thn]', $q['rencana_karir_5thn'] ?? '') ?></textarea>
			</div>

			<!-- Butir 8: Masalah Tersulit & Solusi -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_masalah" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					8. Ceritakan masalah kerja paling sulit yang pernah Anda hadapi dan bagaimana Anda menyelesaikannya! <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_masalah" name="quest[masalah_tersulit_solusi]" rows="2" placeholder="Uraikan situasi kendala, tindakan yang Anda ambil, dan hasil akhirnya..." required style="width:100%; font-size:13px"><?= set_value('quest[masalah_tersulit_solusi]', $q['masalah_tersulit_solusi'] ?? '') ?></textarea>
			</div>

			<!-- Butir 9: Lingkungan Kerja Idaman & Dihindari -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_lingkungan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					9. Lingkungan kerja seperti apa yang paling Anda sukai dan yang paling Anda hindari? <span style="color:var(--crit)">*</span>
				</label>
				<textarea id="q_lingkungan" name="quest[lingkungan_kerja_idaman]" rows="2" placeholder="Sebutkan budaya kerja yang membuat Anda produktif serta kondisi yang kurang Anda sukai..." required style="width:100%; font-size:13px"><?= set_value('quest[lingkungan_kerja_idaman]', $q['lingkungan_kerja_idaman'] ?? '') ?></textarea>
			</div>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(7)">
					&larr; Sebelumnya
				</button>
			<button type="button" class="btn btn-primary" onclick="nextStep(7)">
				Selanjutnya: Informasi Umum &amp; Final &rarr;
			</button>
		</div>
	</div>

	<!-- =========================================================================
	     STEP 8: SECTION VIII & IX — INFORMASI UMUM, PAYROLL & PERNYATAAN AKHIR
	     ========================================================================= -->
	<div class="wizard-step" id="step-8">
		<div style="margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div class="eyebrow" style="color:var(--accent); font-weight:700">Section VIII &amp; IX: Informasi Umum &amp; Final</div>
			<h2 style="margin:2px 0; font-size:16px; font-weight:700">Kesiapan Kerja, Payroll &amp; Pernyataan Keabsahan</h2>
			<p class="muted" style="margin:0; font-size:12.5px">
				Langkah terakhir: konfirmasi kesiapan kerja operasional, rekening bank payroll, dan persetujuan pernyataan keabsahan berkas.
			</p>
		</div>

		<div style="display:flex; flex-direction:column; gap:16px">
			<!-- Riwayat Melamar RPG & Kendaraan Operasional -->
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px">
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_melamar_rpg" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Apakah Anda pernah melamar di RPG sebelumnya? Kapan &amp; untuk posisi apa? <span style="color:var(--crit)">*</span>
					</label>
					<input type="text" id="q_melamar_rpg" name="quest[riwayat_melamar_rpg]" value="<?= set_value('quest[riwayat_melamar_rpg]', $q['riwayat_melamar_rpg'] ?? 'Tidak Pernah') ?>" placeholder="Tuliskan 'Tidak Pernah' atau sebutkan tahun & posisi bila pernah." required style="width:100%; font-size:13px">
				</div>

				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_kendaraan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Kepemilikan Kendaraan Pribadi &amp; SIM untuk Operasional
					</label>
					<input type="text" id="q_kendaraan" name="quest[kepemilikan_kendaraan]" value="<?= set_value('quest[kepemilikan_kendaraan]', $q['kepemilikan_kendaraan'] ?? '') ?>" placeholder="Misal: Motor pribadi & SIM C aktif / Mobil & SIM A" style="width:100%; font-size:13px">
				</div>
			</div>

			<!-- Ekspektasi Gaji & Tanggal Siap Mulai -->
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px">
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_harapan_gaji" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Berapa ekspektasi gaji dan fasilitas yang Anda harapkan di RPG? <span style="color:var(--crit)">*</span>
					</label>
					<input type="text" id="q_harapan_gaji" name="quest[harapan_gaji_fasilitas]" value="<?= set_value('quest[harapan_gaji_fasilitas]', $q['harapan_gaji_fasilitas'] ?? '') ?>" placeholder="Misal: Rp 4.500.000 (Nego) / BPJS / Uang Makan" required style="width:100%; font-size:13px">
				</div>
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_ketersediaan" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Kapan Anda siap mulai aktif bekerja jika diterima? <span style="color:var(--crit)">*</span>
					</label>
					<input type="text" id="q_ketersediaan" name="quest[ketersediaan_mulai]" value="<?= set_value('quest[ketersediaan_mulai]', $q['ketersediaan_mulai'] ?? '') ?>" placeholder="Misal: Segera / 1 Minggu / 1 Bulan" required style="width:100%; font-size:13px">
				</div>
			</div>

			<!-- Kesiapan Shift, Lembur, dan Penempatan Luar Kota -->
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px">
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_shift" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Kesiapan sistem shift, akhir pekan, atau lembur operasional <span style="color:var(--crit)">*</span>
					</label>
					<input type="text" id="q_shift" name="quest[bersedia_shift_lembur]" value="<?= set_value('quest[bersedia_shift_lembur]', $q['bersedia_shift_lembur'] ?? 'Bersedia') ?>" placeholder="Ya, bersedia / Keterangan kesiapan" required style="width:100%; font-size:13px">
				</div>
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_luarkota" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Kesiapan penugasan luar kota / mutasi cabang RPG <span style="color:var(--crit)">*</span>
					</label>
					<input type="text" id="q_luarkota" name="quest[bersedia_luar_kota]" value="<?= set_value('quest[bersedia_luar_kota]', $q['bersedia_luar_kota'] ?? 'Bersedia') ?>" placeholder="Ya, bersedia / Hanya area tertentu" required style="width:100%; font-size:13px">
				</div>
			</div>

			<!-- Bisnis Sampingan, Relasi Keluarga & Riwayat Hukum -->
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px">
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_bisnis" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Pekerjaan sampingan / usaha bisnis pribadi saat ini
					</label>
					<input type="text" id="q_bisnis" name="quest[punya_bisnis_sampingan]" value="<?= set_value('quest[punya_bisnis_sampingan]', $q['punya_bisnis_sampingan'] ?? '') ?>" placeholder="Tuliskan jenis usaha atau 'Tidak Ada'" style="width:100%; font-size:13px">
				</div>
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
					<label for="q_relasi" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
						Keluarga / kenalan yang saat ini bekerja di RPG
					</label>
					<input type="text" id="q_relasi" name="quest[relasi_keluarga_rpg]" value="<?= set_value('quest[relasi_keluarga_rpg]', $q['relasi_keluarga_rpg'] ?? '') ?>" placeholder="Sebutkan Nama & Hubungan atau 'Tidak Ada'" style="width:100%; font-size:13px">
				</div>
			</div>

			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px">
				<label for="q_pidana" style="display:block; font-size:12.5px; font-weight:700; margin-bottom:6px; color:var(--text)">
					Riwayat perkara hukum / tindak pidana <span style="color:var(--crit)">*</span>
				</label>
				<input type="text" id="q_pidana" name="quest[riwayat_tindak_pidana]" value="<?= set_value('quest[riwayat_tindak_pidana]', $q['riwayat_tindak_pidana'] ?? 'Tidak Pernah') ?>" placeholder="Tuliskan 'Tidak Pernah' atau jelaskan secara jujur bila pernah." required style="width:100%; font-size:13px">
			</div>

			<!-- REKENING BANK PAYROLL -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px">
				<div style="font-size:13px; font-weight:700; color:var(--text); margin-bottom:4px">
					Data Rekening Bank untuk Payroll / Penggajian
				</div>
				<div style="font-size:11.5px; color:var(--text-muted); margin-bottom:12px">
					Rekening wajib atas nama pribadi kandidat untuk kelancaran transfer gaji bulanan.
				</div>
				<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px">
					<div>
						<label for="nama_bank" style="display:block; font-size:12px; font-weight:600; margin-bottom:3px">Nama Bank <span style="color:var(--crit)">*</span></label>
						<input type="text" id="nama_bank" name="nama_bank" value="<?= set_value('nama_bank', $t['nama_bank'] ?? 'BCA') ?>" placeholder="Misal: BCA / Mandiri / BRI" required style="width:100%; font-size:12.5px">
					</div>
					<div>
						<label for="no_rekening" style="display:block; font-size:12px; font-weight:600; margin-bottom:3px">Nomor Rekening <span style="color:var(--crit)">*</span></label>
						<input type="text" id="no_rekening" name="no_rekening" value="<?= set_value('no_rekening', $t['no_rekening'] ?? '') ?>" placeholder="Nomor rekening bank" required style="width:100%; font-size:12.5px">
					</div>
					<div>
						<label for="nama_pemilik_bank" style="display:block; font-size:12px; font-weight:600; margin-bottom:3px">Nama Pemilik Rekening <span style="color:var(--crit)">*</span></label>
						<input type="text" id="nama_pemilik_bank" name="nama_pemilik_bank" value="<?= set_value('nama_pemilik_bank', $t['nama_pemilik_bank'] ?? $t['nama_lengkap']) ?>" placeholder="Nama sesuai buku tabungan" required style="width:100%; font-size:12.5px">
					</div>
				</div>
			</div>

			<!-- KESEHATAN KHUSUS (PDP) -->
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:16px">
				<label for="riwayat_penyakit" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">
					Riwayat Penyakit Berat / Rawat Inap / Alergi Tertentu (Opsional)
				</label>
				<textarea id="riwayat_penyakit" name="riwayat_penyakit" rows="2" placeholder="Tuliskan bila ada (misal: riwayat asma, alergi obat). Kosongkan jika tidak ada." style="width:100%; font-size:13px; margin-bottom:10px"><?= set_value('riwayat_penyakit', $t['riwayat_penyakit'] ?? '') ?></textarea>
				<label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer; font-size:11.5px; color:var(--text-muted)">
					<input type="checkbox" name="consent_kesehatan" value="1" <?= set_checkbox('consent_kesehatan', '1', !empty($t['consent_kesehatan'])) ?> style="margin-top:2px">
					<span>
						Saya bersedia memberikan informasi riwayat kesehatan ini secara sukarela untuk keperluan penyesuaian lingkungan kerja dan tanggap darurat medis sesuai UU Pelindungan Data Pribadi (UU PDP No. 27/2022).
					</span>
				</label>
			</div>

			<!-- PERNYATAAN KEBENARAN DATA -->
			<div style="background:var(--surface-2); border-radius:8px; padding:16px; font-size:12.5px; line-height:1.5; border:1px solid var(--border)">
				<label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-weight:600">
					<input type="checkbox" required style="margin-top:3px">
					<span>
						Pernyataan Keabsahan Data *<br>
						<span style="font-weight:400; color:var(--text-muted)">
							Dengan ini saya menyatakan bahwa seluruh keterangan dan data yang saya berikan dalam formulir aplikasi ini adalah benar, lengkap dan sah. Apabila di kemudian hari terbukti terdapat ketidakbenaran atau manipulasi data, saya bersedia menerima sanksi pemutusan hubungan kerja secara sepihak tanpa syarat sesuai ketentuan perusahaan dan hukum yang berlaku.
						</span>
					</span>
				</label>
			</div>
		</div>

		<div class="wizard-btn-bar">
			<button type="button" class="btn btn-secondary" onclick="prevStep(8)">
					&larr; Sebelumnya
				</button>
			<button type="submit" onclick="document.getElementById('action_mode').value='final'" class="btn btn-primary" style="padding:12px 32px; font-size:14px; font-weight:700">
				Kirim Kelengkapan Berkas Formulir &rarr;
			</button>
		</div>
	</div>

	<?= form_close() ?>
</div>

<script>
var currentStep = 1;
var totalSteps = 8;

// Baca parameter query step jika ada (misal setelah simpan draft)
(function() {
	var urlParams = new URLSearchParams(window.location.search);
	var s = parseInt(urlParams.get('step'), 10);
	if (s >= 1 && s <= totalSteps) {
		currentStep = s;
	}
})();

var _autoSaveTimer = null;
var _isSaving = false;

function triggerAutoSave() {
	if (_isSaving) return;
	var f = document.getElementById('form-onboarding');
	if (!f) return;

	var dot = document.getElementById('autosave-dot');
	var txt = document.getElementById('autosave-text');

	if (dot && txt) {
		dot.style.background = 'var(--accent)';
		txt.textContent = 'Menyimpan progres...';
	}

	_isSaving = true;

	var formData = new FormData(f);
	formData.set('action_mode', 'draft');
	formData.set('current_step', currentStep);
	formData.set('is_ajax', '1');

	// Jika ada file pas foto tapi tidak dipilih baru, hapus agar tidak upload file kosong
	var fotoInput = document.getElementById('pas_foto');
	if (fotoInput && (!fotoInput.files || fotoInput.files.length === 0)) {
		formData.delete('pas_foto');
	}

	fetch(f.action || window.location.href, {
		method: 'POST',
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		},
		body: formData
	})
	.then(function(res){ return res.json(); })
	.then(function(data){
		_isSaving = false;
		if (dot && txt) {
			if (data && data.success) {
				dot.style.background = 'var(--good)';
				txt.textContent = 'Tersimpan otomatis ' + (data.saved_at ? '(' + data.saved_at + ')' : '');
			} else {
				dot.style.background = 'var(--warn)';
				txt.textContent = 'Gagal menyimpan otomatis';
			}
		}
	})
	.catch(function(err){
		_isSaving = false;
		if (dot && txt) {
			dot.style.background = 'var(--warn)';
			txt.textContent = 'Tersimpan lokal (offline)';
		}
	});
}

function queueAutoSave() {
	var dot = document.getElementById('autosave-dot');
	var txt = document.getElementById('autosave-text');
	if (dot && txt) {
		dot.style.background = 'var(--text-muted)';
		txt.textContent = 'Ada perubahan belum tersimpan...';
	}
	clearTimeout(_autoSaveTimer);
	_autoSaveTimer = setTimeout(function(){
		triggerAutoSave();
	}, 1800); // Auto-save 1.8 detik setelah berhenti mengetik
}

// Pasang event listener ke seluruh input di dalam form untuk mendeteksi perubahan
document.addEventListener('DOMContentLoaded', function() {
	var f = document.getElementById('form-onboarding');
	if (f) {
		f.addEventListener('input', function(e) {
			if (e.target && e.target.type !== 'password' && e.target.type !== 'file') {
				queueAutoSave();
			}
		});
		f.addEventListener('change', function(e) {
			queueAutoSave();
		});
		// Simpan instan saat input kehilangan fokus (blur/focusout)
		f.addEventListener('focusout', function(e) {
			if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA')) {
				clearTimeout(_autoSaveTimer);
				triggerAutoSave();
			}
		});
	}

	// Simpan instan saat tab browser diminimalkan atau berpindah aplikasi
	document.addEventListener('visibilitychange', function() {
		if (document.visibilityState === 'hidden') {
			clearTimeout(_autoSaveTimer);
			triggerAutoSave();
		}
	});

	// Simpan instan saat window/tab hendak ditutup
	window.addEventListener('beforeunload', function() {
		if (_autoSaveTimer) {
			clearTimeout(_autoSaveTimer);
			triggerAutoSave();
		}
	});
});

var stepLabels = {
	1: 'Langkah 1 dari 8: Data Pribadi & Kontak Darurat (Section I)',
	2: 'Langkah 2 dari 8: Susunan Anggota Keluarga (Section II)',
	3: 'Langkah 3 dari 8: Pendidikan & Pelatihan Kerja (Section III)',
	4: 'Langkah 4 dari 8: Riwayat Pengalaman Kerja (Section IV)',
	5: 'Langkah 5 dari 8: Keahlian Komputer & Bahasa (Section V)',
	6: 'Langkah 6 dari 8: Referensi Kerja Profesional (Section VI)',
	7: 'Langkah 7 dari 8: Minat & Konsep Pribadi (Section VII)',
	8: 'Langkah 8 dari 8: Informasi Umum, Payroll & Final (Section VIII & IX)'
};

function goToStep(step) {
	if (step < 1 || step > totalSteps) return;

	// Jika melangkah maju, validasi form step saat ini terlebih dahulu
	if (step > currentStep) {
		if (!validateStep(currentStep)) {
			return;
		}
	}

	// Update container step
	for (var i = 1; i <= totalSteps; i++) {
		var el = document.getElementById('step-' + i);
		if (el) {
			if (i === step) {
				el.classList.add('active');
			} else {
				el.classList.remove('active');
			}
		}
	}

	currentStep = step;

	// Update label & bar
	var pct = Math.round((currentStep / totalSteps) * 100);
	document.getElementById('wizard-step-label').textContent = stepLabels[currentStep];
	document.getElementById('wizard-step-percent').textContent = pct + '%';
	document.getElementById('wizard-progress-bar').style.width = pct + '%';

	// Update chips
	var chips = document.getElementById('wizard-nav-chips').getElementsByTagName('button');
	for (var j = 0; j < chips.length; j++) {
		var chipStep = j + 1;
		chips[j].classList.remove('active');
		if (chipStep === currentStep) {
			chips[j].classList.add('active');
		} else if (chipStep < currentStep) {
			chips[j].classList.add('completed');
		}
	}

	// Auto scroll ke atas form agar pelamar nyaman membaca
	window.scrollTo({ top: 180, behavior: 'smooth' });
}

function nextStep(step) {
	if (validateStep(step)) {
		goToStep(step + 1);
		triggerAutoSave(); // Otomatis simpan saat lanjut ke langkah berikutnya
	}
}

function prevStep(step) {
	goToStep(step - 1);
	triggerAutoSave(); // Otomatis simpan saat kembali ke langkah sebelumnya
}

function validateStep(step) {
	var stepContainer = document.getElementById('step-' + step);
	if (!stepContainer) return true;

	var inputs = stepContainer.querySelectorAll('input[required], select[required], textarea[required]');
	for (var i = 0; i < inputs.length; i++) {
		var inp = inputs[i];
		if (!inp.checkValidity()) {
			inp.reportValidity();
			inp.focus();
			return false;
		}
	}
	return true;
}

// Dynamic Multi-Row: Experience
var expCount = <?= count($exp_init) ?>;
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
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px">
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Alasan Berhenti / Pindah</label>
				<input type="text" name="exp[${idx}][alasan_keluar]" placeholder="Misal: Habis kontrak / Karir" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Uraian Tanggung Jawab / Prestasi</label>
				<input type="text" name="exp[${idx}][deskripsi_tugas]" placeholder="Uraian pekerjaan utama" style="width:100%; font-size:12.5px">
			</div>
		</div>
	`;
	c.appendChild(div);
}
function removeExpCard(btn) {
	var c = document.getElementById('experience-container');
	if (c.children.length > 1) {
		btn.closest('.exp-card').remove();
		var cards = c.getElementsByClassName('exp-card');
		for (var i = 0; i < cards.length; i++) {
			cards[i].querySelector('.exp-num').textContent = 'Pekerjaan #' + (i + 1);
		}
	}
}

// Dynamic Multi-Row: Family
var famCount = <?= count($fam_init) ?>;
function toggleTempatTinggalLainnya(val) {
		var wrap = document.getElementById('wrap_tempat_tinggal_lainnya');
		var inp  = document.getElementById('status_tempat_tinggal_lainnya');
		if (!wrap) return;
		if (val === 'Lainnya') {
			wrap.style.display = 'block';
			if (inp) {
				inp.setAttribute('required', 'required');
				inp.focus();
			}
		} else {
			wrap.style.display = 'none';
			if (inp) {
				inp.removeAttribute('required');
				inp.value = '';
			}
		}
	}

	function previewPasFoto(input) {
	if (input.files && input.files[0]) {
		var file = input.files[0];
		if (file.size > 3 * 1024 * 1024) {
			alert('Ukuran file foto maksimal 3 MB!');
			input.value = '';
			return;
		}
		var reader = new FileReader();
		reader.onload = function(e) {
			var img = document.getElementById('foto-preview-img');
			var placeholder = document.getElementById('foto-placeholder');
			img.src = e.target.result;
			img.style.display = 'block';
			if (placeholder) placeholder.style.display = 'none';
		};
		reader.readAsDataURL(file);
	}
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
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px">
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Hubungan Keluarga</label>
					<select name="fam[${idx}][hubungan]" style="width:100%; font-size:12.5px">
						<option value="Ayah">Ayah</option><option value="Ibu">Ibu</option>
						<option value="Suami">Suami</option><option value="Istri">Istri</option>
						<option value="Anak">Anak</option><option value="Kakak">Kakak</option><option value="Adik">Adik</option>
					</select>
				</div>
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Lengkap</label>
					<input type="text" name="fam[${idx}][nama_lengkap]" placeholder="Nama lengkap anggota keluarga" style="width:100%; font-size:12.5px" required>
				</div>
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Jenis Kelamin</label>
					<select name="fam[${idx}][jenis_kelamin]" style="width:100%; font-size:12.5px">
						<option value="L">L</option><option value="P">P</option>
					</select>
				</div>
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Usia (Tahun)</label>
					<input type="number" name="fam[${idx}][usia]" placeholder="Misal: 45" style="width:100%; font-size:12.5px">
				</div>
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Pendidikan Terakhir</label>
					<input type="text" name="fam[${idx}][pendidikan]" placeholder="Misal: SMA / S1" style="width:100%; font-size:12.5px">
				</div>
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Pekerjaan / Usaha</label>
					<input type="text" name="fam[${idx}][pekerjaan]" placeholder="Misal: Wirausaha / Pensiunan / IRT" style="width:100%; font-size:12.5px">
				</div>
				<div>
					<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nomor Telepon / WA</label>
					<input type="text" name="fam[${idx}][no_telp]" placeholder="08xxxxxxxxxx" style="width:100%; font-size:12.5px">
				</div>
			</div>
		`;
		c.appendChild(div);
	}
	function removeFamCard(btn) {
	var c = document.getElementById('family-container');
	if (c.children.length > 1) {
		btn.closest('.fam-card').remove();
		var cards = c.getElementsByClassName('fam-card');
		for (var i = 0; i < cards.length; i++) {
			cards[i].querySelector('.fam-num').textContent = 'Anggota Keluarga #' + (i + 1);
		}
	}
}

// Dynamic Multi-Row: Training
var trnCount = <?= count($trn_init) ?>;
function addTrainingRow() {
	var c = document.getElementById('training-container');
	var idx = trnCount++;
	var div = document.createElement('div');
	div.className = 'trn-card';
	div.style = 'background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px; position:relative';
	div.innerHTML = `
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
			<strong style="font-size:12.5px; color:var(--text)" class="trn-num">Pelatihan #${c.children.length + 1}</strong>
			<button type="button" class="btn-sm btn-ghost" onclick="removeTrnCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11px">
				✕ Hapus
			</button>
		</div>
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px">
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Kursus / Pelatihan</label>
				<input type="text" name="trn[${idx}][nama_pelatihan]" placeholder="Nama Kursus / Pelatihan" style="width:100%; font-size:12.5px" required>
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Lembaga Penyelenggara</label>
				<input type="text" name="trn[${idx}][penyelenggara]" placeholder="Lembaga Penyelenggara" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Tahun</label>
				<input type="text" name="trn[${idx}][tahun]" placeholder="Tahun" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Keterangan / No. Sertifikat</label>
				<input type="text" name="trn[${idx}][keterangan]" placeholder="Keterangan" style="width:100%; font-size:12.5px">
			</div>
		</div>
	`;
	c.appendChild(div);
}
function removeTrnCard(btn) {
	btn.closest('.trn-card').remove();
	var c = document.getElementById('training-container');
	var cards = c.getElementsByClassName('trn-card');
	for (var i = 0; i < cards.length; i++) {
		cards[i].querySelector('.trn-num').textContent = 'Pelatihan #' + (i + 1);
	}
}

// Dynamic Multi-Row: Reference
var refCount = <?= count($ref_init) ?>;
function addReferenceRow() {
	var c = document.getElementById('reference-container');
	var idx = refCount++;
	var div = document.createElement('div');
	div.className = 'ref-card';
	div.style = 'background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px; position:relative';
	div.innerHTML = `
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
			<strong style="font-size:12.5px; color:var(--text)" class="ref-num">Referensi #${c.children.length + 1}</strong>
			<button type="button" class="btn-sm btn-ghost" onclick="removeRefCard(this)" style="color:var(--crit); border:none; background:none; cursor:pointer; font-size:11px">
				✕ Hapus
			</button>
		</div>
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px">
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nama Lengkap</label>
				<input type="text" name="ref[${idx}][nama_referensi]" placeholder="Nama pemberi referensi" style="width:100%; font-size:12.5px" required>
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Perusahaan / Instansi</label>
				<input type="text" name="ref[${idx}][perusahaan]" placeholder="Nama instansi" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Jabatan</label>
				<input type="text" name="ref[${idx}][jabatan]" placeholder="Mis. Branch Manager / SPV" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Nomor Telepon / WA</label>
				<input type="text" name="ref[${idx}][no_telp]" placeholder="08xxxxxxxxxx" style="width:100%; font-size:12.5px">
			</div>
			<div>
				<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:3px">Hubungan Kerja</label>
				<input type="text" name="ref[${idx}][hubungan]" placeholder="Hubungan kerja" style="width:100%; font-size:12.5px">
			</div>
		</div>
	`;
	c.appendChild(div);
}
function removeRefCard(btn) {
	btn.closest('.ref-card').remove();
	var c = document.getElementById('reference-container');
	var cards = c.getElementsByClassName('ref-card');
	for (var i = 0; i < cards.length; i++) {
		cards[i].querySelector('.ref-num').textContent = 'Referensi #' + (i + 1);
	}
}

// Inisialisasi step awal (menyesuaikan URL ?step=N bila ada)
if (currentStep !== 1) {
	goToStep(currentStep);
}
</script>

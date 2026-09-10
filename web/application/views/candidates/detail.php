<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: candidates/detail.php -- Profil Lengkap Kandidat & Riwayat Seleksi
 *
 * Fungsi:
 * - Menyajikan data komprehensif pelamar (identitas, keluarga, pendidikan, riwayat kerja, kesehatan, referensi).
 * - Menangani visualisasi pas foto pelamar dan dokumen terunggah secara aman.
 * - Membatasi akses data sensitif sesuai kepatuhan UU PDP 27/2022 dan RBAC RPG.
 * - Menyediakan fitur pembuatan token tautan formulir onboarding mandiri untuk kandidat.
 */

// Hitung usia kandidat jika tgl lahir tersedia
$usia_kandidat = '-';
if (!empty($c['tanggal_lahir'])) {
	try {
		$dob = new DateTime($c['tanggal_lahir']);
		$now = new DateTime();
		$usia_kandidat = $now->diff($dob)->y . ' tahun';
	} catch (Exception $ex) {
		$usia_kandidat = '-';
	}
}

// Deteksi dokumen CV dan Pas Foto
$cv_doc = NULL;
if (!empty($documents)) {
	foreach ($documents as $d) {
		if (strtoupper($d['nama_dokumen'] ?? '') === 'CV' || stripos($d['nama_dokumen'] ?? '', 'cv') !== false) {
			$cv_doc = $d;
			break;
		}
	}
}
$has_photo = !empty($c['foto_path']) && is_file($c['foto_path']);

// Status form onboarding
$is_form_filled = !empty($onboarding_token['dipakai_pada']);
$is_form_active = !empty($onboarding_token['valid']) && !$is_form_filled;

// Total interview & psikotes
$total_evaluasi = count($interviews ?? array()) + count($psikotes ?? array()) + (!empty($offer) ? 1 : 0);
?>

<style>
/* =========================================================================
   DESAIN BERSIH & FORMAL PROFIL KANDIDAT RPG (NO EMOJI)
   ========================================================================= */

/* Hero Header Card */
.cand-hero {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 12px;
	padding: 22px 26px;
	margin-bottom: 20px;
	box-shadow: var(--shadow-sm);
	position: relative;
	overflow: hidden;
}
.cand-hero::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 3px;
	background: var(--accent);
}
.cand-photo-frame {
	width: 84px;
	height: 104px;
	border-radius: 8px;
	border: 2px solid var(--border);
	background: var(--surface-2);
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	overflow: hidden;
	box-shadow: 0 2px 8px rgba(0,0,0,0.06);
	position: relative;
}
.cand-photo-frame img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
}
.cand-photo-placeholder {
	font-size: 28px;
	font-weight: 700;
	color: var(--text-faint);
	letter-spacing: -0.02em;
}

/* Stepper Alur Seleksi */
.stage-stepper {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 16px 20px;
	margin-bottom: 20px;
	overflow-x: auto;
}
.stepper-track {
	display: flex;
	align-items: flex-start;
	min-width: 600px;
}
.stepper-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	position: relative;
	flex: 1;
	text-align: center;
}
.stepper-node {
	width: 32px;
	height: 32px;
	min-width: 32px;
	min-height: 32px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 12px;
	font-weight: 700;
	z-index: 2;
	border: 2px solid transparent;
	box-sizing: border-box;
}
.stepper-item.done .stepper-node {
	background: var(--good);
	color: #ffffff;
}
.stepper-item.current .stepper-node {
	background: var(--accent);
	color: #ffffff;
	box-shadow: 0 0 0 4px var(--accent-soft);
}
.stepper-item.pending .stepper-node {
	background: var(--surface-2);
	color: var(--text-faint);
	border-color: var(--border);
}
.stepper-item.bypassed .stepper-node {
	background: var(--surface-2);
	color: var(--text-faint);
	opacity: 0.6;
}
.stepper-line {
	position: absolute;
	top: 16px;
	left: calc(50% + 16px);
	right: calc(-50% + 16px);
	height: 2px;
	background: var(--border);
	z-index: 1;
}
.stepper-item.done .stepper-line {
	background: var(--good);
}
.stepper-item:last-child .stepper-line {
	display: none;
}
.stepper-label {
	margin-top: 8px;
	font-size: 11.5px;
	font-weight: 600;
	color: var(--text-muted);
	max-width: 110px;
	line-height: 1.25;
	word-break: break-word;
}
.stepper-item.current .stepper-label {
	color: var(--accent);
	font-weight: 700;
}
.stepper-item.done .stepper-label {
	color: var(--text);
}

/* Quick KPI Highlight Stat Cards */
.kpi-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	gap: 14px;
	margin-bottom: 20px;
}
.kpi-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 8px;
	padding: 14px 16px;
	display: flex;
	align-items: center;
	gap: 14px;
	box-shadow: var(--shadow-sm);
}
.kpi-card-inner {
	flex: 1;
}

/* Tab Navigation Formal & Bersih */
.profile-tabs-nav {
	display: flex;
	gap: 4px;
	border-bottom: 2px solid var(--border);
	margin-bottom: 20px;
	overflow-x: auto;
	padding-bottom: 0;
}
.profile-tab-btn {
	background: none;
	border: none;
	border-bottom: 2px solid transparent;
	margin-bottom: -2px;
	padding: 10px 16px;
	font-size: 13px;
	font-weight: 600;
	color: var(--text-muted);
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	white-space: nowrap;
	border-radius: 6px 6px 0 0;
	transition: all .15s ease;
}
.profile-tab-btn:hover {
	color: var(--text);
	background: var(--surface-2);
}
.profile-tab-btn.active {
	color: var(--accent);
	border-bottom-color: var(--accent);
	background: var(--surface);
	font-weight: 700;
}
.profile-tab-badge {
	font-size: 10px;
	font-family: 'IBM Plex Mono', monospace;
	padding: 1px 7px;
	border-radius: 10px;
	background: var(--surface-2);
	color: var(--text-muted);
}
.profile-tab-btn.active .profile-tab-badge {
	background: var(--accent);
	color: #ffffff;
}

/* Tab Panes */
.profile-tab-pane {
	display: none;
	animation: fadeIn .15s ease;
}
.profile-tab-pane.active {
	display: block;
}
@keyframes fadeIn {
	from { opacity: 0; transform: translateY(2px); }
	to { opacity: 1; transform: translateY(0); }
}

/* Box Section Card */
.section-box {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 8px;
	padding: 18px 20px;
	margin-bottom: 18px;
	box-shadow: var(--shadow-sm);
}
.section-box-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	border-bottom: 1px solid var(--border);
	padding-bottom: 10px;
	margin-bottom: 14px;
}
.section-box-title {
	margin: 0;
	font-size: 14px;
	font-weight: 700;
	color: var(--text);
}

/* Key-Value Grid */
.kv-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
	gap: 10px 16px;
}
.kv-item {
	padding: 8px 10px;
	background: var(--surface-2);
	border-radius: 6px;
	border: 1px solid var(--border);
}
.kv-label {
	font-size: 11px;
	font-weight: 600;
	color: var(--text-muted);
	text-transform: uppercase;
	letter-spacing: 0.04em;
	margin-bottom: 2px;
}
.kv-val {
	font-size: 13px;
	color: var(--text);
	font-weight: 600;
	word-break: break-word;
}

/* Timeline List */
.audit-timeline {
	position: relative;
	padding-left: 28px;
}
.audit-timeline::before {
	content: '';
	position: absolute;
	left: 9px;
	top: 10px;
	bottom: 10px;
	width: 2px;
	background: var(--border);
	border-radius: 1px;
}
.audit-item {
	position: relative;
	margin-bottom: 14px;
}
.audit-item:last-child {
	margin-bottom: 0;
}
.audit-dot {
	position: absolute;
	left: -28px;
	top: 14px;
	width: 20px;
	height: 20px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 10px;
	line-height: 1;
	border: 2px solid var(--surface);
	box-shadow: 0 0 0 1px var(--border);
}
.audit-dot.dot-advance  { background: var(--accent); color: #fff; }
.audit-dot.dot-status   { background: var(--good);   color: #fff; }
.audit-dot.dot-contact  { background: #6366f1;       color: #fff; }
.audit-dot.dot-reject   { background: var(--crit);   color: #fff; }
.audit-dot.dot-default  { background: var(--surface-3); color: var(--text-muted); }
.audit-card {
	background: var(--surface-2);
	border: 1px solid var(--border);
	border-radius: 8px;
	padding: 12px 16px;
	transition: box-shadow .15s;
}
.audit-card:hover {
	box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.audit-card .audit-headline {
	font-size: 13px;
	font-weight: 600;
	color: var(--text);
	line-height: 1.4;
}
.audit-card .audit-meta {
	display: flex;
	align-items: center;
	gap: 6px;
	flex-wrap: wrap;
	margin-top: 6px;
	font-size: 11.5px;
	color: var(--text-muted);
}
.audit-card .audit-meta .sep { opacity: .35; }
.audit-card .audit-desc {
	margin-top: 6px;
	font-size: 12px;
	color: var(--text-faint);
	line-height: 1.45;
	padding: 6px 10px;
	background: var(--surface);
	border-radius: 6px;
	border-left: 3px solid var(--border);
}
</style>

<div>
	<!-- ================= 1. TOP HERO PROFILE CARD ================= -->
	<div class="cand-hero">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px">
			<!-- Kolom Kiri: Foto + Biodata Pokok -->
			<div style="display:flex; gap:18px; align-items:flex-start; flex:1; min-width:320px">
				<div class="cand-photo-frame">
					<?php if ($has_photo): ?>
						<a href="<?= site_url('candidates/photo/' . (int) $c['id_lamaran']) ?>" target="_blank" title="Buka pas foto penuh">
							<img src="<?= site_url('candidates/photo/' . (int) $c['id_lamaran']) ?>" alt="<?= html_escape($c['nama_lengkap']) ?>">
						</a>
					<?php else: ?>
						<div class="cand-photo-placeholder"><?= mb_substr($c['nama_lengkap'] ?? 'P', 0, 1) ?></div>
					<?php endif; ?>
				</div>

				<div>
					<div class="eyebrow" style="margin-bottom:4px">Profil Pelamar &amp; Rekrutmen</div>
					<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap">
						<h1 style="margin:0; font-size:22px; font-weight:700"><?= html_escape($c['nama_lengkap']) ?></h1>
						<?php if (!empty($c['nama_panggilan'])): ?>
							<span class="tag" style="font-size:11.5px; background:var(--surface-2); font-weight:600">
								"<?= html_escape($c['nama_panggilan']) ?>"
							</span>
						<?php endif; ?>
						<span class="tag <?= in_array($c['status_global'], array('Hired','Approved','Sourcing')) ? 'on' : ($c['status_global'] === 'Rejected' ? 'off' : 'info') ?>" style="font-size:11.5px; padding:2px 8px; font-weight:700">
							<?= html_escape(label_status($c['status_global'])) ?>
						</span>
						<span class="tag mono" style="font-size:11px; background:var(--surface-2)">
							#<?= (int) $c['id_lamaran'] ?>
						</span>
					</div>

					<!-- Posisi, Departemen & Requisition -->
					<div style="margin-top:6px; font-size:13px; color:var(--text); display:flex; align-items:center; gap:6px; flex-wrap:wrap">
						<span style="font-weight:700; color:var(--accent)"><?= html_escape($c['nama_posisi']) ?></span>
						<span class="muted">&middot;</span>
						<span class="muted"><?= html_escape($c['nama_departemen'] ?: '-') ?></span>
						<?php if (!empty($c['nama_outlet'])): ?>
							<span class="tag" style="font-size:10px; background:var(--surface-2)"><?= html_escape($c['nama_outlet']) ?></span>
						<?php endif; ?>
						<span class="muted">&middot;</span>
						<span class="mono faint" style="font-size:12px">MPR: <?= html_escape($c['no_mpr'] ?: '#' . $c['id_req']) ?></span>
						<?php if (in_array($c['status_req'] ?? '', array('Sourcing', 'Approved', 'Sourcing_Ulang'))): ?>
							<span class="tag on" style="font-size:9.5px; padding:1px 5px; font-weight:700">Open</span>
						<?php else: ?>
							<span class="tag off" style="font-size:9.5px; padding:1px 5px; font-weight:700">Closed</span>
						<?php endif; ?>
					</div>

					<!-- Fast Contacts Strip -->
					<div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap; margin-top:10px; font-size:12.5px">
						<?php if (!empty($c['no_wa_normal'])): ?>
							<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['no_wa_normal']) ?>" target="_blank" style="color:var(--good); font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; background:var(--good-soft); padding:2px 8px; border-radius:4px">
								<span>WA:</span> <span class="mono"><?= html_escape($c['no_wa_normal']) ?></span>
							</a>
						<?php endif; ?>
						<?php if (!empty($c['email'])): ?>
							<span class="muted"><?= html_escape($c['email']) ?></span>
						<?php endif; ?>
						<?php if (!empty($c['kota_domisili'])): ?>
							<span class="muted"><?= html_escape($c['kota_domisili']) ?></span>
						<?php endif; ?>
						<span class="muted"><?= $usia_kandidat ?> (<?= $c['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($c['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?>)</span>
					</div>
				</div>
			</div>

			<!-- Kolom Kanan: Tombol Navigasi Cepat & Cetak -->
			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
				<a class="btn btn-sm btn-ghost" href="<?= site_url('pipeline/index/' . (int) $c['id_req']) ?>" style="font-weight:600">
					&larr; Pipeline Seleksi
				</a>
				<?php if ($cv_doc): ?>
					<a class="btn btn-sm btn-ghost" href="<?= site_url('documents/open/' . (int) $cv_doc['id_cand_doc']) ?>" target="_blank" style="font-weight:600; color:var(--accent)">
						Lihat CV (PDF) &rarr;
					</a>
				<?php endif; ?>
				<a class="btn btn-sm btn-primary" href="<?= site_url('candidates/print_form/' . (int) $c['id_lamaran']) ?>" target="_blank" style="background:var(--accent-ink); color:#fff; font-weight:600">
					Cetak Formulir Resmi
				</a>
			</div>
		</div>
	</div>

	<!-- Flash Messages -->
	<?php if ($this->session->flashdata('success')): ?>
		<div class="flash ok" style="margin-bottom:16px"><?= html_escape($this->session->flashdata('success')) ?></div>
	<?php endif; ?>
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:16px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>

	<!-- ================= 2. STEPPER PROGRESS SELEKSI VISUAL ================= -->
	<?php if (!empty($stages)): ?>
		<div class="stage-stepper">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
				<div style="font-size:12.5px; font-weight:700; color:var(--text)">
					Perjalanan Alur Seleksi &mdash; Flow: <?= html_escape($c['nama_flow'] ?: 'Standar RPG') ?>
				</div>
				<span class="faint" style="font-size:11.5px"><?= count($stages) ?> Tahapan Terjadwal</span>
			</div>
			<div class="stepper-track">
				<?php
				foreach ($stages as $idx => $st):
					$is_done = ($st['status_tahap'] === 'Selesai');
					$is_curr = ($st['status_tahap'] === 'Berjalan');
					$is_bypassed = ($st['status_tahap'] === 'Lewat');
					$is_pending = ($st['status_tahap'] === 'Pending');

					$node_class = $is_done ? 'done' : ($is_curr ? 'current' : ($is_bypassed ? 'bypassed' : 'pending'));
				?>
					<div class="stepper-item <?= $node_class ?>">
						<div class="stepper-line"></div>
						<div class="stepper-node" title="<?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['status_tahap']) ?>)">
							<?php if ($is_done): ?>
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
							<?php else: ?>
								<?= (int) $st['urutan'] ?>
							<?php endif; ?>
						</div>
						<div class="stepper-label">
							<?= html_escape($st['nama_tahap']) ?>
							<?php if ($is_curr): ?>
								<div style="font-size:9.5px; color:var(--accent); margin-top:2px; font-weight:700">Tahap Kini</div>
							<?php elseif ($is_done && !empty($st['tanggal_selesai'])): ?>
								<div style="font-size:9.5px; color:var(--text-faint); margin-top:2px"><?= substr($st['tanggal_selesai'], 0, 10) ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- ================= 3. KPI QUICK STAT STRIP ================= -->
	<div class="kpi-grid">
		<!-- KPI 1: Tahap Saat Ini -->
		<div class="kpi-card">
			<div class="kpi-card-inner">
				<div class="muted" style="font-size:11px; text-transform:uppercase; font-weight:600">Tahap Seleksi Kini</div>
				<div style="font-size:14px; font-weight:700; color:var(--text); margin-top:2px">
					<?= html_escape($c['nama_tahap_kini'] ?: '-') ?>
				</div>
				<div class="faint" style="font-size:11px; margin-top:1px">
					Tipe: <?= html_escape($c['tipe_tahap_kini'] ?: '-') ?>
				</div>
			</div>
		</div>

		<!-- KPI 2: Status Form Pelamar -->
		<div class="kpi-card">
			<div class="kpi-card-inner">
				<div class="muted" style="font-size:11px; text-transform:uppercase; font-weight:600">Form Pelamar</div>
				<div style="margin-top:3px">
					<?php if ($is_form_filled): ?>
						<span class="tag on" style="font-size:11px; font-weight:700">Sudah Terisi</span>
					<?php elseif ($is_form_active): ?>
						<span class="tag info" style="font-size:11px; font-weight:700">Tautan Aktif (Menunggu)</span>
					<?php else: ?>
						<span class="tag" style="font-size:11px; background:var(--surface-2)">Belum Dibuat</span>
					<?php endif; ?>
				</div>
				<?php if ($can_kelola): ?>
					<div style="margin-top:4px">
						<a href="#" onclick="openOnboardingModal(event, <?= (int) $c['id_lamaran'] ?>, '<?= html_escape(addslashes($c['nama_lengkap'])) ?>')" style="font-size:11.5px; font-weight:600">
							<?= $is_form_active ? 'Salin Link Form &rarr;' : '+ Buat Link Form' ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- KPI 4: Ekspektasi Gaji -->
		<div class="kpi-card">
			<div class="kpi-card-inner">
				<div class="muted" style="font-size:11px; text-transform:uppercase; font-weight:600">Ekspektasi Gaji Pelamar</div>
				<div style="font-size:14px; font-weight:700; color:var(--text); margin-top:2px">
					<?php if ($can_gaji_pelamar): ?>
						<?= !empty($profile['gaji_diharapkan']) ? 'Rp ' . number_format((float)$profile['gaji_diharapkan'], 0, ',', '.') : '-' ?>
					<?php else: ?>
						<span class="faint mono" style="font-size:12px">[Akses Terbatas]</span>
					<?php endif; ?>
				</div>
				<div class="faint" style="font-size:11px; margin-top:1px">
					<?php if ($can_gaji_pelamar && !empty($profile['gaji_terakhir'])): ?>
						Terakhir: Rp <?= number_format((float)$profile['gaji_terakhir'], 0, ',', '.') ?>
					<?php else: ?>
						Berdasarkan Form Onboarding
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- ================= 4. PANEL AKSI TRANSISI TAHAP SELEKSI ================= -->
	<?php if ($can_kelola && $active_stage && ! in_array($c['status_global'], array('Hired','Rejected','Withdrawn','Offer_Declined','No_Show'))): ?>
		<div class="card" style="padding:14px 20px; margin-bottom:20px; background:var(--surface); border:1px solid var(--accent); border-left:4px solid var(--accent); box-shadow:var(--shadow-sm)">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px">
				<div style="display:flex; align-items:center; gap:8px">
					<span style="font-size:13.5px; font-weight:700">Keputusan Transisi Tahap Berjalan:</span>
					<span class="tag accent" style="font-size:12px; font-weight:700">
						<?= (int) $active_stage['urutan'] ?>. <?= html_escape($active_stage['nama_tahap']) ?>
					</span>
					<span class="tag info" style="font-size:10.5px"><?= html_escape($active_stage['tipe_tahap']) ?></span>
				</div>
				<span class="faint mono" style="font-size:11.5px">Mulai sejak: <?= $active_stage['tanggal_mulai'] ? html_escape(substr($active_stage['tanggal_mulai'], 0, 16)) : '-' ?></span>
			</div>

			<?= form_open(site_url('pipeline/advance/' . (int) $c['id_req']), array('style' => 'margin:0')) ?>
				<input type="hidden" name="id_app_stage" value="<?= (int) $active_stage['id_app_stage'] ?>">
				<input type="hidden" name="redirect_to" value="candidates/detail/<?= (int) $c['id_lamaran'] ?>">

				<div style="display:grid; grid-template-columns: 260px 1fr auto; gap:10px; align-items:center">
					<div>
						<select name="id_remark" required style="width:100%; font-size:12.5px; padding:8px 10px; border-radius:6px; background:var(--surface-2)">
							<option value="">-- Pilih Hasil / Remark Evaluasi --</option>
							<?php if ( ! empty($remarks)): ?>
								<?php foreach ($remarks as $rmk): ?>
									<option value="<?= (int) $rmk['id_remark'] ?>">
										<?= html_escape($rmk['label']) ?> (<?= html_escape($rmk['efek_status']) ?>)
									</option>
								<?php endforeach; ?>
							<?php else: ?>
								<option value="">Lanjut Tahap Berikutnya</option>
							<?php endif; ?>
						</select>
					</div>
					<div>
						<input type="text" name="catatan" placeholder="Tambahkan catatan evaluasi / alasan keputusan seleksi (opsional)..." style="width:100%; font-size:12.5px; padding:8px 10px; border-radius:6px; margin:0">
					</div>
					<div>
						<button type="submit" class="btn btn-sm btn-primary" style="white-space:nowrap; padding:8px 18px; font-weight:700" onclick="return confirm('Eksekusi keputusan seleksi untuk kandidat ini?')">
							Proses Keputusan &rarr;
						</button>
					</div>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>

	<!-- Panel Koreksi Pembatalan Hired (Khusus status Hired) -->
	<?php if ($can_kelola && $c['status_global'] === 'Hired'): ?>
		<div class="card" style="padding:14px 18px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-left:4px solid var(--crit)">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px">
				<div style="font-size:13px; font-weight:700; color:var(--crit)">Pembatalan Status Hired (Koreksi Kuota)</div>
				<div class="muted" style="font-size:11.5px">Gunakan opsi ini jika kandidat yang telah diterima mengundurkan diri atau batal onboarding.</div>
			</div>
			<?= form_open(site_url('pipeline/cancel_hired/' . (int) $c['id_req']), array('style' => 'margin:0')) ?>
				<input type="hidden" name="id_lamaran" value="<?= (int) $c['id_lamaran'] ?>">
				<input type="hidden" name="redirect_to" value="candidates/detail/<?= (int) $c['id_lamaran'] ?>">
				<div style="display:grid; grid-template-columns: 220px 1fr auto; gap:10px; align-items:center">
					<select name="status_tujuan" required style="width:100%; font-size:12.5px; padding:7px 9px; border-radius:6px">
						<option value="Withdrawn">Mengundurkan Diri</option>
						<option value="Offer_Declined">Menolak Penawaran</option>
						<option value="Rejected">Dibatalkan oleh Perusahaan</option>
					</select>
					<input type="text" name="alasan" required placeholder="Tuliskan alasan pembatalan status hired..." style="width:100%; font-size:12.5px; padding:7px 9px; border-radius:6px; margin:0">
					<button type="submit" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit); white-space:nowrap; padding:7px 14px; font-weight:700" onclick="return confirm('Batalkan status Hired kandidat ini?')">
						Batalkan Hired
					</button>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>

	<!-- ================= 5. SISTEM TAB INTERAKTIF ================= -->
	<nav class="profile-tabs-nav" id="profileTabsNav">
		<button type="button" class="profile-tab-btn active" onclick="switchTab('tab-pribadi', this)">
			<span>Biodata &amp; Kontak</span>
		</button>
		<button type="button" class="profile-tab-btn" onclick="switchTab('tab-kerja', this)">
			<span>Riwayat Kerja</span>
			<?php if (!empty($experiences)): ?>
				<span class="profile-tab-badge"><?= count($experiences) ?></span>
			<?php endif; ?>
		</button>
		<button type="button" class="profile-tab-btn" onclick="switchTab('tab-keluarga', this)">
			<span>Keluarga &amp; Kursus</span>
			<?php if (!empty($families) || !empty($trainings)): ?>
				<span class="profile-tab-badge"><?= count($families) + count($trainings) ?></span>
			<?php endif; ?>
		</button>
		<button type="button" class="profile-tab-btn" onclick="switchTab('tab-kuesioner', this)">
			<span>18 Butir Kuesioner RPG</span>
			<?php if (!empty($questionnaire)): ?>
				<span class="profile-tab-badge" style="background:var(--good); color:#fff">Terisi</span>
			<?php endif; ?>
		</button>
		<button type="button" class="profile-tab-btn" onclick="switchTab('tab-evaluasi', this)">
			<span>Interview &amp; Evaluasi</span>
			<?php if ($total_evaluasi > 0): ?>
				<span class="profile-tab-badge"><?= $total_evaluasi ?></span>
			<?php endif; ?>
		</button>
		<button type="button" class="profile-tab-btn" onclick="switchTab('tab-riwayat', this)">
			<span>Riwayat Proses</span>
			<span class="profile-tab-badge"><?= count($history ?? array()) ?></span>
		</button>
	</nav>

	<!-- ================= TAB 1: DATA PRIBADI & KONTAK ================= -->
	<div id="tab-pribadi" class="profile-tab-pane active">
		<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap:18px">
			<!-- Box 1: Identitas Pokok & Fisik -->
			<div class="section-box">
				<div class="section-box-header">
					<h3 class="section-box-title">Identitas Diri &amp; Karakteristik Fisik</h3>
					<span class="tag info" style="font-size:10px">Identitas Dasar</span>
				</div>
				<div class="kv-grid">
					<div class="kv-item">
						<div class="kv-label">Nama Lengkap Sesuai KTP</div>
						<div class="kv-val"><?= html_escape($c['nama_lengkap']) ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Nama Panggilan</div>
						<div class="kv-val"><?= html_escape($c['nama_panggilan'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Tempat &amp; Tanggal Lahir</div>
						<div class="kv-val"><?= html_escape($c['tempat_lahir'] ?: '-') ?>, <?= html_escape($c['tanggal_lahir'] ? substr($c['tanggal_lahir'], 0, 10) : '-') ?> (<?= $usia_kandidat ?>)</div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Jenis Kelamin</div>
						<div class="kv-val"><?= $c['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($c['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Status Pernikahan</div>
						<div class="kv-val"><?= html_escape($c['status_pernikahan'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Agama / Kepercayaan</div>
						<div class="kv-val"><?= html_escape($c['agama'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Golongan Darah</div>
						<div class="kv-val mono"><?= html_escape($c['gol_darah'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Tinggi &amp; Berat Badan</div>
						<div class="kv-val"><?= $c['tinggi_badan'] ? (int)$c['tinggi_badan'] . ' cm' : '-' ?> &middot; <?= $c['berat_badan'] ? (int)$c['berat_badan'] . ' kg' : '-' ?></div>
					</div>
					<div class="kv-item" style="grid-column:1/-1">
						<div class="kv-label">Status Tempat Tinggal Saat Ini</div>
						<div class="kv-val"><?= html_escape($c['status_tempat_tinggal'] ?: '-') ?></div>
					</div>
				</div>
			</div>

			<!-- Box 2: Legalitas Identitas & Kontak Lengkap -->
			<div class="section-box">
				<div class="section-box-header">
					<h3 class="section-box-title">Nomor Identitas &amp; Alamat Domisili</h3>
					<span class="tag" style="font-size:10px">Verifikasi</span>
				</div>
				<div class="kv-grid">
					<div class="kv-item">
						<div class="kv-label">Nomor Induk Kependudukan (NIK KTP)</div>
						<div class="kv-val mono"><?= html_escape($c['nik'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Nomor Pokok Wajib Pajak (NPWP)</div>
						<div class="kv-val mono"><?= html_escape($c['npwp'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Kepemilikan SIM (A/B/C)</div>
						<div class="kv-val" style="color:var(--accent)"><?= html_escape($c['no_sim'] ?: 'Tidak Memiliki') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Kota Domisili</div>
						<div class="kv-val"><?= html_escape($c['kota_domisili'] ?: '-') ?></div>
					</div>
					<div class="kv-item" style="grid-column:1/-1">
						<div class="kv-label">Alamat Lengkap Tempat Tinggal</div>
						<div class="kv-val" style="font-weight:normal; line-height:1.4"><?= nl2br(html_escape($c['alamat_lengkap'] ?: '-')) ?></div>
					</div>
					<div class="kv-item" style="grid-column:1/-1">
						<div class="kv-label">Kontak Darurat (Emergency Contact)</div>
						<div class="kv-val">
							<?= html_escape($c['kontak_darurat_nama'] ?: '-') ?>
							<?php if (!empty($c['kontak_darurat_hub'])): ?>
								<span class="tag" style="font-size:10px; margin-left:4px"><?= html_escape($c['kontak_darurat_hub']) ?></span>
							<?php endif; ?>
							&middot; <span class="mono" style="font-weight:700"><?= html_escape($c['kontak_darurat_telp'] ?: '-') ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Box 3: Pendidikan Terakhir & Keahlian Kompetensi -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Pendidikan Formal &amp; Penguasaan Keahlian</h3>
				<span class="tag on" style="font-size:10px">Kualifikasi</span>
			</div>
			<div class="kv-grid">
				<div class="kv-item">
					<div class="kv-label">Pendidikan Terakhir</div>
					<div class="kv-val"><?= html_escape($c['pendidikan_terakhir'] ?: '-') ?></div>
				</div>
				<div class="kv-item">
					<div class="kv-label">Jurusan / Bidang Studi</div>
					<div class="kv-val"><?= html_escape($c['jurusan'] ?: '-') ?></div>
				</div>
				<div class="kv-item">
					<div class="kv-label">Nama Sekolah / Universitas / Lembaga Pendidikan</div>
					<div class="kv-val"><?= html_escape($c['nama_sekolah'] ?: '-') ?></div>
				</div>
				<div class="kv-item">
					<div class="kv-label">Keahlian Komputer &amp; Perangkat Lunak</div>
					<div class="kv-val"><?= html_escape($c['keahlian_komputer'] ?: '-') ?></div>
				</div>
				<div class="kv-item">
					<div class="kv-label">Penguasaan Bahasa Asing</div>
					<div class="kv-val"><?= html_escape($c['bahasa_asing'] ?: '-') ?></div>
				</div>
			</div>
		</div>
	</div>

	<!-- ================= TAB 2: RIWAYAT KERJA & KOMPENSASI ================= -->
	<div id="tab-kerja" class="profile-tab-pane">
		<!-- Box Kompensasi & Rekening Bank -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Ringkasan Kompensasi &amp; Rekening Payroll</h3>
				<span class="tag" style="font-size:10px">Sensitif</span>
			</div>
			<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px">
				<div class="kv-item">
					<div class="kv-label">Gaji Terakhir yang Diterima</div>
					<div class="kv-val" style="font-size:15px">
						<?php if ($can_gaji_pelamar): ?>
							<?= !empty($profile['gaji_terakhir']) ? 'Rp ' . number_format((float)$profile['gaji_terakhir'], 0, ',', '.') : '-' ?>
						<?php else: ?>
							<span class="muted">[Izin Akses Gaji Diperlukan]</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="kv-item">
					<div class="kv-label">Ekspektasi Gaji yang Diharapkan</div>
					<div class="kv-val" style="font-size:15px; color:var(--accent)">
						<?php if ($can_gaji_pelamar): ?>
							<?= !empty($profile['gaji_diharapkan']) ? 'Rp ' . number_format((float)$profile['gaji_diharapkan'], 0, ',', '.') : '-' ?>
						<?php else: ?>
							<span class="muted">[Izin Akses Gaji Diperlukan]</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="kv-item">
					<div class="kv-label">Nomor Rekening Bank Payroll</div>
					<div class="kv-val">
						<?php if ($can_finansial && $bank): ?>
							<div><?= html_escape($bank['nama_bank']) ?> &middot; <span class="mono"><?= html_escape($bank['no_rekening']) ?></span></div>
							<div class="faint" style="font-size:11px">a.n <?= html_escape($bank['nama_pemilik']) ?></div>
						<?php else: ?>
							<span class="muted"><?= $bank ? '[Data Finansial Terproteksi]' : 'Belum diisi' ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Daftar Pengalaman Kerja -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Daftar Riwayat Pengalaman Kerja Profesional</h3>
				<span class="tag on" style="font-size:10.5px"><?= count($experiences ?? array()) ?> Pengalaman</span>
			</div>
			<?php if (!empty($experiences)): ?>
				<div class="table-responsive-fit">
					<table>
						<thead>
							<tr>
								<th style="width:36px; text-align:center">#</th>
								<th>Perusahaan &amp; Jabatan</th>
								<th>Periode Kerja</th>
								<th>Gaji Terakhir</th>
								<th>Alasan Berhenti</th>
								<th>Uraian Tugas &amp; Tanggung Jawab</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($experiences as $e): ?>
								<tr>
									<td class="mono" style="text-align:center"><?= (int) $e['urutan'] ?></td>
									<td>
										<div style="font-weight:700; font-size:13.5px"><?= html_escape($e['nama_perusahaan']) ?></div>
										<div class="tag info" style="font-size:11px; margin-top:3px"><?= html_escape($e['posisi_jabatan']) ?></div>
									</td>
									<td class="faint mono" style="font-size:12px; white-space:nowrap"><?= html_escape($e['periode_kerja'] ?: '-') ?></td>
									<td class="mono" style="font-size:12.5px; white-space:nowrap">
										<?php if ($can_gaji_pelamar): ?>
											<?= $e['gaji_terakhir'] !== NULL ? 'Rp ' . number_format((float)$e['gaji_terakhir'], 0, ',', '.') : '-' ?>
										<?php else: ?>
											<span class="muted">[Terproteksi]</span>
										<?php endif; ?>
									</td>
									<td style="font-size:12px"><?= html_escape($e['alasan_keluar'] ?: '-') ?></td>
									<td style="font-size:12px; max-width:280px"><?= nl2br(html_escape($e['deskripsi_tugas'] ?: '-')) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="muted" style="font-size:13px; padding:14px 0; text-align:center">Belum ada data riwayat pengalaman kerja yang dimasukkan kandidat.</div>
			<?php endif; ?>
		</div>

		<!-- Referensi Kerja Profesional -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Kontak Referensi Kerja Profesional</h3>
				<span class="tag" style="font-size:10.5px"><?= count($references ?? array()) ?> Referensi</span>
			</div>
			<?php if (!empty($references)): ?>
				<div class="table-responsive-fit">
					<table>
						<thead>
							<tr>
								<th style="width:36px; text-align:center">#</th>
								<th>Nama Referensi</th>
								<th>Perusahaan &amp; Jabatan</th>
								<th>Nomor Telepon</th>
								<th>Hubungan Kerja</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($references as $rf): ?>
								<tr>
									<td class="mono" style="text-align:center"><?= (int) $rf['urutan'] ?></td>
									<td><strong><?= html_escape($rf['nama_referensi']) ?></strong></td>
									<td><?= html_escape($rf['perusahaan'] ?: '-') ?> &middot; <?= html_escape($rf['jabatan'] ?: '-') ?></td>
									<td>
										<?php if (!empty($rf['no_telp'])): ?>
											<a href="tel:<?= preg_replace('/[^0-9+]/', '', $rf['no_telp']) ?>" class="mono" style="font-weight:700">
												Tel: <?= html_escape($rf['no_telp']) ?>
											</a>
										<?php else: ?>
											<span class="muted">-</span>
										<?php endif; ?>
									</td>
									<td><span class="tag" style="font-size:11px"><?= html_escape($rf['hubungan'] ?: '-') ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="muted" style="font-size:13px; padding:14px 0; text-align:center">Belum ada data kontak referensi kerja profesional.</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- ================= TAB 3: KELUARGA & PELATIHAN ================= -->
	<div id="tab-keluarga" class="profile-tab-pane">
		<!-- Susunan Anggota Keluarga -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Susunan Anggota Keluarga Inti</h3>
				<span class="tag on" style="font-size:10.5px"><?= count($families ?? array()) ?> Anggota Terdaftar</span>
			</div>
			<?php if (!empty($families)): ?>
				<div class="table-responsive-fit">
					<table>
						<thead>
							<tr>
								<th style="width:36px; text-align:center">#</th>
								<th>Hubungan</th>
								<th>Nama Lengkap</th>
								<th style="text-align:center">L/P</th>
								<th style="text-align:center">Usia</th>
								<th>Pendidikan Terakhir</th>
								<th>Pekerjaan Saat Ini</th>
								<th>No. Telepon</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($families as $fam): ?>
								<tr>
									<td class="mono" style="text-align:center"><?= (int) $fam['urutan'] ?></td>
									<td>
										<span class="tag <?= in_array(strtolower($fam['hubungan']), array('ayah','ibu','suami','istri')) ? 'on' : 'info' ?>" style="font-size:11px; font-weight:700">
											<?= html_escape($fam['hubungan']) ?>
										</span>
									</td>
									<td><strong><?= html_escape($fam['nama_lengkap']) ?></strong></td>
									<td style="text-align:center"><?= html_escape($fam['jenis_kelamin'] ?: '-') ?></td>
									<td class="mono" style="text-align:center"><?= $fam['usia'] ? (int)$fam['usia'] . ' th' : '-' ?></td>
									<td><?= html_escape($fam['pendidikan'] ?: '-') ?></td>
									<td><?= html_escape($fam['pekerjaan'] ?: '-') ?></td>
									<td><span class="mono"><?= html_escape($fam['no_telp'] ?: '-') ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="muted" style="font-size:13px; padding:14px 0; text-align:center">Belum ada data susunan keluarga yang diisi pelamar.</div>
			<?php endif; ?>
		</div>

		<!-- Pelatihan / Kursus Non-Formal -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Pelatihan, Kursus &amp; Sertifikasi Non-Formal</h3>
				<span class="tag on" style="font-size:10.5px"><?= count($trainings ?? array()) ?> Pelatihan</span>
			</div>
			<?php if (!empty($trainings)): ?>
				<div class="table-responsive-fit">
					<table>
						<thead>
							<tr>
								<th style="width:36px; text-align:center">#</th>
								<th>Nama Pelatihan / Kursus</th>
								<th>Lembaga Penyelenggara</th>
								<th style="text-align:center">Tahun</th>
								<th>Keterangan / No. Sertifikat</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($trainings as $tr): ?>
								<tr>
									<td class="mono" style="text-align:center"><?= (int) $tr['urutan'] ?></td>
									<td><strong><?= html_escape($tr['nama_pelatihan']) ?></strong></td>
									<td><?= html_escape($tr['penyelenggara'] ?: '-') ?></td>
									<td class="mono" style="text-align:center"><span class="tag" style="font-size:10.5px"><?= html_escape($tr['tahun'] ?: '-') ?></span></td>
									<td><?= html_escape($tr['keterangan'] ?: '-') ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="muted" style="font-size:13px; padding:14px 0; text-align:center">Belum ada riwayat pelatihan atau kursus yang dimasukkan.</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- ================= TAB 4: 18 BUTIR KUESIONER RPG ================= -->
	<div id="tab-kuesioner" class="profile-tab-pane">
		<div class="section-box">
			<div class="section-box-header">
				<div>
					<h3 class="section-box-title">18 Butir Evaluasi Minat, Konsep Diri &amp; Kesiapan Kerja RPG</h3>
					<div class="muted" style="font-size:11.5px; margin-top:2px">Formulir baku assessment internal calon karyawan Ratu Pertiwi Group</div>
				</div>
				<?php if (!empty($questionnaire)): ?>
					<span class="tag on" style="font-size:11px; font-weight:700">Lengkap Diisi Kandidat</span>
				<?php else: ?>
					<span class="tag off" style="font-size:11px">Belum Mengisi Formulir</span>
				<?php endif; ?>
			</div>

			<?php if (!empty($questionnaire)): ?>
				<?php
				$categories = array(
					'A. Visi, Motivasi & Pengetahuan Brand RPG' => array(
						array('1. Motivasi / Alasan Melamar di RPG', $questionnaire['alasan_melamar'] ?? ''),
						array('2. Alasan Merasa Cocok untuk Posisi yang Dilamar', $questionnaire['alasan_cocok_posisi'] ?? ''),
						array('3. Pengetahuan tentang Bisnis & Brand RPG', $questionnaire['pengetahuan_rpg'] ?? ''),
						array('7. Rencana & Sasaran Karir 3-5 Tahun ke Depan', $questionnaire['rencana_karir_5thn'] ?? ''),
						array('10. Riwayat Pernah Melamar di Brand RPG Sebelumnya', $questionnaire['riwayat_melamar_rpg'] ?? ''),
					),
					'B. Karakter Diri, Kompetensi & Problem Solving' => array(
						array('4. Kelebihan / Kekuatan Utama Diri', $questionnaire['kelebihan_diri'] ?? ''),
						array('5. Kekurangan Diri & Cara Mengatasinya', $questionnaire['kekurangan_diri'] ?? ''),
						array('6. Prestasi / Pencapaian Terbesar yang Membanggakan', $questionnaire['prestasi_terbesar'] ?? ''),
						array('8. Masalah Paling Sulit Pernah Dihadapi & Solusinya', $questionnaire['masalah_tersulit_solusi'] ?? ''),
						array('9. Lingkungan Kerja Idaman & yang Ingin Dihindari', $questionnaire['lingkungan_kerja_idaman'] ?? ''),
					),
					'C. Kesiapan Operasional, Shift & Fleksibilitas Kerja' => array(
						array('11. Kepemilikan Kendaraan Pribadi & SIM Operasional', $questionnaire['kepemilikan_kendaraan'] ?? ''),
						array('13. Ketersediaan Mulai Bekerja (Start Date)', $questionnaire['ketersediaan_mulai'] ?? ''),
						array('14. Kesiapan Menjalankan Shift, Hari Libur & Lembur', $questionnaire['bersedia_shift_lembur'] ?? ''),
						array('15. Kesiapan Penugasan Luar Kota / Mutasi Cabang', $questionnaire['bersedia_luar_kota'] ?? ''),
					),
					'D. Kompensasi, Integritas & Kepatuhan Hukum' => array(
						array('12. Harapan Gaji, Tunjangan, & Fasilitas Kerja', $questionnaire['harapan_gaji_fasilitas'] ?? ''),
						array('16. Pekerjaan Sampingan / Bisnis / Ikatan Dinas Lain', $questionnaire['punya_bisnis_sampingan'] ?? ''),
						array('17. Relasi / Kenalan / Keluarga yang Bekerja di RPG', $questionnaire['relasi_keluarga_rpg'] ?? ''),
						array('18. Riwayat Keterlibatan Perkara Pidana / Hukum', $questionnaire['riwayat_tindak_pidana'] ?? ''),
					),
				);
				?>

				<?php foreach ($categories as $cat_title => $items): ?>
					<div style="margin-bottom:20px">
						<div style="font-size:13px; font-weight:700; color:var(--accent); margin-bottom:10px; padding-bottom:4px; border-bottom:1px solid var(--border)">
							<?= html_escape($cat_title) ?>
						</div>
						<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:12px">
							<?php foreach ($items as $q): ?>
								<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 14px">
									<div style="font-size:11.5px; font-weight:700; color:var(--text-muted); margin-bottom:6px">
										<?= html_escape($q[0]) ?>
									</div>
									<div style="font-size:13px; color:var(--text); line-height:1.45; font-weight:500">
										<?= nl2br(html_escape($q[1] ?: '-')) ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else: ?>
				<div style="padding:40px 20px; text-align:center">
					<div style="font-weight:700; font-size:14px; margin-bottom:4px">Kandidat Belum Mengisi 18 Butir Kuesioner RPG</div>
					<div class="muted" style="font-size:12.5px; max-width:480px; margin:0 auto 16px">
						Data evaluasi minat dan konsep diri diisi secara mandiri oleh kandidat melalui tautan formulir pelamar online.
					</div>
					<?php if ($can_kelola): ?>
						<button type="button" class="btn btn-sm btn-primary" onclick="openOnboardingModal(event, <?= (int) $c['id_lamaran'] ?>, '<?= html_escape(addslashes($c['nama_lengkap'])) ?>')">
							Bagikan Tautan Formulir ke Kandidat &rarr;
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- ================= TAB 5: INTERVIEW & EVALUASI SELEKSI ================= -->
	<div id="tab-evaluasi" class="profile-tab-pane">
		<!-- Sesi Interview -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Jadwal &amp; Hasil Sesi Wawancara (Interviews)</h3>
				<span class="tag" style="font-size:10.5px"><?= count($interviews ?? array()) ?> Sesi</span>
			</div>
			<?php if (!empty($interviews)): ?>
				<div class="table-responsive-fit">
					<table>
						<thead>
							<tr>
								<th>Tahap / Tipe</th>
								<th>Jadwal Pelaksanaan</th>
								<th>Pewawancara (Interviewer)</th>
								<th>Lokasi / Tautan</th>
								<th style="text-align:center">Hasil</th>
								<th style="text-align:center">Skor</th>
								<th>Catatan Evaluasi</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($interviews as $iv): ?>
								<tr>
									<td>
										<strong><?= html_escape($iv['nama_tahap']) ?></strong>
										<div class="faint" style="font-size:11px"><?= html_escape($iv['tipe']) ?></div>
									</td>
									<td class="mono" style="font-size:12px; white-space:nowrap">
										<?= html_escape(substr($iv['jadwal'], 0, 16)) ?>
									</td>
									<td>
										<strong><?= html_escape($iv['nama_interviewer'] ?: '-') ?></strong>
										<?php if (!empty($iv['peran_interviewer'])): ?>
											<span class="tag" style="font-size:10px"><?= html_escape($iv['peran_interviewer']) ?></span>
										<?php endif; ?>
									</td>
									<td style="font-size:12px"><?= html_escape($iv['lokasi_atau_link'] ?: '-') ?></td>
									<td style="text-align:center">
										<?php
										$h = $iv['hasil'];
										$h_tag = ($h === 'Lolos') ? 'on' : (($h === 'Gagal') ? 'off' : 'info');
										?>
										<span class="tag <?= $h_tag ?>" style="font-size:11px; font-weight:700">
											<?= html_escape($h ?: 'Pending') ?>
										</span>
									</td>
									<td class="mono" style="text-align:center; font-weight:700">
										<?= $iv['skor'] !== NULL ? (int)$iv['skor'] : '-' ?>
									</td>
									<td style="font-size:12px; max-width:240px"><?= nl2br(html_escape($iv['catatan'] ?: '-')) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="muted" style="padding:16px 0; text-align:center">Belum ada sesi wawancara yang dicatat untuk kandidat ini.</div>
			<?php endif; ?>
		</div>

		<!-- Hasil Psikotes -->
		<div class="section-box">
			<div class="section-box-header">
				<h3 class="section-box-title">Hasil Uji Psikotes &amp; Assessment</h3>
				<span class="tag" style="font-size:10.5px"><?= count($psikotes ?? array()) ?> Tes</span>
			</div>
			<?php if (!empty($psikotes)): ?>
				<div class="table-responsive-fit">
					<table>
						<thead>
							<tr>
								<th>Tahap &amp; Vendor</th>
								<th>Tanggal Tes</th>
								<th style="text-align:center">Skor Total</th>
								<th style="text-align:center">Hasil</th>
								<th>Rekomendasi Psikolog</th>
								<th>Penguji</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($psikotes as $psi): ?>
								<tr>
									<td>
										<strong><?= html_escape($psi['nama_tahap']) ?></strong>
										<div class="faint" style="font-size:11px"><?= html_escape($psi['vendor_tes'] ?: 'Internal RPG') ?></div>
									</td>
									<td class="mono" style="font-size:12px"><?= html_escape(substr($psi['tanggal_tes'], 0, 10)) ?></td>
									<td class="mono" style="text-align:center; font-weight:700"><?= $psi['skor_total'] !== NULL ? (int)$psi['skor_total'] : '-' ?></td>
									<td style="text-align:center">
										<span class="tag <?= $psi['hasil'] === 'Disarankan' ? 'on' : ($psi['hasil'] === 'Tidak_Disarankan' ? 'off' : 'info') ?>">
											<?= html_escape($psi['hasil'] ?: '-') ?>
										</span>
									</td>
									<td style="font-size:12.5px"><?= html_escape($psi['rekomendasi'] ?: '-') ?></td>
									<td><?= html_escape($psi['dilakukan_oleh_nama'] ?: '-') ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="muted" style="padding:16px 0; text-align:center">Belum ada hasil psikotes yang tercatat.</div>
			<?php endif; ?>
		</div>

		<!-- Status Offering Letter (Jika ada) -->
		<?php if (!empty($offer)): ?>
			<div class="section-box">
				<div class="section-box-header">
					<h3 class="section-box-title">Status Surat Penawaran Kerja (Offering Letter)</h3>
					<span class="tag <?= $offer['status_offer'] === 'Diterima' ? 'on' : ($offer['status_offer'] === 'Ditolak' ? 'off' : 'info') ?>" style="font-size:11px; font-weight:700">
						<?= html_escape($offer['status_offer']) ?>
					</span>
				</div>
				<div class="kv-grid">
					<div class="kv-item">
						<div class="kv-label">Gaji yang Ditawarkan</div>
						<div class="kv-val" style="color:var(--accent); font-size:15px">
							<?php if ($can_gaji): ?>
								<?= $offer['gaji_ditawarkan'] !== NULL ? 'Rp ' . number_format((float)$offer['gaji_ditawarkan'], 0, ',', '.') : '-' ?>
							<?php else: ?>
								<span class="muted">[Izin Akses Gaji Diperlukan]</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Rencana Tanggal Bergabung (Join Date)</div>
						<div class="kv-val mono"><?= html_escape($offer['tanggal_join_rencana'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Batas Waktu Respon Kandidat</div>
						<div class="kv-val mono"><?= html_escape($offer['tanggal_kadaluarsa'] ?: '-') ?></div>
					</div>
					<div class="kv-item">
						<div class="kv-label">Catatan Offering</div>
						<div class="kv-val"><?= html_escape($offer['catatan'] ?: '-') ?></div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- ================= TAB 6: RIWAYAT & AUDIT TRAIL ================= -->
	<div id="tab-riwayat" class="profile-tab-pane">
		<div class="section-box">
			<div class="section-box-header">
				<div>
					<h3 class="section-box-title">Riwayat Proses Seleksi</h3>
					<div class="muted" style="font-size:11.5px; margin-top:2px">Kronologi lengkap setiap langkah seleksi kandidat ini, dari awal hingga akhir.</div>
				</div>
				<span class="faint mono" style="font-size:11px"><?= count($history ?? array()) ?> Aktivitas</span>
			</div>
			<?php if (!empty($history)): ?>
				<div class="audit-timeline">
					<?php foreach ($history as $h): ?>
					<?php
						/* -- Terjemahkan jenis_event + tentukan ikon & warna dot -- */
						$evt = $h['jenis_event'];
						$dot_class = 'dot-default';
						$icon = '●';
						switch ($evt) {
							case 'STAGE_CHANGE':
								$dot_class = 'dot-advance';
								$icon = '→';
								break;
							case 'STATUS_CHANGE':
								$is_bad = in_array($h['status_ke'], array('Rejected','Withdrawn','Offer_Declined','No_Show','Tidak_Lulus'));
								$dot_class = $is_bad ? 'dot-reject' : 'dot-status';
								$icon = $is_bad ? '✕' : '✓';
								break;
							case 'KONTAK':
								$dot_class = 'dot-contact';
								$icon = '☎';
								break;
							case 'DOKUMEN':
								$dot_class = 'dot-default';
								$icon = '📄';
								break;
						}

						/* -- Terjemahkan deskripsi mentah DB ke bahasa manusia -- */
						$desc_raw = trim($h['deskripsi'] ?? '');
						$desc_display = '';
						$catatan_user = '';

						if (preg_match('/^efek=(\w+)(?:\s*\|\s*(.*))?$/i', $desc_raw, $m)) {
							$efek_map = array(
								'LANJUT' => 'Lanjut ke tahap berikutnya',
								'lanjut' => 'Lanjut ke tahap berikutnya',
								'HIRED'  => 'Kandidat diterima (Hired)',
								'hired'  => 'Kandidat diterima (Hired)',
								'TOLAK'  => 'Kandidat tidak lolos tahap ini',
								'tolak'  => 'Kandidat tidak lolos tahap ini',
							);
							$desc_display = isset($efek_map[$m[1]]) ? $efek_map[$m[1]] : ucfirst(strtolower($m[1]));
							$catatan_user = !empty($m[2]) ? trim($m[2]) : '';
						} elseif (preg_match('/^Kontak\s*#(\d+)\s*\((\w+)\/(\w+)\)$/i', $desc_raw, $m)) {
							$metode_map = array(
								'whatsapp' => 'WhatsApp', 'wa' => 'WhatsApp', 'telepon' => 'Telepon',
								'email' => 'Email', 'sms' => 'SMS',
							);
							$hasil_map = array(
								'berhasil' => 'Berhasil dihubungi', 'tidak_berhasil' => 'Tidak berhasil',
								'gagal' => 'Tidak berhasil', 'tidak_aktif' => 'Nomor tidak aktif',
								'voicemail' => 'Masuk voicemail',
							);
							$metode = isset($metode_map[strtolower($m[2])]) ? $metode_map[strtolower($m[2])] : $m[2];
							$hasil  = isset($hasil_map[strtolower($m[3])]) ? $hasil_map[strtolower($m[3])] : $m[3];
							$desc_display = 'Upaya kontak ke-' . $m[1] . ' via ' . $metode . ' — ' . $hasil;
						} elseif (preg_match('/^Verifikasi dokumen #\d+ -> (.+)$/i', $desc_raw, $m)) {
							$desc_display = 'Dokumen diverifikasi — status: ' . trim($m[1]);
						} elseif (preg_match('/^Pembatalan status Hired \| (.+)$/i', $desc_raw, $m)) {
							$desc_display = 'Status Hired dibatalkan';
							$catatan_user = trim($m[1]);
						} elseif ($desc_raw !== '') {
							$desc_display = $desc_raw;
						}

						/* -- Bangun kalimat headline yang mudah dipahami -- */
						$headline = '';
						if ($evt === 'STAGE_CHANGE') {
							if ($h['tahap_asal'] && $h['tahap_tujuan']) {
								$headline = 'Maju ke tahap <strong>' . html_escape($h['tahap_tujuan']) . '</strong>';
								if (!empty($h['remark_label'])) {
									$headline .= ' — hasil: ' . html_escape($h['remark_label']);
								}
							} elseif ($h['tahap_tujuan']) {
								$headline = 'Masuk tahap <strong>' . html_escape($h['tahap_tujuan']) . '</strong>';
							} else {
								$headline = 'Perpindahan tahap seleksi';
							}
						} elseif ($evt === 'STATUS_CHANGE') {
							/* Terjemahkan status ke label ramah */
							$status_labels = array(
								'In_Progress' => 'Sedang Proses', 'Hired' => 'Diterima',
								'Rejected' => 'Ditolak', 'Withdrawn' => 'Mengundurkan Diri',
								'Offer_Declined' => 'Menolak Penawaran', 'No_Show' => 'Tidak Hadir',
							);
							$dari_label = isset($status_labels[$h['status_dari']]) ? $status_labels[$h['status_dari']] : ($h['status_dari'] ?: 'Awal');
							$ke_label   = isset($status_labels[$h['status_ke']])   ? $status_labels[$h['status_ke']]   : ($h['status_ke'] ?: '-');
							$headline = 'Status berubah: <span class="faint">' . html_escape($dari_label) . '</span> → <strong>' . html_escape($ke_label) . '</strong>';
						} elseif ($evt === 'KONTAK') {
							$headline = 'Upaya menghubungi kandidat';
						} elseif ($evt === 'DOKUMEN') {
							$headline = 'Pembaruan dokumen';
						} else {
							$headline = html_escape(str_replace('_', ' ', ucfirst(strtolower($evt))));
						}

						/* -- Format waktu ramah -- */
						$waktu_raw = $h['waktu'];
						$waktu_fmt = '';
						$waktu_relative = '';
						if ($waktu_raw) {
							$ts = strtotime(substr($waktu_raw, 0, 19));
							$waktu_fmt = date('d M Y, H:i', $ts);
							$selisih = time() - $ts;
							if ($selisih >= 0 && $selisih < 3600) {
								$waktu_relative = max(1, (int) floor($selisih / 60)) . ' menit lalu';
							} elseif ($selisih >= 0 && $selisih < 86400) {
								$waktu_relative = (int) floor($selisih / 3600) . ' jam lalu';
							} elseif ($selisih >= 0 && $selisih < 86400 * 30) {
								$waktu_relative = (int) floor($selisih / 86400) . ' hari lalu';
							}
						}
					?>
						<div class="audit-item">
							<div class="audit-dot <?= $dot_class ?>"><?= $icon ?></div>
							<div class="audit-card">
								<div class="audit-headline"><?= $headline ?></div>
								<?php if ($desc_display !== ''): ?>
									<div class="audit-desc">
										<?= html_escape($desc_display) ?>
										<?php if ($catatan_user !== '' && $catatan_user !== '-'): ?>
											<div style="margin-top:4px; font-style:italic; color:var(--text-muted)">
												💬 <?= html_escape($catatan_user) ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<div class="audit-meta">
									<span title="<?= html_escape($waktu_fmt) ?>">
										<?php if ($waktu_relative !== ''): ?>
											🕐 <?= $waktu_relative ?> <span class="sep">·</span> <?= $waktu_fmt ?>
										<?php else: ?>
											🕐 <?= $waktu_fmt ?>
										<?php endif; ?>
									</span>
									<span class="sep">·</span>
									<span>👤 <?= html_escape($h['oleh_nama'] ?: 'Sistem') ?></span>
									<?php if ($h['tahap_asal'] && $evt === 'STAGE_CHANGE'): ?>
										<span class="sep">·</span>
										<span class="faint">dari: <?= html_escape($h['tahap_asal']) ?></span>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div style="padding:28px 16px; text-align:center">
					<div style="font-size:28px; margin-bottom:6px; opacity:.35">📋</div>
					<div class="muted" style="font-size:13px">Belum ada catatan riwayat proses seleksi untuk kandidat ini.</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- ================= JAVASCRIPT TAB SWITCHER ================= -->
<script>
function switchTab(tabId, btnElem) {
	var panes = document.querySelectorAll('.profile-tab-pane');
	for (var i = 0; i < panes.length; i++) {
		panes[i].classList.remove('active');
	}

	var buttons = document.querySelectorAll('.profile-tab-btn');
	for (var j = 0; j < buttons.length; j++) {
		buttons[j].classList.remove('active');
	}

	var target = document.getElementById(tabId);
	if (target) {
		target.classList.add('active');
	}
	if (btnElem) {
		btnElem.classList.add('active');
	}
}
</script>

<!-- ================= MODAL DIALOG POPUP GENERATE LINK ONBOARDING ================= -->
<style>
	#dlg-onboarding-link {
		border: 1px solid var(--border);
		border-radius: 12px;
		padding: 0;
		max-width: 520px;
		width: 92%;
		background: var(--surface);
		color: var(--text);
		box-shadow: 0 20px 48px rgba(0, 0, 0, 0.28);
		overflow: hidden;
	}
	#dlg-onboarding-link[open] {
		display: flex;
		flex-direction: column;
	}
	#dlg-onboarding-link::backdrop {
		background: rgba(12, 18, 14, 0.55);
		backdrop-filter: blur(3px);
	}
	.ob-modal-header {
		padding: 16px 20px;
		border-bottom: 1px solid var(--border);
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--surface-2);
	}
	.ob-modal-body {
		padding: 20px;
	}
	.ob-modal-footer {
		padding: 12px 20px;
		border-top: 1px solid var(--border);
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--surface-2);
	}
</style>

<dialog id="dlg-onboarding-link">
	<div class="ob-modal-header">
		<div>
			<h3 style="margin:0; font-size:15px; font-weight:700">Tautan Formulir Pelamar</h3>
			<div class="muted" id="ob-modal-subtitle" style="font-size:12px">Kandidat Ratu Pertiwi Group</div>
		</div>
		<button type="button" onclick="closeOnboardingModal(true)" class="btn btn-sm btn-ghost" style="padding:4px 8px; font-size:16px; line-height:1" title="Tutup">&times;</button>
	</div>

	<div class="ob-modal-body">
		<!-- State Loading -->
		<div id="ob-state-loading" style="display:none; text-align:center; padding:30px 10px">
			<div id="ob-loading-text" style="font-weight:600; font-size:13.5px">Memeriksa tautan formulir...</div>
			<div class="muted" style="font-size:12px; margin-top:4px">Menghubungkan ke server RPG</div>
		</div>

		<!-- State Sukses -->
		<div id="ob-state-success" style="display:none">
			<div id="ob-alert-box" style="background:var(--accent-soft); color:var(--accent-ink); padding:10px 12px; border-radius:6px; font-size:12.5px; margin-bottom:14px">
				<span id="ob-success-msg" style="font-weight:600">Tautan formulir pelamar aktif ditemukan.</span>
			</div>

			<div style="margin-bottom:12px">
				<label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:4px">URL Formulir Pelamar</label>
				<div style="display:flex; gap:6px">
					<input type="text" id="ob-input-url" readonly style="flex:1; font-size:12px; font-family:monospace; padding:8px 10px; background:var(--surface-2); border:1px solid var(--border); border-radius:6px" onclick="this.select()">
					<button type="button" id="ob-btn-copy" onclick="copyObUrl()" class="btn btn-sm btn-primary" style="white-space:nowrap; font-size:12px; font-weight:600">
						Salin
					</button>
				</div>
				<div id="ob-copy-feedback" style="display:none; color:var(--good); font-size:11.5px; margin-top:4px; font-weight:600">Berhasil disalin ke clipboard!</div>
			</div>

			<div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px">
				<a id="ob-btn-wa" href="#" target="_blank" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; color:var(--good); font-size:12.5px; font-weight:600; text-decoration:none; border:1px solid var(--border)">
					Bagikan ke WhatsApp
				</a>
				<a id="ob-btn-open" href="#" target="_blank" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; text-decoration:none; border:1px solid var(--border)">
					Buka Halaman Form &rarr;
				</a>
			</div>
		</div>
	</div>

	<div class="ob-modal-footer">
		<div>
			<button type="button" id="ob-btn-regen" onclick="submitGenerateOnboarding(true)" class="btn btn-sm btn-ghost" style="display:none; color:var(--text-muted); font-size:12px" title="Cabut link lama dan buat link baru">
				Buat Ulang Link Baru
			</button>
		</div>
		<div style="display:flex; gap:8px">
			<button type="button" onclick="closeOnboardingModal(true)" class="btn btn-sm btn-ghost">Tutup</button>
		</div>
	</div>
</dialog>

<script>
var currentObIdLamaran = null;
var isGeneratingOnboarding = false;

function openOnboardingModal(event, idLamaran, namaKandidat) {
	if (event) event.preventDefault();
	currentObIdLamaran = idLamaran;

	var modal = document.getElementById('dlg-onboarding-link');
	var subtitle = document.getElementById('ob-modal-subtitle');
	if (subtitle) {
		subtitle.innerText = 'Kandidat: ' + (namaKandidat || 'Pelamar RPG');
	}

	document.getElementById('ob-copy-feedback').style.display = 'none';
	submitGenerateOnboarding(false);

	if (modal && typeof modal.showModal === 'function') {
		modal.showModal();
	}
}

function closeOnboardingModal(reloadIfUpdated) {
	var modal = document.getElementById('dlg-onboarding-link');
	if (modal) modal.close();
	if (reloadIfUpdated && isGeneratingOnboarding) {
		window.location.reload();
	}
}

function submitGenerateOnboarding(forceNew) {
	if (!currentObIdLamaran) return;

	var stateLoading = document.getElementById('ob-state-loading');
	var stateSuccess = document.getElementById('ob-state-success');
	var loadingText = document.getElementById('ob-loading-text');

	stateLoading.style.display = 'block';
	stateSuccess.style.display = 'none';
	loadingText.innerText = forceNew ? 'Membuat tautan baru...' : 'Memeriksa tautan aktif...';

	var url = '<?= site_url("candidates/generate_onboarding_link") ?>/' + currentObIdLamaran + '?format=json';
	if (forceNew) {
		url += '&force_new=1';
	}

	fetch(url, {
		method: 'GET',
		headers: { 'X-Requested-With': 'XMLHttpRequest' }
	})
	.then(function(res) {
		if (!res.ok) throw new Error('Terjadi kendala pada server (' + res.status + ')');
		return res.json();
	})
	.then(function(data) {
		stateLoading.style.display = 'none';
		if (!data.success) {
			showToast('Gagal: ' + (data.message || 'Tidak dapat memproses tautan'), 'err', 6000);
			return;
		}

		stateSuccess.style.display = 'block';
		document.getElementById('ob-input-url').value = data.onboarding_url;
		document.getElementById('ob-success-msg').innerText = data.message || 'Tautan aktif tersedia.';

		var btnWa = document.getElementById('ob-btn-wa');
		if (data.wa_link) {
			btnWa.href = data.wa_link;
			btnWa.style.display = 'inline-flex';
		} else {
			btnWa.style.display = 'none';
		}

		var btnOpen = document.getElementById('ob-btn-open');
		btnOpen.href = data.onboarding_url;

		var btnRegen = document.getElementById('ob-btn-regen');
		btnRegen.style.display = data.is_existing ? 'inline-block' : 'none';

		if (forceNew) {
			isGeneratingOnboarding = true;
		}
	})
	.catch(function(err) {
		stateLoading.style.display = 'none';
		showToast('Gagal mengambil tautan formulir: ' + err.message, 'err', 6000);
	});
}

function copyObUrl() {
	var input = document.getElementById('ob-input-url');
	if (!input) return;
	input.select();
	input.setSelectionRange(0, 99999);
	var feedback = document.getElementById('ob-copy-feedback');

	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(input.value).then(function() {
			if (feedback) feedback.style.display = 'block';
		});
	} else {
		document.execCommand('copy');
		if (feedback) feedback.style.display = 'block';
	}
}
</script>

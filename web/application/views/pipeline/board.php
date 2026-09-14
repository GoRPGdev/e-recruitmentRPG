<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Agregasi statistik kandidat untuk pipeline toolbar
$total_kandidat    = 0;
$total_in_progress = 0;
$total_hired       = 0;
$total_overdue     = 0;

foreach ($stages as $s) {
	foreach ($s['cards'] as $c) {
		$total_kandidat++;
		if ($c['status_global'] === 'In_Progress') {
			$total_in_progress++;
		} elseif ($c['status_global'] === 'Hired') {
			$total_hired++;
		}
		if ((int) $c['hari_di_tahap'] > 7) {
			$total_overdue++;
		}
	}
}
?>

<style>
/* CSS Modernisasi Pipeline, Kolom, Catatan, & Modal */
.pipeline-stat-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 12px;
	padding: 16px 20px;
	display: flex;
	flex-direction: column;
	gap: 6px;
	transition: all .18s cubic-bezier(0.16, 1, 0.3, 1);
	position: relative;
	overflow: hidden;
}
.pipeline-stat-card::before {
	content: "";
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 3px;
	background: transparent;
	transition: background .18s ease;
}
.pipeline-stat-card:hover {
	border-color: var(--accent);
	box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
	transform: translateY(-1px);
}
.pipeline-stat-card:hover::before {
	background: var(--accent);
}
.stage-section {
	margin-top: 20px;
	border: 1px solid var(--border);
	border-radius: 12px;
	background: var(--surface);
	box-shadow: 0 2px 8px rgba(0,0,0,0.03);
	overflow: hidden;
	transition: border-color .15s ease;
}
.stage-section:hover {
	border-color: var(--border-strong);
}
.stage-header-bar {
	background: var(--surface-2);
	border-bottom: 1px solid var(--border);
	padding: 13px 20px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap: 12px;
	cursor: pointer;
	list-style: none;
	user-select: none;
	transition: background-color .15s ease;
}
.stage-header-bar::-webkit-details-marker {
	display: none;
}
.stage-header-bar:hover {
	background: var(--surface);
}
details.stage-section:not([open]) .stage-header-bar {
	border-bottom: none;
}
.stage-chevron-icon {
	width: 18px;
	height: 18px;
	color: var(--text-muted);
	transition: transform .2s ease;
	flex: none;
}
details.stage-section:not([open]) .stage-chevron-icon {
	transform: rotate(-90deg);
}
.candidate-row {
	border-bottom: 1px solid var(--border);
	transition: background-color .15s ease;
}
.candidate-row:hover {
	background-color: var(--surface-2);
}
.candidate-row:last-child {
	border-bottom: none;
}
.candidate-row td {
	vertical-align: top;
}

/* Card Catatan Tahap */
.pipeline-note-card {
	background: var(--surface-2);
	border: 1px solid var(--border);
	border-left: 3px solid var(--accent);
	border-radius: 8px;
	padding: 9px 12px;
	margin-bottom: 6px;
	position: relative;
	transition: border-color .15s ease, background-color .15s ease;
}
.pipeline-note-card:hover {
	border-color: var(--accent);
	background: var(--surface);
	box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
	transform: translateY(-1px);
}
.pipeline-note-card.empty {
	background: transparent;
	border: 1px dashed var(--border);
	border-left: 1px dashed var(--border);
	padding: 8px 12px;
	color: var(--text-faint);
	font-style: italic;
	font-size: 11.5px;
	border-radius: 8px;
}
.pipeline-note-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 6px;
	margin-bottom: 4px;
	flex-wrap: wrap;
}
.pipeline-note-content {
	font-size: 12px;
	color: var(--text);
	line-height: 1.45;
	word-break: break-word;
	white-space: pre-wrap;
}

/* Avatar Inisial Pelamar */
.candidate-avatar {
	width: 34px;
	height: 34px;
	border-radius: 10px;
	background: var(--accent-soft);
	color: var(--accent-ink);
	border: 1px solid var(--border);
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-weight: 700;
	font-size: 12px;
	letter-spacing: -0.02em;
	flex: none;
	box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

/* Micro-Badge Kandidat (Sumber & Status Form Pelamar) */
.pipe-badge {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 2.5px 8px;
	border-radius: 6px;
	font-family: "IBM Plex Sans", system-ui, sans-serif;
	font-size: 11px;
	line-height: 1.35;
	font-weight: 600;
	white-space: nowrap;
	box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

/* Floating Menu Kandidat (Anti-cut off & modern UI) */
.pipeline-floating-menu {
	position: fixed;
	z-index: 99999;
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 14px;
	box-shadow: 0 20px 48px -4px rgba(0, 0, 0, 0.22), 0 8px 24px -2px rgba(0, 0, 0, 0.1);
	min-width: 260px;
	max-width: 290px;
	padding: 8px;
	backdrop-filter: blur(12px);
	animation: pipeMenuFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes pipeMenuFadeIn {
	from { opacity: 0; transform: scale(0.95) translateY(-5px); }
	to { opacity: 1; transform: scale(1) translateY(0); }
}
.pipe-menu-header {
	padding: 6px 10px 8px;
	font-size: 10.5px;
	font-weight: 700;
	color: var(--text-faint);
	text-transform: uppercase;
	letter-spacing: 0.06em;
	border-bottom: 1px solid var(--border);
	margin-bottom: 6px;
}
.pipe-menu-item {
	display: flex;
	align-items: center;
	gap: 11px;
	padding: 9px 11px;
	border-radius: 9px;
	text-decoration: none;
	color: var(--text);
	transition: all 0.15s ease;
}
.pipe-menu-item:hover {
	background: var(--surface-2);
	transform: translateX(2px);
}
.pipe-menu-icon {
	width: 30px;
	height: 30px;
	border-radius: 8px;
	display: grid;
	place-items: center;
	flex: none;
	transition: transform 0.15s ease;
}
.pipe-menu-item:hover .pipe-menu-icon {
	transform: scale(1.06);
}
.pipe-kebab-btn {
	width: 28px;
	height: 28px;
	border-radius: 7px;
	border: 1px solid var(--border);
	background: var(--surface);
	color: var(--text-muted);
	font-size: 16px;
	font-weight: 700;
	line-height: 1;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
	padding: 0;
	box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.pipe-kebab-btn:hover, .pipe-kebab-btn.active {
	background: var(--surface-2);
	border-color: var(--accent);
	color: var(--accent);
	transform: scale(1.05);
	box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

/* Tombol Aksi & Pill */
.icon-pill {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 11.5px;
	font-weight: 600;
	padding: 6px 11px;
	border-radius: 7px;
	border: 1px solid var(--border);
	background: var(--surface);
	color: var(--text);
	text-decoration: none;
	cursor: pointer;
	transition: all .15s cubic-bezier(0.16, 1, 0.3, 1);
	line-height: 1.2;
	box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.icon-pill:hover {
	border-color: var(--accent);
	color: var(--accent);
	background: var(--surface-2);
	transform: translateY(-1px);
	box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.btn-advance-action {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 6px;
	padding: 6px 14px;
	font-size: 11.5px;
	font-weight: 600;
	border-radius: 7px;
	border: 1px solid var(--accent);
	background: var(--accent);
	color: var(--accent-contrast);
	cursor: pointer;
	transition: all .15s cubic-bezier(0.16, 1, 0.3, 1);
	text-decoration: none;
	box-shadow: 0 1px 3px rgba(0,0,0,0.08);
	white-space: nowrap;
	line-height: 1.2;
}
.btn-advance-action:hover {
	background: var(--accent-ink);
	border-color: var(--accent-ink);
	color: var(--accent-contrast);
	transform: translateY(-1px);
	box-shadow: 0 3px 8px rgba(0,0,0,0.12);
}
.btn-advance-action:active {
	transform: scale(0.98);
}
.btn-advance-action.has-decision {
	background: var(--surface-2);
	color: var(--text);
	border: 1px solid var(--border-strong);
	box-shadow: none;
}
.btn-advance-action.has-decision:hover {
	background: var(--surface);
	border-color: var(--accent);
	color: var(--accent);
}

/* Modal Dialog Standar RPG */
.rpg-modal {
	border: 1px solid var(--border);
	border-radius: 16px;
	padding: 0;
	max-width: 540px;
	width: 94%;
	max-height: 88vh;
	background: var(--surface);
	color: var(--text);
	box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
	overflow: hidden;
}
.rpg-modal[open] {
	display: flex !important;
	flex-direction: column !important;
	position: fixed !important;
	top: 50% !important;
	left: 50% !important;
	transform: translate(-50%, -50%) !important;
	margin: 0 auto !important;
	z-index: 100000 !important;
}
dialog#dlg-notes-history, .rpg-modal#dlg-notes-history {
	border: 1px solid var(--border);
	border-radius: 14px;
	padding: 0;
	max-width: 620px;
	width: 95%;
	max-height: 85vh;
	background: var(--surface);
	color: var(--text);
	box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
	overflow: hidden;
}
.rpg-modal::backdrop {
	background: rgba(12, 18, 14, 0.6);
	backdrop-filter: blur(4px);
}
.rpg-modal-header {
	padding: 16px 22px;
	border-bottom: 1px solid var(--border);
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: var(--surface-2);
	flex: none;
}
.rpg-modal-body {
	padding: 22px;
	overflow-y: auto;
	flex: 1;
	min-height: 0;
}
.rpg-modal-body::-webkit-scrollbar {
	width: 6px;
}
.rpg-modal-body::-webkit-scrollbar-thumb {
	background: var(--border);
	border-radius: 4px;
}
.rpg-modal-footer {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
	padding: 14px 22px;
	border-top: 1px solid var(--border);
	background: var(--surface-2);
	flex: none;
}
.rpg-modal-close {
	background: none;
	border: none;
	font-size: 20px;
	line-height: 1;
	color: var(--text-muted);
	cursor: pointer;
	padding: 2px 6px;
	border-radius: 4px;
}
.rpg-modal-close:hover {
	color: var(--text);
	background: var(--border);
}
</style>

<div style="margin-bottom:24px">
	<!-- HEADER UTAMA PIPELINE -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:16px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px">
				<a href="<?= site_url('requisitions') ?>" style="font-size:12px; text-decoration:none; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px">
					<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
					<span>Daftar Requisition</span>
				</a>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Papan Seleksi Rekrutmen</span>
			</div>

			<h1 style="margin:0 0 8px; font-size:24px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				<?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?> &mdash; <?= html_escape($req['nama_posisi']) ?>
			</h1>

			<div class="muted" style="font-size:13px; display:flex; gap:12px; flex-wrap:wrap; align-items:center">
				<span>Departemen: <strong><?= html_escape($req['departemen'] ?: '-') ?></strong></span>
				<span>&bull;</span>
				<span>Kebutuhan: <strong><?= (int) $req['jumlah_dibutuhkan'] ?></strong> orang</span>
				<span>&bull;</span>
				<span>Terpenuhi: <strong style="color:var(--good)"><?= (int) $req['jumlah_terpenuhi'] ?></strong> orang</span>
				<span>&bull;</span>
				<span class="tag <?= in_array($req['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : 'warn' ?>" style="font-size:11px">
					<?= html_escape($req['status_req']) ?>
				</span>
			</div>
		</div>

		<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
			<?php if (!empty($can_aksi) && in_array($req['status_req'], array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian'))): ?>
			<a href="<?= site_url('manual/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-weight:600">
				<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>Tambah Pelamar</span>
			</a>
			<?php endif; ?>
			<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px">
				<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
				<span>Detail MPR</span>
			</a>
		</div>
	</div>

	<!-- RINGKASAN METRIK OPERASIONAL -->
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:22px">
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Total Kandidat Aktif</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:var(--text); line-height:1.2"><?= $total_kandidat ?></div>
			<div class="muted" style="font-size:11.5px">Berada di seluruh alur seleksi</div>
		</div>
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Sedang Berjalan</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:var(--info); line-height:1.2"><?= $total_in_progress ?></div>
			<div class="muted" style="font-size:11.5px">Status In_Progress aktif</div>
		</div>
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Diterima / Hired</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:var(--good); line-height:1.2"><?= $total_hired ?></div>
			<div class="muted" style="font-size:11.5px">Lolos tahapan seleksi</div>
		</div>
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Tertahan (>7 Hari)</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:<?= $total_overdue > 0 ? 'var(--crit)' : 'var(--text-muted)' ?>; line-height:1.2">
				<?= $total_overdue ?>
			</div>
			<div class="muted" style="font-size:11.5px">Perlu perhatian &amp; tindak lanjut</div>
		</div>
	</div>

	<!-- TOOLBAR FILTER & NAVIGASI TAHAP -->
	<div style="display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap; background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:12px 16px; box-shadow:0 1px 3px rgba(0,0,0,0.02)">
		<div style="display:flex; gap:14px; align-items:center; flex:1; min-width:280px; flex-wrap:wrap">
			<div style="position:relative; width:100%; max-width:340px">
				<input type="text" id="pipeline-search" placeholder="Cari nama kandidat, nomor WA, status..." oninput="filterPipelineRows()"
					style="width:100%; padding:8px 12px 8px 34px; font-size:12.5px; border-radius:8px; margin:0; border:1px solid var(--border); background:var(--surface-2); transition:border-color .15s ease">
				<svg style="position:absolute; left:11px; top:50%; transform:translateY(-50%); width:14px; height:14px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
				</svg>
			</div>
			<label style="display:inline-flex; align-items:center; gap:7px; font-size:12.5px; font-weight:500; margin:0; cursor:pointer; user-select:none; white-space:nowrap; color:var(--text-muted)">
				<input type="checkbox" id="filter-overdue-only" onchange="filterPipelineRows()" style="margin:0; width:15px; height:15px; accent-color:var(--crit)">
				<span>Hanya tertahan (>7 hari)</span>
			</label>
		</div>

		<!-- STAGE JUMP & TOGGLE BUTTONS -->
		<div style="display:flex; gap:6px; align-items:center; overflow-x:auto; padding:2px 0; max-width:100%">
			<button type="button" class="btn btn-sm btn-ghost" onclick="toggleAllStages()" id="btn-toggle-all-stages" style="padding:5px 10px; font-size:11.5px; font-weight:600; white-space:nowrap; border-radius:7px; border:1px solid var(--border); background:var(--surface); display:inline-flex; align-items:center; gap:5px" title="Buka atau lipat seluruh section tahap">
				<svg style="width:12px; height:12px; color:var(--text-muted)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
				<span id="btn-toggle-all-label">Lipat Semua Tahap</span>
			</button>
			<span class="faint" style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; white-space:nowrap; margin-right:4px">Lompat:</span>
			<?php foreach ($stages as $urut => $s): ?>
				<a href="#stage-sec-<?= (int) $urut ?>" class="btn btn-sm btn-ghost" style="padding:5px 11px; font-size:12px; font-weight:600; white-space:nowrap; border-radius:7px; display:inline-flex; align-items:center; gap:6px; border:1px solid var(--border); background:var(--surface)">
					<span><?= html_escape($s['nama']) ?></span>
					<?php if (! empty($s['is_sisipan'])): ?>
		<span style="font-size:9.5px; background:rgba(217, 119, 6, 0.15); color:#d97706; border:1px solid rgba(217, 119, 6, 0.35); padding:0 5px; border-radius:4px; font-weight:700">Sisipan</span>
	<?php endif; ?>
	<span style="background:var(--surface-2); border:1px solid var(--border); padding:1px 6px; border-radius:10px; font-size:10px; font-weight:700; color:var(--text)">
						<?= count($s['cards']) ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( ! $stages): ?>
		<div style="padding:48px 20px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:10px">
			<svg style="width:38px; height:38px; margin:0 auto 12px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
			<h3 style="margin:0 0 6px; font-size:15px; font-weight:700">Belum ada kandidat di alur pipeline lowongan ini</h3>
			<p class="muted" style="font-size:13px; margin:0 0 16px">Kandidat yang melamar via form publik atau ditambahkan manual akan muncul di sini.</p>
			<a href="<?= site_url('manual') ?>" class="btn btn-sm btn-primary">+ Tambah Pelamar Manual</a>
		</div>
	<?php else: ?>
		<!-- ================= DAFTAR TAHAP SELEKSI ================= -->
		<div id="pipeline-table-container">
			<?php
			// Tentukan urutan tahap pengisian form pelamar di alur pipeline
			$form_stage_urutan = NULL;
			foreach ($stages as $u => $stg) {
				$stg_tipe = strtoupper(trim($stg['tipe'] ?? ''));
				$stg_kode = strtoupper(trim($stg['kode'] ?? ''));
				$stg_nama = strtoupper(trim($stg['nama'] ?? ''));
				if ($stg_tipe === 'FORM' || $stg_kode === 'FORM_PELAMAR' || stripos($stg_nama, 'form') !== FALSE) {
					$form_stage_urutan = (int) $u;
					break;
				}
			}
			if ($form_stage_urutan === NULL) {
				foreach ($stages as $u => $stg) {
					$stg_tipe = strtoupper(trim($stg['tipe'] ?? ''));
					$stg_nama = strtoupper(trim($stg['nama'] ?? ''));
					if ($stg_tipe === 'ONBOARD' || stripos($stg_nama, 'onboard') !== FALSE) {
						$form_stage_urutan = (int) $u;
						break;
					}
				}
			}
			?>
			<?php foreach ($stages as $urut => $s): ?>
			<?php
				$id_stage    = (int) $s['id_stage'];
				$total_cards = count($s['cards']);
			?>
			<details id="stage-sec-<?= (int) $urut ?>" class="stage-section" open>
				<!-- Header Bar Tahap (Bisa di-collapse & expand) -->
				<summary class="stage-header-bar" title="Klik untuk melipat / membuka tahap ini">
					<div style="display:flex; align-items:center; gap:10px">
						<h2 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
							<?= html_escape($s['nama']) ?>
							<?php if (! empty($s['is_sisipan'])): ?>
								<span class="tag warn" style="font-size:10px; font-weight:700; vertical-align:middle; margin-left:6px">Tahap Tambahan</span>
							<?php endif; ?>
						</h2>
						<span class="tag info" style="font-size:10.5px; text-transform:uppercase">
							<?= html_escape($s['tipe']) ?>
						</span>
						<span class="muted mono" style="font-size:12px">
							(<?= $total_cards ?> kandidat)
						</span>
					</div>
					<div style="display:flex; align-items:center; gap:8px">
						<span class="faint mono" style="font-size:11px; font-weight:500">Klik untuk tutup/buka</span>
						<svg class="stage-chevron-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
						</svg>
					</div>
				</summary>

				<?php if ( ! $total_cards): ?>
					<div style="padding:24px 16px; text-align:center; color:var(--text-faint); font-size:13px; font-style:italic">
						Tidak ada kandidat pada tahap <?= html_escape($s['nama']) ?> saat ini.
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="width:100%; overflow-x:auto">
						<table style="width:100%; border-collapse:collapse; margin:0; table-layout:fixed">
							<thead>
								<tr style="background:var(--surface); font-size:11.5px; color:var(--text-muted); border-bottom:1px solid var(--border)">
									<th style="width:38px; padding:10px 6px; text-align:center">#</th>
									<th style="width:44%; padding:10px 14px; text-align:left">Kandidat &amp; Kontak</th>
									<th style="width:53%; padding:10px 14px; text-align:left"><?= $can_aksi ? 'Evaluasi &amp; Aksi Tahap' : 'Evaluasi &amp; Catatan Tahap' ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$no = 1;
								$is_form_stage = (strtoupper(trim($s['tipe'] ?? '')) === 'FORM'
									|| strtoupper(trim($s['tipe'] ?? '')) === 'ONBOARD'
									|| stripos($s['nama'] ?? '', 'form') !== FALSE
									|| stripos($s['nama'] ?? '', 'onboard') !== FALSE);
								foreach ($s['cards'] as $c):
									$id_app_stage = (int) $c['id_app_stage'];
									$id_lamaran   = (int) $c['id_lamaran'];
									$hari         = (int) $c['hari_di_tahap'];

									$cur_ivs   = isset($interviews[$id_app_stage]) ? $interviews[$id_app_stage] : array();
									$latest_iv = ! empty($cur_ivs) ? $cur_ivs[0] : NULL;

									$cur_psi    = isset($psikotes[$id_app_stage]) ? $psikotes[$id_app_stage] : array();
									$latest_psi = ! empty($cur_psi) ? $cur_psi[0] : NULL;

									$cur_off = isset($offers[$id_lamaran]) ? $offers[$id_lamaran] : NULL;

									$has_catatan = ! empty($c['catatan']);
									$has_remark  = ! empty($c['label_remark']);
								?>
								<tr class="candidate-row" data-overdue="<?= $hari > 7 ? '1' : '0' ?>"
									data-search="<?= strtolower(html_escape($c['nama_lengkap'] . ' ' . $c['no_wa_normal'] . ' ' . $c['status_global'] . ' ' . ($c['catatan'] ?? '') . ' ' . ($c['label_remark'] ?? '') . ' ' . ($latest_iv['hasil'] ?? '') . ' ' . ($latest_psi['hasil'] ?? ''))) ?>">

									<td style="padding:12px 6px; text-align:center" class="muted mono"><?= $no++ ?></td>

									<!-- Kolom 1: Kandidat & Kontak (Layout 2 Sisi Seimbang) -->
									<td style="padding:12px 14px; word-break:break-word">
										<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px">
											<!-- Sub-kiri: Nama, ID, Status, Sumber, & WhatsApp -->
											<div style="flex:1; min-width:0">
												<div style="display:flex; align-items:center; gap:6px; margin-bottom:3px">
													<?php
													$words = explode(' ', trim($c['nama_lengkap']));
													$initials = '';
													foreach ($words as $w) {
														if ($w !== '') {
															$initials .= mb_strtoupper(mb_substr($w, 0, 1));
															if (strlen($initials) >= 2) break;
														}
													}
													if ($initials === '') $initials = 'P';
													?>
													<span class="candidate-avatar" title="<?= html_escape($c['nama_lengkap']) ?>"><?= html_escape($initials) ?></span>
													<a href="<?= site_url('candidates/detail/' . $id_lamaran) ?>" title="Buka Profil Pelamar" style="font-weight:700; font-size:13.5px; line-height:1.3; color:var(--text); text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
														<?= html_escape($c['nama_lengkap']) ?>
													</a>
												</div>
												<div style="display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-bottom:5px">
													<span class="mono faint" style="font-size:11px">#<?= $id_lamaran ?></span>
																																																					</span>
													<?php if (! empty($c['is_sisipan'])): ?>
														<span class="tag warn" style="font-size:10px; padding:1px 6px; font-weight:700">Tahap Sisipan</span>
													<?php endif; ?>
												</div>
												<div style="font-size:12px; margin-top:2px" class="mono wa-row">
													<?php if ($c['no_wa_normal']): ?>
														<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['no_wa_normal']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--good); text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600" title="Chat WhatsApp Pelamar">
															<svg style="width:13px; height:13px; flex:none" fill="currentColor" viewBox="0 0 24 24">
																<path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.586-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/>
															</svg>
															<span><?= html_escape($c['no_wa_normal']) ?></span>
														</a>
													<?php else: ?>
														<span class="faint">-</span>
													<?php endif; ?>
												</div>
											</div>

											<!-- Sub-kanan: Durasi Tahap, Menu Kebab, & Status Form Pelamar -->
											<div style="flex:none; display:flex; flex-direction:column; align-items:flex-end; gap:5px">
												<div style="display:flex; align-items:center; gap:6px">
													<div style="position:relative; flex:none">
																											</span>
													<div style="position:relative; flex:none">
														<?php
														$has_form_done = ! empty($c['form_dipakai_pada']);
														$can_print_form = $has_form_done
															|| $is_form_stage
															|| ($form_stage_urutan !== NULL && (int) ($s['urutan'] ?? $urut ?? $c['urutan'] ?? 0) >= $form_stage_urutan);
														?>
																												<button type="button" class="pipe-kebab-btn" onclick="toggleCandidateMenu(event, <?= (int) $id_lamaran ?>, '<?= html_escape(addslashes($c['nama_lengkap'])) ?>', <?= $is_form_stage ? 'true' : 'false' ?>, <?= $can_print_form ? 'true' : 'false' ?>)" title="Opsi Kandidat">&#8942;</button>
													</div>
												</div>

												<?php
												$has_form_done = ! empty($c['form_dipakai_pada']);
												$has_form_tok  = ! empty($c['form_token']);
												$form_is_rev   = ! empty($c['form_revoked']);

												if ($has_form_done) {
													$tgl_form = date('d/m/Y', strtotime($c['form_dipakai_pada']));
													$form_label = 'Form Terisi';
													$form_title = 'Formulir Pelamar resmi RPG telah diisi lengkap pada ' . $tgl_form;
													$form_style = 'background:var(--good-soft); color:var(--good); border:1px solid rgba(47,125,79,0.25)';
													$form_icon  = '<svg style="width:10px; height:10px; flex:none" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
												} elseif ($has_form_tok && ! $form_is_rev) {
													$form_label = 'Menunggu Form';
													$form_title = 'Tautan formulir pelamar sudah digenerate / aktif, menunggu pengisian oleh kandidat';
													$form_style = 'background:var(--warn-soft); color:var(--warn); border:1px solid rgba(143,100,16,0.25)';
													$form_icon  = '<svg style="width:10px; height:10px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
												} else {
													$form_label = 'Belum Ada Form';
													$form_title = 'Kandidat belum pernah digenerate tautan formulir pelamar (atau tautan dicabut)';
													$form_style = 'background:var(--surface-2); color:var(--text-faint); border:1px dashed var(--border)';
													$form_icon  = '<span style="width:5.5px; height:5.5px; border-radius:50%; background:var(--text-faint); display:inline-block"></span>';
												}
												?>
												<span class="pipe-badge" style="<?= $form_style ?>" title="<?= html_escape($form_title) ?>">
													<?= $form_icon ?>
													<span><?= html_escape($form_label) ?></span>
												</span>
											</div>
										</div>
									</td>

									<!-- Kolom 2: Evaluasi & Aksi Tahap Terpadu (2 Sisi Seimbang) -->
									<td style="padding:12px 14px">
										<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:14px">
											<!-- Sub-kiri: Card Evaluasi HR & Trigger Riwayat Lengkap -->
											<div style="flex:1; min-width:0">
												<?php
												$c_hist = isset($candidate_stage_history[$id_lamaran]) ? $candidate_stage_history[$id_lamaran] : array();
												$curr_catatan = trim($c['catatan'] ?? '');
												$curr_remark  = trim($c['label_remark'] ?? '');

												// Cari catatan terbaru dari tahap sebelumnya jika tahap saat ini belum ada catatan
												$prev_note_text  = '';
												$prev_note_stage = '';
												if (empty($curr_catatan)) {
													foreach (array_reverse($c_hist) as $h) {
														if ((int) $h['id_app_stage'] !== (int) $id_app_stage && ! empty($h['catatan'])) {
															$prev_note_text  = $h['catatan'];
															$prev_note_stage = $h['nama_tahap'];
															break;
														}
													}
												}
												?>
												<div class="pipeline-note-card" style="margin-bottom:0; cursor:pointer"
													onclick="openNotesHistoryModal(<?= (int) $id_lamaran ?>, <?= json_encode($c['nama_lengkap']) ?>)"
													title="Klik untuk melihat catatan lengkap seluruh tahapan kandidat ini">
													<div class="pipeline-note-header">
														<div style="display:flex; align-items:center; gap:5px; flex-wrap:wrap">
															<span style="font-size:10.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; display:inline-flex; align-items:center; gap:3px">
																<svg style="width:11px; height:11px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
																<span><?= ! empty($curr_catatan) ? 'Catatan HR (Tahap Ini)' : (! empty($prev_note_text) ? 'Catatan Terakhir (' . html_escape($prev_note_stage) . ')' : 'Catatan HR') ?></span>
															</span>
															<?php if ($has_remark): ?>
																<span class="tag accent" style="font-size:10px; padding:1px 6px">
																	<?= html_escape($c['label_remark']) ?>
																</span>
															<?php endif; ?>
														</div>
														<span style="font-size:10.5px; color:var(--accent); font-weight:600; display:inline-flex; align-items:center; gap:3px">
															<svg style="width:11px; height:11px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
															<span>Riwayat Lengkap &rarr;</span>
														</span>
													</div>
													<div class="pipeline-note-content" style="max-height:54px; overflow:hidden; text-overflow:ellipsis">
														<?php if (! empty($curr_catatan)): ?>
															<?= html_escape($curr_catatan) ?>
														<?php elseif (! empty($prev_note_text)): ?>
															<span style="color:var(--text-muted); font-style:italic">[Dari <?= html_escape($prev_note_stage) ?>]:</span> <?= html_escape($prev_note_text) ?>
														<?php elseif ($has_remark): ?>
															Keputusan: <?= html_escape($c['label_remark']) ?>
														<?php else: ?>
															<span class="muted" style="font-style:italic">Belum ada catatan pada tahap ini. Klik untuk riwayat lengkap.</span>
														<?php endif; ?>
													</div>
												</div>

												<!-- Ringkasan Kontekstual Evaluasi Khusus Tahap -->
												<?php if ($latest_iv && ! empty($latest_iv['hasil'])): ?>
													<div style="margin-top:6px; display:flex; align-items:center; gap:6px; font-size:11.5px">
														<span class="muted" style="font-size:10.5px">Hasil Wawancara:</span>
														<span class="tag <?= $latest_iv['hasil'] === 'Lulus' ? 'on' : ($latest_iv['hasil'] === 'Tidak_Lulus' ? 'off' : 'warn') ?>" style="font-size:10px; padding:1px 6px">
															<?= html_escape($latest_iv['hasil']) ?> <?= ! empty($latest_iv['skor']) ? '(' . (int) $latest_iv['skor'] . ')' : '' ?>
														</span>
													</div>
												<?php endif; ?>
												<?php if ($cur_off && ! empty($cur_off['status_offer'])): ?>
													<div style="margin-top:6px; display:flex; align-items:center; gap:6px; font-size:11.5px">
														<span class="muted" style="font-size:10.5px">Status Offering:</span>
														<span class="tag <?= $cur_off['status_offer'] === 'Accepted' ? 'on' : ($cur_off['status_offer'] === 'Declined' ? 'off' : 'info') ?>" style="font-size:10px; padding:1px 6px">
															<?= html_escape($cur_off['status_offer']) ?>
														</span>
													</div>
												<?php endif; ?>
											</div>

											<!-- Baris Tombol Aksi: Proses & Aksi Bertipe -->
											<?php if ($can_aksi): ?>
												<div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
													<!-- Tombol Proses Tunggal -->
													<button type="button" class="btn-advance-action <?= ($has_remark || $has_catatan) ? 'has-decision' : '' ?>"
														onclick='openAdvanceModal(<?= (int) $id_app_stage ?>, <?= (int) $id_stage ?>, <?= json_encode($c["nama_lengkap"]) ?>, <?= (int) ($c["id_remark"] ?? 0) ?>, <?= json_encode($c["catatan"] ?? "") ?>)'>
														<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
														<span><?= ($has_remark || $has_catatan) ? 'Ubah Proses' : 'Proses &rarr;' ?></span>
													</button>

													<button type="button" class="icon-pill" title="Sisipkan tahap seleksi ad-hoc untuk kandidat ini"
														onclick='openAdHocModal(<?= (int) $id_lamaran ?>, <?= json_encode($c["nama_lengkap"]) ?>, <?= (int) $id_stage ?>, <?= json_encode($s["nama"]) ?>)'>
														<svg style="width:12px; height:12px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
														<span>Sisip Tahap</span>
													</button>

													<button type="button" class="icon-pill" title="Lihat riwayat catatan &amp; evaluasi seluruh tahapan"
														onclick="openNotesHistoryModal(<?= (int) $id_lamaran ?>, <?= json_encode($c['nama_lengkap']) ?>)">
														<svg style="width:12px; height:12px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
														<span>Riwayat Catatan</span>
													</button>
												</div>
											<?php endif; ?>
										</div>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- ================= ARSIP KANDIDAT FINAL & TERMINAL ================= -->
	<div id="sec-final-candidates" style="margin-top:28px; border:1px solid var(--border); border-radius:10px; background:var(--surface); overflow:hidden">
		<details <?= ! empty($final_candidates) ? 'open' : '' ?> style="border:none; margin:0; padding:0">
			<summary style="background:var(--surface-2); border-bottom:1px solid var(--border); padding:14px 18px; display:flex; justify-content:space-between; align-items:center; cursor:pointer; list-style:none; user-select:none">
				<div style="display:flex; align-items:center; gap:10px">
					<span style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:6px; background:var(--surface); border:1px solid var(--border); color:var(--text-muted)">
						<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
					</span>
					<div>
						<span style="font-size:14.5px; font-weight:700; color:var(--text)">
							Arsip Pelamar Selesai &amp; Ditolak
						</span>
						<span class="muted" style="font-size:12.5px; margin-left:6px">
							(<?= count($final_candidates ?? array()) ?> kandidat status final)
						</span>
					</div>
				</div>
				<div style="display:flex; align-items:center; gap:8px">
					<span class="tag off" style="font-size:10.5px">Terminal State</span>
					<span class="muted" style="font-size:12px">&#9662; Buka / Tutup</span>
				</div>
			</summary>

			<div style="padding:0">
				<?php if (empty($final_candidates)): ?>
					<div style="padding:32px 16px; text-align:center; color:var(--text-muted); font-size:13px">
						Belum ada kandidat berstatus final (Ditolak, Mengundurkan Diri, atau Diterima) pada lowongan ini.
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="overflow-x:auto">
						<table style="width:100%; table-layout:fixed; font-size:13px; margin:0; border-collapse:collapse">
							<thead>
								<tr style="border-bottom:1px solid var(--border); background:var(--surface)">
									<th style="width:30%; text-align:left; padding:10px 14px">Kandidat &amp; Kontak</th>
									<th style="width:20%; text-align:left; padding:10px 12px">Tahap Terakhir</th>
									<th style="width:24%; text-align:left; padding:10px 12px">Alasan / Remark</th>
									<th style="width:14%; text-align:center; padding:10px 12px">Status Akhir</th>
									<th style="width:12%; text-align:right; padding:10px 14px">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($final_candidates as $fc): ?>
									<?php
									$fc_status = $fc['status_global'];
									$fc_badge = 'off';
									if (in_array($fc_status, array('Hired', 'Approved'))) {
										$fc_badge = 'on';
									} elseif ($fc_status === 'Withdrawn' || $fc_status === 'Offer_Declined') {
										$fc_badge = 'warn';
									}
									?>
									<tr style="border-bottom:1px solid var(--border)">
										<td style="padding:10px 14px; word-break:break-word">
											<a href="<?= site_url('candidates/detail/' . (int) $fc['id_lamaran']) ?>" style="font-weight:700; color:var(--text); text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
												<?= html_escape($fc['nama_lengkap']) ?>
											</a>
											<div class="muted mono" style="font-size:11.5px; margin-top:2px">
												<?= html_escape($fc['no_wa_normal'] ?: '-') ?>
											</div>
											<?php if ($fc['email']): ?>
												<div class="faint" style="font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
													<?= html_escape($fc['email']) ?>
												</div>
											<?php endif; ?>
										</td>
										<td style="padding:10px 12px; word-break:break-word">
											<span style="font-weight:600; color:var(--text)">
												<?= html_escape($fc['tahap_terakhir'] ?: '-') ?>
											</span>
											<div class="faint" style="font-size:11px; margin-top:2px">
												Tgl Lamar: <?= html_escape($fc['tanggal_lamar'] ? substr($fc['tanggal_lamar'], 0, 10) : '-') ?>
											</div>
										</td>
										<td style="padding:10px 12px; word-break:break-word">
											<?php if ($fc['label_remark_terakhir']): ?>
												<span style="color:var(--crit); font-weight:600; font-size:12px">
													<?= html_escape($fc['label_remark_terakhir']) ?>
												</span>
											<?php else: ?>
												<span class="muted faint" style="font-size:12px">-</span>
											<?php endif; ?>
										</td>
										<td style="padding:10px 12px; text-align:center">
											<span class="tag <?= $fc_badge ?>" style="font-size:11px">
												<?= html_escape($fc['status_global']) ?>
											</span>
										</td>
										<td style="padding:10px 14px; text-align:right; white-space:nowrap">
											<button type="button" class="btn btn-sm btn-ghost" style="padding:4px 9px; font-size:11.5px; margin-right:4px; display:inline-flex; align-items:center; gap:4px"
												onclick="openNotesHistoryModal(<?= (int) $fc['id_lamaran'] ?>, <?= json_encode($fc['nama_lengkap']) ?>)"
												title="Lihat riwayat catatan seluruh tahapan">
												<svg style="width:11px; height:11px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
												<span>Riwayat Catatan</span>
											</button>
											<a href="<?= site_url('candidates/detail/' . (int) $fc['id_lamaran']) ?>" class="btn btn-sm btn-ghost" style="padding:4px 8px; font-size:11.5px" title="Lihat Profil Pelamar">
												Profil
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</details>
	</div>
</div>

<!-- ================= FLOATING ACTION MENU KANDIDAT ================= -->
<div id="pipeline-floating-menu" class="pipeline-floating-menu" style="display:none">
	<div class="pipe-menu-header">Opsi Pelamar</div>
	<a id="pipe-menu-cv" href="#" class="pipe-menu-item">
		<span class="pipe-menu-icon" style="background:var(--accent-soft); color:var(--accent)">
			<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
		</span>
		<div style="flex:1">
			<div style="font-weight:600; font-size:12.5px; color:var(--text); line-height:1.2">Lihat CV</div>
			<div style="font-size:11px; color:var(--text-muted); margin-top:2px">Buka berkas dokumen CV pelamar</div>
		</div>
		<svg style="width:12px; height:12px; color:var(--text-faint); flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
	</a>
	<a id="pipe-menu-onboarding" href="javascript:void(0)" onclick="triggerCandidateOnboardingModal(event)" class="pipe-menu-item">
		<span class="pipe-menu-icon" style="background:var(--accent-soft); color:var(--accent)">
			<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
		</span>
		<div style="flex:1">
			<div style="font-weight:600; font-size:12.5px; color:var(--text); line-height:1.2">Salin / Kirim Link Form Pelamar</div>
			<div style="font-size:11px; color:var(--text-muted); margin-top:2px">Buka pop-up tautan &amp; kirim WhatsApp</div>
		</div>
		<svg style="width:12px; height:12px; color:var(--text-faint); flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
	</a>
	<a id="pipe-menu-form" href="#" target="_blank" class="pipe-menu-item">
		<span class="pipe-menu-icon" style="background:var(--info-soft, #e0f2fe); color:var(--info, #0284c7)">
			<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
		</span>
		<div style="flex:1">
			<div style="font-weight:600; font-size:12.5px; color:var(--text); line-height:1.2">Cetak Form Pelamar A-I</div>
			<div style="font-size:11px; color:var(--text-muted); margin-top:2px">Dokumen resmi aplikasi calon karyawan</div>
		</div>
		<svg style="width:12px; height:12px; color:var(--text-faint); flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
	</a>
</div>

<!-- ================= MODAL ADVANCE & CATATAN TAHAP LENGKAP ================= -->
<dialog id="dlg-advance" class="rpg-modal">
	<div class="rpg-modal-header">
		<div>
			<h3 id="dlg-adv-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Proses Tahap Seleksi
			</h3>
			<div id="dlg-adv-subtitle" class="muted" style="font-size:12px; margin-top:2px">Kandidat: -</div>
		</div>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-advance').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_app_stage" id="adv-id-app-stage">
		<div class="rpg-modal-body">
			<div style="margin-bottom:16px">
				<label for="adv-id-remark" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Pilih Keputusan / Remark <span style="color:var(--crit)">*</span>
				</label>
				<select name="id_remark" id="adv-id-remark" required style="width:100%; font-size:13px; padding:8px 10px; border-radius:7px">
					<!-- Diisi dinamis via openAdvanceModal -->
				</select>
				<div id="adv-remark-info" class="faint" style="font-size:11.5px; margin-top:4px">
					Pilih remark untuk menerapkan efek status alur (Lulus, Tidak Lulus, On Hold, dsb).
				</div>
			</div>

			<div>
				<label for="adv-catatan" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Catatan Evaluasi / Alasan Keputusan
				</label>
				<textarea name="catatan" id="adv-catatan" rows="4" style="width:100%; font-size:13px; padding:8px 10px; border-radius:7px" placeholder="Tuliskan catatan evaluasi, ulasan hasil seleksi, atau alasan mutasi alur kandidat..."></textarea>
				<div class="faint" style="font-size:11px; margin-top:3px">Catatan ini akan tersimpan pada tahap kandidat dan riwayat seleksi.</div>
			</div>
		</div>
		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-advance').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan &amp; Proses</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL SISIP TAHAP AD-HOC (PER KANDIDAT) ================= -->
<dialog id="dlg-adhoc" class="rpg-modal" style="max-width:500px">
	<div class="rpg-modal-header">
		<div>
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Sisip Tahap Tambahan (Ad-Hoc)
			</h3>
			<div class="muted" style="font-size:12px; margin-top:2px">
				Kandidat: <strong id="adhoc-nama-kandidat" style="color:var(--text)">-</strong>
			</div>
		</div>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-adhoc').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/insert_stage/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_lamaran" id="adhoc-id-lamaran">
		<div class="rpg-modal-body">
			<!-- Info Tahap Saat Ini -->
			<div style="margin-bottom:14px; padding:10px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:7px; display:flex; align-items:center; justify-content:space-between">
				<span style="font-size:12px; color:var(--text-muted)">Menyelesaikan Tahap:</span>
				<strong id="adhoc-stage-kini-nama" style="font-size:12.5px; color:var(--accent)">-</strong>
			</div>

			<!-- Keputusan / Remark Lanjut Tahap Kini -->
			<div style="margin-bottom:14px">
				<label for="adhoc-id-remark" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Keputusan / Remark untuk Lanjut <span style="color:var(--crit)">*</span>
				</label>
				<select name="id_remark" id="adhoc-id-remark" required style="width:100%; font-size:13px; padding:8px 10px; border-radius:6px">
					<!-- Diisi dinamis hanya remark dengan efek LANJUT -->
				</select>
				<div class="faint" style="font-size:11px; margin-top:4px; color:var(--text-muted)">
					Pilih keputusan kelulusan tahap saat ini untuk melanjutkan kandidat ke tahap tambahan.
				</div>
			</div>

			<!-- Alert jika semua tahap tambahan sudah pernah dijalani oleh kandidat -->
			<div id="adhoc-no-stages-alert" style="display:none; margin-bottom:14px; padding:12px 14px; background:var(--warn-soft); border:1px solid var(--warn); border-radius:7px; font-size:12.5px; color:var(--text)">
				<div style="font-weight:700; color:var(--warn); margin-bottom:3px">Tahap Tambahan Tidak Tersedia</div>
				<div>Seluruh opsi tahap tambahan yang diizinkan sudah pernah ditambahkan atau sedang dijalani oleh kandidat ini.</div>
			</div>

			<!-- Pilih Tahap Tambahan yang Disisipkan -->
			<div id="adhoc-stage-select-container" style="margin-bottom:14px">
				<label for="adhoc-stage" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Pilih Tahap Tambahan yang Disisipkan <span style="color:var(--crit)">*</span>
				</label>
				<select name="id_stage" id="adhoc-stage" required style="width:100%; font-size:13px; padding:8px 10px; border-radius:6px">
					<?php foreach ($all_stages as $st): ?>
						<option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['tipe_tahap']) ?>)</option>
					<?php endforeach; ?>
				</select>
				<div class="faint" style="font-size:11px; margin-top:4px; color:var(--text-muted)">
					Kandidat akan langsung dipindahkan dan berstatus aktif pada tahap tambahan ini.
				</div>
			</div>

			<div>
				<label for="adhoc-catatan" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Catatan / Alasan Sisip Tahap
				</label>
				<textarea name="catatan" id="adhoc-catatan" rows="3" style="font-size:12.5px; padding:8px 10px; width:100%; border-radius:6px" placeholder="Mis. Hasil evaluasi memuaskan, diperlukan interview teknis tambahan dengan User/BOD..."></textarea>
			</div>
		</div>
		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-adhoc').close()">Batal</button>
			<button type="submit" id="adhoc-submit-btn" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan &amp; Lanjut ke Tahap Tambahan</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL RIWAYAT CATATAN SELURUH TAHAPAN ================= -->
<dialog id="dlg-notes-history" class="rpg-modal" style="max-width:580px">
	<div class="rpg-modal-header" style="background:var(--surface-2)">
		<div>
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:6px">
				<svg style="width:16px; height:16px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
				<span>Riwayat Catatan &amp; Evaluasi Seleksi</span>
			</h3>
			<div class="muted" style="font-size:12px; margin-top:2px">
				Kandidat: <strong id="notes-history-nama-kandidat" style="color:var(--text)">-</strong>
			</div>
		</div>
		<button type="button" class="rpg-modal-close" onclick="closeNotesHistoryModal()">&times;</button>
	</div>
	<div class="rpg-modal-body" style="padding:16px 20px; max-height:65vh; overflow-y:auto">
		<div id="notes-history-container" style="display:flex; flex-direction:column; gap:12px">
			<!-- Diisi dinamis via openNotesHistoryModal -->
		</div>
	</div>
	<div class="rpg-modal-footer" style="background:var(--surface-2); display:flex; justify-content:flex-end">
		<button type="button" class="btn btn-primary" onclick="closeNotesHistoryModal()">Tutup</button>
	</div>
</dialog>


<!-- ================= MODAL INTERVIEW ================= -->
<dialog id="dlg-interview" class="rpg-modal">
	<div class="rpg-modal-header">
		<h3 id="dlg-iv-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
			Jadwal &amp; Hasil Evaluasi Interview
		</h3>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-interview').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/save_interview/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_interview" id="iv-id-interview">
		<input type="hidden" name="id_app_stage" id="iv-id-app-stage">

		<div class="rpg-modal-body">
			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="iv-tipe" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Tipe Interview</label>
					<select name="tipe" id="iv-tipe" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="Online">Online</option>
						<option value="Offline">Offline</option>
					</select>
				</div>
				<div>
					<label for="iv-jadwal" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Jadwal Wawancara</label>
					<input type="text" name="jadwal" id="iv-jadwal" placeholder="YYYY-MM-DD HH:MM" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			</div>

			<div style="margin-bottom:12px">
				<label for="iv-lokasi" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Lokasi / Tautan Meeting</label>
				<input type="text" name="lokasi_atau_link" id="iv-lokasi" placeholder="Mis. Google Meet link / Ruang HR Lt. 2" style="width:100%; font-size:12.5px; padding:6px 10px">
			</div>

			<div style="display:grid; grid-template-columns:1.5fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="iv-interviewer" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Pewawancara</label>
					<select name="id_interviewer" id="iv-interviewer" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="">- Pilih Pewawancara -</option>
						<?php foreach ($interviewers as $usr): ?>
							<option value="<?= (int) $usr['id_user'] ?>"><?= html_escape($usr['nama_lengkap']) ?> (<?= html_escape($usr['role']) ?>)</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="iv-peran" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Peran Interviewer</label>
					<select name="peran_interviewer" id="iv-peran" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="HR">HR</option>
						<option value="User">User</option>
						<option value="BOD">BOD</option>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1.5fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="iv-hasil" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Hasil Evaluasi</label>
					<select name="hasil" id="iv-hasil" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="">(Belum Ada / Terjadwal)</option>
						<option value="Lulus">Lulus</option>
						<option value="Tidak_Lulus">Tidak Lulus</option>
						<option value="Dipertimbangkan">Dipertimbangkan</option>
						<option value="Reschedule">Reschedule</option>
						<option value="No_Show">No Show</option>
					</select>
				</div>
				<div>
					<label for="iv-skor" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Skor (0-100)</label>
					<input type="number" name="skor" id="iv-skor" min="0" max="100" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			</div>

			<div>
				<label for="iv-catatan" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Catatan Interview</label>
				<textarea name="catatan" id="iv-catatan" rows="3" placeholder="Ulasan kompetensi, sikap, kelebihan, kekurangan..." style="width:100%; font-size:12.5px; padding:6px 10px"></textarea>
			</div>
		</div>

		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-interview').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan Interview</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL OFFER ================= -->
<dialog id="dlg-offer" class="rpg-modal">
	<div class="rpg-modal-header">
		<h3 id="dlg-off-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
			Penawaran Kerja (Offering)
		</h3>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-offer').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/save_offer/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_offer" id="off-id-offer">
		<input type="hidden" name="id_lamaran" id="off-id-lamaran">

		<div class="rpg-modal-body">
			<?php if ($can_gaji): ?>
				<div style="margin-bottom:12px">
					<label for="off-gaji" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Gaji yang Ditawarkan (IDR)</label>
					<input type="number" name="gaji_ditawarkan" id="off-gaji" placeholder="Mis. 5000000" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			<?php endif; ?>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="off-tgl-penawaran" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Tanggal Penawaran</label>
					<input type="date" name="tanggal_penawaran" id="off-tgl-penawaran" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
				<div>
					<label for="off-status" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Status Offer</label>
					<select name="status_offer" id="off-status" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="Nego">Nego / Ditawarkan</option>
						<option value="Accepted">Accepted (Diterima)</option>
						<option value="Declined">Declined (Ditolak Kandidat)</option>
						<option value="Canceled">Canceled (Dibatalkan RPG)</option>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="off-tgl-join-sepakat" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Target Join Sepakat</label>
					<input type="date" name="tanggal_join_disepakati" id="off-tgl-join-sepakat" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
				<div>
					<label for="off-tgl-join-aktual" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Join Aktual</label>
					<input type="date" name="tanggal_join_aktual" id="off-tgl-join-aktual" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			</div>

			<div>
				<label for="off-alasan" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Catatan / Alasan</label>
				<textarea name="alasan" id="off-alasan" rows="2" placeholder="Catatan negosiasi gaji, tunjangan, fasilitas, atau alasan tolak..." style="width:100%; font-size:12.5px; padding:6px 10px"></textarea>
			</div>
		</div>

		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-offer').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan Offer</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL DIALOG POPUP GENERATE LINK FORM PELAMAR ================= -->
<dialog id="dlg-onboarding-link" class="rpg-modal" style="max-width:520px">
	<div class="rpg-modal-header" style="background:var(--surface-2)">
		<div style="display:flex; align-items:center; gap:10px">
			<div style="width:34px; height:34px; border-radius:8px; background:var(--accent-soft); display:grid; place-items:center; color:var(--accent); flex:none">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
			</div>
			<div>
				<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">Tautan Formulir Pelamar</h3>
				<div class="muted" id="ob-modal-subtitle" style="font-size:12px; margin-top:2px">Kandidat Ratu Pertiwi Group</div>
			</div>
		</div>
		<button type="button" class="rpg-modal-close" onclick="closeOnboardingModal()" title="Tutup">&times;</button>
	</div>

	<div class="rpg-modal-body" style="padding:20px">
		<!-- State Loading -->
		<div id="ob-state-loading" style="display:none; text-align:center; padding:28px 10px">
			<div style="font-size:24px; margin-bottom:8px">⏳</div>
			<div id="ob-loading-text" style="font-weight:600; font-size:13.5px">Memeriksa tautan formulir...</div>
			<div class="muted" style="font-size:12px; margin-top:4px">Menghubungkan ke server RPG</div>
		</div>

		<!-- State Error Terstruktur -->
		<div id="ob-state-error" style="display:none; padding:10px 0">
			<div style="background:var(--crit-soft, #fee2e2); color:var(--crit, #dc2626); border:1px solid rgba(220,38,38,0.25); padding:12px 14px; border-radius:8px; font-size:12.5px; line-height:1.4">
				<div style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:4px">
					<span>⚠</span> Gagal Menyiapkan Tautan
				</div>
				<div id="ob-error-msg" style="color:var(--text, #1e293b)">Terjadi kesalahan saat memproses permintaan formulir.</div>
			</div>
			<div style="margin-top:12px; text-align:center">
				<button type="button" onclick="loadOnboardingToken(false)" class="btn btn-sm btn-ghost" style="border:1px solid var(--border); font-size:12px">
					↻ Coba Lagi
				</button>
			</div>
		</div>

		<!-- State Sukses / Tautan Aktif -->
		<div id="ob-state-success" style="display:none">
			<div id="ob-alert-box" style="background:var(--accent-soft); color:var(--accent-ink); padding:10px 12px; border-radius:8px; font-size:12.5px; margin-bottom:14px; display:flex; align-items:center; gap:8px">
				<span id="ob-alert-icon" style="font-size:15px; font-weight:bold">ℹ</span>
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
					<svg style="width:14px; height:14px; flex:none" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.586-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/></svg>
					Bagikan ke WhatsApp
				</a>
				<a id="ob-btn-open" href="#" target="_blank" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; text-decoration:none; border:1px solid var(--border)">
					Buka Halaman Form &rarr;
				</a>
			</div>
		</div>
	</div>

	<div class="rpg-modal-footer" style="display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<button type="button" id="ob-btn-regen" onclick="submitGenerateOnboarding(true)" class="btn btn-sm btn-ghost" style="display:none; color:var(--text-muted); font-size:12px" title="Cabut link lama dan buat link baru">
				↻ Buat Ulang Link Baru
			</button>
		</div>
		<div style="display:flex; gap:8px">
			<button type="button" onclick="closeOnboardingModal()" class="btn btn-sm btn-ghost">Tutup</button>
		</div>
	</div>
</dialog>

<!-- SCRIPT FILTER & MODAL HANDLER -->
<script>
// Kamus remarks per id_stage
var stageRemarksMap = <?= json_encode($remarks) ?>;
// Seluruh master tahap yang diizinkan untuk disisipkan
var allAvailableStages = <?= json_encode($all_stages) ?>;
// Peta tahap yang sudah pernah dijalani / ada pada setiap kandidat
var candidateExistingStagesMap = <?= json_encode($candidate_existing_stages ?? array()) ?>;
// Riwayat lengkap seluruh catatan & evaluasi tahapan pelamar
var candidateStageHistoryMap = <?= json_encode($candidate_stage_history ?? array()) ?>;

function toggleAllStages() {
	var details = document.querySelectorAll('details.stage-section');
	var btnLabel = document.getElementById('btn-toggle-all-label');
	var anyOpen = false;
	details.forEach(function(d) {
		if (d.open) anyOpen = true;
	});

	details.forEach(function(d) {
		d.open = !anyOpen;
	});

	if (btnLabel) {
		btnLabel.textContent = anyOpen ? 'Buka Semua Tahap' : 'Lipat Semua Tahap';
	}
}

function filterPipelineRows() {
	var query = (document.getElementById('pipeline-search').value || '').toLowerCase().trim();
	var overdueOnly = document.getElementById('filter-overdue-only').checked;
	var rows = document.querySelectorAll('.candidate-row');

	// Jika pengguna mengetik pencarian atau memfilter kandidat tertahan, buka otomatis semua tahap
	if (query || overdueOnly) {
		document.querySelectorAll('details.stage-section').forEach(function(d) {
			d.open = true;
		});
		var btnLabel = document.getElementById('btn-toggle-all-label');
		if (btnLabel) btnLabel.textContent = 'Lipat Semua Tahap';
	}

	rows.forEach(function(row) {
		var text = row.getAttribute('data-search') || '';
		var isOverdue = row.getAttribute('data-overdue') === '1';

		var matchQuery = !query || text.indexOf(query) !== -1;
		var matchOverdue = !overdueOnly || isOverdue;

		if (matchQuery && matchOverdue) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
}

function openAdvanceModal(idAppStage, idStage, namaKandidat, currentRemarkId, currentCatatan) {
	var dlg = document.getElementById('dlg-advance');
	document.getElementById('dlg-adv-subtitle').textContent = 'Kandidat: ' + namaKandidat;
	document.getElementById('adv-id-app-stage').value = idAppStage;
	document.getElementById('adv-catatan').value = currentCatatan || '';

	var sel = document.getElementById('adv-id-remark');
	sel.innerHTML = '<option value="">-- Pilih Keputusan --</option>';
	var rmkList = stageRemarksMap[idStage] || [];

	var grpLanjut = document.createElement('optgroup');
	grpLanjut.label = '✔ Lolos / Lanjut Tahap';
	var grpReject = document.createElement('optgroup');
	grpReject.label = '✖ Gugur / Ditolak (Rejected)';
	var grpLain = document.createElement('optgroup');
	grpLain.label = '⏳ Status Khusus (Hold / Unreachable)';

	rmkList.forEach(function(r) {
		var opt = document.createElement('option');
		opt.value = r.id_remark;
		opt.setAttribute('data-efek', r.efek_status);
		if (currentRemarkId && parseInt(currentRemarkId) === parseInt(r.id_remark)) {
			opt.selected = true;
		}
		if (r.efek_status === 'LANJUT' || r.efek_status === 'HIRED') {
			opt.textContent = '✔ Lanjut: ' + r.label;
			grpLanjut.appendChild(opt);
		} else if (r.efek_status === 'TOLAK' || r.efek_status === 'WITHDRAWN' || r.efek_status === 'OFFER_DECLINED' || r.efek_status === 'NO_SHOW') {
			opt.textContent = '✖ Tolak: ' + r.label;
			grpReject.appendChild(opt);
		} else {
			opt.textContent = '● ' + r.label + ' (' + r.efek_status + ')';
			grpLain.appendChild(opt);
		}
	});

	if (grpLanjut.children.length > 0) sel.appendChild(grpLanjut);
	if (grpReject.children.length > 0) sel.appendChild(grpReject);
	if (grpLain.children.length > 0) sel.appendChild(grpLain);

	dlg.showModal();
}

function openAdHocModal(idLamaran, namaKandidat, currentStageId, currentStageName) {
	document.getElementById('adhoc-id-lamaran').value = idLamaran;
	document.getElementById('adhoc-nama-kandidat').textContent = namaKandidat + ' (#' + idLamaran + ')';
	document.getElementById('adhoc-stage-kini-nama').textContent = currentStageName || '-';
	document.getElementById('adhoc-catatan').value = '';

	// 1. Filter dan isi remark khusus berstatus LANJUT untuk tahap saat ini
	var selRemark = document.getElementById('adhoc-id-remark');
	selRemark.innerHTML = '';

	var rmkList = stageRemarksMap[currentStageId] || [];
	// Filter HANYA remark dengan efek LANJUT (remark TOLAK tidak ditampilkan)
	var lanjutList = rmkList.filter(function(r) {
		return r.efek_status === 'LANJUT';
	});

	if (lanjutList.length > 0) {
		selRemark.innerHTML = '<option value="">-- Pilih Keputusan Lolos / Lanjut --</option>';
		lanjutList.forEach(function(r) {
			var opt = document.createElement('option');
			opt.value = r.id_remark;
			opt.textContent = '✔ Lanjut: ' + r.label;
			selRemark.appendChild(opt);
		});
		selRemark.required = true;
	} else {
		// Opsi default jika tahap ini belum memiliki daftar remark LANJUT spesifik
		var opt = document.createElement('option');
		opt.value = '';
		opt.textContent = '✔ Lolos / Lanjut ke Tahap Tambahan';
		selRemark.appendChild(opt);
		selRemark.required = false;
	}

	// 2. Filter dropdown tahap tambahan: SEMBUNYIKAN tahap yang sudah pernah dilalui/dimiliki kandidat ini
	var selStage = document.getElementById('adhoc-stage');
	selStage.innerHTML = '';

	var usedStages = (candidateExistingStagesMap && candidateExistingStagesMap[idLamaran]) || [];
	var availableStages = (allAvailableStages || []).filter(function(st) {
		// Abaikan jika kandidat sudah pernah memiliki/menjalani tahap ini
		return usedStages.indexOf(parseInt(st.id_stage)) === -1;
	});

	var alertBox = document.getElementById('adhoc-no-stages-alert');
	var submitBtn = document.getElementById('adhoc-submit-btn');
	var stageContainer = document.getElementById('adhoc-stage-select-container');

	if (availableStages.length > 0) {
		availableStages.forEach(function(st) {
			var opt = document.createElement('option');
			opt.value = st.id_stage;
			opt.textContent = st.nama_tahap + ' (' + st.tipe_tahap + ')';
			selStage.appendChild(opt);
		});
		if (alertBox) alertBox.style.display = 'none';
		if (stageContainer) stageContainer.style.display = 'block';
		if (submitBtn) submitBtn.disabled = false;
		selStage.required = true;
	} else {
		var opt = document.createElement('option');
		opt.value = '';
		opt.textContent = '-- Tidak ada opsi tahap tambahan yang tersedia --';
		selStage.appendChild(opt);
		if (alertBox) alertBox.style.display = 'block';
		if (stageContainer) stageContainer.style.display = 'none';
		if (submitBtn) submitBtn.disabled = true;
		selStage.required = false;
	}

	document.getElementById('dlg-adhoc').showModal();
}

function openInterviewModal(data) {
	var dlg = document.getElementById('dlg-interview');
	document.getElementById('dlg-iv-title').textContent = 'Interview: ' + data.nama;
	document.getElementById('iv-id-app-stage').value = data.id_app_stage;
	var iv = data.interview || {};
	document.getElementById('iv-id-interview').value = iv.id_interview || '';
	document.getElementById('iv-tipe').value = iv.tipe || 'Online';
	document.getElementById('iv-jadwal').value = iv.jadwal || '';
	document.getElementById('iv-lokasi').value = iv.lokasi_atau_link || '';
	document.getElementById('iv-interviewer').value = iv.id_interviewer || '';
	document.getElementById('iv-peran').value = iv.peran_interviewer || 'HR';
	document.getElementById('iv-hasil').value = iv.hasil || '';
	document.getElementById('iv-skor').value = iv.skor !== null && iv.skor !== undefined ? iv.skor : '';
	document.getElementById('iv-catatan').value = iv.catatan || '';
	dlg.showModal();
}

function openOfferModal(data) {
	var dlg = document.getElementById('dlg-offer');
	document.getElementById('dlg-off-title').textContent = 'Offering: ' + data.nama;
	document.getElementById('off-id-lamaran').value = data.id_lamaran;
	var off = data.offer || {};
	document.getElementById('off-id-offer').value = off.id_offer || '';
	var gInput = document.getElementById('off-gaji');
	if (gInput) {
		gInput.value = off.gaji_ditawarkan ? Math.round(off.gaji_ditawarkan) : '';
	}
	document.getElementById('off-tgl-penawaran').value = off.tanggal_penawaran || '';
	document.getElementById('off-status').value = off.status_offer || 'Nego';
	document.getElementById('off-tgl-join-sepakat').value = off.tanggal_join_disepakati || '';
	document.getElementById('off-tgl-join-aktual').value = off.tanggal_join_aktual || '';
	document.getElementById('off-alasan').value = off.alasan || '';
	dlg.showModal();
}

// Floating Candidate Action Menu (Anti-cut off & boundary aware)
var activeCandidateBtn = null;
var currentCandidateId = null;
var currentCandidateName = '';
var candidateCvBaseUrl = '<?= site_url("candidates/cv/") ?>';
var candidateFormBaseUrl = '<?= site_url("candidates/print_form/") ?>';

function toggleCandidateMenu(e, idLamaran, namaKandidat, canGenerateForm, canPrintForm) {
	e.stopPropagation();
	var btn = e.currentTarget;
	var menu = document.getElementById('pipeline-floating-menu');
	if (!menu) return;

	if (activeCandidateBtn === btn && menu.style.display === 'block') {
		closeCandidateMenu();
		return;
	}

	if (activeCandidateBtn) activeCandidateBtn.classList.remove('active');
	activeCandidateBtn = btn;
	btn.classList.add('active');

	currentCandidateId = idLamaran;
	currentCandidateName = namaKandidat || ('Pelamar #' + idLamaran);

	var cvItem = document.getElementById('pipe-menu-cv');
	if (cvItem) cvItem.href = candidateCvBaseUrl + idLamaran;
	var formItem = document.getElementById('pipe-menu-form');
	if (formItem) {
		formItem.href = candidateFormBaseUrl + idLamaran;
		formItem.style.display = canPrintForm ? 'flex' : 'none';
	}

	var obMenuItem = document.getElementById('pipe-menu-onboarding');
	if (obMenuItem) {
		obMenuItem.style.display = canGenerateForm ? 'flex' : 'none';
	}

	menu.style.visibility = 'hidden';
	menu.style.display = 'block';

	var rect = btn.getBoundingClientRect();
	var menuWidth = menu.offsetWidth || 260;
	var menuHeight = menu.offsetHeight || 130;

	var left = rect.right - menuWidth;
	if (left < 12) left = 12;
	if (left + menuWidth > window.innerWidth - 12) {
		left = window.innerWidth - menuWidth - 12;
	}

	var top = rect.bottom + 6;
	if (top + menuHeight > window.innerHeight - 12) {
		top = rect.top - menuHeight - 6;
	}
	if (top < 12) top = 12;

	menu.style.left = left + 'px';
	menu.style.top = top + 'px';
	menu.style.visibility = 'visible';
}

function closeCandidateMenu() {
	var menu = document.getElementById('pipeline-floating-menu');
	if (menu) menu.style.display = 'none';
	if (activeCandidateBtn) {
		activeCandidateBtn.classList.remove('active');
		activeCandidateBtn = null;
	}
}

function triggerCandidateOnboardingModal(e) {
	if (e) {
		e.preventDefault();
		e.stopPropagation();
	}
	var id = currentCandidateId;
	var name = currentCandidateName;
	closeCandidateMenu();
	if (id) {
		openOnboardingModal(null, id, name);
	}
}

// Logic Pop-up Tautan Onboarding / Formulir Pelamar
var _currentObId = null;
var _currentObName = '';
var _hasGeneratedNew = false;

function openOnboardingModal(e, idLamaran, namaKandidat) {
	if (e) {
		e.preventDefault();
		e.stopPropagation();
	}

	_currentObId = idLamaran;
	_currentObName = namaKandidat;
	_hasGeneratedNew = false;

	document.getElementById('ob-modal-subtitle').textContent = 'Kandidat: ' + namaKandidat;

	document.getElementById('ob-state-success').style.display = 'none';
	var errBox = document.getElementById('ob-state-error');
	if (errBox) errBox.style.display = 'none';
	document.getElementById('ob-btn-regen').style.display = 'none';
	document.getElementById('ob-copy-feedback').style.display = 'none';

	document.getElementById('ob-loading-text').textContent = 'Memeriksa tautan formulir...';
	document.getElementById('ob-state-loading').style.display = 'block';

	var dlg = document.getElementById('dlg-onboarding-link');
	if (dlg && typeof dlg.showModal === 'function') {
		dlg.showModal();
	} else if (dlg) {
		dlg.setAttribute('open', '');
	}

	loadOnboardingToken(false);
}

function closeOnboardingModal() {
	var dlg = document.getElementById('dlg-onboarding-link');
	if (dlg && typeof dlg.close === 'function') {
		dlg.close();
	} else if (dlg) {
		dlg.removeAttribute('open');
	}
}

function submitGenerateOnboarding(forceNew) {
	if (forceNew) {
		if (!confirm('Apakah Anda yakin ingin membuat ulang tautan baru?\nTautan lama akan dicabut dan kandidat harus menggunakan tautan yang baru.')) {
			return;
		}
	}
	loadOnboardingToken(forceNew);
}

function loadOnboardingToken(forceNew) {
	if (!_currentObId) return;

	document.getElementById('ob-state-success').style.display = 'none';
	var errBox = document.getElementById('ob-state-error');
	if (errBox) errBox.style.display = 'none';
	document.getElementById('ob-btn-regen').style.display = 'none';

	document.getElementById('ob-loading-text').textContent = forceNew ? 'Membuat tautan formulir baru...' : 'Memeriksa tautan formulir...';
	document.getElementById('ob-state-loading').style.display = 'block';

	var url = '<?= site_url("candidates/generate_onboarding_link/") ?>' + _currentObId + '?format=json' + (forceNew ? '&force_new=1' : '');

	fetch(url, {
		method: 'GET',
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		}
	})
	.then(function(res){
		return res.text().then(function(text){
			var json = null;
			try {
				json = JSON.parse(text);
			} catch (e) {
				json = null;
			}
			if (!res.ok) {
				var msg = (json && (json.message || json.error)) ? (json.message || json.error) : ('Terjadi kesalahan HTTP ' + res.status + ' (' + res.statusText + ')');
				throw new Error(msg);
			}
			if (!json) {
				throw new Error('Respon server tidak valid atau terjadi kesalahan format.');
			}
			return json;
		});
	})
	.then(function(data){
		document.getElementById('ob-state-loading').style.display = 'none';

		if (data && data.success) {
			_hasGeneratedNew = true;
			document.getElementById('ob-input-url').value = data.onboarding_url || '';
			document.getElementById('ob-btn-wa').href = data.wa_link || '#';
			document.getElementById('ob-btn-open').href = data.onboarding_url || '#';

			var alertBox = document.getElementById('ob-alert-box');
			var alertIcon = document.getElementById('ob-alert-icon');
			var alertMsg = document.getElementById('ob-success-msg');

			if (data.is_existing) {
				alertBox.style.background = 'var(--surface-2)';
				alertBox.style.color = 'var(--text)';
				alertIcon.textContent = 'ℹ';
				alertMsg.textContent = data.message || ('Tautan formulir aktif ditemukan (s.d. ' + (data.kadaluarsa || '-') + ')');
			} else {
				alertBox.style.background = 'var(--accent-soft)';
				alertBox.style.color = 'var(--accent-ink)';
				alertIcon.textContent = '✓';
				alertMsg.textContent = data.message || ('Tautan baru berhasil dibuat (Masa aktif s.d. ' + (data.kadaluarsa || '14 hari') + ')');
			}

			document.getElementById('ob-state-success').style.display = 'block';
			document.getElementById('ob-btn-regen').style.display = 'inline-block';
		} else {
			throw new Error((data && (data.message || data.error)) ? (data.message || data.error) : 'Gagal memproses tautan formulir.');
		}
	})
	.catch(function(err){
		document.getElementById('ob-state-loading').style.display = 'none';
		var errDiv = document.getElementById('ob-state-error');
		var errMsg = document.getElementById('ob-error-msg');
		if (errDiv && errMsg) {
			errMsg.textContent = err && err.message ? err.message : 'Terjadi gangguan jaringan atau server.';
			errDiv.style.display = 'block';
		} else {
			alert('Gagal: ' + (err && err.message ? err.message : 'Terjadi kesalahan sistem.'));
			closeOnboardingModal();
		}
	});
}

function copyObUrl() {
	var input = document.getElementById('ob-input-url');
	if (!input) return;

	input.select();
	input.setSelectionRange(0, 99999);

	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(input.value).then(function(){
			showCopyFeedback();
		}).catch(function(){
			document.execCommand('copy');
			showCopyFeedback();
		});
	} else {
		document.execCommand('copy');
		showCopyFeedback();
	}
}

function showCopyFeedback() {
	var fb = document.getElementById('ob-copy-feedback');
	if (!fb) return;
	fb.style.display = 'block';
	setTimeout(function(){
		fb.style.display = 'none';
	}, 3000);
}

document.addEventListener('click', function(e) {
	if (!e.target.closest('.pipe-kebab-btn') && !e.target.closest('#pipeline-floating-menu')) {
		closeCandidateMenu();
	}
});

function escapeHtml(str) {
	if (!str) return '';
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function closeNotesHistoryModal() {
	var dlg = document.getElementById('dlg-notes-history');
	if (!dlg) return;
	if (typeof dlg.close === 'function') {
		try { dlg.close(); } catch (e) {}
	}
	dlg.removeAttribute('open');
	dlg.style.display = 'none';
}

function openNotesHistoryModal(idLamaran, namaKandidat) {
	var dlg = document.getElementById('dlg-notes-history');
	if (!dlg) {
		alert('Modal riwayat catatan tidak ditemukan.');
		return;
	}

	document.getElementById('notes-history-nama-kandidat').textContent = namaKandidat + ' (#' + idLamaran + ')';
	var container = document.getElementById('notes-history-container');
	container.innerHTML = '';

	var list = [];
	if (typeof candidateStageHistoryMap !== 'undefined' && candidateStageHistoryMap) {
		list = candidateStageHistoryMap[idLamaran] || candidateStageHistoryMap[String(idLamaran)] || [];
	}

	if (!list || list.length === 0) {
		container.innerHTML = '<div style="padding:32px 16px; text-align:center; color:var(--text-muted); font-size:13px">' +
			'<div style="font-size:24px; margin-bottom:8px">📋</div>' +
			'<strong style="color:var(--text)">Belum ada catatan evaluasi sebelumnya</strong>' +
			'<div class="muted" style="margin-top:4px">Kandidat belum memiliki catatan dari tahapan seleksi sebelumnya.</div>' +
		'</div>';
	} else {
		list.forEach(function(item) {
			var isCurrent = (item.status_tahap === 'Berjalan');
			var statusTagClass = 'off';
			var statusLabel = item.status_tahap;

			if (item.status_tahap === 'Lulus') {
				statusTagClass = 'on';
				statusLabel = 'Lulus';
			} else if (item.status_tahap === 'Berjalan') {
				statusTagClass = 'warn';
				statusLabel = 'Sedang Berjalan (Tahap Aktif)';
			} else if (item.status_tahap === 'Tidak_Lulus') {
				statusTagClass = 'crit';
				statusLabel = 'Tidak Lulus';
			} else if (item.status_tahap === 'Belum') {
				statusTagClass = 'off';
				statusLabel = 'Belum Dimulai';
			}

			var card = document.createElement('div');
			card.style.cssText = 'border:1.5px solid ' + (isCurrent ? 'var(--accent)' : 'var(--border)') + '; border-radius:9px; padding:13px 15px; background:' + (isCurrent ? 'rgba(2, 132, 199, 0.05)' : 'var(--surface)');

			var sisipanBadge = (item.is_sisipan == 1) ? '<span style="font-size:9.5px; background:rgba(217,119,6,0.15); color:#d97706; border:1px solid rgba(217,119,6,0.35); padding:1px 6px; border-radius:4px; font-weight:700; margin-left:4px">Tahap Tambahan</span>' : '';

			var headerDiv = document.createElement('div');
			headerDiv.style.cssText = 'display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom:6px';
			headerDiv.innerHTML = '<div style="display:flex; align-items:center; gap:6px">' +
				'<span style="font-size:11px; font-weight:700; color:var(--text-muted); font-family:monospace; background:var(--surface-2); padding:2px 6px; border-radius:4px">#' + item.urutan + '</span>' +
				'<strong style="font-size:13.5px; color:var(--text)">' + escapeHtml(item.nama_tahap) + '</strong>' +
				sisipanBadge +
			'</div>' +
			'<span class="tag ' + statusTagClass + '" style="font-size:10.5px; padding:2px 8px; font-weight:600">' + escapeHtml(statusLabel) + '</span>';

			var metaDiv = document.createElement('div');
			metaDiv.style.cssText = 'font-size:11.5px; color:var(--text-muted); margin-bottom:8px; display:flex; gap:12px; flex-wrap:wrap';
			var dateInfo = item.tanggal_selesai ? 'Selesai: ' + escapeHtml(item.tanggal_selesai) : (item.tanggal_mulai ? 'Mulai: ' + escapeHtml(item.tanggal_mulai) : '');
			metaDiv.innerHTML = '<span>Evaluator / PIC: <strong>' + escapeHtml(item.nama_pic || 'Tim HR') + '</strong></span>' + (dateInfo ? '<span>&bull; ' + dateInfo + '</span>' : '');

			card.appendChild(headerDiv);
			card.appendChild(metaDiv);

			if (item.label_remark) {
				var rmkDiv = document.createElement('div');
				rmkDiv.style.cssText = 'margin-bottom:7px; font-size:12px';
				rmkDiv.innerHTML = '<span style="color:var(--text-muted); font-weight:500">Keputusan / Remark: </span><span class="tag accent" style="font-size:10.5px; padding:2px 7px; font-weight:600">' + escapeHtml(item.label_remark) + '</span>';
				card.appendChild(rmkDiv);
			}

			var noteBox = document.createElement('div');
			noteBox.style.cssText = 'background:var(--surface-2); border:1px solid var(--border); border-radius:7px; padding:9px 12px; font-size:12.5px; line-height:1.55; color:var(--text); white-space:pre-line; word-break:break-word';
			if (item.catatan && String(item.catatan).trim() !== '') {
				noteBox.innerHTML = '<div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:3px">Catatan HR:</div>' + escapeHtml(item.catatan);
			} else {
				noteBox.innerHTML = '<div style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:3px">Catatan HR:</div><span style="color:var(--text-muted); font-style:italic">Tidak ada catatan pada tahap ini.</span>';
			}
			card.appendChild(noteBox);

			container.appendChild(card);
		});
	}

	dlg.style.display = 'flex';
	try {
		if (typeof dlg.showModal === 'function') {
			if (dlg.open) dlg.close();
			dlg.showModal();
		} else {
			dlg.setAttribute('open', '');
		}
	} catch (e) {
		console.warn('showModal fallback:', e);
		dlg.setAttribute('open', '');
	}
}

// Tutup modal riwayat catatan saat klik di luar area modal (backdrop)
(function() {
	var historyDlg = document.getElementById('dlg-notes-history');
	if (historyDlg) {
		historyDlg.addEventListener('click', function(e) {
			var rect = historyDlg.getBoundingClientRect();
			var isInDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height
				&& rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
			if (!isInDialog) {
				closeNotesHistoryModal();
			}
		});
	}
})();

window.addEventListener('scroll', closeCandidateMenu, true);
window.addEventListener('resize', closeCandidateMenu);
</script>

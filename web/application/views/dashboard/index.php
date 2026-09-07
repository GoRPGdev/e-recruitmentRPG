<?php defined('BASEPATH') OR exit('No direct script access allowed');
$g   = function ($k) use ($f) { return isset($f[$k]) ? $f[$k] : ''; };
$sel = function ($a, $b) { return (string) $a === (string) $b ? 'selected' : ''; };

// Susun KPI metrik
$met = array();
foreach ($d['metrik'] as $m) {
	$met[$m['status_global']] = (int) $m['jumlah'];
}
$aging_count = count($d['aging'] ?? array());
?>

<style>
/* Dashboard full-width alami mengikuti viewport */
.view.wide {
	padding: 16px 20px !important;
	width: 100%;
	max-width: 100%;
	box-sizing: border-box;
}

/* Container Dashboard RPG Full-Width */
.dash-screen {
	display: flex;
	flex-direction: column;
	gap: 14px;
	width: 100%;
	box-sizing: border-box;
}

.dash-header-bar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	flex-wrap: nowrap;
	flex-shrink: 0;
}

.dash-title-group {
	display: flex;
	align-items: center;
	gap: 10px;
}

.dash-h1 {
	margin: 0;
	font-size: 18px;
	font-weight: 700;
	color: var(--text);
	letter-spacing: -.02em;
	line-height: 1.2;
}

.role-badge {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	font-size: 10px;
	font-weight: 700;
	padding: 2px 7px;
	border-radius: 5px;
	text-transform: uppercase;
	letter-spacing: .04em;
	flex-shrink: 0;
}
.role-badge.admin {
	background: color-mix(in srgb, var(--accent) 15%, transparent);
	color: var(--accent);
	border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
}
.role-badge.dept {
	background: color-mix(in srgb, var(--info) 15%, transparent);
	color: var(--info);
	border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);
}

/* Card Universal */
.dash-box {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	box-shadow: 0 1px 2px rgba(0,0,0,0.02);
	overflow: hidden;
}

/* Filter Strip Kompak */
.dash-filter-strip {
	padding: 8px 12px;
	flex-shrink: 0;
	background: var(--surface);
}
.dash-filter-form {
	display: flex;
	gap: 8px;
	align-items: center;
	flex-wrap: wrap;
	margin: 0;
	font-size: 12px;
}
.dash-filter-item {
	display: flex;
	align-items: center;
	gap: 6px;
}
.dash-filter-label {
	font-size: 10.5px;
	font-weight: 700;
	color: var(--text-faint);
	text-transform: uppercase;
	letter-spacing: .03em;
	white-space: nowrap;
}
.dash-filter-input, .dash-filter-select {
	padding: 4px 8px;
	font-size: 11.5px;
	border-radius: 6px;
	border: 1px solid var(--border);
	background: var(--surface-2);
	color: var(--text);
	height: 28px;
	box-sizing: border-box;
}

/* KPI Strip Horizontal 8 status */
.dash-kpi-strip {
	display: grid;
	grid-template-columns: repeat(8, 1fr);
	gap: 8px;
	flex-shrink: 0;
}
.kpi-chip {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 8px;
	padding: 6px 10px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 6px;
	min-width: 0;
	transition: border-color .15s ease;
}
.kpi-chip:hover {
	border-color: var(--accent);
}
.kpi-chip-label {
	font-size: 10px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: .03em;
	color: var(--text-muted);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.kpi-chip-val {
	font-size: 16px;
	font-weight: 700;
	line-height: 1;
	font-family: var(--mono);
}

/* Main Split Grid (Mengisi sisa tinggi 1 layar) */
.dash-grid-main {
	display: grid;
	grid-template-columns: 2.2fr 1fr;
	gap: 12px;
	flex: 1;
	min-height: 0; /* penting agar child flex/grid bisa scroll internal */
}

/* Panel Matriks Funnel */
.dash-matrix-panel {
	display: flex;
	flex-direction: column;
	height: 100%;
	min-height: 0;
}
.dash-panel-head {
	padding: 10px 14px;
	background: var(--surface);
	border-bottom: 1px solid var(--border);
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 10px;
	flex-shrink: 0;
}
.dash-panel-body {
	flex: 1;
	overflow: auto;
	min-height: 0;
	position: relative;
	background: var(--surface);
}

/* Tabel Matrix Funnel */
.matrix-table {
	width: 100%;
	border-collapse: separate;
	border-spacing: 0;
	font-size: 12px;
	white-space: nowrap;
}
.matrix-table th {
	position: sticky;
	top: 0;
	background: var(--surface-2);
	border-bottom: 1px solid var(--border);
	border-right: 1px solid var(--border);
	padding: 7px 10px;
	font-size: 11px;
	font-weight: 700;
	color: var(--text);
	z-index: 10;
}
.matrix-table th.col-freeze {
	position: sticky;
	left: 0;
	top: 0;
	z-index: 20;
	background: var(--surface-2);
	border-right: 2px solid var(--border);
}
.matrix-table td {
	padding: 7px 10px;
	border-bottom: 1px solid var(--border);
	border-right: 1px solid var(--border);
	vertical-align: middle;
}
.matrix-table td.col-freeze {
	position: sticky;
	left: 0;
	z-index: 5;
	background: var(--surface);
	border-right: 2px solid var(--border);
}
.matrix-table tr:hover td {
	background: color-mix(in srgb, var(--accent) 4%, var(--surface));
}
.matrix-table tr:hover td.col-freeze {
	background: color-mix(in srgb, var(--accent) 7%, var(--surface));
}
.matrix-table tfoot td {
	position: sticky;
	bottom: 0;
	background: var(--surface-2);
	font-weight: 700;
	border-top: 2px solid var(--border);
	border-bottom: none;
	z-index: 15;
}
.matrix-table tfoot td.col-freeze {
	left: 0;
	z-index: 25;
}

/* Sidebar Tabbed Panel (Kanan) */
.dash-tabs-panel {
	display: flex;
	flex-direction: column;
	height: 100%;
	min-height: 0;
}
.dash-tabs-nav {
	display: flex;
	background: var(--surface-2);
	border-bottom: 1px solid var(--border);
	padding: 4px 6px 0;
	gap: 4px;
	flex-shrink: 0;
}
.dash-tab-btn {
	padding: 6px 12px;
	font-size: 11px;
	font-weight: 600;
	color: var(--text-muted);
	background: transparent;
	border: none;
	border-bottom: 2px solid transparent;
	cursor: pointer;
	border-radius: 6px 6px 0 0;
	transition: all .15s ease;
	display: inline-flex;
	align-items: center;
	gap: 6px;
}
.dash-tab-btn:hover {
	color: var(--text);
}
.dash-tab-btn.active {
	color: var(--accent);
	background: var(--surface);
	border-bottom: 2px solid var(--accent);
	font-weight: 700;
}
.dash-tab-content {
	flex: 1;
	overflow-y: auto;
	min-height: 0;
	padding: 12px 14px;
	display: none;
	background: var(--surface);
}
.dash-tab-content.active {
	display: block;
}

/* Sub-tabel di panel kanan */
.side-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 11.5px;
}
.side-table th {
	padding: 6px 8px;
	background: var(--surface-2);
	color: var(--text-muted);
	font-size: 10.5px;
	text-align: left;
	border-bottom: 1px solid var(--border);
	position: sticky;
	top: 0;
	z-index: 2;
}
.side-table td {
	padding: 6px 8px;
	border-bottom: 1px solid var(--border);
}
.side-table tr:last-child td {
	border-bottom: none;
}
.side-table tr:hover {
	background: var(--surface-2);
}

@media (max-width: 1180px) {
	.dash-screen {
		height: auto;
		min-height: 0;
	}
	.dash-kpi-strip {
		grid-template-columns: repeat(4, 1fr);
	}
	.dash-grid-main {
		grid-template-columns: 1fr;
		height: auto;
	}
	.dash-matrix-panel {
		height: 400px;
	}
	.dash-tabs-panel {
		height: 380px;
	}
}
</style>

<?php if ($user_role === 'USER_DEPT'): ?>
	<!-- =========================================================================
	     DASHBOARD ROLE: USER_DEPT (FIT 1-SCREEN VIEWPORT)
	     ========================================================================= -->
	<div class="dash-screen">
		<!-- Header User Dept -->
		<div class="dash-header-bar">
			<div class="dash-title-group">
				<span class="role-badge dept">Hiring Manager</span>
				<h1 class="dash-h1">Portal Departemen: <?= html_escape($user_dept_nama ?: 'Departemen Anda') ?></h1>
				<span class="muted" style="font-size:12px">&bull; Halo, <strong><?= html_escape($user_nama) ?></strong></span>
			</div>
			<div style="display:flex; gap:8px; align-items:center">
				<a href="<?= site_url('requisitions/create') ?>" class="btn btn-sm btn-primary" style="padding:5px 12px; font-size:12px; display:inline-flex; align-items:center; gap:5px; font-weight:600">
					<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
					<span>Ajukan MPR</span>
				</a>
				<a href="<?= site_url('requisitions') ?>" class="btn btn-sm btn-ghost" style="padding:5px 10px; font-size:12px">
					<span>Daftar Pengajuan</span>
				</a>
			</div>
		</div>

		<!-- 4 KPI Cards Ringkas User Dept -->
		<div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; flex-shrink:0">
			<div class="dash-box" style="padding:10px 14px; border-left:3px solid var(--accent)">
				<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700">Total Pengajuan MPR</div>
				<div class="mono" style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px">
					<?= (int) ($dept_metrics['total_mpr'] ?? 0) ?>
				</div>
				<div class="muted" style="font-size:10.5px">Seluruh riwayat permintaan</div>
			</div>
			<div class="dash-box" style="padding:10px 14px; border-left:3px solid var(--good)">
				<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700">Realisasi Pemenuhan</div>
				<div class="mono" style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px">
					<span style="color:var(--good)"><?= (int) ($dept_metrics['total_terpenuhi'] ?? 0) ?></span>
					<span class="muted" style="font-size:13px; font-weight:normal">/ <?= (int) ($dept_metrics['total_dibutuhkan'] ?? 0) ?> org</span>
				</div>
				<div class="muted" style="font-size:10.5px">Kebutuhan terisi</div>
			</div>
			<div class="dash-box" style="padding:10px 14px; border-left:3px solid var(--warn)">
				<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700">MPR Dalam Proses</div>
				<div class="mono" style="font-size:20px; font-weight:700; color:var(--warn); line-height:1.2; margin-top:2px">
					<?= (int) ($dept_metrics['mpr_aktif'] ?? 0) + (int) ($dept_metrics['mpr_pending'] ?? 0) ?>
				</div>
				<div class="muted" style="font-size:10.5px"><?= (int) ($dept_metrics['mpr_pending'] ?? 0) ?> pending / <?= (int) ($dept_metrics['mpr_aktif'] ?? 0) ?> sourcing</div>
			</div>
			<div class="dash-box" style="padding:10px 14px; border-left:3px solid var(--info)">
				<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700">Kandidat Aktif Diseleksi</div>
				<div class="mono" style="font-size:20px; font-weight:700; color:var(--info); line-height:1.2; margin-top:2px">
					<?= count($dept_candidates ?? array()) ?>
				</div>
				<div class="muted" style="font-size:10.5px">Dalam alur seleksi tim</div>
			</div>
		</div>

		<!-- 2 Panel Utama User Dept (Mengisi sisa layar dengan scroll internal) -->
		<div style="display:grid; grid-template-columns:1.2fr 1fr; gap:12px; flex:1; min-height:0">
			<!-- Panel Kiri: MPR Terbaru -->
			<div class="dash-box" style="display:flex; flex-direction:column; height:100%; min-height:0">
				<div class="dash-panel-head">
					<div style="display:flex; align-items:center; gap:6px">
						<svg style="width:14px; height:14px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
						<span style="font-size:13px; font-weight:700; color:var(--text)">Permintaan Karyawan (MPR)</span>
					</div>
					<a href="<?= site_url('requisitions') ?>" style="font-size:11.5px; text-decoration:none; color:var(--accent); font-weight:600">Semua &rarr;</a>
				</div>
				<div style="flex:1; overflow-y:auto; min-height:0">
					<?php if (empty($dept_mpr)): ?>
						<div style="padding:28px 16px; text-align:center" class="muted">
							<div style="font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:2px">Belum Ada Pengajuan</div>
							<div style="font-size:11.5px; margin-bottom:8px">Departemen belum memiliki pengajuan penambahan karyawan.</div>
							<a href="<?= site_url('requisitions/create') ?>" class="btn btn-sm btn-primary" style="padding:4px 10px; font-size:11.5px">+ Ajukan MPR</a>
						</div>
					<?php else: ?>
						<table class="side-table">
							<thead>
								<tr>
									<th>Posisi / No MPR</th>
									<th style="text-align:center">Kuota</th>
									<th style="text-align:center">Status</th>
									<th style="text-align:right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($dept_mpr as $rm): ?>
									<tr>
										<td>
											<div style="font-weight:700; color:var(--text)"><?= html_escape($rm['nama_posisi']) ?></div>
											<div class="muted mono" style="font-size:10px"><?= html_escape($rm['no_mpr'] ?: '#' . $rm['id_req']) ?> &bull; <?= html_escape($rm['tipe_penempatan']) ?></div>
										</td>
										<td style="text-align:center">
											<span class="mono" style="font-weight:600"><?= (int) $rm['jumlah_terpenuhi'] ?> / <?= (int) $rm['jumlah_dibutuhkan'] ?></span>
										</td>
										<td style="text-align:center">
											<span class="tag <?= in_array($rm['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : (in_array($rm['status_req'], array('Review_HR','Menunggu_BOD')) ? 'warn' : 'off') ?>" style="font-size:9.5px; padding:2px 6px">
												<?= html_escape($rm['status_req']) ?>
											</span>
										</td>
										<td style="text-align:right; white-space:nowrap">
											<a href="<?= site_url('requisitions/view/' . (int) $rm['id_req']) ?>" class="btn btn-sm btn-ghost" style="padding:2px 6px; font-size:11px">Detail</a>
											<?php if (in_array($rm['status_req'], array('Sourcing','Approved','Terpenuhi'))): ?>
												<a href="<?= site_url('pipeline/index/' . (int) $rm['id_req']) ?>" class="btn btn-sm btn-primary" style="padding:2px 6px; font-size:11px">Pipeline</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Panel Kanan: Pelamar Sedang Diseleksi -->
			<div class="dash-box" style="display:flex; flex-direction:column; height:100%; min-height:0">
				<div class="dash-panel-head">
					<div style="display:flex; align-items:center; gap:6px">
						<svg style="width:14px; height:14px; color:var(--info)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
						<span style="font-size:13px; font-weight:700; color:var(--text)">Pelamar Dalam Seleksi</span>
					</div>
					<span class="muted" style="font-size:11.5px"><?= count($dept_candidates ?? array()) ?> kandidat</span>
				</div>
				<div style="flex:1; overflow-y:auto; min-height:0">
					<?php if (empty($dept_candidates)): ?>
						<div style="padding:28px 16px; text-align:center" class="muted">
							<div style="font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:2px">Belum Ada Pelamar Aktif</div>
							<div style="font-size:11.5px">Kandidat yang sedang diproses oleh HR akan tampil di sini.</div>
						</div>
					<?php else: ?>
						<table class="side-table">
							<thead>
								<tr>
									<th>Kandidat</th>
									<th>Tahap Seleksi</th>
									<th style="text-align:right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($dept_candidates as $dc): ?>
									<tr>
										<td>
											<div style="font-weight:700; color:var(--text)"><?= html_escape($dc['nama_lengkap']) ?></div>
											<div class="muted" style="font-size:10px"><?= html_escape($dc['nama_posisi']) ?></div>
										</td>
										<td>
											<span class="tag info" style="font-size:9.5px; padding:2px 6px">
												<?= html_escape($dc['tahap_kini'] ?: $dc['tipe_tahap']) ?>
											</span>
										</td>
										<td style="text-align:right; white-space:nowrap">
											<a href="<?= site_url('candidates/detail/' . (int) $dc['id_lamaran']) ?>" class="btn btn-sm btn-ghost" style="padding:2px 6px; font-size:11px">Profil</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

<?php else: ?>
	<!-- =========================================================================
	     DASHBOARD ROLE: SUPER_ADMIN (FIT 1-SCREEN VIEWPORT)
	     ========================================================================= -->
	<div class="dash-screen">
		<!-- 1. Header Super Admin Kompak -->
		<div class="dash-header-bar">
			<div class="dash-title-group">
				<span class="role-badge admin">Recruitment Hub</span>
				<h1 class="dash-h1">Dashboard Operasional Rekrutmen</h1>
				<span class="faint" style="font-size:11.5px">&bull; Monitoring Funnel 7-Tahap RPG</span>
			</div>
			<div style="display:flex; gap:6px; align-items:center">
				<a class="btn btn-sm btn-ghost" href="<?= site_url('export/candidates' . '?' . http_build_query(array_filter($f))) ?>" style="padding:4px 8px; font-size:11.5px; display:inline-flex; align-items:center; gap:4px">
					<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					<span>Export</span>
				</a>
				<a class="btn btn-sm btn-ghost" href="<?= site_url('manual') ?>" style="padding:4px 8px; font-size:11.5px; display:inline-flex; align-items:center; gap:4px">
					<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
					<span>Manual</span>
				</a>
				<a class="btn btn-sm btn-primary" href="<?= site_url('requisitions/create') ?>" style="padding:4px 10px; font-size:11.5px; display:inline-flex; align-items:center; gap:4px; font-weight:600">
					<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
					<span>+ Permintaan</span>
				</a>
			</div>
		</div>

		<!-- 2. Filter Bar Ramping (1 Baris Horisontal) -->
		<div class="dash-box dash-filter-strip">
			<form method="get" action="<?= site_url('dashboard') ?>" class="dash-filter-form">
				<div class="dash-filter-item">
					<span class="dash-filter-label">DARI:</span>
					<input type="date" name="dari" value="<?= html_escape($g('dari')) ?>" class="dash-filter-input" style="width:115px">
				</div>
				<div class="dash-filter-item">
					<span class="dash-filter-label">S/D:</span>
					<input type="date" name="sampai" value="<?= html_escape($g('sampai')) ?>" class="dash-filter-input" style="width:115px">
				</div>
				<div class="dash-filter-item">
					<span class="dash-filter-label">TGL TUNGGAL:</span>
					<input type="date" name="tanggal" value="<?= html_escape($g('tanggal')) ?>" title="Filter 1 tanggal spesifik" class="dash-filter-input" style="width:115px">
				</div>

				<?php
				$fsel = array(
					'dept'   => array('Dept', $opt['dept'], 'id_departemen', 'nama'),
					'posisi' => array('Posisi', $opt['posisi'], 'id_posisi', 'nama_posisi'),
					'outlet' => array('Outlet', $opt['outlet'], 'id_outlet', 'nama_outlet'),
				);
				foreach ($fsel as $key => $c): ?>
					<div class="dash-filter-item">
						<select name="<?= $key ?>" class="dash-filter-select" style="max-width:120px">
							<option value="">Semua <?= $c[0] ?></option>
							<?php foreach ($c[1] as $o): ?>
								<option value="<?= html_escape($o[$c[2]]) ?>" <?= $sel($g($key), $o[$c[2]]) ?>><?= html_escape($o[$c[3]]) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endforeach; ?>

				<div class="dash-filter-item">
					<select name="status" class="dash-filter-select" style="max-width:110px">
						<option value="">Semua Status</option>
						<?php foreach ($status_global as $s): ?>
							<option value="<?= $s ?>" <?= $sel($g('status'), $s) ?>><?= $s ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="display:flex; gap:4px; margin-left:auto">
					<button type="submit" class="btn btn-sm btn-primary" style="padding:3px 10px; font-size:11.5px">Filter</button>
					<a class="btn btn-sm btn-ghost" href="<?= site_url('dashboard') ?>" style="padding:3px 8px; font-size:11.5px">Reset</a>
				</div>
			</form>
		</div>

		<!-- 3. KPI Strip Kompak (8 Status Lamaran) -->
		<div class="dash-kpi-strip">
			<?php
			$cards = array(
				'In_Progress'    => array('Proses', '#3a6ea5'),
				'On_Hold'        => array('Hold', '#a67c1e'),
				'Hired'          => array('Hired', '#2f7d4f'),
				'Rejected'       => array('Gugur', '#b23b3b'),
				'Offer_Declined' => array('Declined', '#b23b3b'),
				'No_Show'        => array('No Show', '#b23b3b'),
				'Talent_Pool'    => array('Talent', '#6b4fa0'),
				'Unreachable'    => array('Unreach', '#8f6410'),
			);
			foreach ($cards as $k => $info):
				$val = (int) ($met[$k] ?? 0);
			?>
				<div class="kpi-chip" style="border-left:3px solid <?= $info[1] ?>">
					<span class="kpi-chip-label"><?= $info[0] ?></span>
					<span class="kpi-chip-val" style="color:<?= $val > 0 ? 'var(--text)' : 'var(--text-faint)' ?>"><?= $val ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- 4. Area Utama 1 Layar: Matriks Funnel (Kiri) + Tabbed Widgets (Kanan) -->
		<div class="dash-grid-main">
			<!-- Panel Matriks Funnel Posisi x Tahapan -->
			<div class="dash-box dash-matrix-panel">
				<div class="dash-panel-head">
					<div style="display:flex; align-items:center; gap:8px">
						<h2 style="font-size:13.5px; font-weight:700; margin:0; color:var(--text)">Matriks Funnel: Posisi &times; Tahapan</h2>
						<span class="tag info" style="font-size:9.5px; padding:1px 5px">Dinamis</span>
						<?php if (!empty($pos_stage_funnel['dari'])): ?>
							<span class="faint" style="font-size:11px">
								(<?= html_escape($pos_stage_funnel['dari']) ?><?= $pos_stage_funnel['dari'] !== $pos_stage_funnel['sampai'] ? ' s/d ' . html_escape($pos_stage_funnel['sampai']) : '' ?>)
							</span>
						<?php endif; ?>
					</div>
					<div style="font-size:11.5px">
						<span class="muted">Total:</span>
						<span class="mono" style="font-weight:700; color:var(--accent); font-size:13px">
							<?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?> kandidat
						</span>
					</div>
				</div>

				<div class="dash-panel-body">
					<?php if (empty($pos_stage_funnel['positions']) || empty($pos_stage_funnel['stages'])): ?>
						<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; padding:20px; text-align:center" class="muted">
							<div style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px">Tidak Ada Pergerakan Kandidat</div>
							<div style="font-size:11.5px">Tidak ada lowongan atau tahapan seleksi aktif pada filter tanggal yang dipilih.</div>
						</div>
					<?php else: ?>
						<table class="matrix-table">
							<thead>
								<tr>
									<th class="col-freeze" style="min-width:180px; width:220px">Posisi Lowongan</th>
									<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
										<th style="text-align:center; min-width:85px; padding:6px 8px">
											<div style="font-size:11px; line-height:1.2"><?= html_escape($st['nama_tahap']) ?></div>
											<span class="tag <?= in_array($st['tipe_tahap'], array('OFFER', 'ONBOARD')) ? 'on' : (in_array($st['tipe_tahap'], array('INTERVIEW')) ? 'warn' : 'info') ?>" style="font-size:9px; padding:0 4px; margin-top:2px; display:inline-block">
												<?= html_escape($st['tipe_tahap']) ?>
											</span>
										</th>
									<?php endforeach; ?>
									<th style="text-align:right; width:60px">Total</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pos_stage_funnel['positions'] as $p_id => $pos): ?>
									<tr>
										<td class="col-freeze">
											<div style="font-weight:700; color:var(--text); font-size:12px; line-height:1.2">
												<?= html_escape($pos['nama_posisi']) ?>
											</div>
											<div style="display:flex; align-items:center; gap:5px; margin-top:2px; font-size:10.5px">
												<?php if (!empty($pos['departemen'])): ?>
													<span class="muted"><?= html_escape($pos['departemen']) ?></span>
												<?php endif; ?>
												<?php if (!empty($pos['id_req'])): ?>
													<span class="faint">&bull;</span>
													<a href="<?= site_url('pipeline/index/' . (int) $pos['id_req']) ?>" style="text-decoration:none; color:var(--accent); font-weight:600">
														Pipeline &rarr;
													</a>
												<?php endif; ?>
											</div>
										</td>
										<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st):
											$cell = $pos_stage_funnel['matrix'][$p_id][$st_id] ?? NULL;
											$cnt  = $cell ? (int) $cell['total'] : 0;
										?>
											<td style="text-align:center; padding:6px 4px">
												<?php if ($cnt > 0): ?>
													<span class="tag info" style="font-size:10.5px; font-weight:700; padding:1px 6px; min-width:20px; display:inline-block">
														<?= $cnt ?>
													</span>
												<?php else: ?>
													<span class="faint" style="font-size:10.5px">-</span>
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
										<td style="text-align:right; font-weight:700" class="mono">
											<?= (int) $pos['total'] ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="col-freeze" style="color:var(--text); font-size:11.5px">
										TOTAL
									</td>
									<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
										<td style="text-align:center; color:var(--accent)" class="mono">
											<?= (int) $st['total'] ?>
										</td>
									<?php endforeach; ?>
									<td style="text-align:right; color:var(--accent)" class="mono">
										<?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?>
									</td>
								</tr>
							</tfoot>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Panel Kanan: Tabbed Side Widgets (Metrik & Kepatuhan, Tertahan, Trend 14 Hari) -->
			<div class="dash-box dash-tabs-panel">
				<div class="dash-tabs-nav">
					<button type="button" class="dash-tab-btn active" onclick="switchDashTab(this, 'tab-metrik')">
						Metrik &amp; Kepatuhan
					</button>
					<button type="button" class="dash-tab-btn" onclick="switchDashTab(this, 'tab-aging')">
						Tertahan (>7 Hari)
						<?php if ($aging_count > 0): ?>
							<span class="tag off" style="font-size:9.5px; padding:1px 5px"><?= $aging_count ?></span>
						<?php endif; ?>
					</button>
					<button type="button" class="dash-tab-btn" onclick="switchDashTab(this, 'tab-trend')">
						Tren 14 Hari
					</button>
				</div>

				<!-- TAB 1: Kecepatan Proses & UU PDP -->
				<div id="tab-metrik" class="dash-tab-content active">
					<div style="margin-bottom:14px">
						<div class="faint" style="font-size:10.5px; text-transform:uppercase; font-weight:700">Durasi Pemenuhan Lowongan</div>
						<div class="mono" style="font-size:24px; font-weight:700; color:var(--accent); line-height:1.1; margin-top:2px">
							<?= $d['waktu']['rata_lama_proses_hari'] !== NULL ? round($d['waktu']['rata_lama_proses_hari'], 1) . ' <span style="font-size:13px; font-weight:normal">hari</span>' : '-' ?>
						</div>
						<div class="faint" style="font-size:11px; margin-top:2px">Dihitung sejak permohonan disetujui hingga hired.</div>
					</div>

					<div style="margin-bottom:14px">
						<div class="faint" style="font-size:10.5px; text-transform:uppercase; font-weight:700">Waktu Persetujuan BOD</div>
						<div class="mono" style="font-size:20px; font-weight:700; color:var(--text); line-height:1.1; margin-top:2px">
							<?= $d['waktu']['rata_hari_menunggu_approval'] !== NULL ? round($d['waktu']['rata_hari_menunggu_approval'], 1) . ' <span style="font-size:12px; font-weight:normal">hari</span>' : '-' ?>
						</div>
						<div class="faint" style="font-size:11px; margin-top:2px">Dari pengajuan departemen ke keputusan final.</div>
					</div>

					<div style="padding:10px 12px; background:var(--surface-2); border-radius:8px; border:1px solid var(--border)">
						<div style="display:flex; align-items:center; gap:6px; margin-bottom:4px">
							<svg style="width:14px; height:14px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
							<span style="font-weight:700; font-size:11.5px; color:var(--text)">Audit UU PDP 27/2022</span>
						</div>
						<p class="muted" style="font-size:11px; margin:0; line-height:1.35">
							Akses identitas sensitif (KTP, Finansial, Gaji) diaudit real-time di <code style="font-size:10px">ACCESS_LOG_SENSITIF</code>.
						</p>
					</div>
				</div>

				<!-- TAB 2: Peringatan Tertahan (>7 Hari) -->
				<div id="tab-aging" class="dash-tab-content">
					<?php if ( ! $d['aging']): ?>
						<div style="padding:24px 12px; text-align:center" class="muted">
							<div style="color:var(--good); font-weight:700; font-size:13px; margin-bottom:2px">&#10003; Proses Lancar</div>
							<div style="font-size:11px">Tidak ada kandidat yang tertahan lebih dari 7 hari di tahap berjalan.</div>
						</div>
					<?php else: ?>
						<table class="side-table">
							<thead>
								<tr>
									<th>Kandidat</th>
									<th>Tahap</th>
									<th style="text-align:center">Lama</th>
									<th style="text-align:right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($d['aging'] as $a): ?>
									<tr>
										<td>
											<div style="font-weight:700; color:var(--text)"><?= html_escape($a['nama_lengkap']) ?></div>
											<div class="muted" style="font-size:10px"><?= html_escape($a['nama_posisi']) ?></div>
										</td>
										<td>
											<span class="tag info" style="font-size:9.5px; padding:1px 5px"><?= html_escape($a['tipe_tahap']) ?></span>
										</td>
										<td style="text-align:center; color:var(--crit); font-weight:700" class="mono">
											<?= (int) $a['hari_di_tahap'] ?> hari
										</td>
										<td style="text-align:right">
											<a href="<?= site_url('pipeline/index/' . (int) $a['id_req']) ?>" class="btn btn-sm btn-ghost" style="padding:2px 6px; font-size:10.5px">
												Pipeline
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<!-- TAB 3: Trend 14 Hari Agregat Funnel -->
				<div id="tab-trend" class="dash-tab-content">
					<?php if ( ! $trend): ?>
						<div style="padding:24px 12px; text-align:center" class="muted">
							<div style="font-size:11.5px">Belum ada data snapshot harian.</div>
							<div class="faint" style="font-size:10px; margin-top:4px">Jalankan <code>tools/build-funnel.php</code></div>
						</div>
					<?php else: ?>
						<table class="side-table">
							<thead>
								<tr>
									<th>Tanggal</th>
									<th>Tahap</th>
									<th style="text-align:right">Volume</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($trend as $tr): ?>
									<tr>
										<td class="mono"><?= html_escape(substr($tr['tanggal'], 5)) ?></td>
										<td><span class="tag info" style="font-size:9.5px; padding:1px 5px"><?= html_escape($tr['tipe_tahap']) ?></span></td>
										<td style="text-align:right; font-weight:700" class="mono"><?= (int) $tr['jumlah'] ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<script>
	function switchDashTab(btn, tabId) {
		var nav = btn.closest('.dash-tabs-nav');
		var panel = btn.closest('.dash-tabs-panel');
		if (!nav || !panel) return;
		nav.querySelectorAll('.dash-tab-btn').forEach(function(b) { b.classList.remove('active'); });
		panel.querySelectorAll('.dash-tab-content').forEach(function(c) { c.classList.remove('active'); });
		btn.classList.add('active');
		var target = document.getElementById(tabId);
		if (target) target.classList.add('active');
	}
	</script>
<?php endif; ?>

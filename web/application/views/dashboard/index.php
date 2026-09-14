<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: dashboard/index.php -- Dasbor Operasional Utama E-Recruitment RPG
 *
 * Fungsi:
 * - Menampilkan ringkasan metrik live kandidat aktif, lowongan aktif, dan tugas harian rekrutmen.
 * - Menyajikan tabel matriks funnel seleksi per tahap dan status global pelamar.
 * - Menyediakan visualisasi ganda remark keputusan pelamar:
 *   1. Diagram Batang Vertikal Terkelompok (Grouped Column Chart per Tahapan Seleksi).
 *      - Tahap 1-6 (Intermediate): Menampilkan 2 pilar (Masih Proses vs Ditolak).
 *      - Tahap Onboarding (Final): Menampilkan pilar Diterima (Hired) vs Ditolak/Batal.
 *   2. Diagram Batang Horizontal (Ranked Horizontal Bar Chart per Alasan/Remark).
 *      - Interaktif: cross-filtering dengan klik kolom tahap, filter status/efek, pencarian, dan sorting.
 */

$g   = function ($k) use ($f) { return isset($f[$k]) ? $f[$k] : ''; };
$sel = function ($a, $b) { return (string) $a === (string) $b ? 'selected' : ''; };

$met = array();
foreach ($d['metrik'] as $m) {
	$met[$m['status_global']] = (int) $m['jumlah'];
}
$total_pelamar    = (int) array_sum($met);
$total_in_process = (int)($met['In_Progress'] ?? 0);
$total_hired      = (int)($met['Hired'] ?? 0);
?>

<style>
/* CSS Spesifik Dashboard RPG */
.dash-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 12px;
	padding: 18px 20px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.03);
	transition: border-color .15s ease, box-shadow .15s ease;
}
.dash-card:hover {
	border-color: var(--accent);
}
.kpi-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 14px 16px;
	display: flex;
	flex-direction: column;
	gap: 4px;
	transition: all .15s ease;
}
.kpi-card:hover {
	border-color: var(--accent);
	box-shadow: var(--shadow-sm);
}
@media (max-width: 640px) {
	.kpi-grid-4 { grid-template-columns: repeat(2, 1fr) !important; }
	.kpi-grid-3 { grid-template-columns: 1fr !important; }
}
.role-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-size: 11px;
	font-weight: 700;
	padding: 3px 8px;
	border-radius: 6px;
	text-transform: uppercase;
	letter-spacing: .04em;
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
.dash-table {
	width: 100%;
	border-collapse: collapse;
	margin: 0;
	font-size: 13px;
}
.dash-table th {
	padding: 10px 12px;
	font-size: 11.5px;
	text-align: left;
	background: var(--surface-2);
	border-bottom: 1px solid var(--border);
	color: var(--text-muted);
}
.dash-table td {
	padding: 10px 12px;
	border-bottom: 1px solid var(--border);
}
.dash-table tr:last-child td {
	border-bottom: none;
}
.dash-table tr:hover {
	background: var(--surface-2);
}
	/* Styling Spesifik Matriks Funnel Konversi */
	.matrix-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: auto; }
	.matrix-table th { padding: 10px 8px; vertical-align: middle; background: var(--surface-2); border: 1px solid var(--border); border-bottom: 2px solid var(--border); white-space: normal; text-align: center; }
	.matrix-table td { padding: 10px 8px; vertical-align: middle; border: 1px solid var(--border); text-align: center; }
	.matrix-col-pos { min-width: 170px; text-align: left !important; vertical-align: middle !important; }
	.matrix-col-stage { text-align: center !important; min-width: 90px; vertical-align: middle !important; }
	.matrix-stage-name { font-weight: 700; color: var(--text); font-size: 11.5px; line-height: 1.3; word-break: normal; overflow-wrap: break-word; margin: 0 auto 4px; text-align: center; }
	.matrix-stage-badge { font-size: 9px; padding: 2px 6px; display: inline-block; white-space: nowrap; letter-spacing: .03em; font-weight: 700; margin: 0 auto; }
	.matrix-val-badge { font-size: 11.5px; font-weight: 700; padding: 3px 8px; min-width: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; margin: 0 auto; text-align: center; }
	.matrix-col-total { text-align: center !important; width: 85px; min-width: 75px; vertical-align: middle !important; }

	/* Modal Popup Fullscreen Matriks */
	dialog.dialog-fullscreen {
		max-width: 96vw !important;
		width: 96vw !important;
		max-height: 92vh !important;
		height: 92vh !important;
		padding: 0 !important;
		border-radius: 12px;
		overflow: hidden;
		flex-direction: column;
		border: 1px solid var(--border);
		background: var(--surface);
		box-shadow: 0 20px 40px rgba(0,0,0,0.25);
	}
	dialog.dialog-fullscreen[open] {
		display: flex !important;
	}
	dialog.dialog-fullscreen:not([open]) {
		display: none !important;
	}
	.modal-header-matrix {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 14px 20px;
		border-bottom: 1px solid var(--border);
		background: var(--surface-2);
		flex-shrink: 0;
	}
	.modal-body-matrix {
		padding: 20px;
		overflow: auto;
		flex: 1;
		width: 100%;
		background: var(--surface);
	}

	/* Styling Visual Grafik Interaktif Distribusi Remark */
	.btn-chart-toggle {
		border: none;
		background: transparent;
		color: var(--text-muted);
		padding: 5px 12px;
		border-radius: 6px;
		font-size: 11.5px;
		font-weight: 600;
		cursor: pointer;
		transition: all .15s ease;
	}
	.btn-chart-toggle.active {
		background: var(--surface);
		color: var(--text);
		font-weight: 700;
		box-shadow: 0 1px 3px rgba(0,0,0,0.08);
	}
	.btn-chart-toggle:hover:not(.active) {
		color: var(--text);
		background: color-mix(in srgb, var(--surface) 60%, transparent);
	}

	/* 1. Grouped Column Bar Chart */
	.grouped-chart-card {
		background: var(--surface-2);
		border: 1px solid var(--border);
		border-radius: 10px;
		padding: 16px 18px;
		margin-bottom: 20px;
		transition: all .15s ease;
	}
	.stage-col-box {
		display: flex;
		flex-direction: column;
		align-items: center;
		min-width: 90px;
		flex: 1;
		cursor: pointer;
		padding: 6px 8px;
		border-radius: 8px;
		transition: background-color .15s ease, border-color .15s ease, transform .12s ease;
		border: 1px solid transparent;
	}
	.stage-col-box:hover {
		background: color-mix(in srgb, var(--surface) 80%, transparent);
		border-color: var(--border);
		transform: translateY(-2px);
	}
	.stage-col-box.selected {
		background: color-mix(in srgb, var(--accent) 12%, var(--surface));
		border-color: var(--accent);
		box-shadow: 0 2px 8px rgba(0,0,0,0.08);
	}
	.col-bar-wrap {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: flex-end;
		height: 100%;
		width: 18px;
	}
	.col-bar {
		width: 100%;
		border-radius: 4px 4px 0 0;
		transition: height .4s cubic-bezier(0.16, 1, 0.3, 1), filter .15s ease;
		min-height: 2px;
	}
	.col-bar:hover {
		filter: brightness(1.2);
	}
	.col-bar-val {
		position: absolute;
		top: -16px;
		left: 50%;
		transform: translateX(-50%);
		font-size: 10px;
		font-weight: 700;
		color: var(--text);
		line-height: 1;
		white-space: nowrap;
	}

	/* 2. Ranked Horizontal Bar Chart */
	.rem-chart-card {
		padding: 12px 16px;
		background: var(--surface);
		border: 1px solid var(--border);
		border-radius: 10px;
		transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease, background-color .15s ease;
		position: relative;
	}
	.rem-chart-card:hover {
		border-color: var(--accent);
		box-shadow: 0 3px 10px rgba(0,0,0,0.06);
		transform: translateY(-1px);
		background: color-mix(in srgb, var(--surface) 97%, var(--accent) 3%);
	}
	.rem-stage-clickable {
		cursor: pointer;
		transition: transform .12s ease, opacity .12s ease;
	}
	.rem-stage-clickable:hover {
		opacity: .85;
		transform: scale(1.04);
	}
	.rem-chart-track {
		width: 100%;
		height: 14px;
		background: var(--surface-2);
		border-radius: 7px;
		overflow: hidden;
		position: relative;
		border: 1px solid color-mix(in srgb, var(--border) 60%, transparent);
	}
	.rem-chart-bar {
		height: 100%;
		border-radius: 6px;
		transition: width .4s cubic-bezier(0.16, 1, 0.3, 1);
		display: flex;
		align-items: center;
		justify-content: flex-end;
		padding-right: 6px;
	}
	.rem-chart-bar-pct {
		font-size: 9px;
		font-weight: 700;
		color: #ffffff;
		line-height: 1;
		font-family: inherit;
		letter-spacing: .02em;
	}

	/* Mobile Ergonomics for Dashboard */
	@media (max-width: 768px) {
		.kpi-grid-4 {
			grid-template-columns: repeat(2, 1fr) !important;
			gap: 10px !important;
		}
		.kpi-grid-3 {
			grid-template-columns: 1fr !important;
			gap: 10px !important;
		}
		.dept-dash-grid {
			grid-template-columns: 1fr !important;
			gap: 16px !important;
		}
		.dash-card {
			padding: 14px !important;
			border-radius: 10px !important;
		}
		.btn-chart-toggle {
			font-size: 11px !important;
			padding: 5px 8px !important;
		}
	}
	@media (max-width: 480px) {
		.kpi-grid-4 {
			grid-template-columns: 1fr !important;
		}
	}
</style>

<?php if ($user_role === 'USER_DEPT'): ?>
	<!-- =========================================================================
	     DASHBOARD ROLE: USER_DEPT (HIRING MANAGER / PEMOHON DEPARTEMEN)
	     ========================================================================= -->
	<div style="margin-bottom:24px">
		<!-- Header User Dept -->
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap; gap:14px">
			<div>
				<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px">
					<span class="role-badge dept">Hiring Manager Portal</span>
					<span class="muted">&bull;</span>
					<span class="muted" style="font-size:12.5px">Departemen: <strong><?= html_escape($user_dept_nama ?: 'Departemen Anda') ?></strong></span>
				</div>
				<h1 style="margin:0 0 4px; font-size:24px; font-weight:700; color:var(--text); letter-spacing:-.02em">
					Selamat Datang, <?= html_escape($user_nama) ?>
				</h1>
				<p class="muted" style="margin:0; font-size:13px">
					Pantau permohonan penambahan karyawan (MPR) dan status seleksi kandidat untuk departemen Anda.
				</p>
			</div>

			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
				<a href="<?= site_url('requisitions/create') ?>" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-weight:600">
					<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
					<span>Ajukan Karyawan Baru (MPR)</span>
				</a>
				<a href="<?= site_url('requisitions') ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px">
					<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					<span>Daftar Pengajuan Saya</span>
				</a>
			</div>
		</div>

		<!-- KPI Cards Khusus User Dept -->
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:24px">
			<div class="kpi-card" style="border-left:3px solid var(--accent)">
				<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Total Pengajuan MPR</div>
				<div class="mono" style="font-size:24px; font-weight:700; color:var(--text); line-height:1.2">
					<?= (int) ($dept_metrics['total_mpr'] ?? 0) ?>
				</div>
				<div class="muted" style="font-size:11.5px">Seluruh riwayat permintaan tim</div>
			</div>

			<div class="kpi-card" style="border-left:3px solid var(--info)">
				<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Kebutuhan vs Terpenuhi</div>
				<div class="mono" style="font-size:24px; font-weight:700; color:var(--text); line-height:1.2">
					<span style="color:var(--good)"><?= (int) ($dept_metrics['total_terpenuhi'] ?? 0) ?></span>
					<span class="muted" style="font-size:16px; font-weight:normal">/ <?= (int) ($dept_metrics['total_dibutuhkan'] ?? 0) ?> org</span>
				</div>
				<div class="muted" style="font-size:11.5px">Realisasi penempatan karyawan</div>
			</div>

			<div class="kpi-card" style="border-left:3px solid var(--warn)">
				<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">MPR Dalam Proses</div>
				<div class="mono" style="font-size:24px; font-weight:700; color:var(--warn); line-height:1.2">
					<?= (int) ($dept_metrics['mpr_aktif'] ?? 0) + (int) ($dept_metrics['mpr_pending'] ?? 0) ?>
				</div>
				<div class="muted" style="font-size:11.5px"><?= (int) ($dept_metrics['mpr_pending'] ?? 0) ?> butuh persetujuan / <?= (int) ($dept_metrics['mpr_aktif'] ?? 0) ?> sourcing</div>
			</div>

			<div class="kpi-card" style="border-left:3px solid var(--good)">
				<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Kandidat Sedang Diseleksi</div>
				<div class="mono" style="font-size:24px; font-weight:700; color:var(--good); line-height:1.2">
					<?= count($dept_candidates ?? array()) ?>
				</div>
				<div class="muted" style="font-size:11.5px">Berada di alur aktif departemen</div>
			</div>
		</div>

		<!-- 2 Kolom Utama User Dept: Status MPR & Antrean Kandidat -->
		<div class="dept-dash-grid" style="display:grid; grid-template-columns:1.2fr 1fr; gap:20px; align-items:start">
			<!-- Kolom Kiri: Status MPR Departemen -->
			<div class="dash-card" style="padding:0; overflow:hidden">
				<div style="padding:14px 18px; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center">
					<div style="display:flex; align-items:center; gap:8px">
						<svg style="width:16px; height:16px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
						<h3 style="margin:0; font-size:14.5px; font-weight:700; color:var(--text)">Permintaan Karyawan Terbaru</h3>
					</div>
					<a href="<?= site_url('requisitions') ?>" style="font-size:12px; text-decoration:none; color:var(--accent); font-weight:600">Lihat Semua &rarr;</a>
				</div>

				<?php if (empty($dept_mpr)): ?>
					<div style="padding:36px 16px; text-align:center" class="muted">
						<div style="font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:4px">Belum Ada Pengajuan Karyawan</div>
						<div style="font-size:12px; margin-bottom:12px">Departemen Anda belum memiliki pengajuan penambahan karyawan aktif.</div>
						<a href="<?= site_url('requisitions/create') ?>" class="btn btn-sm btn-primary">+ Ajukan Permintaan</a>
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="overflow-x:auto">
						<table class="dash-table">
							<thead>
								<tr>
									<th>Posisi &amp; No MPR</th>
									<th style="text-align:center">Kebutuhan</th>
									<th style="text-align:center">Status</th>
									<th style="text-align:right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($dept_mpr as $rm): ?>
									<tr>
										<td>
											<div style="font-weight:700; color:var(--text)">
												<?= html_escape($rm['nama_posisi']) ?>
											</div>
											<div class="muted mono" style="font-size:11px; margin-top:2px">
												<?= html_escape($rm['no_mpr'] ?: '#' . $rm['id_req']) ?> &bull; <?= html_escape($rm['tipe_penempatan']) ?>
											</div>
										</td>
										<td style="text-align:center">
											<span class="mono" style="font-weight:600"><?= (int) $rm['jumlah_terpenuhi'] ?> / <?= (int) $rm['jumlah_dibutuhkan'] ?></span>
										</td>
										<td style="text-align:center">
											<span class="tag <?= in_array($rm['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : (in_array($rm['status_req'], array('Review_HR','Review_BOD','Revisi_HR','Revisi_BOD')) ? 'warn' : 'off') ?>" style="font-size:10.5px">
												<?= html_escape(label_status_req($rm['status_req'])) ?>
											</span>
										</td>
										<td style="text-align:right; white-space:nowrap">
											<a href="<?= site_url('requisitions/view/' . (int) $rm['id_req']) ?>" class="btn btn-sm btn-ghost" style="padding:3px 8px; font-size:11.5px">
												Detail
											</a>
											<?php if (in_array($rm['status_req'], array('Sourcing','Approved','Terpenuhi'))): ?>
												<a href="<?= site_url('pipeline/index/' . (int) $rm['id_req']) ?>" class="btn btn-sm btn-primary" style="padding:3px 8px; font-size:11.5px">
													Pipeline
												</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<!-- Kolom Kanan: Antrean Pelamar & Evaluasi Tim -->
			<div class="dash-card" style="padding:0; overflow:hidden">
				<div style="padding:14px 18px; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center">
					<div style="display:flex; align-items:center; gap:8px">
						<svg style="width:16px; height:16px; color:var(--info)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
						<h3 style="margin:0; font-size:14.5px; font-weight:700; color:var(--text)">Pelamar Dalam Seleksi</h3>
					</div>
					<span class="muted" style="font-size:12px"><?= count($dept_candidates ?? array()) ?> aktif</span>
				</div>

				<?php if (empty($dept_candidates)): ?>
					<div style="padding:36px 16px; text-align:center" class="muted">
						<div style="font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:4px">Belum Ada Pelamar Aktif</div>
						<div style="font-size:12px">Kandidat yang sedang diproses oleh HR untuk departemen Anda akan tampil di sini.</div>
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="overflow-x:auto">
						<table class="dash-table">
							<thead>
								<tr>
									<th>Kandidat</th>
									<th>Tahap Saat Ini</th>
									<th style="text-align:right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($dept_candidates as $dc): ?>
									<tr>
										<td>
											<div style="font-weight:700; color:var(--text)">
												<?= html_escape($dc['nama_lengkap']) ?>
											</div>
											<div class="muted" style="font-size:11px; margin-top:2px">
												<?= html_escape($dc['nama_posisi']) ?>
											</div>
										</td>
										<td>
											<span class="tag info" style="font-size:10.5px">
												<?= html_escape($dc['tahap_kini'] ?: $dc['tipe_tahap']) ?>
											</span>
										</td>
										<td style="text-align:right; white-space:nowrap">
											<a href="<?= site_url('candidates/detail/' . (int) $dc['id_lamaran']) ?>" class="btn btn-sm btn-ghost" style="padding:3px 8px; font-size:11.5px">
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
		</div>
	</div>

<?php else: ?>
	<!-- =========================================================================
	     DASHBOARD ROLE: SUPER_ADMIN (HR RECRUITMENT HEAD & IT ADMIN)
	     ========================================================================= -->
	<div style="margin-bottom:24px">
		<!-- Header Super Admin -->
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:14px">
			<div>
				<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px">
					<span class="role-badge admin">Recruitment Control Center</span>
					<span class="muted">&bull;</span>
					<span class="muted" style="font-size:12.5px">Enterprise Monitoring &amp; Funnel Analytics</span>
				</div>
				<h1 style="margin:0 0 4px; font-size:24px; font-weight:700; color:var(--text); letter-spacing:-.02em">
					Dashboard Rekrutmen RPG
				</h1>
				<p class="muted" style="margin:0; font-size:13px">
					Monitoring performa sourcing, alur konversi seleksi 7 tahap RPG, dan pemenuhan permohonan karyawan.
				</p>
			</div>

			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
				<a class="btn btn-sm btn-ghost" href="<?= site_url('export/candidates' . '?' . http_build_query(array_filter($f))) ?>" style="display:inline-flex; align-items:center; gap:6px; padding:7px 12px">
					<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					<span>Export CSV/Excel</span>
				</a>
				<a class="btn btn-sm btn-primary" href="<?= site_url('requisitions/create') ?>" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-weight:600">
					<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
					<span>+ Permintaan Baru</span>
				</a>
			</div>
		</div>

		<!-- KPI Utama (4 kartu simetris) -->
		<div class="kpi-grid-4" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:14px">
			<div class="kpi-card" style="border-left:3px solid var(--accent, #1f6f5c)">
				<div class="faint" style="font-size:10.5px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Total Pelamar</div>
				<div class="mono" style="font-size:26px; font-weight:700; color:var(--text); line-height:1.2"><?= $total_pelamar ?></div>
				<div class="muted" style="font-size:11px">semua status</div>
			</div>
			<?php
			$primary_cards = array(
				'In_Progress'    => array('Dalam Proses',       '#3a6ea5', 'var(--info)'),
				'Hired'          => array('Diterima (Hired)',    '#2f7d4f', 'var(--good)'),
				'Rejected'       => array('Ditolak (Gugur)',    '#b23b3b', 'var(--crit)'),
			);
			foreach ($primary_cards as $k => $info):
				$val = (int) ($met[$k] ?? 0);
			?>
			<div class="kpi-card" style="border-left:3px solid <?= $info[1] ?>">
				<div class="faint" style="font-size:10.5px; text-transform:uppercase; font-weight:700; letter-spacing:.04em"><?= $info[0] ?></div>
				<div class="mono" style="font-size:26px; font-weight:700; color:<?= $info[2] ?>; line-height:1.2"><?= $val ?></div>
				<div class="muted" style="font-size:11px">pelamar</div>
			</div>
			<?php endforeach; ?>
		</div>
		<!-- KPI Sekunder (hanya tampil jika ada nilai > 0, grid simetris) -->
		<?php
		$secondary_cards = array(
			'On_Hold'        => array('On Hold',            '#7c6316'),
			'Offer_Declined' => array('Offer Ditolak',      '#b23b3b'),
			'No_Show'        => array('No Show',            '#9a4040'),
			'Unreachable'    => array('Unreachable',        '#8f6410'),
		);
		$visible_sec = array_filter($secondary_cards, function($k) use ($met) {
			return (int) ($met[$k] ?? 0) > 0;
		}, ARRAY_FILTER_USE_KEY);
		$sec_count = count($visible_sec);
		?>
		<?php if ($sec_count): ?>
		<div style="display:grid; grid-template-columns:repeat(<?= $sec_count ?>, 1fr); gap:12px; margin-bottom:22px">
			<?php foreach ($visible_sec as $k => $info):
				$val = (int) ($met[$k] ?? 0);
			?>
			<div class="kpi-card" style="border-left:3px solid <?= $info[1] ?>; padding:10px 14px">
				<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700; letter-spacing:.04em"><?= $info[0] ?></div>
				<div class="mono" style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2"><?= $val ?></div>
				<div class="muted" style="font-size:10px">pelamar</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<!-- Filter Bar Terstruktur -->
		<div class="dash-card" style="margin-bottom:20px; padding:14px 18px">
			<form method="get" action="<?= site_url('dashboard') ?>" style="margin:0">
				<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
					<div>
						<label style="margin:0 0 4px; font-size:11px; display:block" class="faint">PERIODE MULAI (DARI)</label>
						<input type="date" name="dari" value="<?= html_escape($g('dari')) ?>" style="width:130px; padding:6px 8px; font-size:12px">
					</div>
					<div>
						<label style="margin:0 0 4px; font-size:11px; display:block" class="faint">PERIODE AKHIR (SAMPAI)</label>
						<input type="date" name="sampai" value="<?= html_escape($g('sampai')) ?>" style="width:130px; padding:6px 8px; font-size:12px">
					</div>
					<div>
						<label style="margin:0 0 4px; font-size:11px; display:block" class="faint">TANGGAL SPESIFIK</label>
						<input type="date" name="tanggal" value="<?= html_escape($g('tanggal')) ?>" title="Isi jika ingin memfilter aktivitas tepat pada 1 hari tertentu saja" style="width:130px; padding:6px 8px; font-size:12px">
					</div>
					<?php
					$fsel = array(
						'dept'   => array('Departemen', $opt['dept'], 'id_departemen', 'nama'),
						'posisi' => array('Posisi', $opt['posisi'], 'id_posisi', 'nama_posisi'),
						'outlet' => array('Outlet', $opt['outlet'], 'id_outlet', 'nama_outlet'),
					);
					foreach ($fsel as $key => $c): ?>
						<div>
							<label style="margin:0 0 4px; font-size:11px; display:block" class="faint"><?= strtoupper($c[0]) ?></label>
							<select name="<?= $key ?>" style="width:auto; min-width:130px; padding:6px 8px; font-size:12px">
								<option value="">Semua <?= $c[0] ?></option>
								<?php foreach ($c[1] as $o): ?>
									<option value="<?= html_escape($o[$c[2]]) ?>" <?= $sel($g($key), $o[$c[2]]) ?>><?= html_escape($o[$c[3]]) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endforeach; ?>
					<div>
						<label style="margin:0 0 4px; font-size:11px; display:block" class="faint">FILTER NO. MPR</label>
						<select name="id_req" style="width:auto; min-width:160px; max-width:240px; padding:6px 8px; font-size:12px">
							<option value="">Semua No. MPR</option>
							<?php if (!empty($requisitions)): ?>
								<optgroup label="MPR Aktif / Dibuka">
									<?php foreach ($requisitions as $rq): if (empty($rq['is_aktif_mpr'])) continue; ?>
										<option value="<?= (int) $rq['id_req'] ?>" <?= ((int)($f['id_req'] ?? 0)) === (int)$rq['id_req'] ? 'selected' : '' ?>>
											<?= html_escape($rq['no_mpr'] ?: '#' . $rq['id_req']) ?> &bull; <?= html_escape($rq['nama_posisi']) ?> [Aktif]
										</option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="MPR Selesai / Ditutup">
									<?php foreach ($requisitions as $rq): if (!empty($rq['is_aktif_mpr'])) continue; ?>
										<option value="<?= (int) $rq['id_req'] ?>" <?= ((int)($f['id_req'] ?? 0)) === (int)$rq['id_req'] ? 'selected' : '' ?>>
											<?= html_escape($rq['no_mpr'] ?: '#' . $rq['id_req']) ?> &bull; <?= html_escape($rq['nama_posisi']) ?> [Tutup]
										</option>
									<?php endforeach; ?>
								</optgroup>
							<?php endif; ?>
						</select>
					</div>
					<div style="display:flex; gap:6px; margin-left:auto">
						<button type="submit" class="btn btn-sm btn-primary" style="padding:6px 14px">Terapkan</button>
						<a class="btn btn-sm btn-ghost" href="<?= site_url('dashboard') ?>" style="padding:6px 12px">Reset</a>
					</div>
				</div>
			</form>
		</div>

		<!-- Funnel Konversi Dinamis (Posisi x Tahap) & Efisiensi Waktu -->
		<div style="display:block; margin-bottom:22px">
			<!-- Matriks Funnel Dinamis (Posisi yang Dibuka x Tahap Seleksi) -->
			<div class="dash-card" style="padding:18px 20px">
				<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; border-bottom:1px solid var(--border); padding-bottom:10px; flex-wrap:wrap; gap:10px">
					<div>
						<div style="display:flex; align-items:center; gap:8px">
							<h2 style="font-size:15px; font-weight:700; margin:0; color:var(--text)">Matriks Funnel Konversi: Posisi &times; Tahapan</h2>
							<span class="tag info" style="font-size:10px">Dinamis</span>
						</div>
						<div class="muted" style="font-size:12px; margin-top:2px">
							Posisi lowongan (baris) &times; tahapan seleksi aktif (kolom).
							<?php if (!empty($pos_stage_funnel['dari'])): ?>
								Filter: <strong style="color:var(--text)"><?= html_escape($pos_stage_funnel['dari']) ?></strong>
								<?php if ($pos_stage_funnel['dari'] !== $pos_stage_funnel['sampai']): ?>
									s/d <strong style="color:var(--text)"><?= html_escape($pos_stage_funnel['sampai']) ?></strong>
								<?php else: ?>
									<span class="faint">(tanggal spesifik)</span>
								<?php endif; ?>
							<?php else: ?>
								<span class="faint">Semua periode berjalan</span>
							<?php endif; ?>
							<?php if (!empty($f['id_req'])): ?>
								&bull; Filter MPR: <strong style="color:var(--accent)">#<?= (int) $f['id_req'] ?></strong>
							<?php endif; ?>
						</div>
					</div>
					<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
						<div style="text-align:right">
							<span class="muted" style="font-size:11.5px">Total Terdata:</span>
							<span class="mono" style="font-size:14px; font-weight:700; color:var(--accent); margin-left:4px">
								<?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?> kandidat
							</span>
						</div>
						<button type="button" class="btn btn-sm btn-ghost" onclick="openMatrixModal()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; font-size:12px; border:1px solid var(--border); border-radius:7px; background:var(--surface)" title="Perbesar Matriks ke Layar Penuh">
							<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
							<span>Perbesar Tampilan</span>
						</button>
					</div>
				</div>

				<?php if (empty($pos_stage_funnel['positions']) || empty($pos_stage_funnel['stages'])): ?>
					<div style="padding:32px 16px; text-align:center; background:var(--surface-2); border-radius:8px">
						<div style="font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:4px">Tidak Ada Pergerakan Kandidat</div>
						<div class="muted" style="font-size:12px">
							Tidak ada posisi atau tahapan seleksi dengan perbaruan data pada tanggal atau rentang tanggal yang dipilih.
						</div>
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="overflow-x:auto">
						<table class="dash-table matrix-table" style="width:100%">
							<thead>
								<tr>
									<th class="matrix-col-pos" style="padding:10px 12px">
										Posisi Lowongan
									</th>
									<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
										<th class="matrix-col-stage">
											<div class="matrix-stage-name"><?= html_escape($st['nama_tahap']) ?></div>
											<span class="tag matrix-stage-badge <?= in_array($st['tipe_tahap'], array('OFFER', 'ONBOARD')) ? 'on' : (in_array($st['tipe_tahap'], array('INTERVIEW')) ? 'warn' : 'info') ?>">
												<?= html_escape($st['tipe_tahap']) ?>
											</span>
										</th>
									<?php endforeach; ?>
									<th class="matrix-col-total">Total</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pos_stage_funnel['positions'] as $p_id => $pos): ?>
									<tr>
										<td class="matrix-col-pos" style="padding:10px 12px">
											<div style="font-weight:700; color:var(--text)">
												<?= html_escape($pos['nama_posisi']) ?>
											</div>
											<div style="display:flex; align-items:center; gap:6px; margin-top:2px">
												<?php if (!empty($pos['departemen'])): ?>
													<span class="muted" style="font-size:11px"><?= html_escape($pos['departemen']) ?></span>
												<?php endif; ?>
											</div>
										</td>
										<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st):
											$cell = $pos_stage_funnel['matrix'][$p_id][$st_id] ?? NULL;
											$cnt  = $cell ? (int) $cell['total'] : 0;
										?>
											<td class="matrix-col-stage">
												<?php if ($cnt > 0): ?>
													<span class="tag info matrix-val-badge">
														<?= $cnt ?>
													</span>
												<?php else: ?>
													<span class="faint" style="font-size:11px">-</span>
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
										<td class="matrix-col-total mono" style="font-weight:700">
											<?= (int) $pos['total'] ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<tr style="background:var(--surface-2); font-weight:700; border-top:2px solid var(--border)">
									<td class="matrix-col-pos" style="padding:10px 12px; color:var(--text); font-weight:700">
										TOTAL SELURUHNYA
									</td>
									<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
										<td class="matrix-col-stage mono" style="color:var(--accent); font-weight:700">
											<?= (int) $st['total'] ?>
										</td>
									<?php endforeach; ?>
									<td class="matrix-col-total mono" style="color:var(--accent); font-weight:700">
										<?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?>
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<!-- Modal Dialog Fullscreen untuk Matriks Funnel -->
			<dialog id="matrixModal" class="dialog-fullscreen">
				<div class="modal-header-matrix">
					<div>
						<div style="display:flex; align-items:center; gap:8px">
							<h2 style="font-size:16px; font-weight:700; margin:0; color:var(--text)">Matriks Funnel Konversi: Posisi &times; Tahapan (Layar Penuh)</h2>
							<span class="tag info" style="font-size:10px">Tampilan Penuh</span>
						</div>
						<div class="muted" style="font-size:12px; margin-top:3px">
							Total Terdata: <strong style="color:var(--accent)"><?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?> kandidat</strong>. Tekan <strong>ESC</strong> atau tombol tutup untuk kembali.
						</div>
					</div>
					<button type="button" class="btn btn-sm btn-ghost" onclick="closeMatrixModal()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; font-weight:600; border:1px solid var(--border); border-radius:7px">
						&times; Tutup
					</button>
				</div>
				<div class="modal-body-matrix">
					<?php if (empty($pos_stage_funnel['positions']) || empty($pos_stage_funnel['stages'])): ?>
						<div style="padding:48px 16px; text-align:center; background:var(--surface-2); border-radius:8px">
							<div style="font-size:14px; font-weight:600; color:var(--text); margin-bottom:4px">Tidak Ada Pergerakan Kandidat</div>
							<div class="muted" style="font-size:12.5px">Tidak ada data posisi atau tahapan seleksi pada filter yang dipilih.</div>
						</div>
					<?php else: ?>
						<div class="table-responsive-fit" style="overflow-x:auto; width:100%">
							<table class="dash-table matrix-table" style="width:100%">
								<thead>
									<tr>
										<th class="matrix-col-pos" style="padding:12px 14px; min-width:220px">
											Posisi Lowongan
										</th>
										<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
											<th class="matrix-col-stage" style="min-width:110px">
												<div class="matrix-stage-name" style="font-size:12px"><?= html_escape($st['nama_tahap']) ?></div>
												<span class="tag matrix-stage-badge <?= in_array($st['tipe_tahap'], array('OFFER', 'ONBOARD')) ? 'on' : (in_array($st['tipe_tahap'], array('INTERVIEW')) ? 'warn' : 'info') ?>">
													<?= html_escape($st['tipe_tahap']) ?>
												</span>
											</th>
										<?php endforeach; ?>
										<th class="matrix-col-total" style="min-width:80px">Total</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($pos_stage_funnel['positions'] as $p_id => $pos): ?>
										<tr>
											<td class="matrix-col-pos" style="padding:12px 14px">
												<div style="font-weight:700; color:var(--text); font-size:13.5px">
													<?= html_escape($pos['nama_posisi']) ?>
												</div>
												<?php if (!empty($pos['departemen'])): ?>
													<div class="muted" style="font-size:11.5px; margin-top:2px"><?= html_escape($pos['departemen']) ?></div>
												<?php endif; ?>
											</td>
											<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st):
												$cell = $pos_stage_funnel['matrix'][$p_id][$st_id] ?? NULL;
												$cnt  = $cell ? (int) $cell['total'] : 0;
											?>
												<td class="matrix-col-stage">
													<?php if ($cnt > 0): ?>
														<span class="tag info matrix-val-badge" style="font-size:12.5px; padding:4px 10px">
															<?= $cnt ?>
														</span>
													<?php else: ?>
														<span class="faint" style="font-size:12px">-</span>
													<?php endif; ?>
												</td>
											<?php endforeach; ?>
											<td class="matrix-col-total mono" style="font-weight:700; font-size:13.5px">
												<?= (int) $pos['total'] ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr style="background:var(--surface-2); font-weight:700; border-top:2px solid var(--border)">
										<td class="matrix-col-pos" style="padding:12px 14px; color:var(--text); font-weight:700">
											TOTAL SELURUHNYA
										</td>
										<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
											<td class="matrix-col-stage mono" style="color:var(--accent); font-weight:700; font-size:13.5px">
												<?= (int) $st['total'] ?>
											</td>
										<?php endforeach; ?>
										<td class="matrix-col-total mono" style="color:var(--accent); font-weight:700; font-size:13.5px">
											<?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?>
										</td>
									</tr>
								</tfoot>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</dialog>
		</div>

		<!-- Analisis & Visualisasi Remark Keputusan Pelamar (Visualisasi Ganda) -->
		<?php
		$rem_total  = (int) ($remarks_summary['total_remarked'] ?? 0);
		$rem_items  = $remarks_summary['items'] ?? array();
		$rem_stages = $remarks_summary['stages'] ?? array();
		$rem_cats   = $remarks_summary['categories'] ?? array();

		// Hitung metrik agregat cepat (3 kategori baku: Masih Proses, Diterima, Ditolak)
		$cnt_lanjut = (int) ($rem_cats['LANJUT']['count'] ?? 0);
		$cnt_hired  = (int) ($rem_cats['HIRED']['count'] ?? 0);
		$cnt_tolak  = (int) ($rem_cats['TOLAK']['count'] ?? 0);

		$pct_lanjut = $rem_total > 0 ? round(($cnt_lanjut / $rem_total) * 100, 1) : 0;
		$pct_hired  = $rem_total > 0 ? round(($cnt_hired / $rem_total) * 100, 1) : 0;
		$pct_tolak  = $rem_total > 0 ? round(($cnt_tolak / $rem_total) * 100, 1) : 0;

		// Siapkan data agregasi per tahap untuk Diagram Batang Kolom (Grouped Column Chart)
		$stage_chart_data = array();
		$max_stage_val = 1;

		foreach ($rem_stages as $st) {
			$sid = (int) $st['id_stage'];
			$stage_chart_data[$sid] = array(
				'id_stage'   => $sid,
				'nama_tahap' => $st['nama_tahap'],
				'tipe_tahap' => $st['tipe_tahap'],
				'total'      => (int) $st['total'],
				'LANJUT'     => 0,
				'HIRED'      => 0,
				'TOLAK'      => 0,
			);
		}

		foreach ($rem_items as $it) {
			$sid = (int) $it['id_stage'];
			$cat = $it['efek_status']; // LANJUT, HIRED, atau TOLAK
			$jml = (int) $it['jumlah'];
			if (isset($stage_chart_data[$sid])) {
				if (isset($stage_chart_data[$sid][$cat])) {
					$stage_chart_data[$sid][$cat] += $jml;
				}
			}
		}

		foreach ($stage_chart_data as $scd) {
			if ($scd['LANJUT'] > $max_stage_val) { $max_stage_val = $scd['LANJUT']; }
			if ($scd['HIRED']  > $max_stage_val) { $max_stage_val = $scd['HIRED']; }
			if ($scd['TOLAK']  > $max_stage_val) { $max_stage_val = $scd['TOLAK']; }
		}
		// Tentukan batas skala Y tertinggi yang simetris
		$y_max = max(4, (int)(ceil($max_stage_val / 4) * 4));
		?>
		<div class="dash-card" style="margin-top:22px; padding:20px">
			<!-- Header Section & Mode Tampilan Switcher -->
			<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:12px; flex-wrap:wrap; gap:12px">
				<div>
					<div style="display:flex; align-items:center; gap:8px">
						<svg style="width:18px; height:18px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
						<h2 style="font-size:16px; font-weight:700; margin:0; color:var(--text)">Analisis &amp; Grafik Distribusi Remark Pelamar</h2>
						<span class="tag info" style="font-size:10px">Multi-Visualisasi</span>
					</div>
					<div class="muted" style="font-size:12px; margin-top:3px">
						Visualisasi komparasi keputusan seleksi kandidat (Masih Proses, Diterima, Ditolak) per tahapan proses dan rincian alasan.
					</div>
				</div>

				<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
					<!-- Segmented Control View Switcher -->
					<div style="display:inline-flex; background:var(--surface-2); padding:3px; border-radius:8px; border:1px solid var(--border); gap:2px">
						<button type="button" class="btn-chart-toggle active" data-chart-view="both" title="Tampilkan kedua diagram batang bersamaan">
							📊 Kedua Diagram
						</button>
						<button type="button" class="btn-chart-toggle" data-chart-view="stage" title="Fokus pada diagram batang vertikal per tahapan seleksi">
							🏛️ Kolom per Tahap
						</button>
						<button type="button" class="btn-chart-toggle" data-chart-view="reason" title="Fokus pada diagram batang horizontal distribusi alasan">
							📋 Batang Alasan
						</button>
					</div>

					<div style="text-align:right">
						<span class="muted" style="font-size:11.5px">Total Terdata:</span>
						<span class="mono" style="font-size:15px; font-weight:700; color:var(--accent); margin-left:4px" id="remTotalDisplay">
							<?= $rem_total ?> kandidat
						</span>
					</div>
				</div>
			</div>

			<!-- 3 Insight Mini-Cards (Masih Proses, Diterima, Ditolak) -->
			<div class="kpi-grid-3" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:18px">
				<div style="padding:12px 14px; background:var(--surface-2); border-radius:8px; border-left:3px solid var(--info, #3a6ea5); border-top:1px solid var(--border); border-right:1px solid var(--border); border-bottom:1px solid var(--border)">
					<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Masih Proses</div>
					<div style="display:flex; align-items:baseline; gap:6px; margin-top:2px">
						<span class="mono" style="font-size:22px; font-weight:700; color:var(--info, #3a6ea5); line-height:1.2"><?= $cnt_lanjut ?></span>
						<span class="muted" style="font-size:11.5px">(<?= $pct_lanjut ?>%)</span>
					</div>
					<div class="muted" style="font-size:10.5px; margin-top:2px">Lolos tahap &amp; lanjut proses seleksi</div>
				</div>

				<div style="padding:12px 14px; background:var(--surface-2); border-radius:8px; border-left:3px solid var(--good, #2f7d4f); border-top:1px solid var(--border); border-right:1px solid var(--border); border-bottom:1px solid var(--border)">
					<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Diterima (Hired)</div>
					<div style="display:flex; align-items:baseline; gap:6px; margin-top:2px">
						<span class="mono" style="font-size:22px; font-weight:700; color:var(--good, #2f7d4f); line-height:1.2"><?= $cnt_hired ?></span>
						<span class="muted" style="font-size:11.5px">(<?= $pct_hired ?>%)</span>
					</div>
					<div class="muted" style="font-size:10.5px; margin-top:2px">Khusus Tahap Akhir Onboarding</div>
				</div>

				<div style="padding:12px 14px; background:var(--surface-2); border-radius:8px; border-left:3px solid var(--crit, #b23b3b); border-top:1px solid var(--border); border-right:1px solid var(--border); border-bottom:1px solid var(--border)">
					<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700; letter-spacing:.04em">Ditolak (Gugur)</div>
					<div style="display:flex; align-items:baseline; gap:6px; margin-top:2px">
						<span class="mono" style="font-size:22px; font-weight:700; color:var(--crit, #b23b3b); line-height:1.2"><?= $cnt_tolak ?></span>
						<span class="muted" style="font-size:11.5px">(<?= $pct_tolak ?>%)</span>
					</div>
					<div class="muted" style="font-size:10.5px; margin-top:2px">Kualifikasi, tes, atau gugur</div>
				</div>
			</div>

			<!-- Grafik Proporsi Ringkas (Stacked Comparison Bar) -->
			<?php if ($rem_total > 0): ?>
			<div style="margin-bottom:18px; padding:12px 16px; background:var(--surface-2); border-radius:8px; border:1px solid var(--border)">
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
					<span style="font-size:11.5px; font-weight:700; color:var(--text); text-transform:uppercase; letter-spacing:.03em">Komposisi Total Keputusan</span>
					<span class="muted mono" style="font-size:11px">100% (<?= $rem_total ?> kandidat)</span>
				</div>
				<!-- Visual Stacked Bar -->
				<div style="height:12px; width:100%; border-radius:6px; overflow:hidden; display:flex; background:var(--surface-3); border:1px solid var(--border)">
					<?php if ($pct_lanjut > 0): ?>
						<div style="width:<?= $pct_lanjut ?>%; background:var(--info, #3a6ea5); transition:width .4s ease" title="Masih Proses: <?= $cnt_lanjut ?> (<?= $pct_lanjut ?>%)"></div>
					<?php endif; ?>
					<?php if ($pct_hired > 0): ?>
						<div style="width:<?= $pct_hired ?>%; background:var(--good, #2f7d4f); transition:width .4s ease" title="Diterima (Onboarding): <?= $cnt_hired ?> (<?= $pct_hired ?>%)"></div>
					<?php endif; ?>
					<?php if ($pct_tolak > 0): ?>
						<div style="width:<?= $pct_tolak ?>%; background:var(--crit, #b23b3b); transition:width .4s ease" title="Ditolak: <?= $cnt_tolak ?> (<?= $pct_tolak ?>%)"></div>
					<?php endif; ?>
				</div>
				<!-- Legend Interaktif -->
				<div style="display:flex; gap:16px; margin-top:8px; flex-wrap:wrap; font-size:11px">
					<div style="display:flex; align-items:center; gap:5px">
						<span style="width:8px; height:8px; border-radius:2px; background:var(--info, #3a6ea5); display:inline-block"></span>
						<span>Masih Proses: <strong><?= $pct_lanjut ?>%</strong></span>
					</div>
					<div style="display:flex; align-items:center; gap:5px">
						<span style="width:8px; height:8px; border-radius:2px; background:var(--good, #2f7d4f); display:inline-block"></span>
						<span>Diterima (Onboarding): <strong><?= $pct_hired ?>%</strong></span>
					</div>
					<div style="display:flex; align-items:center; gap:5px">
						<span style="width:8px; height:8px; border-radius:2px; background:var(--crit, #b23b3b); display:inline-block"></span>
						<span>Ditolak: <strong><?= $pct_tolak ?>%</strong></span>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if (empty($rem_items)): ?>
				<div style="padding:36px 16px; text-align:center; background:var(--surface-2); border-radius:8px">
					<div style="font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:4px">Belum Ada Riwayat Remark Keputusan</div>
					<div class="muted" style="font-size:12px">
						Tidak ada data pelamar dengan remark keputusan pada periode atau filter yang sedang dipilih.
					</div>
				</div>
			<?php else: ?>

				<!-- =========================================================================
				     VISUALISASI 1: DIAGRAM BATANG VERTIKAL TERKELOMPOK PER TAHAPAN SELEKSI
				     ========================================================================= -->
				<div id="secGroupedStageChart" class="grouped-chart-card">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px">
						<div>
							<div style="display:flex; align-items:center; gap:6px">
								<span style="font-size:13px; font-weight:700; color:var(--text)">1. Diagram Batang per Tahapan Seleksi (Grouped Column Chart)</span>
								<span class="tag info" style="font-size:9.5px">Volume Keputusan</span>
							</div>
							<div class="muted" style="font-size:11.5px; margin-top:2px">
								Tahap 1–6 menyajikan rasio <strong>Masih Proses vs Ditolak</strong>; pilar <strong>Diterima (Hired)</strong> eksklusif hanya ada di tahap <strong>Onboarding (Final)</strong>.
							</div>
						</div>

						<!-- Legend Batang -->
						<div style="display:flex; align-items:center; gap:12px; font-size:11px; background:var(--surface); padding:5px 10px; border-radius:6px; border:1px solid var(--border)">
							<div style="display:flex; align-items:center; gap:4px" title="Kandidat yang lolos dan melanjutkan proses">
								<span style="width:9px; height:9px; background:var(--info, #3a6ea5); border-radius:2px; display:inline-block"></span>
								<span>Masih Proses (Tahap 1–6)</span>
							</div>
							<div style="display:flex; align-items:center; gap:4px" title="Kandidat yang lolos tahap akhir dan resmi dipekerjakan">
								<span style="width:9px; height:9px; background:var(--good, #2f7d4f); border-radius:2px; display:inline-block"></span>
								<span>Diterima / Hired (Khusus Onboarding)</span>
							</div>
							<div style="display:flex; align-items:center; gap:4px" title="Kandidat yang tidak lolos / gugur pada tahap bersangkutan">
								<span style="width:9px; height:9px; background:var(--crit, #b23b3b); border-radius:2px; display:inline-block"></span>
								<span>Ditolak / Gugur</span>
							</div>
						</div>
					</div>

					<!-- Area Plot Grafik Batang Vertikal -->
					<div style="position:relative; width:100%; overflow-x:auto; padding:10px 0 6px">
						<div style="min-width:<?= max(580, count($stage_chart_data) * 100) ?>px; position:relative; height:220px; display:flex; flex-direction:column; justify-content:space-between">
							<!-- Garis Kisi Horizontal (Y-Axis Gridlines & Labels) -->
							<div style="position:absolute; inset:0 0 45px 35px; pointer-events:none">
								<?php
								$grid_steps = array(1.0, 0.75, 0.5, 0.25, 0.0);
								foreach ($grid_steps as $step):
									$y_val = round($y_max * $step);
									$top_pct = round((1 - $step) * 100);
								?>
								<div style="position:absolute; top:<?= $top_pct ?>%; left:0; right:0; border-top:1px dashed color-mix(in srgb, var(--border) 65%, transparent); display:flex; align-items:center">
									<span class="mono faint" style="position:absolute; left:-32px; font-size:9.5px; transform:translateY(-50%); width:26px; text-align:right">
										<?= $y_val ?>
									</span>
								</div>
								<?php endforeach; ?>
							</div>

							<!-- Kolom Batang per Tahap -->
							<div style="position:relative; z-index:2; display:flex; gap:10px; height:100%; padding-left:35px; align-items:stretch">
								<?php foreach ($stage_chart_data as $sid => $scd):
									$isOnboard = ($scd['tipe_tahap'] === 'ONBOARD');
									$h_lanjut = $y_max > 0 ? max(0, round(($scd['LANJUT'] / $y_max) * 100)) : 0;
									$h_hired  = $y_max > 0 ? max(0, round(($scd['HIRED']  / $y_max) * 100)) : 0;
									$h_tolak  = $y_max > 0 ? max(0, round(($scd['TOLAK']  / $y_max) * 100)) : 0;
								?>
								<div class="stage-col-box"
									 data-stage-id="<?= $sid ?>"
									 data-stage-name="<?= html_escape($scd['nama_tahap']) ?>"
									 title="Klik untuk memfilter rincian alasan hanya tahap <?= html_escape($scd['nama_tahap']) ?>">
									<!-- Area Batang Vertikal -->
									<div style="position:relative; width:100%; flex:1; display:flex; align-items:flex-end; justify-content:center; gap:6px; border-bottom:2px solid var(--border); padding-bottom:1px">
										<?php if ($isOnboard): ?>
											<!-- Tahap Onboarding (Final): Diterima (Hired) vs Ditolak/Batal (Serta Masih Proses jika ada) -->
											<?php if ($scd['LANJUT'] > 0): ?>
												<div class="col-bar-wrap" title="Masih Proses Onboarding: <?= $scd['LANJUT'] ?> pelamar">
													<span class="col-bar-val mono"><?= $scd['LANJUT'] ?></span>
													<div class="col-bar" style="height:<?= $h_lanjut ?>%; background:var(--info, #3a6ea5);"></div>
												</div>
											<?php endif; ?>

											<!-- Bar Diterima (Hired) -->
											<div class="col-bar-wrap" title="Diterima (Hired): <?= $scd['HIRED'] ?> pelamar">
												<?php if ($scd['HIRED'] > 0): ?>
													<span class="col-bar-val mono"><?= $scd['HIRED'] ?></span>
												<?php endif; ?>
												<div class="col-bar" style="height:<?= $h_hired ?>%; background:var(--good, #2f7d4f);"></div>
											</div>

											<!-- Bar Ditolak / Batal Join -->
											<div class="col-bar-wrap" title="Ditolak / Batal: <?= $scd['TOLAK'] ?> pelamar">
												<?php if ($scd['TOLAK'] > 0): ?>
													<span class="col-bar-val mono"><?= $scd['TOLAK'] ?></span>
												<?php endif; ?>
												<div class="col-bar" style="height:<?= $h_tolak ?>%; background:var(--crit, #b23b3b);"></div>
											</div>
										<?php else: ?>
											<!-- Tahap Seleksi 1-6 (Intermediate): Masih Proses vs Ditolak (Tidak ada pilar Diterima) -->
											<!-- Bar 1: Masih Proses -->
											<div class="col-bar-wrap" title="Masih Proses: <?= $scd['LANJUT'] ?> pelamar">
												<?php if ($scd['LANJUT'] > 0): ?>
													<span class="col-bar-val mono"><?= $scd['LANJUT'] ?></span>
												<?php endif; ?>
												<div class="col-bar" style="height:<?= $h_lanjut ?>%; background:var(--info, #3a6ea5);"></div>
											</div>

											<!-- Bar 2: Ditolak -->
											<div class="col-bar-wrap" title="Ditolak: <?= $scd['TOLAK'] ?> pelamar">
												<?php if ($scd['TOLAK'] > 0): ?>
													<span class="col-bar-val mono"><?= $scd['TOLAK'] ?></span>
												<?php endif; ?>
												<div class="col-bar" style="height:<?= $h_tolak ?>%; background:var(--crit, #b23b3b);"></div>
											</div>
										<?php endif; ?>
									</div>

									<!-- Label Sumbu X (Nama Tahapan Seleksi) -->
									<div style="margin-top:6px; text-align:center; width:100%">
										<div style="font-weight:700; font-size:11px; color:var(--text); line-height:1.25; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?= html_escape($scd['nama_tahap']) ?>">
											<?= html_escape($scd['nama_tahap']) ?>
										</div>
										<div style="display:flex; align-items:center; justify-content:center; gap:4px; margin-top:2px">
											<?php if ($isOnboard): ?>
												<span class="tag on" style="font-size:8.5px; padding:1px 5px; line-height:1; font-weight:700" title="Tahap Final: Penentu Status Diterima / Hired">
													FINAL &bull; ONBOARD
												</span>
											<?php else: ?>
												<span class="tag info" style="font-size:8.5px; padding:1px 4px; line-height:1">
													<?= html_escape($scd['tipe_tahap']) ?>
												</span>
											<?php endif; ?>
											<span class="mono muted" style="font-size:10px">
												(<?= $scd['total'] ?>)
											</span>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- =========================================================================
				     VISUALISASI 2: DIAGRAM BATANG HORIZONTAL DISTRIBUSI ALASAN (RANKED HORIZONTAL BAR)
				     ========================================================================= -->
				<div id="secHorizontalReasonChart">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px">
						<div>
							<div style="display:flex; align-items:center; gap:6px">
								<span style="font-size:13px; font-weight:700; color:var(--text)">2. Diagram Batang Horizontal Distribusi Alasan (Ranked Bar Chart)</span>
								<span class="tag info" style="font-size:9.5px">Rincian Remark</span>
							</div>
							<div class="muted" style="font-size:11.5px; margin-top:2px">
								Perbandingan volume frekuensi setiap alasan/catatan keputusan, diranking dari yang terbanyak.
							</div>
						</div>
					</div>

					<!-- Toolbar Kontrol Interaktif (Tahap, Status, Urutan & Pencarian Alasan) -->
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px; background:var(--surface-2); padding:10px 14px; border-radius:8px; border:1px solid var(--border)">
						<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
							<!-- Filter Tahap -->
							<div style="display:flex; align-items:center; gap:6px">
								<label for="remStageSelect" style="font-size:11.5px; font-weight:600; color:var(--text-muted); white-space:nowrap; margin:0">Tahap:</label>
								<select id="remStageSelect" style="padding:6px 10px; font-size:12px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text); cursor:pointer">
									<option value="ALL">Semua Tahapan (<?= count($rem_stages) ?> tahap)</option>
									<?php foreach ($rem_stages as $st): ?>
										<option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap']) ?> (<?= (int) $st['total'] ?> kandidat)<?= ($st['tipe_tahap'] === 'ONBOARD') ? ' — [Final/Hired]' : '' ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- Filter Status/Efek -->
							<div style="display:flex; align-items:center; gap:6px">
								<label for="remCatSelect" style="font-size:11.5px; font-weight:600; color:var(--text-muted); white-space:nowrap; margin:0">Status:</label>
								<select id="remCatSelect" style="padding:6px 10px; font-size:12px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text); cursor:pointer">
									<option value="ALL">Semua Status</option>
									<option value="LANJUT">Masih Proses</option>
									<option value="HIRED">Diterima (Khusus Onboarding)</option>
									<option value="TOLAK">Ditolak / Gugur</option>
								</select>
							</div>

							<!-- Filter Urutan -->
							<div style="display:flex; align-items:center; gap:6px">
								<label for="remSortSelect" style="font-size:11.5px; font-weight:600; color:var(--text-muted); white-space:nowrap; margin:0">Urutan:</label>
								<select id="remSortSelect" style="padding:6px 10px; font-size:12px; border-radius:6px; border:1px solid var(--border); background:var(--surface); color:var(--text); cursor:pointer">
									<option value="count_desc">Volume Terbanyak</option>
									<option value="count_asc">Volume Paling Sedikit</option>
									<option value="stage">Urutan Tahap</option>
									<option value="alpha">Abjad Alasan (A-Z)</option>
								</select>
							</div>

							<button type="button" id="remResetBtn" class="btn btn-sm btn-ghost" style="display:none; padding:5px 10px; font-size:11px; border-radius:6px; border:1px solid var(--border)" title="Reset filter tahap, status &amp; pencarian">
								&times; Reset Filter
							</button>
						</div>

						<!-- Search Cepat -->
						<div style="position:relative; display:flex; align-items:center">
							<svg style="width:13px; height:13px; position:absolute; left:9px; color:var(--text-muted); pointer-events:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
							<input type="text" id="remSearchInput" placeholder="Cari alasan / keyword..." style="padding:6px 10px 6px 28px; font-size:12px; border-radius:6px; border:1px solid var(--border); width:200px; background:var(--surface); color:var(--text)">
						</div>
					</div>

					<!-- Grid Axis Skala Ukur Grafik Batang Horizontal -->
					<div style="display:flex; justify-content:space-between; padding:0 14px; margin-bottom:6px; font-size:10px" class="faint mono">
						<span>0%</span>
						<span>25%</span>
						<span>50%</span>
						<span>75%</span>
						<span>100% Relatif Thd Terbanyak</span>
					</div>

					<!-- Container Kartu Grafik Batang Horizontal Interaktif -->
					<div id="remChartList" style="display:flex; flex-direction:column; gap:10px">
						<?php
						$max_item_cnt = 1;
						foreach ($rem_items as $it) {
							if ($it['jumlah'] > $max_item_cnt) { $max_item_cnt = $it['jumlah']; }
						}
						?>
						<?php foreach ($rem_items as $idx => $it):
							$pct_bar = round(($it['jumlah'] / $max_item_cnt) * 100, 1);
							$pct_all = $rem_total > 0 ? round(($it['jumlah'] / $rem_total) * 100, 1) : 0;

							// Penentuan warna bar & kategori grup (3 kategori baku)
							$cat_group = 'LANJUT';
							$cat_label = 'Masih Proses';
							$bar_color = 'var(--info, #3a6ea5)';
							$tag_class = 'info';

							if ($it['efek_status'] === 'TOLAK') {
								$cat_group = 'TOLAK';
								$cat_label = 'Ditolak';
								$bar_color = 'var(--crit, #b23b3b)';
								$tag_class = 'off';
							} elseif ($it['efek_status'] === 'HIRED') {
								$cat_group = 'HIRED';
								$cat_label = 'Diterima (Hired)';
								$bar_color = 'var(--good, #2f7d4f)';
								$tag_class = 'on';
							}
						?>
						<div class="rem-row rem-chart-card"
							 data-cat="<?= $cat_group ?>"
							 data-stage="<?= (int) $it['id_stage'] ?>"
							 data-stagename="<?= html_escape($it['nama_tahap']) ?>"
							 data-label="<?= strtolower(html_escape($it['label'] . ' ' . $it['nama_tahap'] . ' ' . $cat_label)) ?>"
							 data-count="<?= (int) $it['jumlah'] ?>"
							 data-text="<?= html_escape($it['label']) ?>"
							 data-order="<?= $idx ?>"
							 style="border-left: 3px solid <?= $bar_color ?>;"
							 title="Tahap: <?= html_escape($it['nama_tahap']) ?> (<?= html_escape($it['tipe_tahap']) ?>) | Efek: <?= $cat_label ?> | <?= (int) $it['jumlah'] ?> pelamar (<?= $pct_all ?>% dari total)">
							<div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:8px">
								<div style="display:flex; align-items:center; gap:8px; overflow:hidden">
									<span class="tag <?= $tag_class ?> rem-stage-clickable"
										  data-stage-id="<?= (int) $it['id_stage'] ?>"
										  style="font-size:10px; font-weight:700; white-space:nowrap; padding:3px 8px; border-radius:5px"
										  title="Klik untuk filter hanya tahap <?= html_escape($it['nama_tahap']) ?>">
										<?= html_escape($it['nama_tahap']) ?>
									</span>
									<span style="font-weight:700; font-size:13px; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
										<?= html_escape($it['label']) ?>
									</span>
									<span class="muted" style="font-size:10.5px; white-space:nowrap">
										&bull; <?= $cat_label ?><?= ($it['efek_status'] === 'HIRED') ? ' (Tahap Onboarding)' : '' ?>
									</span>
								</div>
								<div style="display:flex; align-items:baseline; gap:10px; flex-shrink:0">
									<span class="mono" style="font-size:13.5px; font-weight:700; color:var(--text)">
										<?= (int) $it['jumlah'] ?> <span class="muted" style="font-size:11px; font-weight:normal">pelamar</span>
									</span>
									<span class="muted mono" style="font-size:11.5px; min-width:44px; text-align:right; font-weight:600">
										<?= $pct_all ?>%
									</span>
								</div>
							</div>
							<!-- Visual Batang Horizontal -->
							<div class="rem-chart-track">
								<div class="rem-chart-bar" style="width:<?= $pct_bar ?>%; background:<?= $bar_color ?>;">
									<?php if ($pct_bar >= 16): ?>
										<span class="rem-chart-bar-pct"><?= $pct_bar ?>%</span>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>

					<div id="remEmptyFiltered" style="display:none; padding:36px 16px; text-align:center; background:var(--surface-2); border-radius:8px; margin-top:10px">
						<div class="muted" style="font-size:12.5px">Tidak ada data alasan yang cocok dengan filter yang dipilih.</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<script>
		(function(){
			var curStage = 'ALL';
			var curCat = 'ALL';
			var curSearch = '';
			var curSort = 'count_desc';

			var secStageChart = document.getElementById('secGroupedStageChart');
			var secReasonChart = document.getElementById('secHorizontalReasonChart');
			var listContainer = document.getElementById('remChartList');
			var rows = Array.prototype.slice.call(document.querySelectorAll('.rem-row'));
			var emptyDiv = document.getElementById('remEmptyFiltered');
			var stageSelect = document.getElementById('remStageSelect');
			var catSelect = document.getElementById('remCatSelect');
			var sortSelect = document.getElementById('remSortSelect');
			var searchInput = document.getElementById('remSearchInput');
			var resetBtn = document.getElementById('remResetBtn');
			var totalDisplay = document.getElementById('remTotalDisplay');
			var stageCols = document.querySelectorAll('.stage-col-box');

			// Switcher View Mode (Kedua Diagram / Kolom per Tahap / Batang Alasan)
			var viewToggles = document.querySelectorAll('.btn-chart-toggle');
			viewToggles.forEach(function(btn) {
				btn.addEventListener('click', function() {
					viewToggles.forEach(function(b) { b.classList.remove('active'); });
					this.classList.add('active');
					var v = this.getAttribute('data-chart-view');

					if (v === 'stage') {
						if (secStageChart) secStageChart.style.display = 'block';
						if (secReasonChart) secReasonChart.style.display = 'none';
					} else if (v === 'reason') {
						if (secStageChart) secStageChart.style.display = 'none';
						if (secReasonChart) secReasonChart.style.display = 'block';
					} else {
						// both
						if (secStageChart) secStageChart.style.display = 'block';
						if (secReasonChart) secReasonChart.style.display = 'block';
					}
				});
			});

			function updateStageColHighlights() {
				stageCols.forEach(function(col) {
					if (curStage !== 'ALL' && col.getAttribute('data-stage-id') === curStage) {
						col.classList.add('selected');
					} else {
						col.classList.remove('selected');
					}
				});
			}

			function applyFilters() {
				var visibleCount = 0;
				var totalShownPelamar = 0;

				rows.forEach(function(r) {
					var matchStage = (curStage === 'ALL' || r.dataset.stage === curStage);
					var matchCat   = (curCat === 'ALL' || r.dataset.cat === curCat);
					var matchSearch = (!curSearch || r.dataset.label.indexOf(curSearch) !== -1);

					if (matchStage && matchCat && matchSearch) {
						r.style.display = 'block';
						visibleCount++;
						var cnt = parseInt(r.dataset.count, 10) || 0;
						totalShownPelamar += cnt;
					} else {
						r.style.display = 'none';
					}
				});

				if (emptyDiv) {
					emptyDiv.style.display = visibleCount === 0 ? 'block' : 'none';
				}
				if (totalDisplay) {
					totalDisplay.textContent = (visibleCount === rows.length && curStage === 'ALL' && curCat === 'ALL' && curSearch === '')
						? '<?= $rem_total ?> kandidat'
						: totalShownPelamar + ' kandidat terfilter';
				}

				// Tampilkan tombol reset jika ada filter aktif
				if (resetBtn) {
					resetBtn.style.display = (curStage !== 'ALL' || curCat !== 'ALL' || curSearch !== '') ? 'inline-block' : 'none';
				}

				updateStageColHighlights();
			}

			function sortRows() {
				if (!listContainer) return;
				var sorted = rows.slice();

				if (curSort === 'count_desc') {
					sorted.sort(function(a, b) {
						return (parseInt(b.dataset.count, 10) || 0) - (parseInt(a.dataset.count, 10) || 0);
					});
				} else if (curSort === 'count_asc') {
					sorted.sort(function(a, b) {
						return (parseInt(a.dataset.count, 10) || 0) - (parseInt(b.dataset.count, 10) || 0);
					});
				} else if (curSort === 'alpha') {
					sorted.sort(function(a, b) {
						return (a.dataset.text || '').localeCompare(b.dataset.text || '');
					});
				} else if (curSort === 'stage') {
					sorted.sort(function(a, b) {
						var sDiff = (a.dataset.stagename || '').localeCompare(b.dataset.stagename || '');
						if (sDiff !== 0) return sDiff;
						return (parseInt(b.dataset.count, 10) || 0) - (parseInt(a.dataset.count, 10) || 0);
					});
				}

				sorted.forEach(function(r) {
					listContainer.appendChild(r);
				});
			}

			if (stageSelect) {
				stageSelect.addEventListener('change', function() {
					curStage = this.value;
					applyFilters();
				});
			}

			if (catSelect) {
				catSelect.addEventListener('change', function() {
					curCat = this.value;
					applyFilters();
				});
			}

			if (sortSelect) {
				sortSelect.addEventListener('change', function() {
					curSort = this.value;
					sortRows();
				});
			}

			if (searchInput) {
				searchInput.addEventListener('input', function() {
					curSearch = this.value.trim().toLowerCase();
					applyFilters();
				});
			}

			if (resetBtn) {
				resetBtn.addEventListener('click', function() {
					curStage = 'ALL';
					curCat = 'ALL';
					curSearch = '';
					if (stageSelect) stageSelect.value = 'ALL';
					if (catSelect) catSelect.value = 'ALL';
					if (searchInput) searchInput.value = '';
					applyFilters();
				});
			}

			// Interaktivitas: klik kolom tahap pada Diagram 1 untuk memfilter Diagram 2
			stageCols.forEach(function(col) {
				col.addEventListener('click', function() {
					var sid = this.getAttribute('data-stage-id');
					if (curStage === sid) {
						// Klik ulang untuk un-filter
						curStage = 'ALL';
						if (stageSelect) stageSelect.value = 'ALL';
					} else {
						curStage = sid;
						if (stageSelect) stageSelect.value = sid;
					}
					applyFilters();
				});
			});

			// Interaktivitas: klik tag tahap pada Diagram 2
			document.querySelectorAll('.rem-stage-clickable').forEach(function(tag) {
				tag.addEventListener('click', function(e) {
					e.stopPropagation();
					var stageId = this.getAttribute('data-stage-id');
					if (stageId && stageSelect) {
						stageSelect.value = stageId;
						curStage = stageId;
						applyFilters();
					}
				});
			});
		})();
		</script>
	</div>

	<script>
	function openMatrixModal() {
		var d = document.getElementById('matrixModal');
		if (d && typeof d.showModal === 'function') {
			d.showModal();
		}
	}
	function closeMatrixModal() {
		var d = document.getElementById('matrixModal');
		if (d && typeof d.close === 'function') {
			d.close();
		}
	}
	// Tutup modal jika klik di luar area modal (backdrop)
	document.addEventListener('click', function(e) {
		var d = document.getElementById('matrixModal');
		if (d && d.open && e.target === d) {
			d.close();
		}
	});
	</script>
<?php endif; ?>

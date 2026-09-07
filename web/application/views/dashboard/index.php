<?php defined('BASEPATH') OR exit('No direct script access allowed');
$g   = function ($k) use ($f) { return isset($f[$k]) ? $f[$k] : ''; };
$sel = function ($a, $b) { return (string) $a === (string) $b ? 'selected' : ''; };

// Susun funnel matriks tipe_tahap x status
$order = array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD');
$fmat  = array();
$fstat = array();
foreach ($d['funnel'] as $r) {
	$fmat[$r['tipe_tahap']][$r['status_global']] = (int) $r['jumlah'];
	$fstat[$r['status_global']] = TRUE;
}
$fstat = array_keys($fstat); sort($fstat);

$met = array();
foreach ($d['metrik'] as $m) {
	$met[$m['status_global']] = (int) $m['jumlah'];
}
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
		<div style="display:grid; grid-template-columns:1.2fr 1fr; gap:20px; align-items:start">
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
											<span class="tag <?= in_array($rm['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : (in_array($rm['status_req'], array('Review_HR','Menunggu_BOD')) ? 'warn' : 'off') ?>" style="font-size:10.5px">
												<?= html_escape($rm['status_req']) ?>
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
					Monitoring performa sourcing, alur konversi seleksi 7 tahap, dan kepatuhan SLA antar departemen.
				</p>
			</div>

			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
				<a class="btn btn-sm btn-ghost" href="<?= site_url('export/candidates' . '?' . http_build_query(array_filter($f))) ?>" style="display:inline-flex; align-items:center; gap:6px; padding:7px 12px">
					<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					<span>Export CSV/Excel</span>
				</a>
				<a class="btn btn-sm btn-ghost" href="<?= site_url('manual') ?>" style="display:inline-flex; align-items:center; gap:6px; padding:7px 12px">
					<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
					<span>Entry Manual</span>
				</a>
				<a class="btn btn-sm btn-primary" href="<?= site_url('requisitions/create') ?>" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-weight:600">
					<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
					<span>+ Permintaan Baru</span>
				</a>
			</div>
		</div>

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
						<label style="margin:0 0 4px; font-size:11px; display:block" class="faint">STATUS GLOBAL</label>
						<select name="status" style="width:auto; padding:6px 8px; font-size:12px">
							<option value="">Semua Status</option>
							<?php foreach ($status_global as $s): ?>
								<option value="<?= $s ?>" <?= $sel($g('status'), $s) ?>><?= $s ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div style="display:flex; gap:6px; margin-left:auto">
						<button type="submit" class="btn btn-sm btn-primary" style="padding:6px 14px">Terapkan</button>
						<a class="btn btn-sm btn-ghost" href="<?= site_url('dashboard') ?>" style="padding:6px 12px">Reset</a>
					</div>
				</div>
			</form>
		</div>

		<!-- Status Grid KPI Super Admin -->
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:12px; margin-bottom:22px">
			<?php
			$cards = array(
				'In_Progress'    => array('Dalam Proses', 'var(--info)', '#3a6ea5'),
				'On_Hold'        => array('On Hold', 'var(--warn)', '#7c6316'),
				'Hired'          => array('Diterima (Hired)', 'var(--good)', '#2f7d4f'),
				'Rejected'       => array('Ditolak (Gugur)', 'var(--crit)', '#b23b3b'),
				'Offer_Declined' => array('Offer Ditolak', 'var(--crit)', '#b23b3b'),
				'No_Show'        => array('No Show', 'var(--crit)', '#b23b3b'),
				'Talent_Pool'    => array('Talent Pool', 'var(--accent)', '#6b4fa0'),
				'Unreachable'    => array('Unreachable', 'var(--text-muted)', '#8f6410'),
			);
			foreach ($cards as $k => $info):
				$val = (int) ($met[$k] ?? 0);
			?>
			<div class="kpi-card" style="border-left:3px solid <?= $info[2] ?>">
				<div class="faint" style="font-size:10px; text-transform:uppercase; font-weight:700; letter-spacing:.04em"><?= $info[0] ?></div>
				<div class="mono" style="font-size:22px; font-weight:700; color:var(--text); line-height:1.2"><?= $val ?></div>
				<div class="muted" style="font-size:11px">pelamar</div>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Funnel Konversi Dinamis (Posisi x Tahap) & Efisiensi Waktu -->
		<div style="display:grid; grid-template-columns:2.2fr 1fr; gap:18px; margin-bottom:22px; align-items:start">
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
						</div>
					</div>
					<div style="text-align:right">
						<span class="muted" style="font-size:11.5px">Total Terdata:</span>
						<span class="mono" style="font-size:14px; font-weight:700; color:var(--accent); margin-left:4px">
							<?= (int) ($pos_stage_funnel['total_all'] ?? 0) ?> kandidat
						</span>
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
						<table class="dash-table" style="white-space:nowrap">
							<thead>
								<tr>
									<th style="min-width:180px; position:sticky; left:0; z-index:2; background:var(--surface-2)">
										Posisi Lowongan
									</th>
									<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st): ?>
										<th style="text-align:center; min-width:90px; padding:8px 10px">
											<div style="font-weight:700; color:var(--text); font-size:11.5px"><?= html_escape($st['nama_tahap']) ?></div>
											<span class="tag <?= in_array($st['tipe_tahap'], array('OFFER', 'ONBOARD')) ? 'on' : (in_array($st['tipe_tahap'], array('INTERVIEW')) ? 'warn' : 'info') ?>" style="font-size:9.5px; padding:1px 5px; margin-top:3px; display:inline-block">
												<?= html_escape($st['tipe_tahap']) ?>
											</span>
										</th>
									<?php endforeach; ?>
									<th style="text-align:right; width:70px">Total</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pos_stage_funnel['positions'] as $p_id => $pos): ?>
									<tr>
										<td style="position:sticky; left:0; z-index:1; background:var(--surface)">
											<div style="font-weight:700; color:var(--text)">
												<?= html_escape($pos['nama_posisi']) ?>
											</div>
											<div style="display:flex; align-items:center; gap:6px; margin-top:2px">
												<?php if (!empty($pos['departemen'])): ?>
													<span class="muted" style="font-size:11px"><?= html_escape($pos['departemen']) ?></span>
												<?php endif; ?>
												<?php if (!empty($pos['id_req'])): ?>
													<span class="faint">&bull;</span>
													<a href="<?= site_url('pipeline/index/' . (int) $pos['id_req']) ?>" style="font-size:10.5px; text-decoration:none; color:var(--accent); font-weight:600">
														Buka Pipeline &rarr;
													</a>
												<?php endif; ?>
											</div>
										</td>
										<?php foreach ($pos_stage_funnel['stages'] as $st_id => $st):
											$cell = $pos_stage_funnel['matrix'][$p_id][$st_id] ?? NULL;
											$cnt  = $cell ? (int) $cell['total'] : 0;
										?>
											<td style="text-align:center; padding:8px 6px">
												<?php if ($cnt > 0): ?>
													<span class="tag info" style="font-size:11px; font-weight:700; padding:2px 7px; min-width:26px; display:inline-block">
														<?= $cnt ?>
													</span>
												<?php else: ?>
													<span class="faint" style="font-size:11px">-</span>
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
								<tr style="background:var(--surface-2); font-weight:700; border-top:2px solid var(--border)">
									<td style="position:sticky; left:0; z-index:1; background:var(--surface-2); color:var(--text)">
										TOTAL SELURUHNYA
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
					</div>
				<?php endif; ?>
			</div>

			<!-- Durasi Proses & Kepatuhan UU PDP -->
			<div style="display:flex; flex-direction:column; gap:16px">
				<div class="dash-card" style="padding:18px 20px">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid var(--border); padding-bottom:10px">
						<h2 style="font-size:15px; font-weight:700; margin:0; color:var(--text)">Kecepatan Proses (SLA)</h2>
						<span class="faint" style="font-size:11px; text-transform:uppercase">Time-To-Hire</span>
					</div>

					<div style="margin-bottom:16px">
						<div class="muted" style="font-size:12px; margin-bottom:4px">Rata-rata Durasi Pemenuhan Lowongan:</div>
						<div class="mono" style="font-size:26px; font-weight:700; color:var(--accent); line-height:1.1">
							<?= $d['waktu']['rata_lama_proses_hari'] !== NULL ? round($d['waktu']['rata_lama_proses_hari'], 1) . ' <span style="font-size:14px; font-weight:normal">hari</span>' : '-' ?>
						</div>
						<div class="faint" style="font-size:11px; margin-top:3px">Dihitung sejak permohonan disetujui hingga kandidat hired.</div>
					</div>

					<div>
						<div class="muted" style="font-size:12px; margin-bottom:4px">Rata-rata Waktu Persetujuan BOD:</div>
						<div class="mono" style="font-size:22px; font-weight:700; color:var(--text); line-height:1.1">
							<?= $d['waktu']['rata_hari_menunggu_approval'] !== NULL ? round($d['waktu']['rata_hari_menunggu_approval'], 1) . ' <span style="font-size:13px; font-weight:normal">hari</span>' : '-' ?>
						</div>
						<div class="faint" style="font-size:11px; margin-top:3px">Dari pengajuan departemen ke keputusan approval final.</div>
					</div>
				</div>

				<div class="dash-card" style="padding:16px 18px; background:var(--surface-2)">
					<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px">
						<svg style="width:16px; height:16px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
						<span style="font-weight:700; font-size:13px; color:var(--text)">Audit &amp; Kepatuhan UU PDP 27/2022</span>
					</div>
					<p class="muted" style="font-size:11.5px; margin:0; line-height:1.4">
						Akses terhadap identitas sensitif (KTP, Finansial, Gaji) diaudit secara otomatis di <code style="font-size:10.5px">ACCESS_LOG_SENSITIF</code>.
					</p>
				</div>
			</div>
		</div>

		<!-- Peringatan Aging SLA (> Batas M_STAGE) -->
		<div class="dash-card" style="margin-bottom:22px; padding:18px 20px">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid var(--border); padding-bottom:10px">
				<div style="display:flex; align-items:center; gap:10px">
					<svg style="width:16px; height:16px; color:<?= count($d['aging']) > 0 ? 'var(--crit)' : 'var(--good)' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
					<h2 style="font-size:15px; font-weight:700; margin:0; color:var(--text)">Peringatan Aging SLA Tahapan</h2>
					<span class="tag <?= count($d['aging']) > 0 ? 'off' : 'on' ?>" style="font-size:11px">
						<?= count($d['aging']) ?> Kandidat Melebihi SLA
					</span>
				</div>
				<span class="faint" style="font-size:12px">Batas toleransi berdasarkan konfigurasi M_STAGE</span>
			</div>

			<?php if ( ! $d['aging']): ?>
				<div style="padding:24px 16px; text-align:center; background:var(--surface-2); border-radius:8px">
					<p class="muted" style="margin:0; font-size:13px">&#10003; Semua kandidat yang sedang berjalan berada dalam batas waktu SLA yang aman.</p>
				</div>
			<?php else: ?>
				<div class="table-responsive-fit" style="overflow-x:auto">
					<table class="dash-table">
						<thead>
							<tr>
								<th>No Lamaran</th>
								<th>Nama Kandidat</th>
								<th>Posisi Lowongan</th>
								<th>Tahap Berjalan</th>
								<th style="text-align:center">Batas SLA</th>
								<th style="text-align:center">Hari di Tahap</th>
								<th style="text-align:right">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($d['aging'] as $a): ?>
							<tr>
								<td class="mono">#<?= (int) $a['id_lamaran'] ?></td>
								<td><strong><?= html_escape($a['nama_lengkap']) ?></strong></td>
								<td><?= html_escape($a['nama_posisi']) ?></td>
								<td><span class="tag info" style="font-size:10.5px"><?= html_escape($a['tipe_tahap']) ?></span></td>
								<td style="text-align:center" class="mono"><?= (int) $a['sla_hari'] ?> hari</td>
								<td style="text-align:center; color:var(--crit); font-weight:700" class="mono"><?= (int) $a['hari_di_tahap'] ?> hari</td>
								<td style="text-align:right">
									<a href="<?= site_url('pipeline/index/' . (int) $a['id_req']) ?>" class="btn btn-sm btn-ghost" style="padding:3px 8px; font-size:11.5px">
										Buka Pipeline &rarr;
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>

		<!-- Trend 14 Hari Agregat Funnel -->
		<div class="dash-card" style="padding:18px 20px">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid var(--border); padding-bottom:10px">
				<h2 style="font-size:15px; font-weight:700; margin:0; color:var(--text)">Trend Agregat Funnel Harian (14 Hari Terakhir)</h2>
				<span class="muted" style="font-size:12px">Snapshot: RPT_FUNNEL_HARIAN</span>
			</div>

			<?php if ( ! $trend): ?>
				<p class="muted" style="margin:0; font-size:12.5px">Belum ada data agregat harian. Eksekusi <code>php tools/build-funnel.php</code> untuk memperbarui rekapitulasi.</p>
			<?php else: ?>
				<div class="table-responsive-fit" style="overflow-x:auto">
					<table class="dash-table">
						<thead>
							<tr>
								<th>Tanggal</th>
								<th>Tipe Tahap</th>
								<th style="text-align:right">Volume Masuk</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($trend as $tr): ?>
								<tr>
									<td class="mono"><?= html_escape($tr['tanggal']) ?></td>
									<td><span class="tag info" style="font-size:10.5px"><?= html_escape($tr['tipe_tahap']) ?></span></td>
									<td style="text-align:right; font-weight:700" class="mono"><?= (int) $tr['jumlah'] ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

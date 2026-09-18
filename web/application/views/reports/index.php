<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: reports/index.php -- Laporan Eksekutif, Analitik Funnel Rekrutmen & KPI
 *
 * Fungsi:
 * - Menampilkan metrik KPI utama rekrutmen: Time-to-Hire, rasio konversi funnel, dan persentase pemenuhan kuota MPR.
 * - Menyajikan visualisasi chart interaktif distribusi pelamar per departemen dan efektivitas channel intake.
 * - Menyediakan fitur export data laporan teragregasi.
 */

$total_pelamar = (int) ($kpi['total_pelamar'] ?? 0);
$n_in_progress = (int) ($kpi['n_in_progress'] ?? 0);
$n_hired       = (int) ($kpi['n_hired'] ?? 0);
$n_rejected    = (int) ($kpi['n_rejected'] ?? 0);
$n_withdrawn   = (int) ($kpi['n_withdrawn'] ?? 0);


$total_target_orang    = max(1, (int) ($kpi['total_target_orang'] ?? 0));
$total_terpenuhi_orang = (int) ($kpi['total_terpenuhi_orang'] ?? 0);
$persen_pemenuhan      = min(100, (int) round(($total_terpenuhi_orang / $total_target_orang) * 100));
$sisa_kebutuhan_orang  = max(0, $total_target_orang - $total_terpenuhi_orang);

$conversion_hire_rate  = $total_pelamar > 0 ? round(($n_hired / $total_pelamar) * 100, 1) : 0;
$avg_time_to_hire      = isset($kpi['avg_time_to_hire_days']) && $kpi['avg_time_to_hire_days'] !== NULL
	? round($kpi['avg_time_to_hire_days'], 1)
	: '-';

// Skala maksimum untuk visual bar chart
$max_funnel_val = 1;
foreach ($funnel as $fn) {
	if ((int)$fn['jumlah'] > $max_funnel_val) $max_funnel_val = (int)$fn['jumlah'];
}

$max_pos_val = 1;
foreach ($top_positions as $tp) {
	if ((int)$tp['total_pelamar'] > $max_pos_val) $max_pos_val = (int)$tp['total_pelamar'];
}

$max_trend_val = 1;
foreach ($trends as $tr) {
	if ((int)$tr['total_daftar'] > $max_trend_val) $max_trend_val = (int)$tr['total_daftar'];
}
?>

<style>
/* Scoped styles for Executive Report & Charts */
.rpt-grid-kpi {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}
.rpt-kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 9px;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: var(--shadow-sm);
  transition: transform .1s ease, border-color .15s ease;
}
.rpt-kpi-card:hover {
  border-color: var(--border-strong);
}
.rpt-chart-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(430px, 1fr));
  gap: 18px;
  margin-bottom: 22px;
}
.rpt-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 20px;
  box-shadow: var(--shadow-sm);
}
.rpt-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}
.rpt-funnel-step {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 10px;
  border-radius: 7px;
  background: var(--surface-2);
  transition: background .15s ease;
}
.rpt-funnel-step:hover {
  background: var(--surface-3);
}
.rpt-pos-row {
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding: 8px 10px;
  border-radius: 7px;
  background: var(--surface-2);
}
.rpt-pos-row:hover {
  background: var(--surface-3);
}
.rpt-trend-col {
  flex: 1;
  min-width: 44px;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
  justify-content: flex-end;
  position: relative;
  z-index: 2;
}
.rpt-trend-col:hover .rpt-bar-daftar {
  filter: brightness(1.1);
}
.rpt-stacked-bar {
  display: flex;
  width: 100%;
  height: 16px;
  border-radius: 6px;
  overflow: hidden;
  background: var(--surface-2);
  border: 1px solid var(--border);
  margin-bottom: 16px;
}
.rpt-filter-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  font-size: 11.5px;
  border-radius: 999px;
  background: var(--surface-2);
  color: var(--text-muted);
  border: 1px solid var(--border);
  cursor: pointer;
  text-decoration: none !important;
}
.rpt-filter-pill:hover, .rpt-filter-pill.active {
  background: var(--accent-soft);
  color: var(--accent-ink);
  border-color: var(--accent);
}
</style>

<div style="margin-bottom:32px">
	<!-- Page Header & Action Bar -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:14px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px; color:var(--accent)">Executive Analytics &amp; Pipeline Performance</div>
			<h1 style="margin:0 0 4px; font-size:23px; font-weight:700; letter-spacing:-0.02em">Laporan &amp; Ringkasan Rekrutmen</h1>
			<p class="muted" style="margin:0; font-size:13.5px">
				Metrik konversi seleksi, tren intake kandidat, dan realisasi pemenuhan formasi MPR Ratu Pertiwi Group.
			</p>
		</div>
		<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center">
			<?php if ($can_export): ?>
				<a href="<?= site_url('reports/export_summary?' . http_build_query($f)) ?>" class="btn btn-ghost btn-sm" style="height:34px; padding:0 12px" title="Unduh ringkasan KPI dan analisis dalam format spreadsheet Excel">
					<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					<span>Export Ringkasan (.XLS)</span>
				</a>
				<a href="<?= site_url('reports/export_mpr?' . http_build_query($f)) ?>" class="btn btn-primary btn-sm" style="height:34px; padding:0 12px" title="Unduh data detail pemenuhan formasi setiap dokumen MPR">
					<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
					<span>Export Rekap MPR (.XLS)</span>
				</a>
			<?php endif; ?>
			<button type="button" onclick="window.print()" class="btn btn-ghost btn-sm" style="height:34px; padding:0 10px" title="Cetak laporan">
				<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
			</button>
		</div>
	</div>

	<!-- Filter Bar Komprehensif -->
	<div class="card" style="padding:16px 18px; margin-bottom:22px; background:var(--surface); border:1px solid var(--border)">
		<form method="get" action="<?= site_url('reports') ?>" id="form-report-filter" style="margin:0">
			<!-- Quick Presets -->
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px">
				<span class="eyebrow" style="font-size:10.5px">Filter Periode &amp; Cakupan Data</span>
				<div style="display:flex; gap:6px; flex-wrap:wrap">
					<button type="button" class="rpt-filter-pill" onclick="setPreset('today')">Hari Ini</button>
					<button type="button" class="rpt-filter-pill" onclick="setPreset('this_month')">Bulan Ini</button>
					<button type="button" class="rpt-filter-pill" onclick="setPreset('this_year')">Tahun Ini</button>
					<button type="button" class="rpt-filter-pill" onclick="setPreset('all')">Semua Waktu</button>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px; align-items:flex-end">
				<div>
					<label for="f-dari" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="muted">Dari Tanggal</label>
					<input type="date" id="f-dari" name="dari" value="<?= html_escape($f['dari'] ?? '') ?>" style="margin:0; width:100%; padding:6px 9px; font-size:12.5px">
				</div>
				<div>
					<label for="f-sampai" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="muted">Sampai Tanggal</label>
					<input type="date" id="f-sampai" name="sampai" value="<?= html_escape($f['sampai'] ?? '') ?>" style="margin:0; width:100%; padding:6px 9px; font-size:12.5px">
				</div>

				<?php if (current_user_dept() === NULL && current_user_region() === NULL): ?>
				<div>
					<label for="f-dept" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="muted">Departemen</label>
					<select id="f-dept" name="dept" style="margin:0; width:100%; padding:6px 9px; font-size:12.5px">
						<option value="">Semua Departemen</option>
						<?php foreach ($departments as $d): ?>
							<option value="<?= (int) $d['id_departemen'] ?>" <?= ((int)($f['dept'] ?? 0)) === (int)$d['id_departemen'] ? 'selected' : '' ?>>
								<?= html_escape($d['nama']) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<div>
					<label for="f-posisi" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="muted">Posisi Lowongan</label>
					<select id="f-posisi" name="posisi" style="margin:0; width:100%; padding:6px 9px; font-size:12.5px">
						<option value="">Semua Posisi</option>
						<?php foreach ($positions as $p): ?>
							<option value="<?= (int) $p['id_posisi'] ?>" <?= ((int)($f['posisi'] ?? 0)) === (int)$p['id_posisi'] ? 'selected' : '' ?>>
								<?= html_escape($p['nama_posisi']) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div>
					<label for="f-outlet" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="muted">Unit / Outlet</label>
					<select id="f-outlet" name="outlet" style="margin:0; width:100%; padding:6px 9px; font-size:12.5px">
						<option value="">Semua Outlet / HQ</option>
						<?php foreach ($outlets as $o): ?>
							<option value="<?= (int) $o['id_outlet'] ?>" <?= ((int)($f['outlet'] ?? 0)) === (int)$o['id_outlet'] ? 'selected' : '' ?>>
								<?= html_escape($o['nama_outlet']) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div>
					<label for="f-status-mpr" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="muted">Status MPR</label>
					<select id="f-status-mpr" name="status_mpr" style="margin:0; width:100%; padding:6px 9px; font-size:12.5px">
						<option value="">Semua Dokumen</option>
						<option value="BUKA" <?= ($f['status_mpr'] ?? '') === 'BUKA' ? 'selected' : '' ?>>MPR Aktif / Dibuka</option>
						<option value="TUTUP" <?= ($f['status_mpr'] ?? '') === 'TUTUP' ? 'selected' : '' ?>>MPR Selesai / Ditutup</option>
					</select>
				</div>

				<div style="display:flex; gap:6px">
					<button type="submit" class="btn btn-primary btn-sm" style="flex:1; height:34px; font-weight:600">Terapkan</button>
					<a href="<?= site_url('reports') ?>" class="btn btn-ghost btn-sm" style="height:34px; padding:0 12px; line-height:32px" title="Reset filter ke awal">Reset</a>
				</div>
			</div>
		</form>
	</div>

	<!-- KPI Summary Cards (6 Metrik Utama dengan Kontras & Visual Indikator Tegas) -->
	<div class="rpt-grid-kpi">
		<!-- 1. Total Pelamar -->
		<div class="rpt-kpi-card" style="border-top:3px solid var(--accent)">
			<div style="display:flex; justify-content:space-between; align-items:flex-start">
				<span class="muted" style="font-size:12px; font-weight:600">Total Berkas Masuk</span>
				<div style="width:30px; height:30px; border-radius:7px; background:var(--accent-soft); display:grid; place-items:center; color:var(--accent-ink)">
					<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
				</div>
			</div>
			<div style="margin-top:10px">
				<div class="mono" style="font-size:26px; font-weight:700; color:var(--text); line-height:1"><?= number_format($total_pelamar) ?></div>
				<div class="faint" style="font-size:11.5px; margin-top:5px">Volume berkas terdaftar</div>
			</div>
		</div>

		<!-- 2. Kandidat Aktif di Pipeline -->
		<div class="rpt-kpi-card" style="border-top:3px solid var(--info)">
			<div style="display:flex; justify-content:space-between; align-items:flex-start">
				<span class="muted" style="font-size:12px; font-weight:600">Aktif Seleksi (Pipeline)</span>
				<div style="width:30px; height:30px; border-radius:7px; background:var(--info-soft); display:grid; place-items:center; color:var(--info)">
					<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
				</div>
			</div>
			<div style="margin-top:10px">
				<div class="mono" style="font-size:26px; font-weight:700; color:var(--info); line-height:1"><?= number_format($n_in_progress) ?></div>
				<div class="faint" style="font-size:11.5px; margin-top:5px">
					<strong style="color:var(--text)"><?= $total_pelamar > 0 ? round(($n_in_progress / $total_pelamar) * 100, 1) : 0 ?>%</strong> dari intake berkas
				</div>
			</div>
		</div>

		<!-- 3. Berhasil Direkrut (Hired) -->
		<div class="rpt-kpi-card" style="border-top:3px solid var(--good)">
			<div style="display:flex; justify-content:space-between; align-items:flex-start">
				<span class="muted" style="font-size:12px; font-weight:600">Lolos &amp; Bergabung (Hired)</span>
				<div style="width:30px; height:30px; border-radius:7px; background:var(--good-soft); display:grid; place-items:center; color:var(--good)">
					<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
				</div>
			</div>
			<div style="margin-top:10px">
				<div class="mono" style="font-size:26px; font-weight:700; color:var(--good); line-height:1"><?= number_format($n_hired) ?></div>
				<div class="faint" style="font-size:11.5px; margin-top:5px">
					Konversi: <strong style="color:var(--good)"><?= $conversion_hire_rate ?>%</strong> pelamar
				</div>
			</div>
		</div>

		<!-- 4. Pemenuhan Formasi MPR -->
		<div class="rpt-kpi-card" style="border-top:3px solid var(--accent-ink)">
			<div style="display:flex; justify-content:space-between; align-items:flex-start">
				<span class="muted" style="font-size:12px; font-weight:600">Pemenuhan Formasi</span>
				<div style="width:30px; height:30px; border-radius:7px; background:var(--accent-soft); display:grid; place-items:center; color:var(--accent)">
					<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
				</div>
			</div>
			<div style="margin-top:10px">
				<div class="mono" style="font-size:26px; font-weight:700; color:<?= $persen_pemenuhan >= 100 ? 'var(--good)' : 'var(--accent)' ?>; line-height:1"><?= $persen_pemenuhan ?>%</div>
				<div class="faint" style="font-size:11.5px; margin-top:5px">
					<strong style="color:var(--text)"><?= $total_terpenuhi_orang ?></strong> / <?= $total_target_orang ?> personil terisi
				</div>
			</div>
		</div>

		<!-- 5. Durasi Proses (Time to Hire) -->
		<div class="rpt-kpi-card" style="border-top:3px solid var(--warn)">
			<div style="display:flex; justify-content:space-between; align-items:flex-start">
				<span class="muted" style="font-size:12px; font-weight:600">Kecepatan Rekrut (Avg)</span>
				<div style="width:30px; height:30px; border-radius:7px; background:var(--warn-soft); display:grid; place-items:center; color:var(--warn)">
					<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
				</div>
			</div>
			<div style="margin-top:10px">
				<div class="mono" style="font-size:26px; font-weight:700; color:var(--text); line-height:1">
					<?= $avg_time_to_hire ?> <span style="font-size:14px; font-weight:500; color:var(--text-muted)">hari</span>
				</div>
				<div class="faint" style="font-size:11.5px; margin-top:5px">Time to hire rata-rata</div>
			</div>
		</div>

		<!-- 6. Sisa Kebutuhan Formasi -->
		<div class="rpt-kpi-card" style="border-top:3px solid var(--border-strong)">
			<div style="display:flex; justify-content:space-between; align-items:flex-start">
				<span class="muted" style="font-size:12px; font-weight:600">Sisa Kebutuhan</span>
				<div style="width:30px; height:30px; border-radius:7px; background:var(--surface-2); display:grid; place-items:center; color:var(--text-muted)">
					<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
				</div>
			</div>
			<div style="margin-top:10px">
				<div class="mono" style="font-size:26px; font-weight:700; color:<?= $sisa_kebutuhan_orang > 0 ? 'var(--crit)' : 'var(--good)' ?>; line-height:1">
					<?= number_format($sisa_kebutuhan_orang) ?> <span style="font-size:14px; font-weight:500; color:var(--text-muted)">orang</span>
				</div>
				<div class="faint" style="font-size:11.5px; margin-top:5px">Target posisi yang belum terisi</div>
			</div>
		</div>
	</div>

	<!-- SECTION VISUAL CHARTS (BARIS 1: Funnel Alur Seleksi + Top Posisi Terfavorit) -->
	<div class="rpt-chart-grid">
		<!-- Chart 1: Funnel Alur Seleksi (7 Tahapan dengan Visual Step-down) -->
		<div class="rpt-card">
			<div class="rpt-card-header">
				<div>
					<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">Funnel Alur Seleksi</h3>
					<div class="muted" style="font-size:12px; margin-top:2px">Distribusi kandidat di 7 tahapan proses rekrutmen RPG</div>
				</div>
				<span class="tag tag--info" style="font-size:11px"><?= count($funnel) ?> Tahap Baku</span>
			</div>

			<div style="display:flex; flex-direction:column; gap:10px">
				<?php
				$prev_val = null;
				$stage_colors = array(
					'SCREENING' => '#1f6f5c',
					'KONTAK'    => '#27836d',
					'FORM'      => '#3a6ea5',
					'TEST'      => '#4f83b6',
					'INTERVIEW' => '#6b4fa0',
					'OFFER'     => '#8f6410',
					'ONBOARD'   => '#2f7d4f',
				);
				foreach ($funnel as $idx => $fn):
					$tipe      = $fn['tipe'];
					$cnt       = (int) $fn['jumlah'];
					$pct_total = $total_pelamar > 0 ? round(($cnt / $total_pelamar) * 100, 1) : 0;
					$bar_width = min(100, max(5, round(($cnt / $max_funnel_val) * 100)));
					$color     = $stage_colors[$tipe] ?? 'var(--accent)';

					// Drop-off rate dari tahap sebelumnya
					$pass_rate = '-';
					if ($prev_val !== null && $prev_val > 0) {
						$pass_rate = round(($cnt / $prev_val) * 100, 1) . '%';
					} elseif ($prev_val !== null && $prev_val === 0) {
						$pass_rate = '0%';
					}
					$prev_val = $cnt;
				?>
				<div class="rpt-funnel-step">
					<div style="width:22px; height:22px; border-radius:50%; background:var(--surface); border:1px solid var(--border); display:grid; place-items:center; font-size:11px; font-weight:700; flex:none; color:var(--text)">
						<?= $idx + 1 ?>
					</div>
					<div style="flex:1; min-width:0">
						<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px">
							<div style="display:flex; align-items:center; gap:6px">
								<strong style="font-size:12.5px; color:var(--text)"><?= html_escape($tipe) ?></strong>
								<?php if ($idx > 0 && $pass_rate !== '-'): ?>
									<span class="faint" style="font-size:10.5px" title="Tingkat lolos dari tahap sebelumnya">(&darr; <?= $pass_rate ?>)</span>
								<?php endif; ?>
							</div>
							<div class="mono" style="font-size:12.5px; font-weight:700; color:var(--text)">
								<?= number_format($cnt) ?> <span class="muted" style="font-size:11px; font-weight:500">(<?= $pct_total ?>%)</span>
							</div>
						</div>
						<!-- Visual Progress Bar with Strong Color Track -->
						<div style="width:100%; height:9px; background:var(--surface); border-radius:999px; overflow:hidden; border:1px solid var(--border)">
							<div style="width:<?= $bar_width ?>%; height:100%; background:<?= $color ?>; border-radius:999px; transition:width .25s ease"></div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:10px; border-top:1px solid var(--border); font-size:11.5px" class="muted">
				<span>* % menunjukkan porsi terhadap total berkas pendaftar</span>
				<span>Output Hired: <strong style="color:var(--good)"><?= number_format($n_hired) ?></strong></span>
			</div>
		</div>

		<!-- Chart 2: Top Posisi Paling Diminati -->
		<div class="rpt-card">
			<div class="rpt-card-header">
				<div>
					<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">Posisi Lowongan Terfavorit</h3>
					<div class="muted" style="font-size:12px; margin-top:2px">Formasi dengan volume pendaftar tertinggi</div>
				</div>
				<span class="tag tag--accent" style="font-size:11px">Peminat Terbanyak</span>
			</div>

			<?php if (empty($top_positions)): ?>
				<div style="text-align:center; padding:50px 0; color:var(--text-muted)">
					<svg style="width:36px; height:36px; margin-bottom:8px; opacity:.4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					<div>Tidak ada data lowongan pada filter ini.</div>
				</div>
			<?php else: ?>
				<div style="display:flex; flex-direction:column; gap:10px">
					<?php foreach ($top_positions as $i => $tp):
						$bar_pos = min(100, max(6, round(($tp['total_pelamar'] / $max_pos_val) * 100)));
						$pct_pos = $total_pelamar > 0 ? round(($tp['total_pelamar'] / $total_pelamar) * 100, 1) : 0;
					?>
					<div class="rpt-pos-row">
						<div style="display:flex; justify-content:space-between; align-items:center; font-size:12.5px">
							<div style="display:flex; align-items:center; gap:8px; min-width:0">
								<span style="width:20px; height:20px; border-radius:5px; background:var(--surface); border:1px solid var(--border); display:grid; place-items:center; font-size:11px; font-weight:700; flex:none; color:var(--text)">
									<?= $i + 1 ?>
								</span>
								<div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
									<strong style="color:var(--text)"><?= html_escape($tp['nama_posisi']) ?></strong>
									<span class="faint" style="font-size:11.5px"> &bull; <?= html_escape($tp['nama_departemen'] ?: '-') ?></span>
								</div>
							</div>
							<div style="display:flex; align-items:center; gap:8px; flex:none">
								<span class="tag tag--info" style="font-size:11px; padding:1px 6px" title="Sedang aktif di pipeline"><?= (int)$tp['n_aktif'] ?> aktif</span>
								<span class="tag tag--good" style="font-size:11px; padding:1px 6px" title="Berhasil direkrut"><?= (int)$tp['n_hired'] ?> hired</span>
								<strong class="mono" style="font-size:13px; color:var(--text)"><?= number_format($tp['total_pelamar']) ?></strong>
							</div>
						</div>
						<!-- Progress Bar dengan Track Jelas -->
						<div style="width:100%; height:7px; background:var(--surface); border-radius:999px; overflow:hidden; border:1px solid var(--border)">
							<div style="width:<?= $bar_pos ?>%; height:100%; background:var(--info); border-radius:999px; transition:width .25s ease"></div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>

				<div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:10px; border-top:1px solid var(--border); font-size:11.5px" class="muted">
					<span>Menampilkan <?= count($top_positions) ?> posisi dengan pelamar tertinggi</span>
					<span>Target terisi: <strong style="color:var(--good)"><?= $total_terpenuhi_orang ?> personil</strong></span>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- SECTION VISUAL CHARTS (BARIS 2: Tren Bulanan Bar Chart + Segmented Status Outcome) -->
	<div class="rpt-chart-grid">
		<!-- Chart 3: Tren Intake Bulanan vs Hired (dengan Sumbu & Gridlines yang Jelas) -->
		<div class="rpt-card">
			<div class="rpt-card-header">
				<div>
					<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">Tren Volume Pendaftaran Bulanan</h3>
					<div class="muted" style="font-size:12px; margin-top:2px">Aktivitas pelamar masuk (Intake) dibandingkan dengan kandidat yang diterima (Hired)</div>
				</div>
				<div style="display:flex; gap:12px; align-items:center; font-size:11.5px">
					<div style="display:flex; align-items:center; gap:5px">
						<span style="width:9px; height:9px; background:var(--accent); border-radius:2px; display:inline-block"></span>
						<span style="font-weight:600">Pelamar</span>
					</div>
					<div style="display:flex; align-items:center; gap:5px">
						<span style="width:9px; height:9px; background:var(--good); border-radius:2px; display:inline-block"></span>
						<span style="font-weight:600">Hired</span>
					</div>
				</div>
			</div>

			<?php if (empty($trends)): ?>
				<div style="text-align:center; padding:50px 0; color:var(--text-muted)">
					<svg style="width:36px; height:36px; margin-bottom:8px; opacity:.4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
					<div>Belum ada data tren pendaftaran untuk periode ini.</div>
				</div>
			<?php else: ?>
				<!-- Chart Container with Gridlines -->
				<div style="position:relative; height:185px; padding:12px 10px 0; margin-bottom:8px">
					<!-- Horizontal Guide Lines -->
					<div style="position:absolute; top:20px; left:0; right:0; border-top:1px dashed var(--border); pointer-events:none; opacity:.6"></div>
					<div style="position:absolute; top:75px; left:0; right:0; border-top:1px dashed var(--border); pointer-events:none; opacity:.6"></div>
					<div style="position:absolute; top:130px; left:0; right:0; border-top:1px dashed var(--border); pointer-events:none; opacity:.6"></div>

					<!-- Bar Columns Container -->
					<div style="display:flex; align-items:flex-end; gap:14px; height:150px; border-bottom:2px solid var(--border-strong); overflow-x:auto">
						<?php foreach ($trends as $tr):
							$daftar = (int) $tr['total_daftar'];
							$hired  = (int) $tr['total_hired'];
							$h_daftar = min(100, max(8, round(($daftar / $max_trend_val) * 100)));
							$h_hired  = min(100, max(0, round(($hired / $max_trend_val) * 100)));
						?>
						<div class="rpt-trend-col">
							<!-- Label Angka di Atas Batang -->
							<div class="mono" style="font-size:11px; font-weight:700; color:var(--text); margin-bottom:4px; line-height:1"><?= $daftar ?></div>
							<!-- Bar Groups -->
							<div style="display:flex; gap:3px; align-items:flex-end; width:100%; justify-content:center; height:100%">
								<!-- Bar Total Pelamar -->
								<div class="rpt-bar-daftar" style="width:16px; height:<?= $h_daftar ?>%; background:var(--accent); border-radius:4px 4px 0 0; transition:height .25s ease" title="<?= $daftar ?> Berkas Masuk"></div>
								<!-- Bar Hired -->
								<?php if ($hired > 0): ?>
									<div style="width:16px; height:<?= $h_hired ?>%; background:var(--good); border-radius:4px 4px 0 0; transition:height .25s ease" title="<?= $hired ?> Hired"></div>
								<?php endif; ?>
							</div>
							<!-- Label Periode Bulan -->
							<div class="muted mono" style="font-size:10.5px; font-weight:600; margin-top:8px; white-space:nowrap; transform:translateY(4px)">
								<?= html_escape($tr['periode_bulan'] ?: '-') ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:10px; border-top:1px solid var(--border); font-size:11.5px" class="muted">
					<span>Total Data Titik Waktu: <strong><?= count($trends) ?> Periode</strong></span>
					<span>Puncak Intake: <strong style="color:var(--accent)"><?= $max_trend_val ?> berkas / bulan</strong></span>
				</div>
			<?php endif; ?>
		</div>

		<!-- Chart 4: Segmented Bar & Distribusi Status Akhir Pelamar -->
		<div class="rpt-card">
			<div class="rpt-card-header">
				<div>
					<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">Outcome &amp; Distribusi Status</h3>
					<div class="muted" style="font-size:12px; margin-top:2px">Proporsi hasil seleksi seluruh berkas di database</div>
				</div>
				<span class="tag tag--info" style="font-size:11px">Outcome Pelamar</span>
			</div>

			<!-- Stacked Segmented Composition Bar (Memberikan pandangan visual 1-detik langsung proporsi) -->
			<?php
			$pct_hired     = $total_pelamar > 0 ? round(($n_hired / $total_pelamar) * 100, 1) : 0;
			$pct_active    = $total_pelamar > 0 ? round(($n_in_progress / $total_pelamar) * 100, 1) : 0;
			
			$pct_rejected  = $total_pelamar > 0 ? round(($n_rejected / $total_pelamar) * 100, 1) : 0;
			$pct_withdrawn = $total_pelamar > 0 ? round(($n_withdrawn / $total_pelamar) * 100, 1) : 0;
			?>
			<div class="rpt-stacked-bar" title="Visualisasi Proporsi Status">
				<?php if ($pct_hired > 0): ?>
					<div style="width:<?= $pct_hired ?>%; height:100%; background:var(--good)" title="Hired: <?= $pct_hired ?>%"></div>
				<?php endif; ?>
				<?php if ($pct_active > 0): ?>
					<div style="width:<?= $pct_active ?>%; height:100%; background:var(--info)" title="In Progress: <?= $pct_active ?>%"></div>
				<?php endif; ?>
				<?php if ($pct_withdrawn > 0): ?>
					<div style="width:<?= $pct_withdrawn ?>%; height:100%; background:var(--warn)" title="Withdrawn/Declined: <?= $pct_withdrawn ?>%"></div>
				<?php endif; ?>
				<?php if ($pct_rejected > 0): ?>
					<div style="width:<?= $pct_rejected ?>%; height:100%; background:var(--crit)" title="Rejected: <?= $pct_rejected ?>%"></div>
				<?php endif; ?>
			</div>

			<!-- Breakdown List Status -->
			<div style="display:flex; flex-direction:column; gap:8px">
				<?php foreach ($statuses as $st):
					$st_name = $st['status_global'];
					$cnt = (int) $st['jumlah'];
					$pct = $total_pelamar > 0 ? round(($cnt / $total_pelamar) * 100, 1) : 0;

					$color_bullet = 'var(--text-muted)';
					$tag_class = 'off';
					if ($st_name === 'Hired') { $tag_class = 'on'; $color_bullet = 'var(--good)'; }
					elseif ($st_name === 'In_Progress') { $tag_class = 'info'; $color_bullet = 'var(--info)'; }
					elseif (in_array($st_name, array('On_Hold', 'Unreachable', 'Withdrawn', 'Offer_Declined', 'No_Show'))) { $tag_class = 'warn'; $color_bullet = 'var(--warn)'; }
					elseif ($st_name === 'Rejected') { $tag_class = 'off'; $color_bullet = 'var(--crit)'; }
				?>
				<div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:7px 10px; border-radius:6px; background:var(--surface-2)">
					<div style="display:flex; align-items:center; gap:8px">
						<span style="width:8px; height:8px; border-radius:50%; background:<?= $color_bullet ?>; flex:none"></span>
						<span class="tag <?= $tag_class ?>" style="font-size:11px; padding:2px 8px"><?= html_escape(label_status($st_name)) ?></span>
					</div>
					<div style="display:flex; align-items:center; gap:10px">
						<strong class="mono" style="color:var(--text); font-size:13px"><?= number_format($cnt) ?></strong>
						<span class="muted mono" style="font-size:11.5px; min-width:44px; text-align:right"><?= $pct ?>%</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:10px; border-top:1px solid var(--border); font-size:11.5px" class="muted">
				<span>Total Status Terkatalog: <strong><?= count($statuses) ?> Kategori</strong></span>
				<span>Peluang Hire: <strong style="color:var(--good)"><?= $conversion_hire_rate ?>%</strong></span>
			</div>
		</div>
	</div>

	<!-- SECTION TABEL ANALISIS DETAIL: Rekap Pemenuhan Dokumen MPR & Rekap Departemen -->
	<div class="card" style="padding:0; overflow:hidden; border-radius:10px; border:1px solid var(--border); box-shadow:var(--shadow-sm)">
		<!-- Header Tab Navigation -->
		<div style="display:flex; border-bottom:1px solid var(--border); background:var(--surface-2); padding:4px 12px 0; gap:4px">
			<button type="button" class="tab-btn active" onclick="switchReportTab('tab-mpr')" id="btn-tab-mpr" style="padding:11px 18px; font-size:13px; font-weight:700; border:none; background:var(--surface); color:var(--accent); border-radius:8px 8px 0 0; border:1px solid var(--border); border-bottom-color:var(--surface); margin-bottom:-1px; cursor:pointer">
				Rekap Pemenuhan Dokumen MPR (<?= count($mpr_rows) ?>)
			</button>
			<button type="button" class="tab-btn" onclick="switchReportTab('tab-dept')" id="btn-tab-dept" style="padding:11px 18px; font-size:13px; font-weight:600; border:none; background:transparent; color:var(--text-muted); border-radius:8px 8px 0 0; cursor:pointer">
				Ringkasan per Departemen (<?= count($dept_summary) ?>)
			</button>
		</div>

		<!-- KONTEN TAB 1: REKAP DETAIL PEMENUHAN FORMASI DOKUMEN MPR -->
		<div id="tab-mpr" style="display:block; padding:0">
			<div style="overflow-x:auto">
				<table style="width:100%; border-collapse:collapse; margin:0; font-size:13px">
					<thead>
						<tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
							<th style="text-align:left; padding:12px 14px">Dokumen MPR &amp; Posisi</th>
							<th style="text-align:left; padding:12px 14px">Departemen &amp; Penempatan</th>
							<th style="text-align:center; padding:12px 10px">Tgl Pengajuan</th>
							<th style="text-align:center; padding:12px 10px">Target</th>
							<th style="text-align:center; padding:12px 10px">Hired</th>
							<th style="text-align:center; padding:12px 12px; min-width:140px">Pemenuhan (%)</th>
							<th style="text-align:center; padding:12px 10px">Pipeline Aktif</th>
							<th style="text-align:center; padding:12px 10px">Status Dokumen</th>
							<th style="text-align:right; padding:12px 14px">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($mpr_rows)): ?>
							<tr>
								<td colspan="9" style="text-align:center; padding:45px 20px; color:var(--text-muted)">
									Tidak ada dokumen MPR yang cocok dengan filter yang ditentukan.
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($mpr_rows as $mr):
								$target    = (int) ($mr['jumlah_disetujui'] ?: $mr['jumlah_dibutuhkan']);
								$terpenuhi = (int) $mr['jumlah_terpenuhi'];
								$pct_mpr   = (int) $mr['persen_terpenuhi'];
							?>
								<tr style="border-bottom:1px solid var(--border)">
									<td style="padding:12px 14px">
										<a href="<?= site_url('requisitions/view/' . (int) $mr['id_req']) ?>" style="font-weight:700; color:var(--text); text-decoration:none">
											<?= html_escape($mr['nama_posisi']) ?>
										</a>
										<div class="muted mono" style="font-size:11.5px; margin-top:2px"><?= html_escape($mr['no_mpr']) ?></div>
									</td>
									<td style="padding:12px 14px">
										<div style="font-weight:600; color:var(--text)"><?= html_escape($mr['nama_departemen'] ?: '-') ?></div>
										<div class="muted" style="font-size:11.5px"><?= html_escape($mr['tipe_penempatan'] . ($mr['nama_outlet'] ? ' - ' . $mr['nama_outlet'] : '')) ?></div>
									</td>
									<td style="padding:12px 10px; text-align:center" class="mono faint">
										<?= html_escape($mr['tanggal_pengajuan'] ?: '-') ?>
									</td>
									<td style="padding:12px 10px; text-align:center; font-weight:700" class="mono">
										<?= $target ?> <span class="muted" style="font-size:11px; font-weight:400">org</span>
									</td>
									<td style="padding:12px 10px; text-align:center; font-weight:700; color:var(--good)" class="mono">
										<?= $terpenuhi ?> <span class="muted" style="font-size:11px; font-weight:400">org</span>
									</td>
									<td style="padding:12px 12px; text-align:center">
										<div style="display:flex; align-items:center; gap:8px; justify-content:center">
											<div style="width:68px; height:7px; background:var(--surface-2); border-radius:999px; overflow:hidden; border:1px solid var(--border)">
												<div style="width:<?= $pct_mpr ?>%; height:100%; background:<?= $pct_mpr >= 100 ? 'var(--good)' : 'var(--accent)' ?>; border-radius:999px"></div>
											</div>
											<span class="mono" style="font-size:11.5px; font-weight:700; min-width:32px; text-align:right"><?= $pct_mpr ?>%</span>
										</div>
									</td>
									<td style="padding:12px 10px; text-align:center">
										<span class="tag tag--info"><?= (int) $mr['total_in_progress'] ?> berkas</span>
									</td>
									<td style="padding:12px 10px; text-align:center">
										<span class="tag <?= in_array($mr['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : (in_array($mr['status_req'], array('Review_HR','Review_BOD','Revisi_HR','Revisi_BOD')) ? 'warn' : 'off') ?>">
											<?= html_escape(label_status_req($mr['status_req'])) ?>
										</span>
									</td>
									<td style="padding:12px 14px; text-align:right">
										<div style="display:flex; gap:6px; justify-content:flex-end">
											<a href="<?= site_url('pipeline/index/' . (int) $mr['id_req']) ?>" class="btn btn-sm btn-ghost" style="padding:3px 9px; font-size:11.5px" title="Buka Pipeline Seleksi">
												Pipeline
											</a>
											<a href="<?= site_url('requisitions/view/' . (int) $mr['id_req']) ?>" class="btn btn-sm btn-ghost" style="padding:3px 9px; font-size:11.5px" title="Detail Pengajuan MPR">
												Detail
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- KONTEN TAB 2: RINGKASAN PER DEPARTEMEN -->
		<div id="tab-dept" style="display:none; padding:0">
			<div style="overflow-x:auto">
				<table style="width:100%; border-collapse:collapse; margin:0; font-size:13px">
					<thead>
						<tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
							<th style="text-align:left; padding:12px 14px">Departemen</th>
							<th style="text-align:center; padding:12px 10px">Total Dokumen MPR</th>
							<th style="text-align:center; padding:12px 10px">Target Formasi (Orang)</th>
							<th style="text-align:center; padding:12px 10px">Terpenuhi (Hired)</th>
							<th style="text-align:center; padding:12px 12px; min-width:140px">Pemenuhan (%)</th>
							<th style="text-align:center; padding:12px 10px">Total Pelamar Masuk</th>
							<th style="text-align:center; padding:12px 10px">Konversi Hire</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($dept_summary)): ?>
							<tr>
								<td colspan="7" style="text-align:center; padding:45px 20px; color:var(--text-muted)">
									Tidak ada data departemen yang cocok dengan filter.
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($dept_summary as $ds):
								$dept_pct   = (int) $ds['persen'];
								$pelamar_d  = (int) $ds['total_pelamar'];
								$hired_d    = (int) $ds['total_hired'];
								$conv_dept  = $pelamar_d > 0 ? round(($hired_d / $pelamar_d) * 100, 1) : 0;
							?>
								<tr style="border-bottom:1px solid var(--border)">
									<td style="padding:12px 14px; font-weight:700; color:var(--text)">
										<?= html_escape($ds['nama_departemen'] ?: 'Umum / HQ') ?>
									</td>
									<td style="padding:12px 10px; text-align:center" class="mono">
										<?= (int) $ds['total_mpr'] ?>
									</td>
									<td style="padding:12px 10px; text-align:center; font-weight:700" class="mono">
										<?= (int) $ds['total_target'] ?> <span class="muted" style="font-size:11px; font-weight:400">org</span>
									</td>
									<td style="padding:12px 10px; text-align:center; font-weight:700; color:var(--good)" class="mono">
										<?= $hired_d ?> <span class="muted" style="font-size:11px; font-weight:400">org</span>
									</td>
									<td style="padding:12px 12px; text-align:center">
										<div style="display:flex; align-items:center; gap:8px; justify-content:center">
											<div style="width:68px; height:7px; background:var(--surface-2); border-radius:999px; overflow:hidden; border:1px solid var(--border)">
												<div style="width:<?= $dept_pct ?>%; height:100%; background:<?= $dept_pct >= 100 ? 'var(--good)' : 'var(--accent)' ?>; border-radius:999px"></div>
											</div>
											<span class="mono" style="font-size:11.5px; font-weight:700; min-width:32px; text-align:right"><?= $dept_pct ?>%</span>
										</div>
									</td>
									<td style="padding:12px 10px; text-align:center; font-weight:600" class="mono">
										<?= number_format($pelamar_d) ?>
									</td>
									<td style="padding:12px 10px; text-align:center">
										<span class="tag tag--good"><?= $conv_dept ?>%</span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
// Fungsi Switch Tab Antara Rekap MPR dan Departemen
function switchReportTab(tabId) {
	var tabMpr  = document.getElementById('tab-mpr');
	var tabDept = document.getElementById('tab-dept');
	var btnMpr  = document.getElementById('btn-tab-mpr');
	var btnDept = document.getElementById('btn-tab-dept');

	if (tabId === 'tab-mpr') {
		tabMpr.style.display = 'block';
		tabDept.style.display = 'none';

		btnMpr.style.background = 'var(--surface)';
		btnMpr.style.color = 'var(--accent)';
		btnMpr.style.fontWeight = '700';
		btnMpr.style.border = '1px solid var(--border)';
		btnMpr.style.borderBottomColor = 'var(--surface)';
		btnMpr.style.marginBottom = '-1px';

		btnDept.style.background = 'transparent';
		btnDept.style.color = 'var(--text-muted)';
		btnDept.style.fontWeight = '600';
		btnDept.style.border = 'none';
		btnDept.style.marginBottom = '0';
	} else {
		tabMpr.style.display = 'none';
		tabDept.style.display = 'block';

		btnDept.style.background = 'var(--surface)';
		btnDept.style.color = 'var(--accent)';
		btnDept.style.fontWeight = '700';
		btnDept.style.border = '1px solid var(--border)';
		btnDept.style.borderBottomColor = 'var(--surface)';
		btnDept.style.marginBottom = '-1px';

		btnMpr.style.background = 'transparent';
		btnMpr.style.color = 'var(--text-muted)';
		btnMpr.style.fontWeight = '600';
		btnMpr.style.border = 'none';
		btnMpr.style.marginBottom = '0';
	}
}

// Preset Helper untuk filter tanggal cepat
function setPreset(preset) {
	var dari = document.getElementById('f-dari');
	var sampai = document.getElementById('f-sampai');
	var now = new Date();
	var y = now.getFullYear();
	var m = String(now.getMonth() + 1).padStart(2, '0');
	var d = String(now.getDate()).padStart(2, '0');

	if (preset === 'today') {
		var today = y + '-' + m + '-' + d;
		dari.value = today;
		sampai.value = today;
	} else if (preset === 'this_month') {
		dari.value = y + '-' + m + '-01';
		sampai.value = y + '-' + m + '-' + d;
	} else if (preset === 'this_year') {
		dari.value = y + '-01-01';
		sampai.value = y + '-' + m + '-' + d;
	} else if (preset === 'all') {
		dari.value = '';
		sampai.value = '';
	}
	document.getElementById('form-report-filter').submit();
}
</script>

<?php defined('BASEPATH') OR exit('No direct script access allowed');
$g = function ($k) use ($f) { return isset($f[$k]) ? $f[$k] : ''; };
$sel = function ($a, $b) { return (string) $a === (string) $b ? 'selected' : ''; };

// susun funnel jadi matriks tipe_tahap x status
$order = array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD');
$fmat = array(); $fstat = array();
foreach ($d['funnel'] as $r) {
	$fmat[$r['tipe_tahap']][$r['status_global']] = (int) $r['jumlah'];
	$fstat[$r['status_global']] = TRUE;
}
$fstat = array_keys($fstat); sort($fstat);
$met = array(); foreach ($d['metrik'] as $m) { $met[$m['status_global']] = (int) $m['jumlah']; }
$total_in_process = (int)($met['In_Progress'] ?? 0);
$total_hired = (int)($met['Hired'] ?? 0);
?>

<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:12px">
	<div>
		<div class="eyebrow" style="margin-bottom:2px">Overview & Metrik Seleksi</div>
		<h1 style="margin:0 0 4px">Dashboard Rekrutmen</h1>
		<div class="muted" style="font-size:13px">
			Monitoring alur pelamar, SLA per tahap, dan efektivitas sourcing RPG.
		</div>
	</div>
	<div style="display:flex; gap:8px; align-items:center">
		<a class="btn btn-sm btn-ghost" href="<?= site_url('export/candidates' . '?' . http_build_query(array_filter($f))) ?>">
			<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
			<span>Export CSV/Excel</span>
		</a>
		<a class="btn btn-sm btn-primary" href="<?= site_url('requisitions/create') ?>">
			<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
			<span>Buat Permintaan</span>
		</a>
	</div>
</div>

<!-- Filter Box Modern -->
<div class="card" style="margin-bottom:20px; padding:16px 18px">
	<form method="get" action="<?= site_url('dashboard') ?>" style="margin:0">
		<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Periode Mulai</label>
				<input type="text" name="dari" value="<?= html_escape($g('dari')) ?>" placeholder="YYYY-MM-DD" style="width:120px; padding:6px 9px; font-size:12.5px">
			</div>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Periode Akhir</label>
				<input type="text" name="sampai" value="<?= html_escape($g('sampai')) ?>" placeholder="YYYY-MM-DD" style="width:120px; padding:6px 9px; font-size:12.5px">
			</div>
			<?php
			$fsel = array(
				'dept' => array('Departemen', $opt['dept'], 'id_departemen', 'nama'),
				'posisi' => array('Posisi', $opt['posisi'], 'id_posisi', 'nama_posisi'),
				'outlet' => array('Outlet', $opt['outlet'], 'id_outlet', 'nama_outlet'),
				'channel' => array('Channel', $opt['channel'], 'id_channel', 'nama_channel'),
			);
			foreach ($fsel as $key => $c): ?>
				<div>
					<label style="margin:0 0 3px; font-size:11px" class="eyebrow"><?= $c[0] ?></label>
					<select name="<?= $key ?>" style="width:auto; min-width:130px; padding:6px 8px; font-size:12.5px">
						<option value="">Semua <?= $c[0] ?></option>
						<?php foreach ($c[1] as $o): ?>
							<option value="<?= html_escape($o[$c[2]]) ?>" <?= $sel($g($key), $o[$c[2]]) ?>><?= html_escape($o[$c[3]]) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endforeach; ?>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Status</label>
				<select name="status" style="width:auto; padding:6px 8px; font-size:12.5px">
					<option value="">Semua Status</option>
					<?php foreach ($status_global as $s): ?>
						<option value="<?= $s ?>" <?= $sel($g('status'), $s) ?>><?= $s ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div style="display:flex; gap:6px; margin-left:auto">
				<button type="submit" class="btn btn-sm">Terapkan</button>
				<a class="btn btn-sm btn-ghost" href="<?= site_url('dashboard') ?>">Reset</a>
			</div>
		</div>
	</form>
</div>

<!-- Metrik KPI Card Grid -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(135px, 1fr)); gap:12px; margin-bottom:22px">
	<?php
	$cards = array(
		'In_Progress'    => array('Dalam Proses', 'tag--info', '#3a6ea5'),
		'On_Hold'        => array('On Hold', 'tag--hold', '#7c6316'),
		'Unreachable'    => array('Unreachable', 'tag--warn', '#8f6410'),
		'Hired'          => array('Hired', 'tag--good', '#2f7d4f'),
		'Rejected'       => array('Rejected', 'tag--crit', '#b23b3b'),
		'Offer_Declined' => array('Offer Ditolak', 'tag--crit', '#b23b3b'),
		'No_Show'        => array('No Show', 'tag--crit', '#b23b3b'),
		'Talent_Pool'    => array('Talent Pool', 'tag--talent', '#6b4fa0'),
	);
	foreach ($cards as $k => $info):
		$val = (int) ($met[$k] ?? 0);
	?>
	<div class="card" style="padding:14px 16px; border-left:3px solid <?= $info[2] ?>">
		<div class="eyebrow" style="font-size:10px; margin-bottom:4px"><?= $info[0] ?></div>
		<div style="font-size:24px; font-weight:700; font-family:'Archivo', sans-serif"><?= $val ?></div>
		<div class="muted" style="font-size:11px; margin-top:2px">kandidat</div>
	</div>
	<?php endforeach; ?>
</div>

<!-- Funnel & Durasi Section -->
<div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-bottom:22px">
	<!-- Matriks Funnel -->
	<div class="card" style="padding:18px 20px">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
			<h2 style="font-size:15px; margin:0">Funnel Konversi Tahapan</h2>
			<span class="eyebrow" style="color:var(--accent)">7 Tahap Terstandar</span>
		</div>
		<div style="overflow-x:auto">
			<table style="margin:0">
				<thead>
					<tr>
						<th>Tahap</th>
						<?php foreach ($fstat as $s): ?>
							<th style="text-align:center"><?= html_escape($s) ?></th>
						<?php endforeach; ?>
						<th style="text-align:right">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($order as $tt):
						if ( ! isset($fmat[$tt])) continue;
						$tot = 0;
					?>
					<tr>
						<td><strong style="font-family:'Archivo', sans-serif"><?= $tt ?></strong></td>
						<?php foreach ($fstat as $s):
							$v = $fmat[$tt][$s] ?? 0;
							$tot += $v;
						?>
							<td style="text-align:center"><?= $v ? '<span class="tag ' . ($v > 0 ? 'info' : '') . '">' . $v . '</span>' : '-' ?></td>
						<?php endforeach; ?>
						<td style="text-align:right; font-weight:700"><?= $tot ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Efisiensi & Durasi Proses -->
	<div class="card" style="padding:18px 20px; display:flex; flex-direction:column; justify-content:space-between">
		<div>
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
				<h2 style="font-size:15px; margin:0">Kecepatan Proses</h2>
				<span class="eyebrow">SLA Monitoring</span>
			</div>
			<div style="margin-bottom:16px">
				<div class="muted" style="font-size:12px; margin-bottom:4px">Rata-rata Lama Proses Lamaran:</div>
				<div style="font-size:24px; font-weight:700; color:var(--accent); font-family:'Archivo', sans-serif">
					<?= $d['waktu']['rata_lama_proses_hari'] !== NULL ? round($d['waktu']['rata_lama_proses_hari'], 1) . ' <span style="font-size:14px; font-weight:normal">hari</span>' : '-' ?>
				</div>
				<div class="faint" style="font-size:11.5px">Dihitung sejak permohonan disetujui hingga posisi terpenuhi.</div>
			</div>
			<div>
				<div class="muted" style="font-size:12px; margin-bottom:4px">Rata-rata Waktu Approval BOD:</div>
				<div style="font-size:22px; font-weight:700; color:var(--text); font-family:'Archivo', sans-serif">
					<?= $d['waktu']['rata_hari_menunggu_approval'] !== NULL ? round($d['waktu']['rata_hari_menunggu_approval'], 1) . ' <span style="font-size:14px; font-weight:normal">hari</span>' : '-' ?>
				</div>
				<div class="faint" style="font-size:11.5px">Dari pengajuan User Dept ke keputusan persetujuan.</div>
			</div>
		</div>

		<div style="background:var(--surface-2); padding:10px 12px; border-radius:8px; margin-top:16px; font-size:12px">
			<div style="font-weight:600; margin-bottom:2px">Compliance UU PDP 27/2022</div>
			<div class="muted">Setiap pembukaan dokumen identitas & finansial otomatis diaudit di <span class="mono">ACCESS_LOG_SENSITIF</span>.</div>
		</div>
	</div>
</div>

<!-- Aging SLA Warning Section -->
<div class="card" style="margin-bottom:22px; padding:18px 20px">
	<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
		<div style="display:flex; align-items:center; gap:8px">
			<h2 style="font-size:15px; margin:0">Peringatan Aging SLA</h2>
			<span class="tag <?= count($d['aging']) > 0 ? 'off' : 'on' ?>">
				<?= count($d['aging']) ?> Melebihi Batas
			</span>
		</div>
		<span class="faint" style="font-size:12px">Sesuai konfigurasi SLA di M_STAGE</span>
	</div>

	<?php if ( ! $d['aging']): ?>
		<div style="padding:20px; text-align:center; background:var(--surface-2); border-radius:8px">
			<p class="muted" style="margin:0; font-size:13px">Semua lamaran berjalan berada dalam batas waktu SLA yang aman.</p>
		</div>
	<?php else: ?>
		<div style="overflow-x:auto">
			<table style="margin:0">
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
						<td><span class="tag info"><?= html_escape($a['tipe_tahap']) ?></span></td>
						<td style="text-align:center"><?= (int) $a['sla_hari'] ?> hari</td>
						<td style="text-align:center; color:var(--crit); font-weight:700"><?= (int) $a['hari_di_tahap'] ?> hari</td>
						<td style="text-align:right">
							<a href="<?= site_url('pipeline/index/' . (int) $a['id_req']) ?>" class="btn btn-sm btn-ghost">Buka Pipeline &rarr;</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<!-- Trend 14 Hari -->
<div class="card" style="padding:18px 20px">
	<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
		<h2 style="font-size:15px; margin:0">Trend Agregat Funnel Harian (14 Hari Terakhir)</h2>
		<span class="muted" style="font-size:12px">Sumber: RPT_FUNNEL_HARIAN</span>
	</div>

	<?php if ( ! $trend): ?>
		<p class="muted" style="margin:0">Belum ada data agregat harian. Eksekusi <code>php tools/build-funnel.php</code> untuk memperbarui.</p>
	<?php else: ?>
		<div style="overflow-x:auto">
			<table style="margin:0">
				<thead>
					<tr>
						<th>Tanggal Agregat</th>
						<th>Tipe Tahap Seleksi</th>
						<th style="text-align:right">Jumlah Pelamar Terproses</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($trend as $t): ?>
					<tr>
						<td class="mono"><?= html_escape($t['tanggal']) ?></td>
						<td><span class="tag"><?= html_escape($t['tipe_tahap']) ?></span></td>
						<td style="text-align:right; font-weight:700"><?= (int) $t['jumlah'] ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

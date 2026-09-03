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
?>
<main class="card">
	<h1>Dashboard Rekrutmen</h1>

	<form method="get" action="<?= site_url('dashboard') ?>" style="margin:0 0 14px">
		<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; font-size:13px">
			<span><label style="margin:0">Periode</label><br>
				<input type="text" name="dari" value="<?= html_escape($g('dari')) ?>" placeholder="dari" style="width:100px">
				<input type="text" name="sampai" value="<?= html_escape($g('sampai')) ?>" placeholder="sampai" style="width:100px"></span>
			<?php
			$fsel = array(
				'dept' => array('Departemen', $opt['dept'], 'id_departemen', 'nama'),
				'posisi' => array('Posisi', $opt['posisi'], 'id_posisi', 'nama_posisi'),
				'outlet' => array('Outlet', $opt['outlet'], 'id_outlet', 'nama_outlet'),
				'flow' => array('Flow', $opt['flow'], 'id_flow', 'kode_flow'),
				'channel' => array('Channel', $opt['channel'], 'id_channel', 'nama_channel'),
			);
			foreach ($fsel as $key => $c): ?>
				<span><label style="margin:0"><?= $c[0] ?></label><br>
				<select name="<?= $key ?>" style="width:auto; padding:4px">
					<option value="">semua</option>
					<?php foreach ($c[1] as $o): ?><option value="<?= html_escape($o[$c[2]]) ?>" <?= $sel($g($key), $o[$c[2]]) ?>><?= html_escape($o[$c[3]]) ?></option><?php endforeach; ?>
				</select></span>
			<?php endforeach; ?>
			<span><label style="margin:0">Tipe tahap</label><br>
				<select name="tipe_tahap" style="width:auto; padding:4px"><option value="">semua</option>
					<?php foreach ($tipe_tahap as $t): ?><option <?= $sel($g('tipe_tahap'), $t) ?>><?= $t ?></option><?php endforeach; ?>
				</select></span>
			<span><label style="margin:0">Status</label><br>
				<select name="status" style="width:auto; padding:4px"><option value="">semua</option>
					<?php foreach ($status_global as $s): ?><option <?= $sel($g('status'), $s) ?>><?= $s ?></option><?php endforeach; ?>
				</select></span>
			<span><label style="margin:0">PIC</label><br>
				<select name="pic" style="width:auto; padding:4px"><option value="">semua</option>
					<?php foreach ($opt['pic'] as $r): ?><option <?= $sel($g('pic'), $r['kode_role']) ?>><?= html_escape($r['kode_role']) ?></option><?php endforeach; ?>
				</select></span>
			<button type="submit" class="btn-sm">Terapkan</button>
			<a class="btn-sm btn-ghost" href="<?= site_url('dashboard') ?>">reset</a>
		</div>
	</form>

	<h2>Metrik</h2>
	<div style="display:flex; gap:10px; flex-wrap:wrap">
		<?php
		$cards = array('In_Progress' => 'Dalam proses', 'On_Hold' => 'On hold', 'Unreachable' => 'Unreachable',
			'Hired' => 'Hired', 'Rejected' => 'Rejected', 'Offer_Declined' => 'Offer ditolak', 'No_Show' => 'No show', 'Talent_Pool' => 'Talent pool');
		foreach ($cards as $k => $lbl): ?>
		<div style="border:1px solid #e2e4e8; border-radius:8px; padding:10px 14px; min-width:110px">
			<div style="font-size:22px; font-weight:700"><?= (int) ($met[$k] ?? 0) ?></div>
			<div class="muted" style="margin:0; font-size:12px"><?= $lbl ?></div>
		</div>
		<?php endforeach; ?>
	</div>

	<h2>Funnel (per tipe tahap &times; status)</h2>
	<div style="overflow-x:auto"><table>
		<tr><th>Tipe tahap</th><?php foreach ($fstat as $s): ?><th><?= html_escape($s) ?></th><?php endforeach; ?><th>Total</th></tr>
		<?php foreach ($order as $tt): if ( ! isset($fmat[$tt])) continue; $tot = 0; ?>
		<tr>
			<td><strong><?= $tt ?></strong></td>
			<?php foreach ($fstat as $s): $v = $fmat[$tt][$s] ?? 0; $tot += $v; ?><td><?= $v ?: '' ?></td><?php endforeach; ?>
			<td><strong><?= $tot ?></strong></td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h2>Waktu</h2>
	<p>
		Rata-rata <strong>lama proses</strong> (lamaran berjalan, dari tanggal permintaan):
		<strong><?= $d['waktu']['rata_lama_proses_hari'] !== NULL ? round($d['waktu']['rata_lama_proses_hari'], 1) . ' hari' : '-' ?></strong><br>
		Rata-rata <strong>menunggu approval BOD</strong> (diajukan &rarr; keputusan):
		<strong><?= $d['waktu']['rata_hari_menunggu_approval'] !== NULL ? round($d['waktu']['rata_hari_menunggu_approval'], 1) . ' hari' : '-' ?></strong>
	</p>

	<h2>Aging SLA (<?= count($d['aging']) ?> lewat batas)</h2>
	<?php if ( ! $d['aging']): ?>
		<p class="muted">Tidak ada tahap yang lewat SLA.</p>
	<?php else: ?>
	<div style="overflow-x:auto"><table>
		<tr><th>Lamaran</th><th>Kandidat</th><th>Posisi</th><th>Tipe tahap</th><th>SLA (hari)</th><th>Di tahap (hari)</th></tr>
		<?php foreach ($d['aging'] as $a): ?>
		<tr>
			<td><a href="<?= site_url('pipeline/index/' . (int) $a['id_req']) ?>">#<?= (int) $a['id_lamaran'] ?></a></td>
			<td><?= html_escape($a['nama_lengkap']) ?></td>
			<td><?= html_escape($a['nama_posisi']) ?></td>
			<td><?= html_escape($a['tipe_tahap']) ?></td>
			<td><?= (int) $a['sla_hari'] ?></td>
			<td style="color:#a12626; font-weight:700"><?= (int) $a['hari_di_tahap'] ?></td>
		</tr>
		<?php endforeach; ?>
	</table></div>
	<?php endif; ?>

	<h2>Trend funnel (14 hari &mdash; dari RPT_FUNNEL_HARIAN)</h2>
	<?php if ( ! $trend): ?>
		<p class="muted">Belum ada data agregat. Jalankan <code>php tools/build-funnel.php</code> (dijadwalkan harian).</p>
	<?php else: ?>
	<div style="overflow-x:auto"><table>
		<tr><th>Tanggal</th><th>Tipe tahap</th><th>Jumlah</th></tr>
		<?php foreach ($trend as $t): ?>
		<tr><td><?= html_escape($t['tanggal']) ?></td><td><?= html_escape($t['tipe_tahap']) ?></td><td><?= (int) $t['jumlah'] ?></td></tr>
		<?php endforeach; ?>
	</table></div>
	<?php endif; ?>

	<p style="margin-top:16px">
		<a class="btn-sm" href="<?= site_url('export/candidates' . '?' . http_build_query(array_filter($f))) ?>">Export kandidat (Excel)</a>
	</p>
</main>

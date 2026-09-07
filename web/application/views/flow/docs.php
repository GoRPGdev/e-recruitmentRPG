<?php defined('BASEPATH') OR exit('No direct script access allowed');
// group rows per flow_stage
$byfs = array();
foreach ($rows as $r) {
	$k = (int) $r['id_flow_stage'];
	if ( ! isset($byfs[$k])) {
		$byfs[$k] = array('nama' => $r['nama_tahap'], 'urutan' => $r['urutan'], 'docs' => array());
	}
	if ($r['id_dokumen']) {
		$byfs[$k]['docs'][(int) $r['id_dokumen']] = array('nama' => $r['nama_dokumen'], 'wajib' => $r['is_wajib']);
	}
}
?>
<main class="card" style="padding:22px 26px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:12px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<a href="<?= site_url('flowbuilder') ?>" class="muted" style="font-size:12px; text-decoration:none">&larr; Alur Standar RPG</a>
				<span class="muted">&bull;</span>
				<a href="<?= site_url('flowbuilder/stages') ?>" class="muted" style="font-size:12px; text-decoration:none">Katalog Tahap &rarr;</a>
			</div>
			<h1 style="margin:0 0 4px; font-size:20px; font-weight:700">Dokumen Wajib Alur Seleksi</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Tentukan jenis dokumen atau berkas pelamar yang harus/opsional diunggah pada setiap tahapan alur.
			</p>
		</div>
	</div>

	<?php if ($this->session->flashdata('ok')): ?>
		<div class="flash ok" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('ok')) ?></div>
	<?php endif; ?>

	<div style="display:flex; flex-direction:column; gap:16px">
		<?php foreach ($byfs as $idfs => $s): ?>
		<div style="border:1px solid var(--border); border-radius:10px; padding:16px 20px; background:var(--surface)">
			<div style="display:flex; align-items:center; gap:10px; margin-bottom:12px">
				<span class="tag on" style="font-size:11px; font-weight:700">Tahap #<?= (int) $s['urutan'] ?></span>
				<h2 style="margin:0; font-size:16px; font-weight:700; color:var(--text)"><?= html_escape($s['nama']) ?></h2>
			</div>

			<div style="overflow-x:auto">
				<table style="width:100%; border-collapse:collapse; margin:0">
					<thead>
						<tr>
							<th>Nama Dokumen</th>
							<th style="width:140px; text-align:center">Status Wajib</th>
							<th style="width:230px; text-align:right">Pengaturan Syarat</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($dokumen as $dk): $cur = $s['docs'][(int) $dk['id_dokumen']] ?? NULL; ?>
						<tr>
							<td>
								<strong style="font-size:13px"><?= html_escape($dk['nama_dokumen']) ?></strong>
								<span class="mono muted" style="font-size:11.5px; margin-left:6px"><?= html_escape($dk['kode_dokumen']) ?></span>
							</td>
							<td style="text-align:center">
								<?php if ($cur === NULL): ?>
									<span class="muted" style="font-size:12px">&mdash; Tidak Disyaratkan &mdash;</span>
								<?php elseif ($cur['wajib']): ?>
									<span class="tag warn" style="font-weight:700">&#9888; Wajib Diunggah</span>
								<?php else: ?>
									<span class="tag info">Opsional</span>
								<?php endif; ?>
							</td>
							<td style="text-align:right; white-space:nowrap">
								<?= form_open(site_url('flowbuilder/set_flow_doc/' . (int) $id_flow), array('class' => 'inline')) ?>
									<input type="hidden" name="id_flow_stage" value="<?= (int) $idfs ?>">
									<input type="hidden" name="id_dokumen" value="<?= (int) $dk['id_dokumen'] ?>">
									<button name="wajib" value="1" class="btn-sm <?= ($cur !== NULL && $cur['wajib']) ? 'btn-primary' : 'btn-ghost' ?>" style="padding:3px 7px; font-size:11px">Wajib</button>
									<button name="wajib" value="0" class="btn-sm <?= ($cur !== NULL && ! $cur['wajib']) ? 'btn-primary' : 'btn-ghost' ?>" style="padding:3px 7px; font-size:11px">Opsional</button>
									<button name="wajib" value="remove" class="btn-sm btn-ghost" style="padding:3px 7px; font-size:11px; color:var(--text-muted)">Lepas</button>
								<?= form_close() ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</main>

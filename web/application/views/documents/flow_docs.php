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
<main class="card">
	<h1>Dokumen wajib per tahap</h1>
	<form method="get" action="" style="margin:0 0 12px">
		Flow:
		<select name="_f" onchange="location.href='<?= site_url('documents/flow_docs') ?>/'+this.value" style="width:auto; padding:4px">
			<?php foreach ($flows as $fl): ?>
				<option value="<?= (int) $fl['id_flow'] ?>" <?= $fl['id_flow'] == $id_flow ? 'selected' : '' ?>><?= html_escape($fl['kode_flow']) ?></option>
			<?php endforeach; ?>
		</select>
	</form>

	<?php foreach ($byfs as $idfs => $s): ?>
	<div style="border-top:2px solid #e2e4e8; padding-top:8px; margin-top:12px">
		<h2 style="margin:0 0 6px"><?= (int) $s['urutan'] ?>. <?= html_escape($s['nama']) ?></h2>
		<table>
			<tr><th>Dokumen</th><th>Status</th><th>Aksi</th></tr>
			<?php foreach ($dokumen as $dk): $cur = $s['docs'][(int) $dk['id_dokumen']] ?? NULL; ?>
			<tr>
				<td><?= html_escape($dk['nama_dokumen']) ?></td>
				<td><?php if ($cur === NULL): ?>-<?php elseif ($cur['wajib']): ?><span class="tag off">wajib</span><?php else: ?><span class="tag">opsional</span><?php endif; ?></td>
				<td>
					<?= form_open(site_url('documents/set_flow_doc/' . (int) $id_flow), array('class' => 'inline')) ?>
						<input type="hidden" name="id_flow_stage" value="<?= (int) $idfs ?>">
						<input type="hidden" name="id_dokumen" value="<?= (int) $dk['id_dokumen'] ?>">
						<button name="wajib" value="1" class="btn-sm">wajib</button>
						<button name="wajib" value="0" class="btn-sm btn-ghost">opsional</button>
						<button name="wajib" value="remove" class="btn-sm btn-ghost">hapus</button>
					<?= form_close() ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>
	<?php endforeach; ?>
</main>

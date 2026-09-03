<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Remark <?= $id_stage ? '&mdash; tahap #' . (int) $id_stage : '(semua tahap)' ?></h1>
	<p class="muted" style="margin-top:0"><a href="<?= site_url('flowbuilder') ?>">&larr; flow builder</a></p>

	<div style="overflow-x:auto"><table>
		<tr><th>Tahap</th><th>Kode</th><th>Label</th><th>Efek status</th><th>Urut</th><th>Status</th><th></th></tr>
		<?php foreach ($rows as $r): ?>
		<tr style="<?= $r['is_aktif'] ? '' : 'opacity:.55' ?>">
			<td><?= html_escape($r['nama_tahap']) ?></td>
			<td><code><?= html_escape($r['kode_remark']) ?></code></td>
			<td><?= html_escape($r['label']) ?></td>
			<td><?= html_escape($r['efek_status']) ?></td>
			<td><?= (int) $r['urutan'] ?></td>
			<td><span class="tag <?= $r['is_aktif'] ? 'on' : 'off' ?>"><?= $r['is_aktif'] ? 'aktif' : 'nonaktif' ?></span></td>
			<td>
				<?= form_open(site_url('flowbuilder/save_remark'), array('class' => 'inline')) ?>
					<input type="hidden" name="id_remark" value="<?= (int) $r['id_remark'] ?>">
					<input type="hidden" name="id_stage" value="<?= (int) $r['id_stage'] ?>">
					<input type="hidden" name="kode_remark" value="<?= html_escape($r['kode_remark']) ?>">
					<input type="hidden" name="label" value="<?= html_escape($r['label']) ?>">
					<input type="hidden" name="efek_status" value="<?= html_escape($r['efek_status']) ?>">
					<input type="hidden" name="urutan" value="<?= (int) $r['urutan'] ?>">
					<input type="hidden" name="is_aktif" value="<?= $r['is_aktif'] ? 0 : 1 ?>">
					<button class="btn-sm btn-ghost"><?= $r['is_aktif'] ? 'nonaktif' : 'aktif' ?></button>
				<?= form_close() ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h2>Tambah remark</h2>
	<?= validation_errors('<div class="flash err">', '</div>') ?>
	<?= form_open(site_url('flowbuilder/save_remark')) ?>
		<label>Tahap</label>
		<select name="id_stage">
			<?php foreach ($all_stages as $st): ?>
				<option value="<?= (int) $st['id_stage'] ?>" <?= $id_stage == $st['id_stage'] ? 'selected' : '' ?>><?= html_escape($st['nama_tahap']) ?></option>
			<?php endforeach; ?>
		</select>
		<label>Kode remark</label><input type="text" name="kode_remark" placeholder="SCV_XXX" required>
		<label>Label</label><input type="text" name="label" required>
		<label>Efek status</label>
		<select name="efek_status"><?php foreach ($efek as $e): ?><option><?= $e ?></option><?php endforeach; ?></select>
		<label>Urutan</label><input type="text" name="urutan" value="0" inputmode="numeric">
		<button type="submit">Simpan</button>
	<?= form_close() ?>
</main>

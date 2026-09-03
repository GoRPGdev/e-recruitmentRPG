<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Flow Builder</h1>
	<p class="muted" style="margin-top:0"><a href="<?= site_url('flowbuilder/remarks') ?>">Kelola remark &rarr;</a></p>

	<div style="overflow-x:auto"><table>
		<tr><th>Kode</th><th>Nama</th><th>Penempatan</th><th>Versi</th><th>Tahap</th><th>Maks kontak</th><th>Induk</th><th>Status</th><th></th></tr>
		<?php foreach ($flows as $f): ?>
		<tr style="<?= $f['is_aktif'] ? '' : 'opacity:.55' ?>">
			<td><code><?= html_escape($f['kode_flow']) ?></code></td>
			<td><?= html_escape($f['nama_flow']) ?></td>
			<td><?= html_escape($f['tipe_penempatan']) ?></td>
			<td><?= (int) $f['versi'] ?></td>
			<td><?= (int) $f['n_stage'] ?></td>
			<td><?= (int) $f['maks_upaya_kontak'] ?></td>
			<td><?= html_escape($f['induk'] ?: '-') ?></td>
			<td><span class="tag <?= $f['is_aktif'] ? 'on' : 'off' ?>"><?= $f['is_aktif'] ? 'aktif' : 'nonaktif' ?></span></td>
			<td>
				<a href="<?= site_url('flowbuilder/edit/' . (int) $f['id_flow']) ?>">edit</a> &middot;
				<?= form_open(site_url('flowbuilder/toggle/' . (int) $f['id_flow']), array('class' => 'inline')) ?>
					<input type="hidden" name="is_aktif" value="<?= $f['is_aktif'] ? 0 : 1 ?>">
					<button class="btn-sm btn-ghost"><?= $f['is_aktif'] ? 'nonaktif' : 'aktif' ?></button>
				<?= form_close() ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h2>Flow baru</h2>
	<?= validation_errors('<div class="flash err">', '</div>') ?>
	<?= form_open(site_url('flowbuilder/save_header')) ?>
		<label>Kode flow</label><input type="text" name="kode_flow" placeholder="HQ_SPECIAL" required>
		<label>Nama</label><input type="text" name="nama_flow" required>
		<label>Penempatan</label><select name="tipe_penempatan"><option>HQ</option><option>OUTLET</option></select>
		<label>Maks upaya kontak</label><input type="text" name="maks_upaya_kontak" value="3" inputmode="numeric">
		<label>SLA total hari (opsional)</label><input type="text" name="sla_total_hari" inputmode="numeric">
		<button type="submit">Buat flow</button>
	<?= form_close() ?>

	<h2>Simpan sebagai template baru (clone)</h2>
	<?= form_open(site_url('flowbuilder/clone_flow')) ?>
		<label>Flow sumber</label>
		<select name="id_flow_sumber">
			<?php foreach ($flows as $f): ?><option value="<?= (int) $f['id_flow'] ?>"><?= html_escape($f['kode_flow']) ?></option><?php endforeach; ?>
		</select>
		<label>Kode flow baru</label><input type="text" name="kode_flow_baru" required>
		<label>Nama flow baru</label><input type="text" name="nama_flow_baru" required>
		<button type="submit">Clone</button>
	<?= form_close() ?>
</main>

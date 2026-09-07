<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Flow: <?= html_escape($flow['kode_flow']) ?> <span class="muted" style="font-size:13px">v<?= (int) $flow['versi'] ?></span></h1>
	<p class="muted" style="margin-top:0"><a href="<?= site_url('flowbuilder') ?>">&larr; semua flow</a></p>

	<h2>Header</h2>
	<?= form_open(site_url('flowbuilder/save_header')) ?>
		<input type="hidden" name="id_flow" value="<?= (int) $flow['id_flow'] ?>">
		<label>Kode</label><input type="text" name="kode_flow" value="<?= html_escape($flow['kode_flow']) ?>" required>
		<label>Nama</label><input type="text" name="nama_flow" value="<?= html_escape($flow['nama_flow']) ?>" required>
		<label>Penempatan</label>
		<select name="tipe_penempatan">
			<option <?= $flow['tipe_penempatan'] === 'HQ' ? 'selected' : '' ?>>HQ</option>
			<option <?= $flow['tipe_penempatan'] === 'OUTLET' ? 'selected' : '' ?>>OUTLET</option>
		</select>
		<label>Maks upaya kontak</label><input type="text" name="maks_upaya_kontak" value="<?= (int) $flow['maks_upaya_kontak'] ?>" inputmode="numeric">
		<input type="hidden" name="sla_total_hari" value="">
		<button type="submit">Simpan header</button>
	<?= form_close() ?>

	<h2>Tahap</h2>
	<div style="overflow-x:auto"><table>
		<tr><th>#</th><th>Tahap</th><th>Tipe</th><th>Wajib</th><th>PIC</th><th>Aksi</th></tr>
		<?php foreach ($stages as $s): ?>
		<tr>
			<td><?= (int) $s['urutan'] ?></td>
			<td><?= html_escape($s['nama_tahap']) ?></td>
			<td><?= html_escape($s['tipe_tahap']) ?></td>
			<td>
				<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('class' => 'inline')) ?>
					<input type="hidden" name="aksi" value="UPDATE">
					<input type="hidden" name="id_flow_stage" value="<?= (int) $s['id_flow_stage'] ?>">
					<label style="display:inline"><input type="checkbox" name="is_wajib" value="1" <?= $s['is_wajib'] ? 'checked' : '' ?> onchange="this.form.submit()"></label>
			</td>
			<td>
					<input type="hidden" name="sla_hari" value="">
			</td>
			<td>
					<select name="role_pic" style="width:auto; padding:4px">
						<option value="">-</option>
						<?php foreach ($roles as $r): ?>
							<option value="<?= html_escape($r['kode_role']) ?>" <?= $s['role_pic'] === $r['kode_role'] ? 'selected' : '' ?>><?= html_escape($r['kode_role']) ?></option>
						<?php endforeach; ?>
					</select>
					<button class="btn-sm">simpan</button>
				<?= form_close() ?>
			</td>
			<td style="white-space:nowrap">
				<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('class' => 'inline')) ?>
					<input type="hidden" name="aksi" value="MOVE">
					<input type="hidden" name="id_flow_stage" value="<?= (int) $s['id_flow_stage'] ?>">
					<input type="text" name="urutan" value="<?= (int) $s['urutan'] ?>" style="width:44px" inputmode="numeric">
					<button class="btn-sm btn-ghost">pindah</button>
				<?= form_close() ?>
				<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('class' => 'inline')) ?>
					<input type="hidden" name="aksi" value="REMOVE">
					<input type="hidden" name="id_flow_stage" value="<?= (int) $s['id_flow_stage'] ?>">
					<button class="btn-sm btn-ghost">hapus</button>
				<?= form_close() ?>
				<a class="btn-sm" href="<?= site_url('flowbuilder/remarks/' . (int) $s['id_stage']) ?>">remark</a>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h3>Tambah tahap</h3>
	<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow'])) ?>
		<input type="hidden" name="aksi" value="ADD">
		<label>Tahap</label>
		<select name="id_stage">
			<?php foreach ($all_stages as $st): ?><option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap'] . ' [' . $st['tipe_tahap'] . ']') ?></option><?php endforeach; ?>
		</select>
		<label>Urutan</label><input type="text" name="urutan" value="<?= count($stages) + 1 ?>" inputmode="numeric">
		<label style="display:inline"><input type="checkbox" name="is_wajib" value="1" checked> wajib</label>
		<button type="submit">Tambah</button>
	<?= form_close() ?>
</main>

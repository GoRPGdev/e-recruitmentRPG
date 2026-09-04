<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Tahap Seleksi</h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('flowbuilder') ?>">&larr; Flow Builder</a> &middot;
		<a href="<?= site_url('flowbuilder/remarks') ?>">Kelola remark &rarr;</a>
	</p>
	<p class="muted" style="margin-top:0">
		Master tahap. <code>tipe_tahap</code> = sumbu tetap semua report (7 nilai).
		Tahap sistem tak bisa dihapus &amp; kode/tipe-nya terkunci; boteh dinonaktifkan.
	</p>

	<div style="overflow-x:auto"><table>
		<tr>
			<th>Kode</th><th>Nama Tahap</th><th>Tipe (Report)</th><th>Terminal</th>
			<th>Sistem</th><th>Penggunaan</th><th>Status</th><th></th>
		</tr>
		<?php foreach ($rows as $r): ?>
		<tr style="<?= empty($r['is_aktif']) ? 'opacity:.55' : '' ?>">
			<td><code><?= html_escape($r['kode_stage']) ?></code></td>
			<td><?= html_escape($r['nama_tahap']) ?></td>
			<td><span class="tag on"><?= html_escape($r['tipe_tahap']) ?></span></td>
			<td><?= $r['is_terminal'] ? 'Ya' : '-' ?></td>
			<td><?= $r['is_sistem'] ? '<span class="tag off" title="Tahap inti bawaan sistem">Sistem</span>' : 'Custom' ?></td>
			<td><span class="muted" style="font-size:12px">
				<?= (int) $r['n_flow'] ?> flow &middot;
				<a href="<?= site_url('flowbuilder/remarks/' . (int) $r['id_stage']) ?>"><?= (int) $r['n_remark'] ?> remark</a>
			</span></td>
			<td><span class="tag <?= $r['is_aktif'] ? 'on' : 'off' ?>"><?= $r['is_aktif'] ? 'aktif' : 'nonaktif' ?></span></td>
			<td style="white-space:nowrap">
				<a href="<?= site_url('flowbuilder/stages?edit=' . (int) $r['id_stage']) ?>" class="btn-sm btn-ghost" style="text-decoration:none; display:inline-block">edit</a>
				<?= form_open(site_url('flowbuilder/toggle_stage'), array('class' => 'inline')) ?>
					<input type="hidden" name="id" value="<?= (int) $r['id_stage'] ?>">
					<input type="hidden" name="is_aktif" value="<?= $r['is_aktif'] ? 0 : 1 ?>">
					<button class="btn-sm btn-ghost"><?= $r['is_aktif'] ? 'nonaktifkan' : 'aktifkan' ?></button>
				<?= form_close() ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h2><?= ! empty($edit_row) ? 'Edit Tahap #' . (int) $edit_row['id_stage'] : 'Tambah Tahap' ?></h2>
	<?php if (! empty($edit_row)): ?>
		<p class="muted" style="margin-top:0"><a href="<?= site_url('flowbuilder/stages') ?>">&larr; batal / tambah tahap baru</a></p>
	<?php endif; ?>

	<?= validation_errors('<div class="flash err">', '</div>') ?>
	<?= form_open(site_url('flowbuilder/save_stage')) ?>
		<?php if (! empty($edit_row)): ?>
			<input type="hidden" name="id_stage" value="<?= (int) $edit_row['id_stage'] ?>">
		<?php endif; ?>

		<label>Kode stage</label>
		<input type="text" name="kode_stage" value="<?= html_escape($edit_row['kode_stage'] ?? '') ?>"
		       placeholder="MIS. TES_KODING" required style="text-transform:uppercase"
		       <?= ! empty($edit_row['is_sistem']) ? 'readonly style="background:#f1f2f4; text-transform:uppercase"' : '' ?>>

		<label>Nama tahap</label>
		<input type="text" name="nama_tahap" value="<?= html_escape($edit_row['nama_tahap'] ?? '') ?>"
		       placeholder="Mis. Tes Koding Praktik" required>

		<label>Tipe tahap (sumbu report &mdash; 7 nilai wajib)</label>
		<?php if (! empty($edit_row['is_sistem'])): ?>
			<input type="text" value="<?= html_escape($edit_row['tipe_tahap']) ?>" disabled>
			<input type="hidden" name="tipe_tahap" value="<?= html_escape($edit_row['tipe_tahap']) ?>">
			<span class="muted" style="display:block; margin-top:2px">Tahap inti sistem: kode &amp; tipe terkunci demi kelancaran flow &amp; report.</span>
		<?php else: ?>
			<select name="tipe_tahap" required>
				<?php foreach ($tipe_tahap as $tp): ?>
					<option value="<?= $tp ?>" <?= (isset($edit_row['tipe_tahap']) && $edit_row['tipe_tahap'] === $tp) ? 'selected' : '' ?>><?= $tp ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>

		<label style="margin-top:10px">
			<input type="checkbox" name="is_terminal" value="1" <?= ! empty($edit_row['is_terminal']) ? 'checked' : '' ?>>
			Tahap terminal (mencapai tahap ini menyelesaikan lamaran, mis. Onboard)
		</label>

		<button type="submit"><?= ! empty($edit_row) ? 'Simpan perubahan' : 'Tambah tahap' ?></button>
	<?= form_close() ?>
</main>

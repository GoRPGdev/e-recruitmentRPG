<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Master Data</h1>
	<p class="muted" style="margin-top:0">
		<?php
		$type_keys = array_keys($types);
		$last_key  = end($type_keys);
		foreach ($types as $k => $v): ?>
			<a href="<?= site_url('master/index/' . $k) ?>" <?= $k === $t ? 'style="font-weight:700"' : '' ?>><?= html_escape($v['label']) ?></a>
			<?= $k !== $last_key ? '&middot;' : '' ?>
		<?php endforeach; ?>
	</p>

	<?php
	// definisi kolom & field per tipe
	$col = array(
		'posisi'     => array('Nama', 'Departemen', 'Level', 'Flow default'),
		'stage'      => array('Kode', 'Nama Tahap', 'Tipe (Report)', 'Terminal', 'Sistem', 'Penggunaan'),
		'departemen' => array('Kode', 'Nama'),
		'outlet'     => array('Kode', 'Nama', 'Brand', 'Region'),
		'channel'    => array('Nama', 'Eksternal'),
		'dokumen'    => array('Nama', 'Kategori', 'Sensitif', 'Wajib default'),
	);
	$levels = array('MP','Staff','Staff_Krusial','Spv','Manager','Senior_Manager');
	$kat    = array('IDENTITAS','PENDIDIKAN','FINANSIAL','LAMARAN');
	$sens   = array('UMUM','IDENTITAS','FINANSIAL');
	?>

	<div style="overflow-x:auto"><table>
		<tr><?php foreach ($col[$t] as $c): ?><th><?= $c ?></th><?php endforeach; ?><th>Status</th><th></th></tr>
		<?php foreach ($rows as $r): ?>
		<tr style="<?= empty($r['is_aktif']) ? 'opacity:.55' : '' ?>">
			<?php if ($t === 'posisi'): ?>
				<td><?= html_escape($r['nama_posisi']) ?></td><td><?= html_escape($r['departemen']) ?></td>
				<td><?= html_escape($r['level_posisi']) ?></td><td><?= html_escape($r['kode_flow'] ?: '-') ?></td>
			<?php elseif ($t === 'stage'): ?>
				<td><code><?= html_escape($r['kode_stage']) ?></code></td>
				<td><?= html_escape($r['nama_tahap']) ?></td>
				<td><span class="tag on"><?= html_escape($r['tipe_tahap']) ?></span></td>
				<td><?= $r['is_terminal'] ? 'Ya' : '-' ?></td>
				<td><?= $r['is_sistem'] ? '<span class="tag off" title="Tahap inti bawaan sistem">Sistem</span>' : 'Custom' ?></td>
				<td>
					<span class="muted" style="font-size:12px">
						<?= (int) $r['n_flow'] ?> flow &middot;
						<a href="<?= site_url('flowbuilder/remarks/' . (int) $r['id_stage']) ?>"><?= (int) $r['n_remark'] ?> remark</a>
					</span>
				</td>
			<?php elseif ($t === 'departemen'): ?>
				<td><?= html_escape($r['kode']) ?></td><td><?= html_escape($r['nama']) ?></td>
			<?php elseif ($t === 'outlet'): ?>
				<td><?= html_escape($r['kode_outlet']) ?></td><td><?= html_escape($r['nama_outlet']) ?></td>
				<td><?= html_escape($r['brand'] ?: '-') ?></td><td><?= html_escape($r['region'] ?: '-') ?></td>
			<?php elseif ($t === 'channel'): ?>
				<td><?= html_escape($r['nama_channel']) ?></td><td><?= $r['is_eksternal'] ? 'ya' : '-' ?></td>
			<?php elseif ($t === 'dokumen'): ?>
				<td><?= html_escape($r['nama_dokumen']) ?></td><td><?= html_escape($r['kategori']) ?></td>
				<td><?= html_escape($r['tingkat_sensitif']) ?></td><td><?= $r['is_mandatory_default'] ? 'ya' : '-' ?></td>
			<?php endif; ?>
			<td><span class="tag <?= $r['is_aktif'] ? 'on' : 'off' ?>"><?= $r['is_aktif'] ? 'aktif' : 'nonaktif' ?></span></td>
			<td style="white-space:nowrap">
				<?php if ($t === 'stage'): ?>
					<a href="<?= site_url('master/index/stage?edit=' . (int) $r['id_stage']) ?>" class="btn-sm btn-ghost" style="text-decoration:none; display:inline-block">edit</a>
				<?php endif; ?>
				<?= form_open(site_url('master/toggle/' . $t), array('class' => 'inline')) ?>
					<input type="hidden" name="id" value="<?= (int) $r[array_key_first($r)] ?>">
					<input type="hidden" name="is_aktif" value="<?= $r['is_aktif'] ? 0 : 1 ?>">
					<button class="btn-sm btn-ghost"><?= $r['is_aktif'] ? 'nonaktifkan' : 'aktifkan' ?></button>
				<?= form_close() ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h2><?= ! empty($edit_row) ? 'Edit Tahap Seleksi #' . (int) $edit_row['id_stage'] : 'Tambah ' . html_escape($types[$t]['label']) ?></h2>
	<?php if (! empty($edit_row)): ?>
		<p class="muted" style="margin-top:0"><a href="<?= site_url('master/index/stage') ?>">&larr; batal edit / tambah tahap baru</a></p>
	<?php endif; ?>

	<?= validation_errors('<div class="flash err">', '</div>') ?>
	<?= form_open(site_url('master/save/' . $t)) ?>
		<?php if ($t === 'posisi'): ?>
			<label>Nama posisi</label><input type="text" name="nama_posisi" required>
			<label>Departemen</label>
			<select name="id_departemen" required>
				<?php foreach ($depts as $d): ?><option value="<?= (int) $d['id_departemen'] ?>"><?= html_escape($d['nama']) ?></option><?php endforeach; ?>
			</select>
			<label>Level</label>
			<select name="level_posisi"><?php foreach ($levels as $l): ?><option><?= $l ?></option><?php endforeach; ?></select>
			<label>Flow default</label>
			<select name="default_flow"><option value="">-</option>
				<?php foreach ($flows as $f): ?><option value="<?= (int) $f['id_flow'] ?>"><?= html_escape($f['kode_flow']) ?></option><?php endforeach; ?>
			</select>
		<?php elseif ($t === 'stage'): ?>
			<?php if (! empty($edit_row)): ?>
				<input type="hidden" name="id_stage" value="<?= (int) $edit_row['id_stage'] ?>">
			<?php endif; ?>
			<label>Kode stage</label>
			<input type="text" name="kode_stage" value="<?= html_escape($edit_row['kode_stage'] ?? '') ?>" placeholder="MIS. TES_KODING" required <?= ! empty($edit_row['is_sistem']) ? 'readonly style="background:#f1f2f4"' : '' ?> style="text-transform:uppercase">
			<label>Nama tahap</label>
			<input type="text" name="nama_tahap" value="<?= html_escape($edit_row['nama_tahap'] ?? '') ?>" placeholder="Mis. Tes Koding Praktik" required>
			<label>Tipe tahap (Sumbu Report &mdash; 7 Tipe Wajib)</label>
			<select name="tipe_tahap" required <?= ! empty($edit_row['is_sistem']) ? 'disabled' : '' ?>>
				<?php foreach ($tipe_tahap as $tp): ?>
					<option value="<?= $tp ?>" <?= (isset($edit_row['tipe_tahap']) && $edit_row['tipe_tahap'] === $tp) ? 'selected' : '' ?>><?= $tp ?></option>
				<?php endforeach; ?>
			</select>
			<?php if (! empty($edit_row['is_sistem'])): ?>
				<input type="hidden" name="tipe_tahap" value="<?= html_escape($edit_row['tipe_tahap']) ?>">
				<span class="muted" style="display:block; margin-top:2px">Tahap inti sistem tidak dapat diubah kodenya untuk menjamin kelancaran flow &amp; report.</span>
			<?php endif; ?>
			<label style="margin-top:10px">
				<input type="checkbox" name="is_terminal" value="1" <?= ! empty($edit_row['is_terminal']) ? 'checked' : '' ?>>
				Tahap terminal (mencapai tahap ini menyelesaikan lamaran, mis. Onboard)
			</label>
		<?php elseif ($t === 'departemen'): ?>
			<label>Kode</label><input type="text" name="kode" required>
			<label>Nama</label><input type="text" name="nama" required>
		<?php elseif ($t === 'outlet'): ?>
			<label>Kode outlet</label><input type="text" name="kode" required>
			<label>Nama outlet</label><input type="text" name="nama" required>
			<label>Brand</label><input type="text" name="brand">
			<label>Region</label><input type="text" name="region">
		<?php elseif ($t === 'channel'): ?>
			<label>Nama channel</label><input type="text" name="nama" required>
			<label><input type="checkbox" name="is_eksternal" value="1"> Channel eksternal</label>
		<?php elseif ($t === 'dokumen'): ?>
			<label>Nama dokumen</label><input type="text" name="nama" required>
			<label>Kategori</label>
			<select name="kategori"><?php foreach ($kat as $k): ?><option><?= $k ?></option><?php endforeach; ?></select>
			<label>Tingkat sensitif</label>
			<select name="tingkat_sensitif"><?php foreach ($sens as $s): ?><option><?= $s ?></option><?php endforeach; ?></select>
			<label><input type="checkbox" name="is_mandatory_default" value="1"> Wajib secara default</label>
		<?php endif; ?>
		<button type="submit"><?= ! empty($edit_row) ? 'Update' : 'Simpan' ?></button>
	<?= form_close() ?>
</main>

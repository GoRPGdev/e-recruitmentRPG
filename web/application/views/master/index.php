<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Master Data</h1>
	<p class="muted" style="margin-top:0">
		<?php foreach ($types as $k => $v): ?>
			<a href="<?= site_url('master/index/' . $k) ?>" <?= $k === $t ? 'style="font-weight:700"' : '' ?>><?= html_escape($v['label']) ?></a>
			<?= $k !== 'dokumen' ? '&middot;' : '' ?>
		<?php endforeach; ?>
	</p>

	<?php
	// definisi kolom & field per tipe
	$col = array(
		'posisi'     => array('Nama', 'Departemen', 'Level', 'Flow default'),
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
			<td>
				<?= form_open(site_url('master/toggle/' . $t), array('class' => 'inline')) ?>
					<input type="hidden" name="id" value="<?= (int) $r[array_key_first($r)] ?>">
					<input type="hidden" name="is_aktif" value="<?= $r['is_aktif'] ? 0 : 1 ?>">
					<button class="btn-sm btn-ghost"><?= $r['is_aktif'] ? 'nonaktifkan' : 'aktifkan' ?></button>
				<?= form_close() ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<h2>Tambah <?= html_escape($types[$t]['label']) ?></h2>
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
		<button type="submit">Simpan</button>
	<?= form_close() ?>
</main>

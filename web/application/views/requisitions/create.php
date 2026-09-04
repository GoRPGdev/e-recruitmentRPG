<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Buat MPR</h1>
	<?= validation_errors('<div class="flash err">', '</div>') ?>

	<?= form_open(site_url('requisitions/create')) ?>
		<label for="id_posisi">Posisi *</label>
		<select id="id_posisi" name="id_posisi" required>
			<option value="">- pilih -</option>
			<?php foreach ($positions as $p): ?>
				<option value="<?= (int) $p['id_posisi'] ?>" <?= set_select('id_posisi', $p['id_posisi']) ?>>
					<?= html_escape($p['nama_posisi'] . ' (' . $p['level_posisi'] . ')') ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="tipe_penempatan">Penempatan *</label>
		<select id="tipe_penempatan" name="tipe_penempatan" required>
			<option value="HQ" <?= set_select('tipe_penempatan', 'HQ') ?>>HQ</option>
			<option value="OUTLET" <?= set_select('tipe_penempatan', 'OUTLET') ?>>Outlet</option>
		</select>

		<label for="id_outlet">Outlet (jika OUTLET)</label>
		<select id="id_outlet" name="id_outlet">
			<option value="">-</option>
			<?php foreach ($outlets as $o): ?>
				<option value="<?= (int) $o['id_outlet'] ?>" <?= set_select('id_outlet', $o['id_outlet']) ?>><?= html_escape($o['nama_outlet']) ?></option>
			<?php endforeach; ?>
		</select>

		<label for="jumlah_dibutuhkan">Jumlah dibutuhkan *</label>
		<input type="text" id="jumlah_dibutuhkan" name="jumlah_dibutuhkan" value="<?= set_value('jumlah_dibutuhkan', '1') ?>" inputmode="numeric" required>

		<label for="status_karyawan">Status karyawan</label>
		<select id="status_karyawan" name="status_karyawan">
			<option value="">-</option>
			<?php foreach (array('Tetap','Kontrak','Harian','Magang') as $s): ?>
				<option value="<?= $s ?>" <?= set_select('status_karyawan', $s) ?>><?= $s ?></option>
			<?php endforeach; ?>
		</select>

		<label for="alasan_permintaan">Alasan permintaan</label>
		<input type="text" id="alasan_permintaan" name="alasan_permintaan" value="<?= set_value('alasan_permintaan') ?>" placeholder="Penggantian / Penambahan / ...">

		<label for="target_tanggal_join">Target tanggal join</label>
		<input type="text" id="target_tanggal_join" name="target_tanggal_join" value="<?= set_value('target_tanggal_join') ?>" placeholder="YYYY-MM-DD">

		<label for="urgensi">Urgensi</label>
		<input type="text" id="urgensi" name="urgensi" value="<?= set_value('urgensi') ?>" placeholder="Normal / Tinggi / Mendesak">

		<label>
			<input type="checkbox" name="butuh_psikotes" value="1" <?= set_checkbox('butuh_psikotes', '1') ?>> Butuh psikotes
		</label>
		<label>
			<input type="checkbox" name="butuh_interview_bod" value="1" <?= set_checkbox('butuh_interview_bod', '1') ?>> Butuh interview BOD
		</label>

		<label for="job_desc">Job desc</label>
		<input type="text" id="job_desc" name="job_desc" value="<?= set_value('job_desc') ?>">
		<label for="kualifikasi">Kualifikasi</label>
		<input type="text" id="kualifikasi" name="kualifikasi" value="<?= set_value('kualifikasi') ?>">

		<?php if (can_sensitif('GAJI')): ?>
		<label for="range_gaji_min">Range gaji min / max (SENSITIF)</label>
		<div style="display:flex; gap:8px">
			<input type="text" id="range_gaji_min" name="range_gaji_min" value="<?= set_value('range_gaji_min') ?>" inputmode="numeric" style="flex:1" placeholder="Gaji min (Rp)">
			<input type="text" name="range_gaji_max" value="<?= set_value('range_gaji_max') ?>" inputmode="numeric" style="flex:1" placeholder="Gaji max (Rp)">
		</div>
		<?php endif; ?>

		<button type="submit">Buat draft</button>
	<?= form_close() ?>
</main>

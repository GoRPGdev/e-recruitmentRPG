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
		<input type="date" id="target_tanggal_join" name="target_tanggal_join" value="<?= set_value('target_tanggal_join') ?>">

		<label for="urgensi">Urgensi</label>
		<input type="text" id="urgensi" name="urgensi" value="<?= set_value('urgensi') ?>" placeholder="Normal / Tinggi / Mendesak">

<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px; margin:14px 0">
			<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px">
				<div style="font-weight:700; font-size:13.5px; color:var(--text)">Spesifikasi Jabatan & Standar Posisi</div>
				<span class="muted" style="font-size:11.5px">Otomatis terisi dari data Master Posisi HR</span>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="pendidikan_minimal" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Pendidikan Minimal</label>
					<select id="pendidikan_minimal" name="pendidikan_minimal" style="width:100%">
						<option value="">- Fleksibel / Template Master -</option>
						<option value="SMA/SMK" <?= set_select('pendidikan_minimal', 'SMA/SMK') ?>>SMA/SMK Sederajat</option>
						<option value="D3" <?= set_select('pendidikan_minimal', 'D3') ?>>Diploma (D3)</option>
						<option value="S1" <?= set_select('pendidikan_minimal', 'S1') ?>>Sarjana (S1 / D4)</option>
						<option value="S2" <?= set_select('pendidikan_minimal', 'S2') ?>>Magister (S2)</option>
					</select>
				</div>
				<div>
					<label for="pengalaman_minimal_tahun" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Pengalaman Minimal (Tahun)</label>
					<input type="number" id="pengalaman_minimal_tahun" name="pengalaman_minimal_tahun" value="<?= set_value('pengalaman_minimal_tahun') ?>" min="0" max="30" placeholder="0 = Fresh graduate" style="width:100%">
				</div>
			</div>

			<div style="margin-bottom:12px">
				<label for="job_desc" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Deskripsi Pekerjaan (Job Description)</label>
				<textarea id="job_desc" name="job_desc" rows="4" placeholder="Uraian tugas dan tanggung jawab jabatan (kosongkan jika ingin memakai standar template HR)..." style="width:100%; font-size:12.5px; line-height:1.4"><?= set_value('job_desc') ?></textarea>
			</div>

			<div>
				<label for="kualifikasi" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Kualifikasi & Persyaratan</label>
				<textarea id="kualifikasi" name="kualifikasi" rows="4" placeholder="Persyaratan kompetensi, keahlian, dan kriteria pelamar (kosongkan jika ingin memakai standar template HR)..." style="width:100%; font-size:12.5px; line-height:1.4"><?= set_value('kualifikasi') ?></textarea>
			</div>
		</div>

<button type="submit">Buat draft</button>
	<?= form_close() ?>
</main>

<script>
(function() {
	var positionsData = <?= json_encode(array_column($positions, NULL, 'id_posisi')) ?>;
	var selectPosisi = document.getElementById('id_posisi');
	var fieldPend = document.getElementById('pendidikan_minimal');
	var fieldExp = document.getElementById('pengalaman_minimal_tahun');
	var fieldJd = document.getElementById('job_desc');
	var fieldKual = document.getElementById('kualifikasi');

	if (!selectPosisi) return;

	selectPosisi.addEventListener('change', function() {
		var posId = this.value;
		if (!posId || !positionsData[posId]) return;

		var pos = positionsData[posId];
		// Auto-populate hanya jika field masih kosong atau user berganti posisi
		if (pos.pendidikan_minimal && (!fieldPend.value || fieldPend.dataset.autofilled === '1')) {
			fieldPend.value = pos.pendidikan_minimal;
			fieldPend.dataset.autofilled = '1';
		}
		if (pos.pengalaman_minimal_tahun !== null && pos.pengalaman_minimal_tahun !== undefined && (!fieldExp.value || fieldExp.dataset.autofilled === '1')) {
			fieldExp.value = pos.pengalaman_minimal_tahun;
			fieldExp.dataset.autofilled = '1';
		}
		if (pos.job_desc && (!fieldJd.value.trim() || fieldJd.dataset.autofilled === '1')) {
			fieldJd.value = pos.job_desc;
			fieldJd.dataset.autofilled = '1';
		}
		if (pos.kualifikasi && (!fieldKual.value.trim() || fieldKual.dataset.autofilled === '1')) {
			fieldKual.value = pos.kualifikasi;
			fieldKual.dataset.autofilled = '1';
		}
	});

	// Tandai jika user mengubah manual sehingga tidak ditimpa
	[fieldPend, fieldExp, fieldJd, fieldKual].forEach(function(el) {
		if (!el) return;
		el.addEventListener('input', function() {
			delete this.dataset.autofilled;
		});
	});
})();
</script>

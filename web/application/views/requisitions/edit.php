<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:16px">
		<div>
			<h1 style="margin:0">Edit MPR <?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?></h1>
			<div class="muted" style="font-size:12px; margin-top:2px">
				Status: <span class="tag <?= ($req['status_req'] === 'Revisi_HR' || $req['status_req'] === 'Revisi_BOD') ? 'warn' : 'off' ?>" style="font-size:11px; padding:2px 6px"><?= html_escape(label_status_req($req['status_req'])) ?></span>
			</div>
		</div>
		<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-ghost">&larr; Kembali ke Detail</a>
	</div>

	<?php if ($req['status_req'] === 'Revisi_HR' && !empty($req['catatan_hr'])): ?>
	<div style="padding:12px 16px; margin-bottom:18px; border-radius:8px; border:1px solid var(--warn); background:var(--warn-soft)">
		<div class="eyebrow" style="color:var(--warn); font-size:11px; margin-bottom:4px">Arahan Revisi dari HR:</div>
		<div style="font-size:13px; color:var(--text); line-height:1.5; white-space:pre-line"><?= html_escape($req['catatan_hr']) ?></div>
	</div>
	<?php elseif ($req['status_req'] === 'Revisi_BOD' && !empty($req['catatan_bod'])): ?>
	<div style="padding:12px 16px; margin-bottom:18px; border-radius:8px; border:1px solid var(--warn); background:var(--warn-soft)">
		<div class="eyebrow" style="color:var(--warn); font-size:11px; margin-bottom:4px">Arahan Revisi dari Direksi (BOD):</div>
		<div style="font-size:13px; color:var(--text); line-height:1.5; white-space:pre-line"><?= html_escape($req['catatan_bod']) ?></div>
	</div>
	<?php endif; ?>

	<?= validation_errors('<div class="flash err">', '</div>') ?>

	<?= form_open(site_url('requisitions/edit/' . (int) $req['id_req'])) ?>
		<label for="id_posisi">Posisi *</label>
		<select id="id_posisi" name="id_posisi" required>
			<option value="">- pilih -</option>
			<?php foreach ($positions as $p): ?>
				<option value="<?= (int) $p['id_posisi'] ?>" <?= ((int) $p['id_posisi'] === (int) ($req['id_posisi'] ?? 0)) ? 'selected' : '' ?>>
					<?= html_escape($p['nama_posisi'] . ' (' . $p['level_posisi'] . ')') ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="tipe_penempatan">Penempatan *</label>
		<select id="tipe_penempatan" name="tipe_penempatan" required>
			<option value="HQ" <?= ($req['tipe_penempatan'] ?? '') === 'HQ' ? 'selected' : '' ?>>HQ</option>
			<option value="OUTLET" <?= ($req['tipe_penempatan'] ?? '') === 'OUTLET' ? 'selected' : '' ?>>Outlet</option>
		</select>

		<label for="id_outlet">Outlet (jika OUTLET)</label>
		<select id="id_outlet" name="id_outlet">
			<option value="">-</option>
			<?php foreach ($outlets as $o): ?>
				<option value="<?= (int) $o['id_outlet'] ?>" <?= ((int) ($req['id_outlet'] ?? 0) === (int) $o['id_outlet']) ? 'selected' : '' ?>><?= html_escape($o['nama_outlet']) ?></option>
			<?php endforeach; ?>
		</select>

		<label for="jumlah_dibutuhkan">Jumlah dibutuhkan *</label>
		<input type="text" id="jumlah_dibutuhkan" name="jumlah_dibutuhkan" value="<?= html_escape($req['jumlah_dibutuhkan'] ?? '1') ?>" inputmode="numeric" required>

		<label for="status_karyawan">Status karyawan</label>
		<select id="status_karyawan" name="status_karyawan">
			<option value="">-</option>
			<?php foreach (array('Tetap','Kontrak','Harian','Magang') as $s): ?>
				<option value="<?= $s ?>" <?= ($req['status_karyawan'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
			<?php endforeach; ?>
		</select>

		<label for="alasan_permintaan">Alasan permintaan</label>
		<input type="text" id="alasan_permintaan" name="alasan_permintaan" value="<?= html_escape($req['alasan_permintaan'] ?? '') ?>" placeholder="Penggantian / Penambahan / ...">

		<label for="target_tanggal_join">Target tanggal join</label>
		<?php
			$ttj = $req['target_tanggal_join'] ?? '';
			if ($ttj instanceof DateTime) { $ttj = $ttj->format('Y-m-d'); }
			elseif (is_string($ttj)) { $ttj = substr($ttj, 0, 10); }
		?>
		<input type="date" id="target_tanggal_join" name="target_tanggal_join" value="<?= html_escape($ttj) ?>">

		<label for="urgensi">Urgensi</label>
		<input type="text" id="urgensi" name="urgensi" value="<?= html_escape($req['urgensi'] ?? '') ?>" placeholder="Normal / Tinggi / Mendesak">

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
						<?php foreach (array('SMA/SMK' => 'SMA/SMK Sederajat', 'D3' => 'Diploma (D3)', 'S1' => 'Sarjana (S1 / D4)', 'S2' => 'Magister (S2)') as $k => $v): ?>
							<option value="<?= $k ?>" <?= ($req['pendidikan_minimal'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="pengalaman_minimal_tahun" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Pengalaman Minimal (Tahun)</label>
					<input type="number" id="pengalaman_minimal_tahun" name="pengalaman_minimal_tahun" value="<?= html_escape($req['pengalaman_minimal_tahun'] ?? '') ?>" min="0" max="30" placeholder="0 = Fresh graduate" style="width:100%">
				</div>
			</div>

			<div style="margin-bottom:12px">
				<label for="job_desc" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Deskripsi Pekerjaan (Job Description)</label>
				<textarea id="job_desc" name="job_desc" rows="4" placeholder="Uraian tugas dan tanggung jawab jabatan..." style="width:100%; font-size:12.5px; line-height:1.4"><?= html_escape($req['job_desc'] ?? '') ?></textarea>
			</div>

			<div>
				<label for="kualifikasi" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Kualifikasi & Persyaratan</label>
				<textarea id="kualifikasi" name="kualifikasi" rows="4" placeholder="Persyaratan kompetensi, keahlian, dan kriteria pelamar..." style="width:100%; font-size:12.5px; line-height:1.4"><?= html_escape($req['kualifikasi'] ?? '') ?></textarea>
			</div>
		</div>

		<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
			<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
			<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>" class="btn btn-ghost">Batal</a>
		</div>
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

	[fieldPend, fieldExp, fieldJd, fieldKual].forEach(function(el) {
		if (!el) return;
		el.addEventListener('input', function() {
			delete this.dataset.autofilled;
		});
	});
})();
</script>

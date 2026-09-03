<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Entry Manual</h1>
	<p class="muted" style="margin-top:0">Untuk MP/outlet, walk-in, atau referral. CV opsional.</p>

	<?= validation_errors('<div class="flash err">', '</div>') ?>

	<?php if ( ! $reqs): ?>
		<div class="flash err">Tidak ada requisition yang menerima lamaran. Buat / setujui MPR dulu.</div>
	<?php else: ?>
	<?= form_open_multipart(site_url('manual')) ?>

		<label for="id_req">Requisition *</label>
		<select id="id_req" name="id_req" required>
			<option value="">- pilih -</option>
			<?php foreach ($reqs as $r): ?>
				<option value="<?= (int) $r['id_req'] ?>" <?= set_select('id_req', $r['id_req']) ?>>
					<?= html_escape(($r['no_mpr'] ?: '#' . $r['id_req']) . ' — ' . $r['nama_posisi']
						. ' [' . $r['tipe_penempatan'] . ($r['nama_outlet'] ? ' / ' . $r['nama_outlet'] : '') . ']') ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="nama_lengkap">Nama lengkap *</label>
		<input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= set_value('nama_lengkap') ?>" required>

		<label for="no_wa">Nomor WhatsApp *</label>
		<input type="text" id="no_wa" name="no_wa" value="<?= set_value('no_wa') ?>" placeholder="08xx / +62xx" required>

		<label for="kota_domisili">Kota domisili</label>
		<input type="text" id="kota_domisili" name="kota_domisili" value="<?= set_value('kota_domisili') ?>">

		<label for="nama_channel">Channel</label>
		<select id="nama_channel" name="nama_channel">
			<?php foreach ($channels as $c): ?>
				<option value="<?= html_escape($c['nama_channel']) ?>" <?= $c['nama_channel'] === 'Walk-in' ? 'selected' : '' ?>>
					<?= html_escape($c['nama_channel']) ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="tanggal_join">Rencana tanggal join (opsional)</label>
		<input type="text" id="tanggal_join" name="tanggal_join" value="<?= set_value('tanggal_join') ?>" placeholder="YYYY-MM-DD">

		<label for="cv">CV (opsional)</label>
		<input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

		<button type="submit">Simpan</button>
	<?= form_close() ?>
	<?php endif; ?>
</main>

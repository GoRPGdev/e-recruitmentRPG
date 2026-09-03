<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>MPR <?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?></h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('requisitions') ?>">&larr; daftar</a> &middot;
		<a href="<?= site_url('pipeline/index/' . (int) $req['id_req']) ?>">pipeline</a>
	</p>

	<table>
		<tr><th>Posisi</th><td><?= html_escape($req['nama_posisi']) ?> &middot; <?= html_escape($req['departemen'] ?: '-') ?></td></tr>
		<tr><th>Pemohon</th><td><?= html_escape($req['pemohon']) ?></td></tr>
		<tr><th>Penempatan</th><td><?= html_escape($req['tipe_penempatan'] . ($req['nama_outlet'] ? ' / ' . $req['nama_outlet'] : '')) ?></td></tr>
		<tr><th>Flow</th><td><?= html_escape($req['kode_flow'] ?: '(belum ada)') ?></td></tr>
		<tr><th>Jumlah</th><td>butuh <?= (int) $req['jumlah_dibutuhkan'] ?> · setuju <?= $req['jumlah_disetujui'] === NULL ? '-' : (int) $req['jumlah_disetujui'] ?> · terpenuhi <?= (int) $req['jumlah_terpenuhi'] ?></td></tr>
		<tr><th>Status</th><td><span class="tag <?= in_array($req['status_req'], array('Sourcing','Approved','Terpenuhi','Terpenuhi_Sebagian')) ? 'on' : 'off' ?>"><?= html_escape($req['status_req']) ?></span></td></tr>
	</table>

	<?php if (in_array($req['status_req'], array('Draft', 'Sourcing_Ulang'))): ?>
		<?= form_open(site_url('requisitions/submit/' . (int) $req['id_req']), array('class' => 'inline')) ?>
			<button type="submit">Ajukan ke BOD</button>
		<?= form_close() ?>
	<?php endif; ?>

	<h2>Riwayat approval</h2>
	<?php if ( ! $approvals): ?>
		<p class="muted">Belum diajukan.</p>
	<?php else: ?>
	<table>
		<tr><th>Putaran</th><th>Diajukan</th><th>Keputusan</th><th>Disetujui</th><th>Tanggal</th><th>Oleh BOD</th><th>Catatan</th></tr>
		<?php foreach ($approvals as $a): ?>
		<tr>
			<td><?= (int) $a['putaran_ke'] ?></td>
			<td><?= $a['diajukan_ke_bod_pada'] ? html_escape(substr($a['diajukan_ke_bod_pada'], 0, 10)) : '-' ?></td>
			<td><?= html_escape($a['keputusan']) ?></td>
			<td><?= $a['jumlah_disetujui'] === NULL ? '-' : (int) $a['jumlah_disetujui'] ?></td>
			<td><?= $a['tanggal_keputusan'] ? html_escape(substr($a['tanggal_keputusan'], 0, 10)) : '-' ?></td>
			<td><?= html_escape($a['disetujui_oleh'] ?: '-') ?></td>
			<td class="muted" style="margin:0"><?= html_escape($a['catatan_bod'] ?: '') ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php endif; ?>

	<?php if ($can_kelola && $open_appr): ?>
		<h2>Catat keputusan BOD (putaran <?= (int) $open_appr['putaran_ke'] ?>)</h2>
		<?= form_open(site_url('requisitions/approve/' . (int) $req['id_req'])) ?>
			<input type="hidden" name="id_approval" value="<?= (int) $open_appr['id_approval'] ?>">
			<label for="keputusan">Keputusan</label>
			<select id="keputusan" name="keputusan">
				<option value="Approved">Approved</option>
				<option value="Approved_Sebagian">Approved sebagian</option>
				<option value="Rejected">Rejected</option>
			</select>
			<label for="jumlah_disetujui">Jumlah disetujui</label>
			<input type="text" id="jumlah_disetujui" name="jumlah_disetujui" value="<?= (int) $req['jumlah_dibutuhkan'] ?>" inputmode="numeric">
			<label for="disetujui_oleh">Nama BOD</label>
			<input type="text" id="disetujui_oleh" name="disetujui_oleh">
			<label for="tanggal_keputusan">Tanggal keputusan</label>
			<input type="text" id="tanggal_keputusan" name="tanggal_keputusan" placeholder="YYYY-MM-DD">
			<label for="catatan_bod">Catatan</label>
			<input type="text" id="catatan_bod" name="catatan_bod">
			<button type="submit">Catat</button>
		<?= form_close() ?>
	<?php endif; ?>

	<?php if ($can_kelola && in_array($req['status_req'], array('Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian'))): ?>
		<h2>Buat job posting</h2>
		<?= form_open(site_url('requisitions/post_job/' . (int) $req['id_req'])) ?>
			<label for="id_channel">Channel</label>
			<select id="id_channel" name="id_channel">
				<?php foreach ($channels as $c): ?>
					<option value="<?= (int) $c['id_channel'] ?>"><?= html_escape($c['nama_channel']) ?></option>
				<?php endforeach; ?>
			</select>
			<label for="judul_posting">Judul posting</label>
			<input type="text" id="judul_posting" name="judul_posting" value="<?= html_escape($req['nama_posisi']) ?>">
			<button type="submit">Buat posting</button>
		<?= form_close() ?>
	<?php endif; ?>
</main>

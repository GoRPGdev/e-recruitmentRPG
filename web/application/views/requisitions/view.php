<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="card" style="padding:22px 26px; margin-bottom:20px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Detail Permintaan Tenaga Kerja</div>
			<h1 style="margin:0 0 6px; font-size:22px">
				MPR: <?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?>
			</h1>
			<div class="muted" style="font-size:13px">
				Posisi: <strong><?= html_escape($req['nama_posisi']) ?></strong> &middot;
				Departemen: <strong><?= html_escape($req['departemen'] ?: '-') ?></strong>
			</div>
		</div>
		<div style="display:flex; gap:8px; align-items:center">
			<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') ?>">&larr; Kembali ke Daftar</a>
			<a class="btn btn-sm btn-primary" href="<?= site_url('pipeline/index/' . (int) $req['id_req']) ?>">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
				<span>Buka Pipeline Pelamar &rarr;</span>
			</a>
		</div>
	</div>

	<!-- Tabel Info Requisition -->
	<div style="overflow-x:auto; margin-bottom:20px">
		<table style="margin:0">
			<tr>
				<th style="width:180px">Status MPR</th>
				<td>
					<span class="tag <?= in_array($req['status_req'], array('Sourcing','Approved','Terpenuhi','Terpenuhi_Sebagian')) ? 'on' : ($req['status_req'] === 'Menunggu_BOD' ? 'warn' : 'off') ?>">
						<?= html_escape($req['status_req']) ?>
					</span>
				</td>
			</tr>
			<tr><th>Pemohon</th><td><strong><?= html_escape($req['pemohon']) ?></strong></td></tr>
			<tr><th>Tipe Penempatan</th><td><?= html_escape($req['tipe_penempatan'] . ($req['nama_outlet'] ? ' &mdash; ' . $req['nama_outlet'] : ' (Headquarters)')) ?></td></tr>
			<tr><th>Alur Seleksi (Flow)</th><td><span class="mono"><?= html_escape($req['kode_flow'] ?: '(Belum ditentukan)') ?></span></td></tr>
			<tr>
				<th>Kebutuhan Formasi</th>
				<td>
					Dibutuhkan: <strong><?= (int) $req['jumlah_dibutuhkan'] ?></strong> orang &middot;
					Disetujui BOD: <strong><?= $req['jumlah_disetujui'] === NULL ? '-' : (int) $req['jumlah_disetujui'] ?></strong> &middot;
					Terpenuhi: <strong style="color:var(--good)"><?= (int) $req['jumlah_terpenuhi'] ?></strong>
				</td>
			</tr>
			<?php if (can_sensitif('GAJI')): ?>
			<tr>
				<th>Range Gaji (Budget)</th>
				<td>
					<strong style="color:var(--accent)">
						<?= ($req['range_gaji_min'] !== NULL || $req['range_gaji_max'] !== NULL)
							? 'Rp ' . number_format((float) $req['range_gaji_min'], 0, ',', '.') . ' &mdash; Rp ' . number_format((float) $req['range_gaji_max'], 0, ',', '.')
							: '<span class="faint">(Belum ditentukan)</span>' ?>
					</strong>
				</td>
			</tr>
			<?php endif; ?>
		</table>
	</div>

	<?php if (in_array($req['status_req'], array('Draft', 'Sourcing_Ulang'))): ?>
		<div style="padding:14px; background:var(--warn-soft); border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center">
			<div>
				<strong style="color:var(--warn)">Dokumen siap diajukan ke BOD?</strong>
				<div class="muted" style="font-size:12px">Setelah diajukan, nomor MPR resmi akan di-generate secara otomatis dan atomik.</div>
			</div>
			<?= form_open(site_url('requisitions/submit/' . (int) $req['id_req']), array('class' => 'inline')) ?>
				<button type="submit" class="btn btn-sm btn-primary">Ajukan Persetujuan BOD &rarr;</button>
			<?= form_close() ?>
		</div>
	<?php endif; ?>

	<!-- Riwayat Approval BOD -->
	<div style="margin-top:24px; padding-top:16px; border-top:1px solid var(--border)">
		<h2 style="font-size:16px; margin-bottom:10px">Riwayat Persetujuan BOD (Requisition Approvals)</h2>
		<?php if ( ! $approvals): ?>
			<p class="muted" style="font-size:13px">Belum ada riwayat pengajuan persetujuan.</p>
		<?php else: ?>
		<div style="overflow-x:auto">
			<table style="margin:0">
				<thead>
					<tr>
						<th>Putaran</th>
						<th>Tanggal Pengajuan</th>
						<th>Keputusan</th>
						<th>Disetujui</th>
						<th>Tgl Keputusan</th>
						<th>Oleh Direksi (BOD)</th>
						<th>Catatan</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($approvals as $a): ?>
					<tr>
						<td class="mono">Ke-<?= (int) $a['putaran_ke'] ?></td>
						<td><?= $a['diajukan_ke_bod_pada'] ? html_escape(substr($a['diajukan_ke_bod_pada'], 0, 10)) : '-' ?></td>
						<td><span class="tag <?= $a['keputusan'] === 'Approved' ? 'on' : ($a['keputusan'] === 'Rejected' ? 'off' : 'warn') ?>"><?= html_escape($a['keputusan'] ?: 'Menunggu') ?></span></td>
						<td style="font-weight:600"><?= $a['jumlah_disetujui'] === NULL ? '-' : (int) $a['jumlah_disetujui'] ?> org</td>
						<td><?= $a['tanggal_keputusan'] ? html_escape(substr($a['tanggal_keputusan'], 0, 10)) : '-' ?></td>
						<td><strong><?= html_escape($a['disetujui_oleh'] ?: '-') ?></strong></td>
						<td class="muted"><?= html_escape($a['catatan_bod'] ?: '-') ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

	<!-- Form Catat Approval jika open -->
	<?php if ($can_kelola && $open_appr): ?>
		<div style="margin-top:24px; padding:18px; background:var(--surface-2); border-radius:8px">
			<h2 style="font-size:15px; margin-top:0">Catat Keputusan BOD (Putaran <?= (int) $open_appr['putaran_ke'] ?>)</h2>
			<?= form_open_multipart(site_url('requisitions/approve/' . (int) $req['id_req'])) ?>
				<input type="hidden" name="id_approval" value="<?= (int) $open_appr['id_approval'] ?>">
				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px">
					<div>
						<label for="keputusan">Keputusan</label>
						<select id="keputusan" name="keputusan">
							<option value="Approved">Approved</option>
							<option value="Approved_Sebagian">Approved Sebagian</option>
							<option value="Rejected">Rejected</option>
						</select>
					</div>
					<div>
						<label for="jumlah_disetujui">Jumlah Disetujui</label>
						<input type="text" id="jumlah_disetujui" name="jumlah_disetujui" value="<?= (int) $req['jumlah_dibutuhkan'] ?>" inputmode="numeric">
					</div>
					<div>
						<label for="disetujui_oleh">Nama Anggota BOD</label>
						<input type="text" id="disetujui_oleh" name="disetujui_oleh" placeholder="e.g. Ibu Ratna / Pak Bambang" required>
					</div>
					<div>
						<label for="tanggal_keputusan">Tanggal Keputusan</label>
						<input type="date" id="tanggal_keputusan" name="tanggal_keputusan" value="<?= date('Y-m-d') ?>">
					</div>
				</div>

				<label for="catatan_bod">Catatan BOD</label>
				<input type="text" id="catatan_bod" name="catatan_bod" placeholder="Catatan approval atau arahan kualifikasi...">

				<label for="lampiran">Lampiran Bukti Approval WA/Memo (JPG/PNG/PDF, Opsional)</label>
				<input type="file" id="lampiran" name="lampiran" accept=".jpg,.jpeg,.png,.pdf">

				<div style="margin-top:14px">
					<button type="submit" class="btn btn-sm btn-primary">Simpan Keputusan BOD</button>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>

	<!-- Form Buat Job Posting jika sudah approved/sourcing -->
	<?php if ($can_kelola && in_array($req['status_req'], array('Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian'))): ?>
		<div style="margin-top:24px; padding:18px; background:var(--surface-2); border-radius:8px">
			<h2 style="font-size:15px; margin-top:0">Publikasi Link Lowongan (Job Posting)</h2>
			<?= form_open(site_url('requisitions/post_job/' . (int) $req['id_req'])) ?>
				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:12px">
					<div>
						<label for="id_channel">Saluran / Channel</label>
						<select id="id_channel" name="id_channel">
							<?php foreach ($channels as $c): ?>
								<option value="<?= (int) $c['id_channel'] ?>"><?= html_escape($c['nama_channel']) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="judul_posting">Judul Lowongan</label>
						<input type="text" id="judul_posting" name="judul_posting" value="<?= html_escape($req['nama_posisi']) ?>" required>
					</div>
				</div>
				<div style="margin-top:12px">
					<button type="submit" class="btn btn-sm btn-primary">+ Generate Link & Form Publik</button>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>
</div>

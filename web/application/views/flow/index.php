<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:2px">Konfigurasi Alur Seleksi</div>
			<h1 style="margin:0 0 4px; font-size:20px">Flow Builder &mdash; 1 Flow Standar RPG</h1>
			<p class="muted" style="margin:0">
				Sistem rekrutmen RPG menggunakan 1 alur seleksi standar korporat baku untuk seluruh proses kandidat.
			</p>
		</div>
		<div style="display:flex; gap:8px; flex-wrap:wrap">
			<a href="<?= site_url('flowbuilder/stages') ?>" class="btn btn-sm btn-ghost">Master Tahap &rarr;</a>
			<a href="<?= site_url('flowbuilder/remarks') ?>" class="btn btn-sm btn-ghost">Master Remark &rarr;</a>
			<a href="<?= site_url('flowbuilder/flow_docs') ?>" class="btn btn-sm btn-ghost">Dokumen Wajib &rarr;</a>
		</div>
	</div>

	<?php
	// Identifikasi 1 Flow Standar Utama RPG (prioritaskan HQ_STAFF atau flow aktif pertama)
	$std_flow = NULL;
	foreach ($flows as $f) {
		if ($f['kode_flow'] === 'HQ_STAFF') {
			$std_flow = $f;
			break;
		}
	}
	if ( ! $std_flow && ! empty($flows)) {
		$std_flow = $flows[0];
	}
	?>

	<?php if ($std_flow): ?>
	<!-- Kartu Fokus: 1 Flow Standar Baku Perusahaan -->
	<div style="background:var(--surface-2); border:2px solid var(--accent); border-radius:10px; padding:20px; margin:20px 0">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px">
			<div>
				<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
					<span class="tag on" style="font-size:11px; font-weight:700">FLOW STANDAR RESMI RPG</span>
					<span class="muted mono" style="font-size:12px">v<?= (int) $std_flow['versi'] ?></span>
				</div>
				<h2 style="margin:0 0 6px; font-size:18px">
					<?= html_escape($std_flow['nama_flow']) ?> (<code><?= html_escape($std_flow['kode_flow']) ?></code>)
				</h2>
				<div class="muted" style="font-size:13px; display:flex; gap:12px; flex-wrap:wrap">
					<span>Penempatan: <strong><?= html_escape($std_flow['tipe_penempatan']) ?></strong></span>
					<span>&bull;</span>
					<span>Jumlah Tahap: <strong><?= (int) $std_flow['n_stage'] ?> tahap seleksi</strong></span>
					<span>&bull;</span>
					<span>Maks. Kontak WA: <strong><?= (int) $std_flow['maks_upaya_kontak'] ?>x</strong></span>
				</div>
			</div>
			<div style="display:flex; gap:8px; align-items:center">
				<a href="<?= site_url('flowbuilder/edit/' . (int) $std_flow['id_flow']) ?>" class="btn btn-primary">
					<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
					<span>Kelola Tahapan Flow Standar</span>
				</a>
			</div>
		</div>
		<p class="muted" style="font-size:12.5px; margin:0; line-height:1.5">
			Alur ini menjadi acuan utama setiap posisi dan MPR lowongan. Tahapan yang diubah di sini akan otomatis menjadi alur standar bagi lowongan baru yang dibuka.
		</p>
	</div>
	<?php endif; ?>

	<!-- Daftar Flow Cadangan / Arsip (Opsional) -->
	<details style="margin-top:20px; border:1px solid var(--border); border-radius:8px; padding:12px 16px; background:var(--surface)">
		<summary style="cursor:pointer; font-weight:600; font-size:13.5px; color:var(--text-muted)">
			Daftar Template Flow Lainnya (<?= count($flows) ?> template terdaftar)
		</summary>
		<div style="overflow-x:auto; margin-top:14px">
			<table>
				<tr><th>Kode</th><th>Nama</th><th>Penempatan</th><th>Versi</th><th>Tahap</th><th>Maks kontak</th><th>Status</th><th>Aksi</th></tr>
				<?php foreach ($flows as $f): ?>
				<tr style="<?= $f['is_aktif'] ? '' : 'opacity:.55' ?>">
					<td><code><?= html_escape($f['kode_flow']) ?></code></td>
					<td><?= html_escape($f['nama_flow']) ?></td>
					<td><?= html_escape($f['tipe_penempatan']) ?></td>
					<td><?= (int) $f['versi'] ?></td>
					<td><?= (int) $f['n_stage'] ?></td>
					<td><?= (int) $f['maks_upaya_kontak'] ?></td>
					<td><span class="tag <?= $f['is_aktif'] ? 'on' : 'off' ?>"><?= $f['is_aktif'] ? 'aktif' : 'nonaktif' ?></span></td>
					<td style="white-space:nowrap">
						<a href="<?= site_url('flowbuilder/edit/' . (int) $f['id_flow']) ?>" class="btn-sm btn-ghost" style="text-decoration:none">edit</a>
						<?= form_open(site_url('flowbuilder/toggle/' . (int) $f['id_flow']), array('class' => 'inline')) ?>
							<input type="hidden" name="is_aktif" value="<?= $f['is_aktif'] ? 0 : 1 ?>">
							<button class="btn-sm btn-ghost"><?= $f['is_aktif'] ? 'nonaktif' : 'aktif' ?></button>
						<?= form_close() ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:16px">
			<div>
				<h3 style="font-size:14px; margin-bottom:8px">Tambah Flow Alternatif</h3>
				<?= form_open(site_url('flowbuilder/save_header')) ?>
					<label>Kode flow</label><input type="text" name="kode_flow" placeholder="Mis. HQ_KHUSUS" required>
					<label>Nama flow</label><input type="text" name="nama_flow" required>
					<label>Penempatan</label><select name="tipe_penempatan"><option>HQ</option><option>OUTLET</option></select>
					<label>Maks kontak</label><input type="text" name="maks_upaya_kontak" value="3" inputmode="numeric">
					<label>SLA hari (opsional)</label><input type="text" name="sla_total_hari" inputmode="numeric">
					<div style="margin-top:10px"><button type="submit" class="btn-sm">Buat Flow</button></div>
				<?= form_close() ?>
			</div>
			<div>
				<h3 style="font-size:14px; margin-bottom:8px">Duplikasi Flow (Clone)</h3>
				<?= form_open(site_url('flowbuilder/clone_flow')) ?>
					<label>Flow sumber</label>
					<select name="id_flow_sumber">
						<?php foreach ($flows as $f): ?><option value="<?= (int) $f['id_flow'] ?>"><?= html_escape($f['kode_flow']) ?></option><?php endforeach; ?>
					</select>
					<label>Kode flow baru</label><input type="text" name="kode_flow_baru" required>
					<label>Nama flow baru</label><input type="text" name="nama_flow_baru" required>
					<div style="margin-top:10px"><button type="submit" class="btn-sm">Clone Flow</button></div>
				<?= form_close() ?>
			</div>
		</div>
	</details>
</main>

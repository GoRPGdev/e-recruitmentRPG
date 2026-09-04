<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $qs = function ($extra = array()) use ($f) {
	return '?' . http_build_query(array_merge(array_filter((array) $f), $extra));
}; ?>
<div class="card" style="padding:20px 24px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Manpower Requisition (MPR)</div>
			<h1 style="margin:0 0 4px; font-size:20px">Daftar Permintaan Tenaga Kerja</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Ditemukan <strong><?= (int) $total ?></strong> dokumen MPR di database.
			</p>
		</div>
		<div style="display:flex; gap:8px">
			<a href="<?= site_url('requisitions/create') ?>" class="btn btn-sm btn-primary">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Buat Permintaan (MPR)</span>
			</a>
		</div>
	</div>

	<!-- Filter Bar -->
	<form method="get" action="<?= site_url('requisitions') ?>" style="margin:0 0 18px; padding:14px; background:var(--surface-2); border-radius:8px">
		<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Status</label>
				<select name="status" style="width:auto; padding:5px 8px; font-size:12.5px">
					<option value="">Semua Status</option>
					<?php foreach (array('Draft','Menunggu_BOD','Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian','Terpenuhi','Dibatalkan','Kadaluarsa') as $s): ?>
						<option value="<?= $s ?>" <?= (isset($f['status']) && $f['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Departemen</label>
				<select name="dept" style="width:auto; min-width:140px; padding:5px 8px; font-size:12.5px">
					<option value="">Semua Departemen</option>
					<?php foreach ($depts as $d): ?>
						<option value="<?= (int) $d['id_departemen'] ?>" <?= (isset($f['dept']) && $f['dept'] == $d['id_departemen']) ? 'selected' : '' ?>><?= html_escape($d['nama']) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Posisi</label>
				<select name="posisi" style="width:auto; min-width:140px; padding:5px 8px; font-size:12.5px">
					<option value="">Semua Posisi</option>
					<?php foreach ($positions as $p): ?>
						<option value="<?= (int) $p['id_posisi'] ?>" <?= (isset($f['posisi']) && $f['posisi'] == $p['id_posisi']) ? 'selected' : '' ?>><?= html_escape($p['nama_posisi']) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Periode Dibuat</label>
				<div style="display:flex; gap:4px">
					<input type="text" name="dari" value="<?= html_escape(isset($f['dari']) ? $f['dari'] : '') ?>" placeholder="YYYY-MM-DD" style="width:115px; padding:5px 8px; font-size:12.5px">
					<input type="text" name="sampai" value="<?= html_escape(isset($f['sampai']) ? $f['sampai'] : '') ?>" placeholder="YYYY-MM-DD" style="width:115px; padding:5px 8px; font-size:12.5px">
				</div>
			</div>
			<div style="display:flex; gap:6px; margin-left:auto">
				<button type="submit" class="btn btn-sm">Filter</button>
				<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') ?>">Reset</a>
			</div>
		</div>
	</form>

	<?php if ( ! $rows): ?>
		<div style="padding:40px 20px; text-align:center; background:var(--surface-2); border-radius:8px">
			<p class="muted" style="margin:0">Tidak ada data MPR yang sesuai dengan filter.</p>
		</div>
	<?php else: ?>
	<div style="overflow-x:auto">
		<table style="margin:0">
			<thead>
				<tr>
					<th>No MPR</th>
					<th>Posisi Lowongan</th>
					<th>Pemohon</th>
					<th>Penempatan</th>
					<th style="text-align:center">Butuh</th>
					<th style="text-align:center">Disetujui</th>
					<th style="text-align:center">Terpenuhi</th>
					<th>Status</th>
					<th style="text-align:right">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><strong class="mono" style="font-size:12.5px"><?= html_escape($r['no_mpr'] ?: '(Draft)') ?></strong></td>
					<td>
						<strong style="color:var(--text)"><?= html_escape($r['nama_posisi']) ?></strong>
					</td>
					<td><?= html_escape($r['pemohon']) ?></td>
					<td><span class="tag"><?= html_escape($r['tipe_penempatan'] . ($r['nama_outlet'] ? ' / ' . $r['nama_outlet'] : '')) ?></span></td>
					<td style="text-align:center; font-weight:600"><?= (int) $r['jumlah_dibutuhkan'] ?></td>
					<td style="text-align:center; color:var(--text-muted)"><?= $r['jumlah_disetujui'] === NULL ? '-' : (int) $r['jumlah_disetujui'] ?></td>
					<td style="text-align:center; font-weight:700; color:var(--good)"><?= (int) $r['jumlah_terpenuhi'] ?></td>
					<td>
						<span class="tag <?= in_array($r['status_req'], array('Sourcing','Approved','Terpenuhi','Terpenuhi_Sebagian')) ? 'on' : ($r['status_req'] === 'Menunggu_BOD' ? 'warn' : 'off') ?>">
							<?= html_escape($r['status_req']) ?>
						</span>
					</td>
					<td style="text-align:right; white-space:nowrap">
						<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions/view/' . (int) $r['id_req']) ?>">Detail</a>
						<?php
						$can_view_pipeline = (current_user_dept() === NULL || (isset($r['id_departemen']) && (int) $r['id_departemen'] === (int) current_user_dept()));
						if ($can_view_pipeline): ?>
							<a class="btn btn-sm btn-primary" href="<?= site_url('pipeline/index/' . (int) $r['id_req']) ?>">Pipeline &rarr;</a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ($pages > 1): ?>
		<div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:12px; border-top:1px solid var(--border)">
			<span class="muted" style="font-size:12.5px">Halaman <?= $page ?> dari <?= $pages ?></span>
			<div style="display:flex; gap:6px">
				<?php if ($page > 1): ?>
					<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') . $qs(array('page' => $page - 1)) ?>">&larr; Sebelumnya</a>
				<?php endif; ?>
				<?php if ($page < $pages): ?>
					<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') . $qs(array('page' => $page + 1)) ?>">Selanjutnya &rarr;</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	<?php endif; ?>
</div>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $qs = function ($extra = array()) use ($f) {
	return '?' . http_build_query(array_merge(array_filter((array) $f), $extra));
}; ?>
<main class="card">
	<h1>MPR</h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('requisitions/create') ?>">+ Buat MPR</a> &middot; <?= (int) $total ?> hasil
	</p>

	<form method="get" action="<?= site_url('requisitions') ?>" style="margin:0 0 14px">
		<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end">
			<span>
				<label style="margin:0 0 2px">Status</label><br>
				<select name="status" style="width:auto; padding:5px">
					<option value="">semua</option>
					<?php foreach (array('Draft','Menunggu_BOD','Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian','Terpenuhi','Dibatalkan','Kadaluarsa') as $s): ?>
						<option value="<?= $s ?>" <?= (isset($f['status']) && $f['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
					<?php endforeach; ?>
				</select>
			</span>
			<span>
				<label style="margin:0 0 2px">Departemen</label><br>
				<select name="dept" style="width:auto; padding:5px">
					<option value="">semua</option>
					<?php foreach ($depts as $d): ?><option value="<?= (int) $d['id_departemen'] ?>" <?= (isset($f['dept']) && $f['dept'] == $d['id_departemen']) ? 'selected' : '' ?>><?= html_escape($d['nama']) ?></option><?php endforeach; ?>
				</select>
			</span>
			<span>
				<label style="margin:0 0 2px">Posisi</label><br>
				<select name="posisi" style="width:auto; padding:5px">
					<option value="">semua</option>
					<?php foreach ($positions as $p): ?><option value="<?= (int) $p['id_posisi'] ?>" <?= (isset($f['posisi']) && $f['posisi'] == $p['id_posisi']) ? 'selected' : '' ?>><?= html_escape($p['nama_posisi']) ?></option><?php endforeach; ?>
				</select>
			</span>
			<span>
				<label style="margin:0 0 2px">Dibuat</label><br>
				<input type="text" name="dari" value="<?= html_escape(isset($f['dari']) ? $f['dari'] : '') ?>" placeholder="dari" style="width:110px">
				<input type="text" name="sampai" value="<?= html_escape(isset($f['sampai']) ? $f['sampai'] : '') ?>" placeholder="sampai" style="width:110px">
			</span>
			<button type="submit" class="btn-sm">Filter</button>
			<a class="btn-sm btn-ghost" href="<?= site_url('requisitions') ?>">reset</a>
		</div>
	</form>

	<?php if ( ! $rows): ?>
		<p class="muted">Tidak ada MPR sesuai filter.</p>
	<?php else: ?>
	<div style="overflow-x:auto"><table>
		<tr><th>No MPR</th><th>Posisi</th><th>Pemohon</th><th>Penempatan</th><th>Butuh</th><th>Setuju</th><th>Terpenuhi</th><th>Status</th><th></th></tr>
		<?php foreach ($rows as $r): ?>
		<tr>
			<td><?= html_escape($r['no_mpr'] ?: '(draft)') ?></td>
			<td><?= html_escape($r['nama_posisi']) ?></td>
			<td><?= html_escape($r['pemohon']) ?></td>
			<td><?= html_escape($r['tipe_penempatan'] . ($r['nama_outlet'] ? ' / ' . $r['nama_outlet'] : '')) ?></td>
			<td><?= (int) $r['jumlah_dibutuhkan'] ?></td>
			<td><?= $r['jumlah_disetujui'] === NULL ? '-' : (int) $r['jumlah_disetujui'] ?></td>
			<td><?= (int) $r['jumlah_terpenuhi'] ?></td>
			<td><span class="tag <?= in_array($r['status_req'], array('Sourcing','Approved','Terpenuhi','Terpenuhi_Sebagian')) ? 'on' : 'off' ?>"><?= html_escape($r['status_req']) ?></span></td>
			<td>
				<a href="<?= site_url('requisitions/view/' . (int) $r['id_req']) ?>">detail</a>
				&middot; <a href="<?= site_url('pipeline/index/' . (int) $r['id_req']) ?>">pipeline</a>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>
	<?php if ($pages > 1): ?>
		<p class="muted">Hal <?= $page ?>/<?= $pages ?>
			<?php if ($page > 1): ?><a href="<?= site_url('requisitions') . $qs(array('page' => $page - 1)) ?>">&larr;</a><?php endif; ?>
			<?php if ($page < $pages): ?><a href="<?= site_url('requisitions') . $qs(array('page' => $page + 1)) ?>">&rarr;</a><?php endif; ?>
		</p>
	<?php endif; ?>
	<?php endif; ?>
</main>

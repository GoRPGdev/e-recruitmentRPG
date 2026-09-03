<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>MPR</h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('requisitions/create') ?>">+ Buat MPR</a> &middot; <?= (int) $total ?> total
		<?php $fs = array('', 'Draft', 'Menunggu_BOD', 'Sourcing', 'Terpenuhi', 'Dibatalkan'); ?>
		&nbsp;|&nbsp;
		<?php foreach ($fs as $f): ?>
			<a href="<?= site_url('requisitions' . ($f ? '?status=' . $f : '')) ?>"><?= $f ?: 'semua' ?></a>&nbsp;
		<?php endforeach; ?>
	</p>

	<?php if ( ! $rows): ?>
		<p class="muted">Tidak ada MPR.</p>
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
			<?php if ($page > 1): ?><a href="?page=<?= $page-1 ?><?= $status ? '&status='.$status : '' ?>">&larr;</a><?php endif; ?>
			<?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?><?= $status ? '&status='.$status : '' ?>">&rarr;</a><?php endif; ?>
		</p>
	<?php endif; ?>
	<?php endif; ?>
</main>

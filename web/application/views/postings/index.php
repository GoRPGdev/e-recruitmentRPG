<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Form Publik</h1>
	<p class="muted" style="margin-top:0"><?= (int) $total ?> posting</p>

	<?php if ( ! $rows): ?>
		<p class="muted">Belum ada job posting.</p>
	<?php else: ?>
		<div style="overflow-x:auto">
		<table>
			<tr>
				<th>Posisi</th><th>MPR</th><th>Slug</th><th>Form</th>
				<th>Jendela</th><th>Submit</th><th>Lamaran</th><th></th>
			</tr>
			<?php foreach ($rows as $r): ?>
			<tr>
				<td><?= html_escape($r['nama_posisi']) ?></td>
				<td><?= html_escape($r['no_mpr'] ?: '-') ?></td>
				<td><code><?= html_escape($r['url_slug'] ?: '(belum ada)') ?></code></td>
				<td>
					<?php if ($r['form_aktif']): ?><span class="tag on">aktif</span>
					<?php else: ?><span class="tag off">nonaktif</span><?php endif; ?>
				</td>
				<td class="muted" style="margin:0">
					<?= $r['form_dibuka']  ? html_escape(substr($r['form_dibuka'], 0, 16))  : '&mdash;' ?><br>
					<?= $r['form_ditutup'] ? html_escape(substr($r['form_ditutup'], 0, 16)) : '&mdash;' ?>
				</td>
				<td><?= (int) $r['jumlah_submit'] ?></td>
				<td><?= (int) $r['n_lamaran'] ?></td>
				<td><a class="btn-sm" href="<?= site_url('postings/form_settings/' . (int) $r['id_posting']) ?>">Kelola</a></td>
			</tr>
			<?php endforeach; ?>
		</table>
		</div>

		<?php if ($pages > 1): ?>
			<p class="muted">
				Halaman <?= $page ?> / <?= $pages ?> &nbsp;
				<?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">&larr; sebelumnya</a><?php endif; ?>
				<?php if ($page < $pages): ?> <a href="?page=<?= $page + 1 ?>">berikutnya &rarr;</a><?php endif; ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</main>

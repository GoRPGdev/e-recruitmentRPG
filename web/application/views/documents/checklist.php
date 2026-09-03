<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Checklist Berkas &mdash; Lamaran #<?= (int) $id_lamaran ?></h1>
	<p class="muted" style="margin-top:0"><a href="<?= site_url('documents?id_lamaran=' . (int) $id_lamaran . '&status=') ?>">lihat semua dokumen lamaran ini</a></p>

	<div style="overflow-x:auto"><table>
		<tr><th>Dokumen</th><th>Kategori</th><th>Wajib (per flow)</th><th>Status</th></tr>
		<?php foreach ($rows as $r): ?>
		<tr>
			<td><?= html_escape($r['nama_dokumen']) ?></td>
			<td><?= html_escape($r['kategori']) ?></td>
			<td><?= $r['wajib'] ? '<span class="tag off">wajib</span>' : '-' ?></td>
			<td>
				<?php if ($r['status_verifikasi'] === NULL): ?>
					<span class="tag <?= $r['wajib'] ? 'off' : '' ?>">belum diunggah</span>
				<?php else: ?>
					<span class="tag <?= $r['status_verifikasi'] === 'Done' ? 'on' : 'off' ?>"><?= html_escape($r['status_verifikasi']) ?></span>
					<?php if ($r['id_cand_doc']): ?> &middot; <a href="<?= site_url('documents/open/' . (int) $r['id_cand_doc']) ?>" target="_blank">buka</a><?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>
</main>

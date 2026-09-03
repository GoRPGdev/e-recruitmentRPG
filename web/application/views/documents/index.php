<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Verifikasi Berkas</h1>
	<p class="muted" style="margin-top:0">
		<a href="<?= site_url('documents/flow_docs') ?>">Atur dokumen wajib per tahap &rarr;</a>
	</p>

	<form method="get" action="<?= site_url('documents') ?>" style="margin:0 0 12px; font-size:13px">
		Status:
		<select name="status" style="width:auto; padding:4px">
			<?php foreach (array('Proses','Done','Ditolak','') as $s): ?>
				<option value="<?= $s ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?: 'semua' ?></option>
			<?php endforeach; ?>
		</select>
		Kategori:
		<select name="kategori" style="width:auto; padding:4px">
			<option value="">semua</option>
			<?php foreach (array('IDENTITAS','PENDIDIKAN','FINANSIAL','LAMARAN') as $k): ?>
				<option value="<?= $k ?>" <?= ($f['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= $k ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn-sm">Filter</button>
	</form>

	<?php if ( ! $rows): ?>
		<p class="muted">Tidak ada dokumen sesuai filter.</p>
	<?php else: ?>
	<div style="overflow-x:auto"><table>
		<tr><th>Kandidat</th><th>Posisi</th><th>Dokumen</th><th>Kategori</th><th>File</th><th>Status</th><th>Verifikasi</th></tr>
		<?php foreach ($rows as $r): ?>
		<tr>
			<td><?= html_escape($r['nama_lengkap']) ?></td>
			<td><?= html_escape($r['nama_posisi']) ?> <span class="muted" style="font-size:12px">(<a href="<?= site_url('documents/checklist/' . (int) $r['id_lamaran']) ?>">checklist</a>)</span></td>
			<td><?= html_escape($r['nama_dokumen']) ?></td>
			<td><?= html_escape($r['kategori']) ?> <?= $r['tingkat_sensitif'] !== 'UMUM' ? '&#128274;' : '' ?></td>
			<td><a href="<?= site_url('documents/open/' . (int) $r['id_cand_doc']) ?>" target="_blank">buka</a>
				<span class="muted" style="font-size:12px"><?= round(($r['ukuran_byte'] ?: 0) / 1024) ?> KB</span></td>
			<td><span class="tag <?= $r['status_verifikasi'] === 'Done' ? 'on' : 'off' ?>"><?= html_escape($r['status_verifikasi']) ?></span>
				<?php if ($r['catatan_verifikasi']): ?><br><span class="muted" style="font-size:12px"><?= html_escape($r['catatan_verifikasi']) ?></span><?php endif; ?></td>
			<td>
				<?php if ($r['status_verifikasi'] === 'Proses'): ?>
				<?= form_open(site_url('documents/verify/' . (int) $r['id_cand_doc']), array('class' => 'inline')) ?>
					<input type="hidden" name="back" value="<?= html_escape(current_url() . '?' . http_build_query($f)) ?>">
					<input type="text" name="catatan" placeholder="catatan" style="width:120px">
					<button name="status" value="Done" class="btn-sm">Done</button>
					<button name="status" value="Ditolak" class="btn-sm btn-ghost">Tolak</button>
				<?= form_close() ?>
				<?php else: ?>
					<span class="muted" style="font-size:12px">oleh #<?= (int) ($r['diverifikasi_pada'] ? 1 : 0) ?> <?= html_escape(substr($r['diverifikasi_pada'] ?: '', 0, 16)) ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table></div>
	<?php endif; ?>
</main>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Preview Import #<?= (int) $batch['id_batch'] ?></h1>
	<p class="muted" style="margin-top:0">
		<?= html_escape($batch['nama_file']) ?> &middot;
		<?= html_escape(($batch['no_mpr'] ?: 'req #' . $batch['id_req']) . ' — ' . ($batch['nama_posisi'] ?: '')) ?> &middot;
		channel <?= html_escape($batch['nama_channel'] ?: '-') ?> &middot;
		status <strong><?= html_escape($batch['status']) ?></strong>
	</p>

	<?php $committed = $batch['status'] !== 'Preview'; ?>

	<?php if ( ! $committed): ?>
	<?= form_open(site_url('import/commit/' . (int) $batch['id_batch'])) ?>
	<?php endif; ?>

	<div style="overflow-x:auto"><table>
		<tr>
			<?php if ( ! $committed): ?><th><input type="checkbox" id="all" checked></th><?php endif; ?>
			<th>#</th><th>Nama</th><th>WA</th><th>Email</th><th>Precek</th>
			<?php if ($committed): ?><th>Hasil</th><th>Lamaran</th><th>Alasan</th><?php endif; ?>
		</tr>
		<?php foreach ($rows as $r): $d = $r['data']; ?>
		<tr>
			<?php if ( ! $committed): ?>
				<td><input type="checkbox" class="row" name="include[]" value="<?= (int) $r['id_row'] ?>"
					<?= $r['hasil'] === 'Duplikat' ? '' : 'checked' ?>></td>
			<?php endif; ?>
			<td><?= (int) $r['nomor_baris'] ?></td>
			<td><?= html_escape(isset($d['nama']) ? $d['nama'] : '') ?></td>
			<td><?= html_escape(isset($d['no_wa']) ? $d['no_wa'] : '') ?></td>
			<td><?= html_escape(isset($d['email']) ? $d['email'] : '') ?></td>
			<td>
				<?php if ($r['hasil'] === 'Duplikat'): ?><span class="tag off">mungkin dobel</span>
				<?php elseif ($r['hasil'] === 'Gagal'): ?><span class="tag off">gagal</span>
				<?php else: ?><span class="tag on">baru</span><?php endif; ?>
			</td>
			<?php if ($committed): ?>
				<td><?= html_escape($r['hasil']) ?></td>
				<td><?= $r['id_lamaran_dibuat'] ? '#' . (int) $r['id_lamaran_dibuat'] : '-' ?></td>
				<td class="muted" style="margin:0"><?= html_escape($r['alasan_gagal'] ?: '') ?></td>
			<?php endif; ?>
		</tr>
		<?php endforeach; ?>
	</table></div>

	<?php if ( ! $committed): ?>
		<p style="margin-top:16px">
			<button type="submit">Commit yang dicentang</button>
		</p>
	<?= form_close() ?>
	<?= form_open(site_url('import/discard/' . (int) $batch['id_batch'])) ?>
		<button type="submit" class="btn-ghost">Batalkan batch</button>
	<?= form_close() ?>
	<script>
	document.getElementById('all').addEventListener('change', function () {
		document.querySelectorAll('.row').forEach(function (c) { c.checked = this.checked; }, this);
	});
	</script>
	<?php else: ?>
		<p><a href="<?= site_url('import') ?>">&larr; import lain</a></p>
	<?php endif; ?>
</main>

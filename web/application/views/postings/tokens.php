<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Token Berkas &mdash; Lamaran #<?= (int) $id_lamaran ?></h1>
	<p class="muted" style="margin-top:0">Link personal untuk kandidat melengkapi berkas. Sekali pakai, bisa dicabut.</p>

	<h2>Buat token baru</h2>
	<?= form_open(site_url('postings/tokens/' . (int) $id_lamaran)) ?>
		<input type="hidden" name="act" value="create">
		<label for="tujuan">Tujuan</label>
		<select id="tujuan" name="tujuan">
			<option value="UPLOAD_DOKUMEN">Upload dokumen</option>
			<option value="FORM2">Lengkapi Form 2</option>
		</select>
		<label for="masa_hari">Berlaku (hari)</label>
		<input type="text" id="masa_hari" name="masa_hari" value="7" inputmode="numeric">
		<button type="submit">Buat</button>
	<?= form_close() ?>

	<h2>Token</h2>
	<?php if ( ! $tokens): ?>
		<p class="muted">Belum ada token.</p>
	<?php else: ?>
		<div style="overflow-x:auto">
		<table>
			<tr><th>Tautan</th><th>Tujuan</th><th>Kadaluarsa</th><th>Status</th><th></th></tr>
			<?php foreach ($tokens as $t): ?>
			<tr>
				<td><code><?= html_escape(site_url('berkas/' . $t['token'])) ?></code></td>
				<td><?= html_escape($t['tujuan']) ?></td>
				<td class="muted" style="margin:0"><?= $t['kadaluarsa_pada'] ? html_escape(substr($t['kadaluarsa_pada'], 0, 16)) : 'tanpa batas' ?></td>
				<td>
					<?php if ($t['is_revoked']): ?><span class="tag off">dicabut</span>
					<?php elseif ($t['dipakai_pada']): ?><span class="tag off">terpakai</span>
					<?php else: ?><span class="tag on">aktif</span><?php endif; ?>
				</td>
				<td>
					<?php if ( ! $t['is_revoked'] && ! $t['dipakai_pada']): ?>
					<?= form_open(site_url('postings/tokens/' . (int) $id_lamaran), array('class' => 'inline')) ?>
						<input type="hidden" name="act" value="revoke">
						<input type="hidden" name="id_token" value="<?= (int) $t['id_token'] ?>">
						<button type="submit" class="btn-sm btn-ghost">Cabut</button>
					<?= form_close() ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		</div>
	<?php endif; ?>
</main>

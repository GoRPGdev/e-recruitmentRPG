<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card">
	<h1>Pipeline &mdash; <?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?></h1>
	<p class="muted" style="margin-top:0">
		<?= html_escape($req['nama_posisi']) ?> &middot;
		butuh <?= (int) $req['jumlah_dibutuhkan'] ?>, terpenuhi <?= (int) $req['jumlah_terpenuhi'] ?> &middot;
		<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>">detail MPR</a>
	</p>

	<?php if ( ! $stages): ?>
		<p class="muted">Belum ada kandidat aktif di pipeline.</p>
	<?php else: ?>
		<?php foreach ($stages as $urut => $s): ?>
		<div style="border-top:2px solid #e2e4e8; padding-top:10px; margin-top:14px">
			<h2 style="margin:0 0 6px">
				<?= (int) $urut ?>. <?= html_escape($s['nama']) ?>
				<span class="muted" style="font-size:12px; font-weight:400">[<?= html_escape($s['tipe']) ?>] &middot; <?= count($s['cards']) ?> kandidat</span>
			</h2>

			<?php foreach ($s['cards'] as $c): ?>
			<div style="border:1px solid #e2e4e8; border-radius:8px; padding:10px 12px; margin:6px 0">
				<strong><?= html_escape($c['nama_lengkap']) ?></strong>
				&middot; <span class="muted" style="font-size:13px"><?= html_escape($c['no_wa_normal'] ?: '-') ?></span>
				&middot; <span class="tag <?= $c['status_global'] === 'In_Progress' ? 'on' : 'off' ?>"><?= html_escape($c['status_global']) ?></span>
				&middot; <span class="muted" style="font-size:12px"><?= (int) $c['hari_di_tahap'] ?> hari di tahap</span>

				<?php if ($can_aksi): ?>
				<div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap">
					<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('class' => 'inline')) ?>
						<input type="hidden" name="id_app_stage" value="<?= (int) $c['id_app_stage'] ?>">
						<select name="id_remark" style="width:auto; padding:4px 8px">
							<option value="">— lulus / lanjut —</option>
							<?php foreach ($remarks[$s['id_stage']] as $rk): ?>
								<option value="<?= (int) $rk['id_remark'] ?>"><?= html_escape($rk['label']) ?> (<?= html_escape($rk['efek_status']) ?>)</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="btn-sm">Proses</button>
					<?= form_close() ?>

					<?php if ($s['tipe'] === 'KONTAK'): ?>
					<?= form_open(site_url('pipeline/contact/' . (int) $req['id_req']), array('class' => 'inline')) ?>
						<input type="hidden" name="id_lamaran" value="<?= (int) $c['id_lamaran'] ?>">
						<select name="metode" style="width:auto; padding:4px 8px">
							<option>WA</option><option>Telepon</option><option>Email</option>
						</select>
						<select name="hasil" style="width:auto; padding:4px 8px">
							<option>Tidak_Respon</option><option>Respon</option><option>Nomor_Salah</option><option>Menolak</option>
						</select>
						<button type="submit" class="btn-sm btn-ghost">Catat kontak</button>
					<?= form_close() ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	<?php endif; ?>
</main>

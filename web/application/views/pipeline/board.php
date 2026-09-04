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
		<?php
		$render_card = function($c, $s, $remarks, $all_stages, $can_aksi, $req) {
		?>
			<div style="flex:0 0 280px; width:280px; border:1px solid #e2e4e8; border-radius:8px; padding:10px 12px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,0.03); display:flex; flex-direction:column; justify-content:space-between">
				<div>
					<strong style="display:block; font-size:14px; margin-bottom:2px"><?= html_escape($c['nama_lengkap']) ?></strong>
					<div class="muted" style="font-size:12px; margin:0 0 6px"><?= html_escape($c['no_wa_normal'] ?: '-') ?></div>
					<div style="margin-bottom:8px">
						<span class="tag <?= $c['status_global'] === 'In_Progress' ? 'on' : 'off' ?>"><?= html_escape($c['status_global']) ?></span>
						&middot; <span class="muted" style="font-size:12px"><?= (int) $c['hari_di_tahap'] ?> hr di tahap</span>
					</div>
				</div>

				<?php if ($can_aksi): ?>
				<div style="border-top:1px solid #f0f1f3; padding-top:8px; margin-top:6px; display:flex; flex-direction:column; gap:6px">
					<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
						<input type="hidden" name="id_app_stage" value="<?= (int) $c['id_app_stage'] ?>">
						<div style="display:flex; gap:4px">
							<select name="id_remark" style="width:100%; padding:3px 6px; font-size:12px">
								<option value="">— lulus / lanjut —</option>
								<?php if (isset($remarks[$s['id_stage']])): ?>
									<?php foreach ($remarks[$s['id_stage']] as $rk): ?>
										<option value="<?= (int) $rk['id_remark'] ?>"><?= html_escape($rk['label']) ?> (<?= html_escape($rk['efek_status']) ?>)</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<button type="submit" class="btn-sm" style="padding:3px 8px; font-size:12px">Proses</button>
						</div>
					<?= form_close() ?>

					<?php if ($s['tipe'] === 'KONTAK'): ?>
					<?= form_open(site_url('pipeline/contact/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
						<input type="hidden" name="id_lamaran" value="<?= (int) $c['id_lamaran'] ?>">
						<div style="display:flex; gap:4px">
							<select name="metode" style="padding:3px 4px; font-size:12px">
								<option>WA</option><option>Telepon</option><option>Email</option>
							</select>
							<select name="hasil" style="padding:3px 4px; font-size:12px">
								<option>Tidak_Respon</option><option>Respon</option><option>Nomor_Salah</option><option>Menolak</option>
							</select>
							<button type="submit" class="btn-sm btn-ghost" style="padding:3px 8px; font-size:12px">Kontak</button>
						</div>
					<?= form_close() ?>
					<?php endif; ?>

					<?= form_open(site_url('pipeline/insert_stage/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
						<input type="hidden" name="id_lamaran" value="<?= (int) $c['id_lamaran'] ?>">
						<div style="display:flex; gap:4px">
							<select name="id_stage" style="width:100%; padding:3px 6px; font-size:12px">
								<?php foreach ($all_stages as $st): ?>
									<option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap']) ?></option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="btn-sm btn-ghost" style="padding:3px 8px; font-size:12px; white-space:nowrap">Sisip</button>
						</div>
					<?= form_close() ?>
				</div>
				<?php endif; ?>
			</div>
		<?php
		};
		?>

		<?php foreach ($stages as $urut => $s): ?>
		<?php
			$total_cards = count($s['cards']);
			$cards_awal  = array_slice($s['cards'], 0, 12);
			$cards_sisa  = array_slice($s['cards'], 12);
		?>
		<div style="border-top:2px solid #e2e4e8; padding-top:10px; margin-top:16px">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
				<h2 style="margin:0">
					<?= (int) $urut ?>. <?= html_escape($s['nama']) ?>
					<span class="muted" style="font-size:12px; font-weight:400">[<?= html_escape($s['tipe']) ?>] &middot; <?= $total_cards ?> kandidat</span>
				</h2>
				<?php if ($total_cards > 12): ?>
					<button type="button" class="btn-sm btn-ghost" onclick="toggleStageCards(<?= (int) $urut ?>)" id="btn-toggle-<?= (int) $urut ?>">
						Lihat semua (<?= $total_cards ?>)
					</button>
				<?php endif; ?>
			</div>

			<?php if ( ! $total_cards): ?>
				<p class="muted" style="font-size:13px; margin:4px 0">Tidak ada kandidat di tahap ini.</p>
			<?php else: ?>
				<!-- baris kartu mengalir ke kanan dengan batas 12 kartu awal -->
				<div style="display:flex; gap:12px; overflow-x:auto; padding:6px 2px 14px; align-items:stretch">
					<?php foreach ($cards_awal as $c): ?>
						<?php $render_card($c, $s, $remarks, $all_stages, $can_aksi, $req); ?>
					<?php endforeach; ?>

					<?php if ($total_cards > 12): ?>
						<div id="card-more-summary-<?= (int) $urut ?>" style="flex:0 0 200px; width:200px; min-height:160px; border:2px dashed #cbd0d8; border-radius:8px; padding:14px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; background:#f9fafb">
							<span style="font-size:22px; font-weight:700; color:#4b5563">+<?= count($cards_sisa) ?></span>
							<span class="muted" style="font-size:12px; margin:2px 0 10px">kandidat lagi</span>
							<button type="button" class="btn-sm btn-ghost" onclick="toggleStageCards(<?= (int) $urut ?>)">Lihat Semua</button>
						</div>

						<div id="more-cards-<?= (int) $urut ?>" style="display:none; gap:12px; flex:0 0 auto">
							<?php foreach ($cards_sisa as $c): ?>
								<?php $render_card($c, $s, $remarks, $all_stages, $can_aksi, $req); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>

		<script>
		function toggleStageCards(urut) {
			var moreBox     = document.getElementById('more-cards-' + urut);
			var moreSummary = document.getElementById('card-more-summary-' + urut);
			var btnToggle   = document.getElementById('btn-toggle-' + urut);
			if (!moreBox) return;

			if (moreBox.style.display === 'none' || moreBox.style.display === '') {
				moreBox.style.display = 'flex';
				if (moreSummary) moreSummary.style.display = 'none';
				if (btnToggle) btnToggle.textContent = 'Persempit (12 kartu)';
			} else {
				moreBox.style.display = 'none';
				if (moreSummary) moreSummary.style.display = 'flex';
				var total = 12 + moreBox.children.length;
				if (btnToggle) btnToggle.textContent = 'Lihat semua (' + total + ')';
			}
		}
		</script>
	<?php endif; ?>
</main>

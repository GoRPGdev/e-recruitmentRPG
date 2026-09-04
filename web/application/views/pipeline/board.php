<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card" style="padding:20px 24px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; flex-wrap:wrap; gap:10px">
		<div>
			<h1 style="margin:0 0 4px"><?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?> &mdash; Pipeline Seleksi</h1>
			<p class="muted" style="margin:0">
				<strong><?= html_escape($req['nama_posisi']) ?></strong> &middot;
				Kebutuhan: <?= (int) $req['jumlah_dibutuhkan'] ?> orang &middot;
				Terpenuhi: <?= (int) $req['jumlah_terpenuhi'] ?> orang &middot;
				<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>">Lihat Detail MPR</a>
			</p>
		</div>
		<div style="display:flex; gap:8px; align-items:center">
			<a href="<?= site_url('requisitions') ?>" class="btn btn-sm btn-ghost">&larr; Daftar MPR</a>
		</div>
	</div>

	<?php if ( ! $stages): ?>
		<div style="padding:40px 20px; text-align:center; background:var(--surface-2); border-radius:8px; margin-top:16px">
			<p class="muted" style="font-size:15px">Belum ada kandidat aktif di pipeline lowongan ini.</p>
		</div>
	<?php else: ?>
		<?php
		$render_card = function($c, $s, $remarks, $all_stages, $can_aksi, $req, $interviews, $psikotes, $offers, $can_gaji) {
			$id_stage = (int) $s['id_stage'];
			$id_app_stage = (int) $c['id_app_stage'];
			$id_lamaran = (int) $c['id_lamaran'];

			$cur_ivs = isset($interviews[$id_app_stage]) ? $interviews[$id_app_stage] : array();
			$latest_iv = !empty($cur_ivs) ? $cur_ivs[0] : NULL;

			$cur_psi = isset($psikotes[$id_app_stage]) ? $psikotes[$id_app_stage] : array();
			$latest_psi = !empty($cur_psi) ? $cur_psi[0] : NULL;

			$cur_off = isset($offers[$id_lamaran]) ? $offers[$id_lamaran] : NULL;
		?>
			<div style="flex:0 0 290px; width:290px; border:1px solid var(--border); border-radius:8px; padding:12px; background:var(--surface); box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between">
				<div>
					<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px">
						<strong style="font-size:14.5px; color:var(--text); line-height:1.3"><?= html_escape($c['nama_lengkap']) ?></strong>
						<span class="tag <?= $c['status_global'] === 'In_Progress' ? 'on' : 'off' ?>"><?= html_escape($c['status_global']) ?></span>
					</div>
					<div class="muted mono" style="font-size:12px; margin-bottom:6px"><?= html_escape($c['no_wa_normal'] ?: '-') ?></div>
					
					<div style="font-size:12px; color:var(--text-muted); margin-bottom:8px">
						<span>⏱️ <?= (int) $c['hari_di_tahap'] ?> hr di tahap ini</span>
						<?php if ($c['screening_score'] !== NULL): ?>
							&middot; <span>Skor CV: <strong><?= (int)$c['screening_score'] ?></strong></span>
						<?php endif; ?>
					</div>

					<!-- Bagian Data Kontekstual Seleksi -->
					<?php if ($s['tipe'] === 'INTERVIEW'): ?>
						<div style="background:var(--surface-2); border-radius:6px; padding:6px 8px; font-size:12px; margin-bottom:8px">
							<?php if ($latest_iv): ?>
								<div>📅 <strong><?= html_escape($latest_iv['tipe'] ?: 'Interview') ?></strong>: <?= html_escape($latest_iv['jadwal'] ?: 'Belum dijadwalkan') ?></div>
								<?php if ($latest_iv['nama_interviewer']): ?>
									<div class="muted">Oleh: <?= html_escape($latest_iv['nama_interviewer']) ?> (<?= html_escape($latest_iv['peran_interviewer']) ?>)</div>
								<?php endif; ?>
								<?php if ($latest_iv['hasil']): ?>
									<div style="margin-top:2px">Hasil: <span class="tag <?= strtolower($latest_iv['hasil']) ?>"><?= html_escape($latest_iv['hasil']) ?></span> <?= $latest_iv['skor'] !== NULL ? '(Skor: ' . (int)$latest_iv['skor'] . ')' : '' ?></div>
								<?php endif; ?>
							<?php else: ?>
								<span class="muted">Belum dijadwalkan interview.</span>
							<?php endif; ?>
						</div>
					<?php elseif ($s['tipe'] === 'TEST'): ?>
						<div style="background:var(--surface-2); border-radius:6px; padding:6px 8px; font-size:12px; margin-bottom:8px">
							<?php if ($latest_psi): ?>
								<div>🧠 <strong><?= html_escape($latest_psi['vendor_tes'] ?: 'Psikotes') ?></strong> &middot; <?= html_escape($latest_psi['tanggal_tes']) ?></div>
								<div>Hasil: <span class="tag <?= strtolower($latest_psi['hasil']) ?>"><?= html_escape($latest_psi['hasil'] ?: 'Pending') ?></span> <?= $latest_psi['skor_total'] !== NULL ? '(Skor: ' . (int)$latest_psi['skor_total'] . ')' : '' ?></div>
							<?php else: ?>
								<span class="muted">Belum ada catatan psikotes.</span>
							<?php endif; ?>
						</div>
					<?php elseif ($s['tipe'] === 'OFFER' || $cur_off): ?>
						<div style="background:var(--surface-2); border-radius:6px; padding:6px 8px; font-size:12px; margin-bottom:8px">
							<?php if ($cur_off): ?>
								<div>💼 Status: <span class="tag <?= strtolower($cur_off['status_offer']) ?>"><?= html_escape($cur_off['status_offer']) ?></span></div>
								<?php if ($cur_off['tanggal_join_disepakati']): ?>
									<div class="muted">Join: <?= html_escape($cur_off['tanggal_join_disepakati']) ?></div>
								<?php endif; ?>
								<?php if ($can_gaji && $cur_off['gaji_ditawarkan'] !== NULL): ?>
									<div style="color:var(--accent); font-weight:600">Rp <?= number_format((float)$cur_off['gaji_ditawarkan'], 0, ',', '.') ?></div>
								<?php elseif ( ! $can_gaji && $cur_off['gaji_ditawarkan'] !== NULL): ?>
									<div class="muted">[Gaji Terproteksi]</div>
								<?php endif; ?>
							<?php else: ?>
								<span class="muted">Belum dibuat draft penawaran.</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ($can_aksi): ?>
				<div style="border-top:1px solid var(--border); padding-top:8px; margin-top:6px; display:flex; flex-direction:column; gap:6px">
					<!-- Advance Stage Form -->
					<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
						<input type="hidden" name="id_app_stage" value="<?= $id_app_stage ?>">
						<div style="display:flex; gap:4px">
							<select name="id_remark" style="width:100%; padding:3px 6px; font-size:12px">
								<option value="">— proses / lanjut —</option>
								<?php if (isset($remarks[$id_stage])): ?>
									<?php foreach ($remarks[$id_stage] as $rk): ?>
										<option value="<?= (int) $rk['id_remark'] ?>"><?= html_escape($rk['label']) ?> (<?= html_escape($rk['efek_status']) ?>)</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<button type="submit" class="btn-sm" style="padding:3px 8px; font-size:12px">Proses</button>
						</div>
					<?= form_close() ?>

					<!-- Stage-Specific Action Modals Triggers -->
					<div style="display:flex; gap:4px; flex-wrap:wrap">
						<?php if ($s['tipe'] === 'KONTAK'): ?>
							<?= form_open(site_url('pipeline/contact/' . (int) $req['id_req']), array('style' => 'display:flex; gap:4px; width:100%')) ?>
								<input type="hidden" name="id_lamaran" value="<?= $id_lamaran ?>">
								<select name="metode" style="padding:3px 4px; font-size:12px; width:35%">
									<option>WA</option><option>Telepon</option><option>Email</option>
								</select>
								<select name="hasil" style="padding:3px 4px; font-size:12px; width:40%">
									<option>Respon</option><option>Tidak_Respon</option><option>Nomor_Salah</option><option>Menolak</option>
								</select>
								<button type="submit" class="btn-sm btn-ghost" style="padding:3px 8px; font-size:12px; width:25%">Log</button>
							<?= form_close() ?>
						<?php endif; ?>

						<?php if ($s['tipe'] === 'INTERVIEW'): ?>
							<button type="button" class="btn-sm btn-ghost" style="width:100%; font-size:12px; justify-content:center"
								onclick='openInterviewModal(<?= json_encode(array(
									"id_app_stage" => $id_app_stage,
									"nama" => $c["nama_lengkap"],
									"interview" => $latest_iv
								)) ?>)'>
								🎤 <?= $latest_iv ? 'Ubah / Catat Hasil Interview' : 'Jadwalkan Interview' ?>
							</button>
						<?php endif; ?>

						<?php if ($s['tipe'] === 'TEST'): ?>
							<button type="button" class="btn-sm btn-ghost" style="width:100%; font-size:12px; justify-content:center"
								onclick='openPsikotesModal(<?= json_encode(array(
									"id_app_stage" => $id_app_stage,
									"nama" => $c["nama_lengkap"],
									"psikotes" => $latest_psi
								)) ?>)'>
								🧠 <?= $latest_psi ? 'Ubah Hasil Psikotes' : 'Catat Hasil Psikotes' ?>
							</button>
						<?php endif; ?>

						<?php if ($s['tipe'] === 'OFFER' || $cur_off): ?>
							<button type="button" class="btn-sm btn-ghost" style="width:100%; font-size:12px; justify-content:center"
								onclick='openOfferModal(<?= json_encode(array(
									"id_lamaran" => $id_lamaran,
									"nama" => $c["nama_lengkap"],
									"offer" => $cur_off
								)) ?>)'>
								💼 <?= $cur_off ? 'Ubah Form / Status Offer' : 'Buat Penawaran (Offer)' ?>
							</button>
						<?php endif; ?>
					</div>

					<!-- Sisip Tahap Ad-Hoc Form -->
					<?= form_open(site_url('pipeline/insert_stage/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
						<input type="hidden" name="id_lamaran" value="<?= $id_lamaran ?>">
						<div style="display:flex; gap:4px">
							<select name="id_stage" style="width:100%; padding:3px 6px; font-size:12px">
								<?php foreach ($all_stages as $st): ?>
									<option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap']) ?></option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="btn-sm btn-ghost" style="padding:3px 8px; font-size:12px; white-space:nowrap">+ Sisip</button>
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
		<div style="border-top:1px solid var(--border); padding-top:14px; margin-top:18px">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
				<h2 style="margin:0; font-size:15px">
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
				<!-- Baris kartu horizontal dengan scroll dan batas 12 kartu -->
				<div style="display:flex; gap:12px; overflow-x:auto; padding:4px 2px 14px; align-items:stretch">
					<?php foreach ($cards_awal as $c): ?>
						<?php $render_card($c, $s, $remarks, $all_stages, $can_aksi, $req, $interviews, $psikotes, $offers, $can_gaji); ?>
					<?php endforeach; ?>

					<?php if ($total_cards > 12): ?>
						<div id="card-more-summary-<?= (int) $urut ?>" style="flex:0 0 200px; width:200px; min-height:160px; border:2px dashed var(--border-strong); border-radius:8px; padding:14px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; background:var(--surface-2)">
							<span style="font-size:22px; font-weight:700; color:var(--text)">+<?= count($cards_sisa) ?></span>
							<span class="muted" style="font-size:12px; margin:2px 0 10px">kandidat lagi</span>
							<button type="button" class="btn-sm btn-ghost" onclick="toggleStageCards(<?= (int) $urut ?>)">Lihat Semua</button>
						</div>

						<div id="more-cards-<?= (int) $urut ?>" style="display:none; gap:12px; flex:0 0 auto">
							<?php foreach ($cards_sisa as $c): ?>
								<?php $render_card($c, $s, $remarks, $all_stages, $can_aksi, $req, $interviews, $psikotes, $offers, $can_gaji); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>

		<!-- ================= DIALOG INTERVIEW ================= -->
		<dialog id="dlg-interview">
			<h2 id="dlg-iv-title" style="margin-top:0">Jadwal & Hasil Interview</h2>
			<?= form_open(site_url('pipeline/save_interview/' . (int) $req['id_req'])) ?>
				<input type="hidden" name="id_interview" id="iv-id-interview">
				<input type="hidden" name="id_app_stage" id="iv-id-app-stage">
				
				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="iv-tipe">Tipe Interview</label>
						<select name="tipe" id="iv-tipe">
							<option value="Online">Online</option>
							<option value="Offline">Offline</option>
						</select>
					</div>
					<div>
						<label for="iv-jadwal">Jadwal</label>
						<input type="text" name="jadwal" id="iv-jadwal" placeholder="YYYY-MM-DD HH:MM">
					</div>
				</div>

				<label for="iv-lokasi">Lokasi / Link Meeting</label>
				<input type="text" name="lokasi_atau_link" id="iv-lokasi" placeholder="e.g. Google Meet link / Ruang HR Lt. 2">

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="iv-interviewer">Pewawancara</label>
						<select name="id_interviewer" id="iv-interviewer">
							<option value="">— Pilih Pewawancara —</option>
							<?php foreach ($interviewers as $usr): ?>
								<option value="<?= (int) $usr['id_user'] ?>"><?= html_escape($usr['nama_lengkap']) ?> (<?= html_escape($usr['role']) ?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="iv-peran">Peran Pewawancara</label>
						<select name="peran_interviewer" id="iv-peran">
							<option value="HR">HR</option>
							<option value="User">User</option>
							<option value="BOD">BOD</option>
						</select>
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="iv-hasil">Hasil Evaluasi</label>
						<select name="hasil" id="iv-hasil">
							<option value="">(Belum Ada / Terjadwal)</option>
							<option value="Lulus">Lulus</option>
							<option value="Tidak_Lulus">Tidak Lulus</option>
							<option value="Dipertimbangkan">Dipertimbangkan</option>
							<option value="Reschedule">Reschedule</option>
							<option value="No_Show">No Show</option>
						</select>
					</div>
					<div>
						<label for="iv-skor">Skor (0-100)</label>
						<input type="number" name="skor" id="iv-skor" min="0" max="100">
					</div>
				</div>

				<label for="iv-catatan">Catatan / Feedback</label>
				<textarea name="catatan" id="iv-catatan" rows="3" placeholder="Catatan kelebihan, kekurangan, rekomendasi..."></textarea>

				<div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px">
					<button type="button" class="btn-ghost" onclick="document.getElementById('dlg-interview').close()">Batal</button>
					<button type="submit">Simpan Interview</button>
				</div>
			<?= form_close() ?>
		</dialog>

		<!-- ================= DIALOG PSIKOTES ================= -->
		<dialog id="dlg-psikotes">
			<h2 id="dlg-psi-title" style="margin-top:0">Catat Hasil Psikotes</h2>
			<?= form_open(site_url('pipeline/save_psikotes/' . (int) $req['id_req'])) ?>
				<input type="hidden" name="id_psikotes" id="psi-id-psikotes">
				<input type="hidden" name="id_app_stage" id="psi-id-app-stage">

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="psi-vendor">Vendor / Alat Tes</label>
						<input type="text" name="vendor_tes" id="psi-vendor" placeholder="e.g. DISC, Papi Kostick">
					</div>
					<div>
						<label for="psi-tanggal">Tanggal Tes</label>
						<input type="date" name="tanggal_tes" id="psi-tanggal">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="psi-skor">Skor Total</label>
						<input type="number" name="skor_total" id="psi-skor" placeholder="Skor angka">
					</div>
					<div>
						<label for="psi-hasil">Hasil Tes</label>
						<select name="hasil" id="psi-hasil">
							<option value="">— Pilih Hasil —</option>
							<option value="Lulus">Lulus</option>
							<option value="Tidak_Lulus">Tidak Lulus</option>
							<option value="Perlu_Review">Perlu Review</option>
						</select>
					</div>
				</div>

				<label for="psi-rekomendasi">Rekomendasi / Profil Singkat</label>
				<textarea name="rekomendasi" id="psi-rekomendasi" rows="3" placeholder="Deskripsi kepribadian, potensi, catatan khusus..."></textarea>

				<div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px">
					<button type="button" class="btn-ghost" onclick="document.getElementById('dlg-psikotes').close()">Batal</button>
					<button type="submit">Simpan Hasil Psikotes</button>
				</div>
			<?= form_close() ?>
		</dialog>

		<!-- ================= DIALOG OFFER ================= -->
		<dialog id="dlg-offer">
			<h2 id="dlg-off-title" style="margin-top:0">Penawaran Kerja (Offering)</h2>
			<?= form_open(site_url('pipeline/save_offer/' . (int) $req['id_req'])) ?>
				<input type="hidden" name="id_offer" id="off-id-offer">
				<input type="hidden" name="id_lamaran" id="off-id-lamaran">

				<?php if ($can_gaji): ?>
					<label for="off-gaji">Gaji Ditawarkan (Rp)</label>
					<input type="number" name="gaji_ditawarkan" id="off-gaji" placeholder="Contoh: 8500000">
				<?php else: ?>
					<div class="muted" style="background:var(--surface-2); padding:8px 10px; border-radius:6px; font-size:12.5px; margin-top:10px">
						🔒 <em>Anda tidak memiliki hak akses untuk mengedit/melihat besaran nominal gaji penawaran.</em>
					</div>
				<?php endif; ?>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="off-tgl-penawaran">Tanggal Penawaran</label>
						<input type="date" name="tanggal_penawaran" id="off-tgl-penawaran">
					</div>
					<div>
						<label for="off-status">Status Offer</label>
						<select name="status_offer" id="off-status">
							<option value="Nego">Nego</option>
							<option value="Diterima">Diterima</option>
							<option value="Ditolak">Ditolak</option>
							<option value="Batal">Batal</option>
						</select>
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
					<div>
						<label for="off-tgl-join-sepakat">Tgl Join Disepakati</label>
						<input type="date" name="tanggal_join_disepakati" id="off-tgl-join-sepakat">
					</div>
					<div>
						<label for="off-tgl-join-aktual">Tgl Join Aktual</label>
						<input type="date" name="tanggal_join_aktual" id="off-tgl-join-aktual">
					</div>
				</div>

				<label for="off-alasan">Alasan / Catatan Penawaran</label>
				<input type="text" name="alasan" id="off-alasan" placeholder="Catatan nego gaji, alasan penolakan, fasilitas...">

				<div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px">
					<button type="button" class="btn-ghost" onclick="document.getElementById('dlg-offer').close()">Batal</button>
					<button type="submit">Simpan Penawaran</button>
				</div>
			<?= form_close() ?>
		</dialog>

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

		function openInterviewModal(data) {
			var dlg = document.getElementById('dlg-interview');
			document.getElementById('dlg-iv-title').textContent = 'Interview: ' + data.nama;
			document.getElementById('iv-id-app-stage').value = data.id_app_stage;
			var iv = data.interview || {};
			document.getElementById('iv-id-interview').value = iv.id_interview || '';
			document.getElementById('iv-tipe').value = iv.tipe || 'Online';
			document.getElementById('iv-jadwal').value = iv.jadwal || '';
			document.getElementById('iv-lokasi').value = iv.lokasi_atau_link || '';
			document.getElementById('iv-interviewer').value = iv.id_interviewer || '';
			document.getElementById('iv-peran').value = iv.peran_interviewer || 'HR';
			document.getElementById('iv-hasil').value = iv.hasil || '';
			document.getElementById('iv-skor').value = iv.skor !== null && iv.skor !== undefined ? iv.skor : '';
			document.getElementById('iv-catatan').value = iv.catatan || '';
			dlg.showModal();
		}

		function openPsikotesModal(data) {
			var dlg = document.getElementById('dlg-psikotes');
			document.getElementById('dlg-psi-title').textContent = 'Psikotes: ' + data.nama;
			document.getElementById('psi-id-app-stage').value = data.id_app_stage;
			var psi = data.psikotes || {};
			document.getElementById('psi-id-psikotes').value = psi.id_psikotes || '';
			document.getElementById('psi-vendor').value = psi.vendor_tes || '';
			document.getElementById('psi-tanggal').value = psi.tanggal_tes || '';
			document.getElementById('psi-skor').value = psi.skor_total !== null && psi.skor_total !== undefined ? psi.skor_total : '';
			document.getElementById('psi-hasil').value = psi.hasil || '';
			document.getElementById('psi-rekomendasi').value = psi.rekomendasi || '';
			dlg.showModal();
		}

		function openOfferModal(data) {
			var dlg = document.getElementById('dlg-offer');
			document.getElementById('dlg-off-title').textContent = 'Offering: ' + data.nama;
			document.getElementById('off-id-lamaran').value = data.id_lamaran;
			var off = data.offer || {};
			document.getElementById('off-id-offer').value = off.id_offer || '';
			var gInput = document.getElementById('off-gaji');
			if (gInput) {
				gInput.value = off.gaji_ditawarkan ? Math.round(off.gaji_ditawarkan) : '';
			}
			document.getElementById('off-tgl-penawaran').value = off.tanggal_penawaran || '';
			document.getElementById('off-status').value = off.status_offer || 'Nego';
			document.getElementById('off-tgl-join-sepakat').value = off.tanggal_join_disepakati || '';
			document.getElementById('off-tgl-join-aktual').value = off.tanggal_join_aktual || '';
			document.getElementById('off-alasan').value = off.alasan || '';
			dlg.showModal();
		}
		</script>
	<?php endif; ?>
</main>


<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Agregasi statistik kandidat untuk pipeline toolbar
$total_kandidat    = 0;
$total_in_progress = 0;
$total_hired       = 0;
$total_overdue     = 0;

foreach ($stages as $s) {
	foreach ($s['cards'] as $c) {
		$total_kandidat++;
		if ($c['status_global'] === 'In_Progress') {
			$total_in_progress++;
		} elseif ($c['status_global'] === 'Hired') {
			$total_hired++;
		}
		if ((int) $c['hari_di_tahap'] > 7) {
			$total_overdue++;
		}
	}
}
?>

<div class="card" style="padding:20px 24px; margin-bottom:20px">
	<!-- HEADER PIPELINE -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:14px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Papan Seleksi Lamaran (Tabel Terstruktur)</div>
			<h1 style="margin:0 0 6px; font-size:22px; font-weight:700">
				<?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?> - <?= html_escape($req['nama_posisi']) ?>
			</h1>
			<div class="muted" style="font-size:13px; display:flex; gap:12px; flex-wrap:wrap; align-items:center">
				<span>Departemen: <strong><?= html_escape($req['departemen'] ?: '-') ?></strong></span>
				<span>&bull;</span>
				<span>Target: <strong><?= (int) $req['jumlah_dibutuhkan'] ?></strong> orang</span>
				<span>&bull;</span>
				<span>Terpenuhi: <strong style="color:var(--good)"><?= (int) $req['jumlah_terpenuhi'] ?></strong> orang</span>
				<span>&bull;</span>
				<span class="tag <?= in_array($req['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : 'warn' ?>">
					<?= html_escape($req['status_req']) ?>
				</span>
			</div>
		</div>

		<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
			<a href="<?= site_url('manual') ?>" class="btn btn-sm btn-primary">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Pelamar</span>
			</a>
			<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-ghost">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
				<span>Detail MPR</span>
			</a>
			<a href="<?= site_url('requisitions') ?>" class="btn btn-sm btn-ghost">&larr; Daftar MPR</a>
		</div>
	</div>

	<!-- METRICS STRIP RINGKAS -->
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 16px">
		<div>
			<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:0.05em">Total Kandidat</div>
			<div class="mono" style="font-size:20px; font-weight:700; color:var(--text)"><?= $total_kandidat ?></div>
		</div>
		<div>
			<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:0.05em">Sedang Diproses</div>
			<div class="mono" style="font-size:20px; font-weight:700; color:var(--info)"><?= $total_in_progress ?></div>
		</div>
		<div>
			<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:0.05em">Lolos / Diterima</div>
			<div class="mono" style="font-size:20px; font-weight:700; color:var(--good)"><?= $total_hired ?></div>
		</div>
		<div>
			<div class="faint" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:0.05em">Perlu Tindakan (>7 Hari)</div>
			<div class="mono" style="font-size:20px; font-weight:700; color:<?= $total_overdue > 0 ? 'var(--crit)' : 'var(--text-muted)' ?>">
				<?= $total_overdue ?>
			</div>
		</div>
	</div>

	<!-- FILTER & SEARCH BAR OPERASIONAL -->
	<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap">
		<div style="display:flex; gap:8px; align-items:center; flex:1; min-width:280px">
			<div style="position:relative; width:100%; max-width:380px">
				<input type="text" id="pipeline-search" placeholder="Cari kandidat, kontak WA, skor..." oninput="filterPipelineRows()"
					style="width:100%; padding:7px 10px 7px 32px; font-size:13px; border-radius:6px; margin:0">
				<svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
				</svg>
			</div>
			<label style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; margin:0; cursor:pointer; white-space:nowrap">
				<input type="checkbox" id="filter-overdue-only" onchange="filterPipelineRows()" style="margin:0; width:auto">
				<span>Hanya kandidat tertahan (>7 hari)</span>
			</label>
		</div>

		<!-- STAGE JUMP NAVIGATOR -->
		<div style="display:flex; gap:6px; align-items:center; overflow-x:auto; padding:2px 0; max-width:100%">
			<span class="faint" style="font-size:11.5px; white-space:nowrap">Lompat ke Tahap:</span>
			<?php foreach ($stages as $urut => $s): ?>
				<a href="#stage-sec-<?= (int) $urut ?>" class="btn btn-sm btn-ghost" style="padding:3px 8px; font-size:11px; white-space:nowrap; border-radius:5px">
					<?= (int) $urut ?>. <?= html_escape($s['nama']) ?>
					<span style="background:var(--surface-2); padding:1px 5px; border-radius:10px; font-size:10px; margin-left:4px; font-weight:700">
						<?= count($s['cards']) ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( ! $stages): ?>
		<div style="padding:48px 20px; text-align:center; background:var(--surface-2); border-radius:8px; margin-top:16px">
			<svg style="width:36px; height:36px; margin:0 auto 10px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
			<p class="muted" style="font-size:14px; margin:0">Belum ada kandidat di alur pipeline lowongan ini.</p>
			<div style="margin-top:12px">
				<a href="<?= site_url('manual') ?>" class="btn btn-sm btn-primary">+ Tambah Pelamar Manual</a>
			</div>
		</div>
	<?php else: ?>
		<!-- ================= TABEL TERSTRUKTUR KE BAWAH ================= -->
		<div id="pipeline-table-container">
			<?php foreach ($stages as $urut => $s): ?>
			<?php
				$id_stage = (int) $s['id_stage'];
				$total_cards = count($s['cards']);
			?>
			<div id="stage-sec-<?= (int) $urut ?>" class="stage-section" style="margin-top:24px; border:1px solid var(--border); border-radius:8px; background:var(--surface); overflow:hidden">
				<!-- Header Tahap -->
				<div style="background:var(--surface-2); border-bottom:1px solid var(--border); padding:10px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px">
					<div style="display:flex; align-items:center; gap:10px">
						<span style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:6px; background:var(--accent); color:var(--accent-contrast); font-weight:700; font-size:12px; font-family:'IBM Plex Mono', monospace">
							<?= (int) $urut ?>
						</span>
						<h2 style="margin:0; font-size:15px; font-weight:700">
							<?= html_escape($s['nama']) ?>
						</h2>
						<span class="tag info" style="font-size:11px; text-transform:uppercase">
							<?= html_escape($s['tipe']) ?>
						</span>
						<span class="faint mono" style="font-size:12px">
							(<?= $total_cards ?> kandidat)
						</span>
					</div>

					<div style="display:flex; align-items:center; gap:8px">
						<?php if ($can_aksi): ?>
							<!-- Tombol Sisip Cepat Form -->
							<details style="position:relative">
								<summary class="btn btn-sm btn-ghost" style="padding:3px 8px; font-size:11.5px; cursor:pointer; list-style:none">
									+ Sisip Tahap Ad-Hoc
								</summary>
								<div style="position:absolute; right:0; top:100%; margin-top:4px; z-index:30; background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow); border-radius:7px; padding:12px; min-width:260px">
									<?= form_open(site_url('pipeline/insert_stage/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
										<div class="faint" style="font-size:11px; margin-bottom:6px; font-weight:600">PILIH TAHAP UNTUK DISISIPKAN:</div>
										<select name="id_stage" required style="width:100%; font-size:12px; padding:5px 8px; margin-bottom:8px">
											<?php foreach ($all_stages as $st): ?>
												<option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['tipe_tahap']) ?>)</option>
											<?php endforeach; ?>
										</select>
										<div class="faint" style="font-size:11px; margin-bottom:6px">Pilih kandidat target:</div>
										<select name="id_lamaran" required style="width:100%; font-size:12px; padding:5px 8px; margin-bottom:10px">
											<?php foreach ($s['cards'] as $c): ?>
												<option value="<?= (int) $c['id_lamaran'] ?>"><?= html_escape($c['nama_lengkap']) ?></option>
											<?php endforeach; ?>
										</select>
										<button type="submit" class="btn btn-sm btn-primary" style="width:100%">Sisipkan Sekarang</button>
									<?= form_close() ?>
								</div>
							</details>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( ! $total_cards): ?>
					<div style="padding:20px; text-align:center; color:var(--text-faint); font-size:13px; font-style:italic">
						Tidak ada kandidat pada tahap <?= html_escape($s['nama']) ?> saat ini.
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="width:100%">
						<table style="width:100%; border-collapse:collapse; margin:0; table-layout:fixed">
							<thead>
								<tr style="background:var(--surface-3); font-size:11.5px; color:var(--text-muted); border-bottom:1px solid var(--border)">
									<th style="width:34px; padding:8px 6px; text-align:center">#</th>
									<th style="width:22%; padding:8px 8px; text-align:left">Kandidat</th>
									<th style="width:14%; padding:8px 8px; text-align:left">Kontak WA</th>
									<th style="width:10%; padding:8px 6px; text-align:center">Aging SLA</th>
									<th style="width:12%; padding:8px 6px; text-align:center">Status</th>
									<th style="width:20%; padding:8px 8px; text-align:left">Evaluasi / Asesmen</th>
									<?php if ($can_aksi): ?>
										<th style="width:22%; padding:8px 8px; text-align:left">Aksi & Remark</th>
									<?php endif; ?>
								</tr>
							</thead>
							<tbody>
								<?php
								$no = 1;
								foreach ($s['cards'] as $c):
									$id_app_stage = (int) $c['id_app_stage'];
									$id_lamaran   = (int) $c['id_lamaran'];
									$hari         = (int) $c['hari_di_tahap'];

									$cur_ivs   = isset($interviews[$id_app_stage]) ? $interviews[$id_app_stage] : array();
									$latest_iv = ! empty($cur_ivs) ? $cur_ivs[0] : NULL;

									$cur_psi    = isset($psikotes[$id_app_stage]) ? $psikotes[$id_app_stage] : array();
									$latest_psi = ! empty($cur_psi) ? $cur_psi[0] : NULL;

									$cur_off = isset($offers[$id_lamaran]) ? $offers[$id_lamaran] : NULL;
								?>
								<tr class="candidate-row" data-overdue="<?= $hari > 7 ? '1' : '0' ?>"
									data-search="<?= strtolower(html_escape($c['nama_lengkap'] . ' ' . $c['no_wa_normal'] . ' ' . $c['status_global'] . ' ' . ($latest_iv['hasil'] ?? '') . ' ' . ($latest_psi['hasil'] ?? ''))) ?>"
									style="border-bottom:1px solid var(--border); transition:background-color .12s">

									<td style="padding:8px 6px; text-align:center" class="muted mono"><?= $no++ ?></td>

									<!-- Kolom Kandidat -->
									<td style="padding:8px 8px; word-break:break-word">
										<div style="font-weight:600; font-size:13px; line-height:1.3">
											<a href="<?= site_url('candidates/detail/' . $id_lamaran) ?>" title="Buka Dossier Kandidat" style="color:var(--text); text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
												<?= html_escape($c['nama_lengkap']) ?>
											</a>
										</div>
										<div class="faint" style="font-size:11px; margin-top:2px">
											#<?= $id_lamaran ?>
											<?php if ($c['screening_score'] !== NULL): ?>
												&middot; CV: <strong class="mono"><?= (int)$c['screening_score'] ?></strong>
											<?php endif; ?>
										</div>
									</td>

									<!-- Kolom Kontak WA -->
									<td style="padding:8px 8px; font-size:12px; word-break:break-all" class="mono">
										<?php if ($c['no_wa_normal']): ?>
											<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['no_wa_normal']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--accent); text-decoration:none; display:inline-block" title="Chat WhatsApp">
												<?= html_escape($c['no_wa_normal']) ?> &nearr;
											</a>
										<?php else: ?>
											<span class="faint">-</span>
										<?php endif; ?>
									</td>

									<!-- Kolom Aging SLA -->
									<td style="padding:8px 6px; text-align:center">
										<?php if ($hari > 7): ?>
											<span class="tag off" title="Tertahan lebih dari 7 hari di tahap ini" style="font-size:10.5px; font-weight:700">
												<?= $hari ?> hr !
											</span>
										<?php elseif ($hari >= 4): ?>
											<span class="tag warn" style="font-size:10.5px">
												<?= $hari ?> hr
											</span>
										<?php else: ?>
											<span class="tag info" style="font-size:10.5px">
												<?= $hari ?> hr
											</span>
										<?php endif; ?>
									</td>

									<!-- Kolom Status Global -->
									<td style="padding:8px 6px; text-align:center">
										<span class="tag <?= $c['status_global'] === 'In_Progress' ? 'on' : ($c['status_global'] === 'Hired' ? 'on' : 'off') ?>" style="font-size:10.5px">
											<?= html_escape($c['status_global']) ?>
										</span>
									</td>

									<!-- Kolom Hasil Evaluasi / Asesmen -->
									<td style="padding:8px 8px; font-size:11.5px; word-break:break-word">
										<?php if ($latest_iv): ?>
											<div style="display:flex; align-items:center; gap:4px; margin-bottom:2px; flex-wrap:wrap">
												<span>🎤 <strong>IV:</strong></span>
												<span class="tag <?= $latest_iv['hasil'] === 'Lulus' ? 'on' : ($latest_iv['hasil'] === 'Tidak_Lulus' ? 'off' : 'warn') ?>" style="font-size:9.5px; padding:1px 5px">
													<?= html_escape($latest_iv['hasil'] ?: 'Jadwal') ?>
												</span>
												<?php if ($latest_iv['skor']): ?>
													<span class="mono faint">(<?= (int)$latest_iv['skor'] ?>)</span>
												<?php endif; ?>
											</div>
										<?php endif; ?>

										<?php if ($latest_psi): ?>
											<div style="display:flex; align-items:center; gap:4px; margin-bottom:2px; flex-wrap:wrap">
												<span>🧠 <strong>Psi:</strong></span>
												<span class="tag <?= $latest_psi['hasil'] === 'Lulus' ? 'on' : ($latest_psi['hasil'] === 'Tidak_Lulus' ? 'off' : 'warn') ?>" style="font-size:9.5px; padding:1px 5px">
													<?= html_escape($latest_psi['hasil'] ?: 'Selesai') ?>
												</span>
												<?php if ($latest_psi['skor_total']): ?>
													<span class="mono faint">(<?= (int)$latest_psi['skor_total'] ?>)</span>
												<?php endif; ?>
											</div>
										<?php endif; ?>

										<?php if ($cur_off): ?>
											<div style="display:flex; align-items:center; gap:4px; margin-bottom:2px; flex-wrap:wrap">
												<span>💼 <strong>Offer:</strong></span>
												<span class="tag <?= $cur_off['status_offer'] === 'Accepted' ? 'on' : 'warn' ?>" style="font-size:9.5px; padding:1px 5px">
													<?= html_escape($cur_off['status_offer']) ?>
												</span>
											</div>
										<?php endif; ?>

										<?php if ( ! $latest_iv && ! $latest_psi && ! $cur_off): ?>
											<span class="faint" style="font-size:11px">- Belum ada -</span>
										<?php endif; ?>
									</td>

									<!-- Kolom Aksi Lanjut & Remark -->
									<?php if ($can_aksi): ?>
									<td style="padding:8px 8px">
										<div style="display:flex; gap:4px; align-items:center; flex-wrap:wrap">
											<!-- Advance Remark Inline Form -->
											<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('style' => 'display:inline-flex; gap:3px; margin:0; align-items:center; flex:1; min-width:120px')) ?>
												<input type="hidden" name="id_app_stage" value="<?= $id_app_stage ?>">
												<select name="id_remark" required style="width:100%; min-width:90px; padding:3px 4px; font-size:11px; border-radius:4px">
													<option value="">- Remark -</option>
													<?php if ( ! empty($remarks[$id_stage])): ?>
														<?php foreach ($remarks[$id_stage] as $rmk): ?>
															<option value="<?= (int) $rmk['id_remark'] ?>">
																<?= html_escape($rmk['label']) ?> (<?= html_escape($rmk['efek_status']) ?>)
															</option>
														<?php endforeach; ?>
													<?php endif; ?>
												</select>
												<button type="submit" class="btn btn-sm btn-primary" title="Eksekusi Alur Tahap" style="padding:3px 7px; font-size:11px; flex:none">
													&rarr;
												</button>
											<?= form_close() ?>

											<!-- Shortcut Tombol Evaluasi Khusus -->
											<div style="display:inline-flex; gap:3px; align-items:center">
												<?php if ($s['tipe'] === 'INTERVIEW'): ?>
													<button type="button" class="btn btn-sm btn-ghost" style="padding:3px 6px; font-size:11px" title="Input Jadwal & Hasil Interview"
														onclick='openInterviewModal(<?= json_encode(array(
															"id_app_stage" => $id_app_stage,
															"nama" => $c["nama_lengkap"],
															"interview" => $latest_iv
														)) ?>)'>
														🎤
													</button>
												<?php endif; ?>

												<?php if ($s['tipe'] === 'TEST'): ?>
													<button type="button" class="btn btn-sm btn-ghost" style="padding:3px 6px; font-size:11px" title="Input Hasil Psikotes"
														onclick='openPsikotesModal(<?= json_encode(array(
															"id_app_stage" => $id_app_stage,
															"nama" => $c["nama_lengkap"],
															"psikotes" => $latest_psi
														)) ?>)'>
														🧠
													</button>
												<?php endif; ?>

												<?php if ($s['tipe'] === 'OFFER' || $cur_off): ?>
													<button type="button" class="btn btn-sm btn-ghost" style="padding:3px 6px; font-size:11px" title="Kelola Offering Letter"
														onclick='openOfferModal(<?= json_encode(array(
															"id_lamaran" => $id_lamaran,
															"nama" => $c["nama_lengkap"],
															"offer" => $cur_off
														)) ?>)'>
														💼
													</button>
												<?php endif; ?>

												<?php if ($s['tipe'] === 'KONTAK'): ?>
													<details style="position:relative; display:inline-block">
														<summary class="btn btn-sm btn-ghost" style="padding:3px 6px; font-size:11px; cursor:pointer; list-style:none" title="Log Respon Kontak WA">
															💬
														</summary>
														<div style="position:absolute; right:0; top:100%; margin-top:4px; z-index:30; background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow); border-radius:6px; padding:8px; width:150px">
															<?= form_open(site_url('pipeline/log_contact/' . (int) $req['id_req']), array('style' => 'margin:0')) ?>
																<input type="hidden" name="id_app_stage" value="<?= $id_app_stage ?>">
																<select name="respons" style="width:100%; padding:3px 5px; font-size:11.5px; margin-bottom:6px">
																	<option value="Terkirim">WA Terkirim</option>
																	<option value="Dibalas">Dibalas</option>
																	<option value="Tidak_Aktif">Tidak Aktif</option>
																	<option value="Menolak">Menolak</option>
																</select>
																<button type="submit" class="btn btn-sm btn-primary" style="width:100%; font-size:11px; padding:3px">Simpan Respon</button>
															<?= form_close() ?>
														</div>
													</details>
												<?php endif; ?>

												<a href="<?= site_url('candidates/detail/' . $id_lamaran) ?>" class="btn btn-sm btn-ghost" style="padding:3px 6px; font-size:10.5px" title="Buka Detail Dossier">
													Doc
												</a>
											</div>
										</div>
									</td>
									<?php endif; ?>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- ================= MODAL INTERVIEW ================= -->
	<dialog id="dlg-interview" style="border:1px solid var(--border); border-radius:10px; padding:24px; max-width:540px; width:100%; background:var(--surface); color:var(--text); box-shadow:var(--shadow)">
		<h2 id="dlg-iv-title" style="margin-top:0; font-size:17px; border-bottom:1px solid var(--border); padding-bottom:8px">Jadwal & Hasil Interview</h2>
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
						<option value="">- Pilih Pewawancara -</option>
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

			<label for="iv-catatan">Catatan Wawancara</label>
			<textarea name="catatan" id="iv-catatan" rows="3" placeholder="Ulasan kompetensi, sikap, kelebihan, kekurangan..."></textarea>

			<div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px">
				<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-interview').close()">Batal</button>
				<button type="submit" class="btn btn-primary">Simpan Hasil Interview</button>
			</div>
		<?= form_close() ?>
	</dialog>

	<!-- ================= MODAL PSIKOTES ================= -->
	<dialog id="dlg-psikotes" style="border:1px solid var(--border); border-radius:10px; padding:24px; max-width:540px; width:100%; background:var(--surface); color:var(--text); box-shadow:var(--shadow)">
		<h2 id="dlg-psi-title" style="margin-top:0; font-size:17px; border-bottom:1px solid var(--border); padding-bottom:8px">Hasil Psikotes / Asesmen</h2>
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
						<option value="">- Pilih Hasil -</option>
						<option value="Lulus">Lulus</option>
						<option value="Tidak_Lulus">Tidak Lulus</option>
						<option value="Perlu_Review">Perlu Review</option>
					</select>
				</div>
			</div>

			<label for="psi-rekomendasi">Rekomendasi / Profil Singkat</label>
			<textarea name="rekomendasi" id="psi-rekomendasi" rows="3" placeholder="Deskripsi kepribadian, potensi, catatan khusus..."></textarea>

			<div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px">
				<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-psikotes').close()">Batal</button>
				<button type="submit" class="btn btn-primary">Simpan Hasil Psikotes</button>
			</div>
		<?= form_close() ?>
	</dialog>

	<!-- ================= MODAL OFFER ================= -->
	<dialog id="dlg-offer" style="border:1px solid var(--border); border-radius:10px; padding:24px; max-width:540px; width:100%; background:var(--surface); color:var(--text); box-shadow:var(--shadow)">
		<h2 id="dlg-off-title" style="margin-top:0; font-size:17px; border-bottom:1px solid var(--border); padding-bottom:8px">Penawaran Kerja (Offering)</h2>
		<?= form_open(site_url('pipeline/save_offer/' . (int) $req['id_req'])) ?>
			<input type="hidden" name="id_offer" id="off-id-offer">
			<input type="hidden" name="id_lamaran" id="off-id-lamaran">

			<?php if ($can_gaji): ?>
				<label for="off-gaji">Gaji yang Ditawarkan (IDR)</label>
				<input type="number" name="gaji_ditawarkan" id="off-gaji" placeholder="e.g. 5000000">
			<?php endif; ?>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
				<div>
					<label for="off-tgl-penawaran">Tanggal Penawaran</label>
					<input type="date" name="tanggal_penawaran" id="off-tgl-penawaran">
				</div>
				<div>
					<label for="off-status">Status Penawaran</label>
					<select name="status_offer" id="off-status">
						<option value="Nego">Nego / Ditawarkan</option>
						<option value="Accepted">Accepted (Diterima)</option>
						<option value="Declined">Declined (Ditolak Kandidat)</option>
						<option value="Canceled">Canceled (Dibatalkan RPG)</option>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
				<div>
					<label for="off-tgl-join-sepakat">Target Join Sepakat</label>
					<input type="date" name="tanggal_join_disepakati" id="off-tgl-join-sepakat">
				</div>
				<div>
					<label for="off-tgl-join-aktual">Join Aktual</label>
					<input type="date" name="tanggal_join_aktual" id="off-tgl-join-aktual">
				</div>
			</div>

			<label for="off-alasan">Catatan / Alasan</label>
			<textarea name="alasan" id="off-alasan" rows="2" placeholder="Catatan negosiasi gaji, tunjangan, atau alasan tolak offer..."></textarea>

			<div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px">
				<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-offer').close()">Batal</button>
				<button type="submit" class="btn btn-primary">Simpan Offer</button>
			</div>
		<?= form_close() ?>
	</dialog>

	<!-- SCRIPT FILTER & MODAL HANDLER -->
	<script>
	function filterPipelineRows() {
		var query = (document.getElementById('pipeline-search').value || '').toLowerCase().trim();
		var overdueOnly = document.getElementById('filter-overdue-only').checked;
		var rows = document.querySelectorAll('.candidate-row');

		rows.forEach(function(row) {
			var text = row.getAttribute('data-search') || '';
			var isOverdue = row.getAttribute('data-overdue') === '1';

			var matchQuery = !query || text.indexOf(query) !== -1;
			var matchOverdue = !overdueOnly || isOverdue;

			if (matchQuery && matchOverdue) {
				row.style.display = '';
			} else {
				row.style.display = 'none';
			}
		});
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
</div>

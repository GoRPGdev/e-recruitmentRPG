<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="card" style="padding:22px 26px; margin-bottom:20px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Detail Permintaan Tenaga Kerja</div>
			<h1 style="margin:0 0 6px; font-size:22px">
				MPR: <?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?>
			</h1>
			<div class="muted" style="font-size:13px">
				Posisi: <strong><?= html_escape($req['nama_posisi']) ?></strong> &middot;
				Departemen: <strong><?= html_escape($req['departemen'] ?: '-') ?></strong>
			</div>
		</div>
		<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
			<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') ?>">&larr; Kembali ke Daftar</a>
			<a class="btn btn-sm btn-primary" href="<?= site_url('pipeline/index/' . (int) $req['id_req']) ?>">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
				<span>Buka Pipeline Pelamar &rarr;</span>
			</a>
		</div>
	</div>

	<!-- Tabel Info Requisition -->
	<div style="overflow-x:auto; margin-bottom:20px">
		<table style="margin:0">
			<tr>
				<th style="width:180px">Status MPR</th>
				<td>
					<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap">
						<span class="tag <?= in_array($req['status_req'], array('Sourcing','Approved','Terpenuhi','Terpenuhi_Sebagian')) ? 'on' : (in_array($req['status_req'], array('Review_HR','Menunggu_BOD')) ? 'warn' : (in_array($req['status_req'], array('Ditolak_HR','Ditolak_BOD','Dibatalkan')) ? 'crit' : 'off')) ?>" style="font-size:13px; padding:4px 10px">
							<?= html_escape($req['status_req']) ?>
						</span>

						<!-- Kontrol Perubahan Status oleh HR -->
						<?php if ($can_kelola && ! in_array($req['status_req'], array('Dibatalkan', 'Terpenuhi'))): ?>
							<details style="position:relative; display:inline-block">
								<summary class="btn btn-sm btn-ghost" style="cursor:pointer; font-size:11px; padding:3px 8px">
									Ubah Status MPR...
								</summary>
								<div style="position:absolute; left:0; top:100%; margin-top:6px; z-index:40; background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow); border-radius:8px; padding:14px; min-width:300px">
									<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
										<div class="faint" style="font-size:11px; margin-bottom:8px; font-weight:600">UPDATE STATUS REQUISITION:</div>
										<label for="status_baru" style="font-size:12px">Pilih Status Baru</label>
										<select name="status_baru" id="status_baru" style="font-size:12px; margin-bottom:8px; width:100%">
											<option value="Sourcing" <?= $req['status_req'] === 'Sourcing' ? 'selected' : '' ?>>Sourcing (Pencarian Aktif)</option>
											<option value="Sourcing_Ulang" <?= $req['status_req'] === 'Sourcing_Ulang' ? 'selected' : '' ?>>Sourcing_Ulang (Batch Baru)</option>
											<option value="Kadaluarsa" <?= $req['status_req'] === 'Kadaluarsa' ? 'selected' : '' ?>>Kadaluarsa (Tutup Pencarian)</option>
										</select>
										<label for="catatan_status" style="font-size:12px">Alasan / Catatan</label>
										<input type="text" name="catatan" id="catatan_status" placeholder="e.g. Pembukaan batch 2 karena pelamar batch 1 habis" style="font-size:12px; margin-bottom:10px; width:100%">
										<button type="submit" class="btn btn-sm btn-primary" style="width:100%">Simpan Perubahan Status</button>
									<?= form_close() ?>
								</div>
							</details>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<tr><th>Pemohon</th><td><strong><?= html_escape($req['pemohon']) ?></strong></td></tr>
			<tr><th>Tipe Penempatan</th><td><?= html_escape($req['tipe_penempatan'] . ($req['nama_outlet'] ? ' &mdash; ' . $req['nama_outlet'] : ' (Headquarters)')) ?></td></tr>
			<tr><th>Alur Seleksi (Flow)</th><td><span class="mono"><?= html_escape($req['kode_flow'] ?: '(Belum ditentukan)') ?></span></td></tr>
			<tr>
				<th>Kebutuhan Formasi</th>
				<td>
					Dibutuhkan: <strong><?= (int) $req['jumlah_dibutuhkan'] ?></strong> orang &middot;
					Disetujui BOD: <strong><?= $req['jumlah_disetujui'] === NULL ? '-' : (int) $req['jumlah_disetujui'] ?></strong> &middot;
					Terpenuhi: <strong style="color:var(--good)"><?= (int) $req['jumlah_terpenuhi'] ?></strong>
				</td>
			</tr>
</table>
	</div>

		<!-- Banner Aksi: Ajukan ke HR (Pemohon: Draft / Ditolak) -->
		<?php if (in_array($req['status_req'], array('Draft', 'Ditolak_HR', 'Ditolak_BOD'))): ?>
			<div style="padding:16px 20px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px">
				<div>
					<strong style="color:var(--text); font-size:14px">Ajukan Permintaan Tenaga Kerja ke Tim HR?</strong>
					<div class="muted" style="font-size:12px">
						<?php if (in_array($req['status_req'], array('Ditolak_HR', 'Ditolak_BOD'))): ?>
							Permintaan ini sebelumnya <strong><?= html_escape($req['status_req']) ?></strong>. Setelah revisi data formasi, Anda dapat mengajukannya kembali ke HR.
						<?php else: ?>
							Dokumen MPR akan ditinjau oleh HR (analisis beban kerja & alokasi budget) sebelum diteruskan ke Direksi (BOD).
						<?php endif; ?>
					</div>
				</div>
				<?= form_open(site_url('requisitions/submit_hr/' . (int) $req['id_req']), array('class' => 'inline')) ?>
					<button type="submit" class="btn btn-sm btn-primary">
						<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
						<span>Ajukan ke HR (Review HR) &rarr;</span>
					</button>
				<?= form_close() ?>
			</div>
		<?php endif; ?>

		<!-- Banner Aksi: Panel Review HR (Status: Review_HR) -->
		<?php if ($req['status_req'] === 'Review_HR'): ?>
			<div style="padding:16px 20px; background:var(--warn-soft); border:1px solid var(--warn); border-radius:8px; margin-bottom:20px">
				<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px">
					<div>
						<div class="eyebrow" style="color:var(--warn); margin-bottom:3px">Evaluasi &amp; Validasi HR</div>
						<strong style="font-size:15px; color:var(--text)">Permintaan Tenaga Kerja Sedang Dalam Tahap Review HR</strong>
						<div class="muted" style="font-size:12px; margin-top:3px">
							Validasi analisis beban kerja, urgensi posisi, dan ketersediaan alokasi budget sebelum diteruskan ke Direksi.
						</div>
					</div>
					<?php if ($can_kelola): ?>
						<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
							<!-- Tombol Tolak HR -->
							<button type="button" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit)" onclick="document.getElementById('modal-tolak-hr').hidden = false">
								Tolak Permintaan (Ditolak HR)
							</button>

							<!-- Tombol Teruskan ke BOD -->
							<?= form_open(site_url('requisitions/submit/' . (int) $req['id_req']), array('class' => 'inline')) ?>
								<button type="submit" class="btn btn-sm btn-primary">
									<span>Setujui &amp; Teruskan ke BOD &rarr;</span>
								</button>
							<?= form_close() ?>
						</div>
					<?php else: ?>
						<span class="tag warn" style="font-size:12px; padding:4px 8px">Menunggu Keputusan HR</span>
					<?php endif; ?>
				</div>

				<!-- Modal / Dialog Tolak HR -->
				<?php if ($can_kelola): ?>
					<div id="modal-tolak-hr" hidden style="margin-top:14px; padding:14px; background:var(--surface); border:1px solid var(--border); border-radius:6px">
						<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
							<input type="hidden" name="status_baru" value="Ditolak_HR">
							<div class="faint" style="font-size:11px; font-weight:600; color:var(--crit); margin-bottom:6px">ALASAN PENOLAKAN PERMINTAAN OLEH HR (WAJIB DIISI):</div>
							<textarea name="catatan" rows="2" placeholder="e.g. Alokasi budget belum tersedia pada Q3 / Analisis beban kerja departemen belum mencukupi" required style="font-size:12px; margin-bottom:10px; width:100%"></textarea>
							<div style="display:flex; justify-content:flex-end; gap:8px">
								<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-tolak-hr').hidden = true">Batal</button>
								<button type="submit" class="btn btn-sm btn-primary" style="background:var(--crit)">Konfirmasi Tolak HR</button>
							</div>
						<?= form_close() ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Banner Aksi: Ajukan ke BOD (Saat status Sourcing_Ulang) -->
		<?php if ($req['status_req'] === 'Sourcing_Ulang'): ?>
			<div style="padding:14px; background:var(--warn-soft); border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center">
				<div>
					<strong style="color:var(--warn)">Buka putaran approval baru ke BOD?</strong>
					<div class="muted" style="font-size:12px">Untuk penambahan alokasi atau perpanjangan batch sourcing.</div>
				</div>
				<?= form_open(site_url('requisitions/submit/' . (int) $req['id_req']), array('class' => 'inline')) ?>
					<button type="submit" class="btn btn-sm btn-primary">Ajukan Persetujuan BOD &rarr;</button>
				<?= form_close() ?>
			</div>
		<?php endif; ?>

	<!-- Lowongan Publik & Form Lamaran Terkait -->
	<div style="margin-top:24px; padding-top:16px; border-top:1px solid var(--border)">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px">
			<div>
				<h2 style="font-size:16px; margin:0">Lowongan &amp; Form Publik</h2>
				<div class="muted" style="font-size:12px">Kontrol akses pelamar publik untuk MPR ini (buka/tutup form lamaran online).</div>
			</div>
			<?php if ($can_kelola && in_array($req['status_req'], array('Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian'))): ?>
				<button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('form-tambah-posting').hidden = !document.getElementById('form-tambah-posting').hidden">
					+ Buat Link Lowongan Baru
				</button>
			<?php endif; ?>
		</div>

		<!-- Form Input Posting Baru (Toggle) -->
		<div id="form-tambah-posting" hidden style="margin-bottom:16px; padding:14px 18px; background:var(--surface-2); border-radius:8px; border:1px solid var(--border)">
			<h3 style="font-size:14px; margin-top:0; margin-bottom:8px">Publikasi Link Lowongan Baru</h3>
			<?= form_open(site_url('requisitions/post_job/' . (int) $req['id_req'])) ?>
				<div style="display:grid; grid-template-columns: 1fr auto; gap:10px; align-items:end">
					<div>
						<label for="judul_posting" style="font-size:12px">Judul Lowongan *</label>
						<input type="text" id="judul_posting" name="judul_posting" value="<?= html_escape($req['nama_posisi']) ?>" required style="font-size:13px">
					</div>
					<button type="submit" class="btn btn-sm btn-primary">Generate Link Form &rarr;</button>
				</div>
			<?= form_close() ?>
		</div>

		<?php if (empty($postings)): ?>
			<div style="padding:16px; text-align:center; background:var(--surface); border:1px dashed var(--border); border-radius:8px">
				<span class="muted" style="font-size:13px">Belum ada link form lowongan publik untuk MPR ini.</span>
			</div>
		<?php else: ?>
			<div style="overflow-x:auto">
				<table style="margin:0; font-size:13px">
					<thead>
						<tr>
							<th>Judul Lowongan</th>
							<th>Tautan Form Publik</th>
							<th style="width:110px; text-align:center">Status Form</th>
							<th style="width:90px; text-align:center">Submit</th>
							<th style="width:90px; text-align:center">Pelamar</th>
							<th style="width:200px; text-align:right">Kontrol Akses</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($postings as $p): ?>
						<tr>
							<td>
								<strong><?= html_escape($p['judul_posting']) ?></strong>
								<div class="faint" style="font-size:11px"><?= !empty($p['tanggal_posting']) ? html_escape(substr($p['tanggal_posting'], 0, 10)) : '' ?></div>
							</td>
							<td>
								<?php if ($p['url_slug']): ?>
									<div style="display:flex; align-items:center; gap:6px">
										<code style="font-size:11px">/lamar/<?= html_escape($p['url_slug']) ?></code>
										<a href="<?= site_url('lamar/' . $p['url_slug']) ?>" target="_blank" class="btn-sm btn-ghost" title="Buka form di tab baru" style="padding:2px 6px; font-size:11px">
											Buka &nearr;
										</a>
										<button type="button" class="btn-sm btn-ghost" title="Salin URL form" style="padding:2px 6px; font-size:11px" onclick="navigator.clipboard.writeText('<?= site_url('lamar/' . $p['url_slug']) ?>'); alert('Link berhasil disalin!')">
											Salin
										</button>
									</div>
								<?php else: ?>
									<span class="faint">(Slug belum digenerate)</span>
								<?php endif; ?>
							</td>
							<td style="text-align:center">
								<?php if ($p['form_aktif']): ?>
									<span class="tag on" style="font-size:11px">Buka (Aktif)</span>
								<?php else: ?>
									<span class="tag off" style="font-size:11px">Ditutup</span>
								<?php endif; ?>
							</td>
							<td style="text-align:center"><?= (int) $p['jumlah_submit'] ?></td>
							<td style="text-align:center"><strong><?= (int) $p['n_lamaran'] ?></strong></td>
							<td style="text-align:right">
								<div style="display:inline-flex; gap:6px; align-items:center">
									<?php if ($can_kelola): ?>
										<?= form_open(site_url('requisitions/toggle_posting/' . (int) $req['id_req'] . '/' . (int) $p['id_posting']), array('style' => 'display:inline')) ?>
											<input type="hidden" name="form_aktif" value="<?= $p['form_aktif'] ? '0' : '1' ?>">
											<?php if ($p['form_aktif']): ?>
												<button type="submit" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit); font-size:11px; padding:3px 8px" onclick="return confirm('Tutup form lamaran publik ini? Pelamar tidak akan bisa mengisi formulir.')">
													Tutup Form
												</button>
											<?php else: ?>
												<button type="submit" class="btn btn-sm btn-primary" style="font-size:11px; padding:3px 8px" <?= in_array($req['status_req'], array('Dibatalkan', 'Kadaluarsa')) ? 'disabled title="MPR Dibatalkan/Kadaluarsa"' : '' ?>>
													Buka Form
												</button>
											<?php endif; ?>
										<?= form_close() ?>
									<?php endif; ?>
									<a class="btn btn-sm btn-ghost" href="<?= site_url('postings/form_settings/' . (int) $p['id_posting']) ?>" style="font-size:11px; padding:3px 8px">
										Pengaturan
									</a>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<!-- Riwayat Approval BOD -->
	<div style="margin-top:24px; padding-top:16px; border-top:1px solid var(--border)">
		<h2 style="font-size:16px; margin-bottom:10px">Riwayat Persetujuan BOD (Requisition Approvals)</h2>
		<?php if ( ! $approvals): ?>
			<p class="muted" style="font-size:13px">Belum ada riwayat pengajuan persetujuan.</p>
		<?php else: ?>
		<div style="overflow-x:auto">
			<table style="margin:0">
				<thead>
					<tr>
						<th>Putaran</th>
						<th>Tanggal Pengajuan</th>
						<th>Keputusan</th>
						<th>Disetujui</th>
						<th>Tgl Keputusan</th>
						<th>Oleh Direksi (BOD)</th>
						<th>Catatan</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($approvals as $a): ?>
					<tr>
						<td class="mono">Ke-<?= (int) $a['putaran_ke'] ?></td>
						<td><?= $a['diajukan_ke_bod_pada'] ? html_escape(substr($a['diajukan_ke_bod_pada'], 0, 10)) : '-' ?></td>
						<td><span class="tag <?= $a['keputusan'] === 'Approved' ? 'on' : ($a['keputusan'] === 'Rejected' ? 'off' : 'warn') ?>"><?= html_escape($a['keputusan'] ?: 'Menunggu') ?></span></td>
						<td style="font-weight:600"><?= $a['jumlah_disetujui'] === NULL ? '-' : (int) $a['jumlah_disetujui'] ?> org</td>
						<td><?= $a['tanggal_keputusan'] ? html_escape(substr($a['tanggal_keputusan'], 0, 10)) : '-' ?></td>
						<td><strong><?= html_escape($a['disetujui_oleh'] ?: '-') ?></strong></td>
						<td class="muted"><?= html_escape($a['catatan_bod'] ?: '-') ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<!-- Opsi Batalkan MPR -->
		<?php if (in_array($req['status_req'], array('Draft', 'Menunggu_BOD', 'Sourcing', 'Sourcing_Ulang'))): ?>
			<div style="margin-top:24px; padding:14px 18px; border:1px solid var(--border); border-radius:8px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; background:var(--surface)">
				<div>
					<span style="font-size:13px; font-weight:600; color:var(--crit)">Batalkan Permintaan Rekrutmen Ini?</span>
					<div class="faint" style="font-size:12px">Jika posisi tidak lagi dibutuhkan atau terjadi pembatalan formasi.</div>
				</div>
				<details style="position:relative">
					<summary class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit); cursor:pointer">
						Batalkan MPR...
					</summary>
					<div style="position:absolute; right:0; bottom:100%; margin-bottom:6px; z-index:30; background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow); border-radius:8px; padding:14px; min-width:280px">
						<?= form_open(site_url('requisitions/cancel/' . (int) $req['id_req'])) ?>
							<div class="faint" style="font-size:11px; margin-bottom:6px; font-weight:600">KONFIRMASI PEMBATALAN:</div>
							<label for="alasan_batal" style="font-size:12px">Alasan Pembatalan</label>
							<input type="text" name="alasan_batal" id="alasan_batal" placeholder="e.g. Pembatalan budget / restrukturisasi" required style="font-size:12px; margin-bottom:10px">
							<button type="submit" class="btn btn-sm btn-primary" style="background:var(--crit); width:100%" onclick="return confirm('Yakin membatalkan MPR ini?')">Ya, Batalkan MPR</button>
						<?= form_close() ?>
					</div>
				</details>
			</div>
		<?php endif; ?>
	</div>

	<!-- Form Catat Approval jika open -->
	<?php if ($can_kelola && $open_appr): ?>
		<div style="margin-top:24px; padding:18px; background:var(--surface-2); border-radius:8px">
			<h2 style="font-size:15px; margin-top:0">Catat Keputusan BOD (Putaran <?= (int) $open_appr['putaran_ke'] ?>)</h2>
			<?= form_open_multipart(site_url('requisitions/approve/' . (int) $req['id_req'])) ?>
				<input type="hidden" name="id_approval" value="<?= (int) $open_appr['id_approval'] ?>">
				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px">
					<div>
						<label for="keputusan">Keputusan</label>
						<select id="keputusan" name="keputusan">
							<option value="Approved">Approved</option>
							<option value="Approved_Sebagian">Approved Sebagian</option>
							<option value="Rejected">Rejected</option>
						</select>
					</div>
					<div>
						<label for="jumlah_disetujui">Jumlah Disetujui</label>
						<input type="text" id="jumlah_disetujui" name="jumlah_disetujui" value="<?= (int) $req['jumlah_dibutuhkan'] ?>" inputmode="numeric">
					</div>
					<div>
						<label for="disetujui_oleh">Nama Anggota BOD</label>
						<input type="text" id="disetujui_oleh" name="disetujui_oleh" placeholder="e.g. Ibu Ratna / Pak Bambang" required>
					</div>
					<div>
						<label for="tanggal_keputusan">Tanggal Keputusan</label>
						<input type="date" id="tanggal_keputusan" name="tanggal_keputusan" value="<?= date('Y-m-d') ?>">
					</div>
				</div>

				<label for="catatan_bod">Catatan BOD</label>
				<input type="text" id="catatan_bod" name="catatan_bod" placeholder="Catatan approval atau arahan kualifikasi...">

				<label for="lampiran">Lampiran Bukti Approval WA/Memo (JPG/PNG/PDF, Opsional)</label>
				<input type="file" id="lampiran" name="lampiran" accept=".jpg,.jpeg,.png,.pdf">

				<div style="margin-top:14px">
					<button type="submit" class="btn btn-sm btn-primary">Simpan Keputusan BOD</button>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>
</div>

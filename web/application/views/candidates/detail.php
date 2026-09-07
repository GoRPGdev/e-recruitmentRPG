<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div>
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Dossier Pelamar &amp; Verifikasi</div>
			<div style="display:flex; align-items:center; gap:10px">
				<h1 style="margin:0; font-size:22px"><?= html_escape($c['nama_lengkap']) ?></h1>
				<span class="tag <?= in_array($c['status_global'], array('Hired','Approved','Sourcing')) ? 'on' : ($c['status_global'] === 'Rejected' ? 'off' : 'info') ?>">
					<?= html_escape($c['status_global']) ?>
				</span>
			</div>
			<div class="muted" style="margin-top:4px; font-size:13px">
				Posisi: <strong><?= html_escape($c['nama_posisi']) ?></strong> &middot;
				<?= html_escape($c['nama_departemen'] ?: '-') ?> <?= $c['nama_outlet'] ? '(' . html_escape($c['nama_outlet']) . ')' : '' ?> &middot;
				MPR: <span class="mono"><?= html_escape($c['no_mpr'] ?: '#' . $c['id_req']) ?></span> <?php if (in_array($c['status_req'] ?? '', array('Sourcing', 'Approved', 'Sourcing_Ulang'))): ?><span class="tag on" style="font-size:9.5px; padding:1px 5px; font-weight:700">● Jalan</span><?php else: ?><span class="tag off" style="font-size:9.5px; padding:1px 5px; font-weight:700">✕ Closed (<?= html_escape($c['status_req'] ?? 'Tutup') ?>)</span><?php endif; ?> &middot;
				Tahap Berjalan: <strong style="color:var(--accent)"><?= html_escape($c['nama_tahap_kini'] ?: '-') ?></strong>
			</div>
		</div>
		<div style="display:flex; gap:8px; flex-wrap:wrap">
			<a class="btn btn-sm btn-ghost" href="<?= site_url('pipeline/index/' . (int) $c['id_req']) ?>">&larr; Kembali ke Pipeline</a>
			<a class="btn btn-sm btn-ghost" href="<?= site_url('documents/checklist/' . (int) $c['id_lamaran']) ?>">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
				<span>Checklist Berkas</span>
			</a>
		</div>
	</div>

	<!-- Notifikasi Flash Message -->
	<?php if ($this->session->flashdata('success')): ?>
		<div class="flash ok" style="margin-bottom:16px"><?= html_escape($this->session->flashdata('success')) ?></div>
	<?php endif; ?>
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:16px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>

	<!-- ================= PANEL TAUTAN FORM ONBOARDING KARYAWAN ================= -->
	<div class="card" style="padding:16px 20px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-left:4px solid var(--accent)">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:12px">
			<div>
				<div class="eyebrow" style="color:var(--accent); font-weight:700">Formulir Onboarding Karyawan Baru</div>
				<div style="font-size:14px; font-weight:600; margin-top:2px; color:var(--text)">
					Tautan Khusus Pengisian Kelengkapan Data Onboarding
				</div>
				<div class="muted" style="font-size:12px; margin-top:2px">
					Kandidat akan mengisi data identitas resmi (NIK/NPWP/SIM), rekening bank payroll, riwayat pengalaman kerja, dan susunan keluarga. Data awal (Form 1) akan otomatis terkunci.
				</div>
			</div>

			<div style="display:flex; align-items:center; gap:8px">
				<?php if (!empty($onboarding_token['dipakai_pada'])): ?>
					<span class="tag on" style="font-size:11px">✓ Sudah Diisi (<?= html_escape(substr($onboarding_token['dipakai_pada'], 0, 16)) ?>)</span>
				<?php elseif (!empty($onboarding_token['valid'])): ?>
					<span class="tag info" style="font-size:11px">Aktif s/d <?= html_escape(substr($onboarding_token['kadaluarsa_pada'], 0, 10)) ?></span>
				<?php elseif (!empty($onboarding_token)): ?>
					<span class="tag off" style="font-size:11px">Kedaluwarsa / Dicabut</span>
				<?php else: ?>
					<span class="tag" style="font-size:11px">Belum Dibuat</span>
				<?php endif; ?>

				<?php if ($can_kelola): ?>
					<a href="<?= site_url('candidates/generate_onboarding_link/' . (int) $c['id_lamaran']) ?>"
					   class="btn btn-sm btn-primary"
					   onclick="return confirm('<?= !empty($onboarding_token) ? "Buat link onboarding baru? (Link lama akan otomatis dicabut)" : "Buat tautan formulir onboarding untuk kandidat ini?" ?>')"
					   style="font-size:12px; font-weight:600; white-space:nowrap">
						<?= !empty($onboarding_token) ? '↻ Buat Link Baru' : '+ Generate Link Onboarding' ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if (!empty($onboarding_token['valid'])): ?>
			<?php
			$onboarding_url = site_url('onboarding/' . $onboarding_token['token']);
			$wa_msg = "Halo " . $c['nama_lengkap'] . ", selamat bergabung di Ratu Pertiwi Group! Mohon untuk melengkapi formulir onboarding karyawan baru melalui tautan resmi berikut: " . $onboarding_url . " . Terima kasih.";
			$wa_link = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $c['no_wa_normal']) . '?text=' . rawurlencode($wa_msg);
			?>
			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; background:var(--surface); padding:10px 14px; border-radius:8px; border:1px solid var(--border)">
				<span class="muted" style="font-size:12px; font-weight:600; white-space:nowrap">Tautan Kandidat:</span>
				<input type="text" id="onboarding_link_input" value="<?= html_escape($onboarding_url) ?>" readonly style="flex:1; min-width:260px; font-size:12px; padding:6px 10px; background:var(--surface-2); border:1px solid var(--border); border-radius:6px; font-family:monospace; margin:0">
				<button type="button" class="btn btn-sm btn-ghost" onclick="copyOnboardingLink()" style="font-size:12px; white-space:nowrap">
					📋 Salin Tautan
				</button>
				<a href="<?= html_escape($wa_link) ?>" target="_blank" class="btn btn-sm btn-ghost" style="color:var(--good); font-size:12px; white-space:nowrap; text-decoration:none">
					💬 Bagikan ke WhatsApp
				</a>
				<a href="<?= html_escape($onboarding_url) ?>" target="_blank" class="btn btn-sm btn-ghost" style="font-size:12px; white-space:nowrap; text-decoration:none">
					Buka Form &rarr;
				</a>
			</div>
		<?php elseif (!empty($onboarding_token['dipakai_pada'])): ?>
			<div style="font-size:12px; color:var(--text-muted); background:var(--surface); padding:8px 12px; border-radius:6px; border:1px solid var(--border)">
				Kandidat telah selesai mengisi formulir onboarding pada <strong><?= html_escape(substr($onboarding_token['dipakai_pada'], 0, 16)) ?></strong>. Data riwayat pekerjaan, keluarga, dan identitas dapat dilihat pada tabel di bawah ini.
			</div>
		<?php endif; ?>
	</div>

	<!-- Panel Aksi Transisi Tahap Saat Ini (Jika Lamaran Aktif) -->
	<?php if ($can_kelola && $active_stage && ! in_array($c['status_global'], array('Hired','Rejected','Withdrawn','Offer_Declined','No_Show','Talent_Pool'))): ?>
		<div class="card" style="padding:16px 20px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-left:4px solid var(--accent)">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px">
				<div>
					<div class="eyebrow" style="color:var(--accent); font-weight:700">Aksi Proses Seleksi Pelamar</div>
					<div style="font-size:14px; font-weight:600; margin-top:2px">
						Tahap Berjalan: <span style="color:var(--text)"><?= (int) $active_stage['urutan'] ?>. <?= html_escape($active_stage['nama_tahap']) ?></span>
						<span class="tag info" style="font-size:10px; margin-left:6px; text-transform:uppercase"><?= html_escape($active_stage['tipe_tahap']) ?></span>
					</div>
				</div>
				<div class="faint" style="font-size:12px">
					Mulai tahap: <?= $active_stage['tanggal_mulai'] ? html_escape(substr($active_stage['tanggal_mulai'], 0, 16)) : '-' ?>
				</div>
			</div>

			<?= form_open(site_url('pipeline/advance/' . (int) $c['id_req']), array('style' => 'margin:0')) ?>
				<input type="hidden" name="id_app_stage" value="<?= (int) $active_stage['id_app_stage'] ?>">
				<input type="hidden" name="redirect_to" value="candidates/detail/<?= (int) $c['id_lamaran'] ?>">

				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; align-items:end">
					<div>
						<label for="id_remark" style="font-size:12px; margin-bottom:4px; display:block">Hasil Evaluasi / Remark *</label>
						<select name="id_remark" id="id_remark" required style="width:100%; font-size:12.5px; padding:6px 8px; border-radius:6px">
							<option value="">-- Pilih Keputusan / Remark --</option>
							<?php if ( ! empty($remarks)): ?>
								<?php foreach ($remarks as $rmk): ?>
									<option value="<?= (int) $rmk['id_remark'] ?>">
										<?= html_escape($rmk['label']) ?> (Efek: <?= html_escape($rmk['efek_status']) ?>)
									</option>
								<?php endforeach; ?>
							<?php else: ?>
								<option value="">Lulus / Lanjut (Tanpa remark khusus)</option>
							<?php endif; ?>
						</select>
					</div>

					<div style="flex:2">
						<label for="catatan_advance" style="font-size:12px; margin-bottom:4px; display:block">Catatan / Alasan Evaluasi (Opsional)</label>
						<input type="text" name="catatan" id="catatan_advance" placeholder="e.g. Lulus wawancara user, lanjut psikotes / nilai tes mencukupi" style="width:100%; font-size:12.5px; padding:6px 8px; border-radius:6px; margin:0">
					</div>

					<div style="display:flex; gap:8px">
						<button type="submit" class="btn btn-sm btn-primary" style="white-space:nowrap; padding:7px 14px" onclick="return confirm('Proses transisi tahap untuk kandidat ini?')">
							Eksekusi Transisi &rarr;
						</button>
					</div>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>

	<!-- Panel Pembatalan Hired (Jika Berstatus Hired) -->
	<?php if ($can_kelola && $c['status_global'] === 'Hired'): ?>
		<div class="card" style="padding:16px 20px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-left:4px solid var(--crit)">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px">
				<div>
					<div class="eyebrow" style="color:var(--crit); font-weight:700">Pembatalan Status Hired</div>
					<div style="font-size:13px; color:var(--text-muted); margin-top:2px">
						Kandidat saat ini tercatat diterima (Hired). Jika kandidat mengundurkan diri atau menolak sebelum/saat tanggal join, Anda dapat membatalkan status ini untuk mengoreksi kuota pemenuhan MPR.
					</div>
				</div>
			</div>

			<?= form_open(site_url('pipeline/cancel_hired/' . (int) $c['id_req']), array('style' => 'margin:0')) ?>
				<input type="hidden" name="id_lamaran" value="<?= (int) $c['id_lamaran'] ?>">
				<input type="hidden" name="redirect_to" value="candidates/detail/<?= (int) $c['id_lamaran'] ?>">

				<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end">
					<div>
						<label for="status_tujuan" style="font-size:12px; margin-bottom:4px; display:block">Ubah Status Akhir Ke *</label>
						<select name="status_tujuan" id="status_tujuan" required style="width:100%; font-size:12.5px; padding:6px 8px; border-radius:6px">
							<option value="Withdrawn">Withdrawn (Mengundurkan Diri)</option>
							<option value="Offer_Declined">Offer_Declined (Menolak Penawaran)</option>
							<option value="Rejected">Rejected (Dibatalkan Perusahaan)</option>
						</select>
					</div>

					<div style="flex:2">
						<label for="alasan_batal" style="font-size:12px; margin-bottom:4px; display:block">Alasan Pembatalan *</label>
						<input type="text" name="alasan" id="alasan_batal" required placeholder="e.g. Mendapat tawaran di tempat lain sebelum tanggal join" style="width:100%; font-size:12.5px; padding:6px 8px; border-radius:6px; margin:0">
					</div>

					<div style="display:flex; align-items:center; gap:8px">
						<label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; margin:0; cursor:pointer; white-space:nowrap">
							<input type="checkbox" name="buka_posting" value="1" checked style="margin:0">
							<span>Buka kembali posting lowongan</span>
						</label>
					</div>

					<div>
						<button type="submit" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit); white-space:nowrap; padding:7px 14px" onclick="return confirm('Apakah Anda yakin ingin membatalkan status Hired kandidat ini? Kuota terpenuhi lowongan akan dikurangi kembali.')">
							Batalkan Hired
						</button>
					</div>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>

	<!-- Layout 2 Kolom -->
	<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:18px; margin-bottom:20px">

		<!-- Kolom 1: Biodata & Data Diri -->
		<div class="card" style="padding:18px 20px">
			<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:10px">
				<h2 style="margin:0; font-size:15px">👤 Biodata &amp; Kontak Pelamar</h2>
				<span class="tag info" style="font-size:10.5px">Data Pribadi</span>
			</div>
			<table style="font-size:13px; margin:0">
				<tr><td class="muted" style="width:150px">Nama Lengkap</td><td><strong><?= html_escape($c['nama_lengkap']) ?></strong> <?= !empty($c['nama_panggilan']) ? '(' . html_escape($c['nama_panggilan']) . ')' : '' ?></td></tr>
				<tr><td class="muted">Nomor WhatsApp</td><td><span class="mono" style="font-weight:600"><?= html_escape($c['no_wa_normal'] ?: '-') ?></span></td></tr>
				<tr><td class="muted">Alamat Email</td><td><?= html_escape($c['email'] ?: '-') ?></td></tr>
				<tr><td class="muted">Tempat, Tgl Lahir</td><td><?= html_escape($c['tempat_lahir'] ?: '-') ?>, <?= html_escape($c['tanggal_lahir'] ? substr($c['tanggal_lahir'], 0, 10) : '-') ?></td></tr>
				<tr><td class="muted">Jenis Kelamin</td><td><?= $c['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($c['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></td></tr>
				<tr><td class="muted">Status Pernikahan</td><td><?= html_escape($c['status_pernikahan'] ?: '-') ?></td></tr>
				<tr><td class="muted">Pendidikan Terakhir</td><td><?= html_escape($c['pendidikan_terakhir'] ?: '-') ?> <?= $c['jurusan'] ? ' - ' . html_escape($c['jurusan']) : '' ?></td></tr>
				<tr><td class="muted">Institusi Pendidikan</td><td><?= html_escape($c['nama_sekolah'] ?: '-') ?></td></tr>
				<tr><td class="muted">Kota Domisili</td><td><?= html_escape($c['kota_domisili'] ?: '-') ?></td></tr>
				<tr><td class="muted">Alamat Lengkap</td><td><?= nl2br(html_escape($c['alamat_lengkap'] ?: '-')) ?></td></tr>
				<tr><td class="muted">Kontak Darurat</td><td><?= html_escape($c['kontak_darurat_nama'] ?: '-') ?> (<?= html_escape($c['kontak_darurat_hub'] ?: '-') ?>) &middot; <span class="mono"><?= html_escape($c['kontak_darurat_telp'] ?: '-') ?></span></td></tr>

				<!-- Data Identitas Lanjutan dari Onboarding -->
				<?php if (!empty($c['nik']) || !empty($c['npwp']) || !empty($c['no_sim'])): ?>
					<tr><td colspan="2" style="padding-top:10px; border-bottom:1px solid var(--border)"><strong style="font-size:12px; color:var(--accent)">Identitas Resmi (Onboarding)</strong></td></tr>
					<tr><td class="muted">NIK (KTP)</td><td><span class="mono" style="font-weight:600"><?= html_escape($c['nik'] ?: '-') ?></span></td></tr>
					<tr><td class="muted">No. NPWP</td><td><span class="mono"><?= html_escape($c['npwp'] ?: '-') ?></span></td></tr>
					<tr><td class="muted">No. SIM</td><td><?= html_escape($c['no_sim'] ?: '-') ?></td></tr>
					<tr><td class="muted">Agama / Gol. Darah</td><td><?= html_escape($c['agama'] ?: '-') ?> &middot; <?= html_escape($c['gol_darah'] ?: '-') ?></td></tr>
					<tr><td class="muted">Tinggi / Berat Badan</td><td><?= $c['tinggi_badan'] ? (int)$c['tinggi_badan'] . ' cm' : '-' ?> / <?= $c['berat_badan'] ? (int)$c['berat_badan'] . ' kg' : '-' ?></td></tr>
				<?php endif; ?>

				<tr><td class="muted">Metode Pendaftaran</td><td><span class="tag on"><?= html_escape($c['intake_method'] ?: 'FORM_PUBLIC') ?></span></td></tr>
				<tr><td class="muted">Tanggal Registrasi</td><td><?= html_escape($c['tanggal_lamar'] ? substr($c['tanggal_lamar'], 0, 10) : '-') ?></td></tr>
				<?php if ($c['retensi_sampai']): ?>
					<tr><td class="muted">Masa Retensi s/d</td><td><span class="mono" style="color:var(--warn); font-weight:600"><?= html_escape(substr($c['retensi_sampai'], 0, 10)) ?></span></td></tr>
				<?php endif; ?>
			</table>
		</div>

		<!-- Kolom 2: Pekerjaan & Data Sensitif (Gaji, Bank, Kesehatan) -->
		<div style="display:flex; flex-direction:column; gap:18px">

			<!-- Riwayat Kerja Terakhir & Ekspektasi Gaji -->
			<div class="card" style="padding:18px 20px">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:10px">
					<h2 style="margin:0; font-size:15px">💼 Riwayat Pekerjaan Terakhir &amp; Gaji</h2>
					<span class="eyebrow" style="font-size:10.5px">Kompensasi</span>
				</div>
				<?php if ($profile): ?>
					<table style="font-size:13px; margin:0">
						<tr><td class="muted" style="width:150px">Perusahaan Terakhir</td><td><?= html_escape($profile['perusahaan_terakhir'] ?: '-') ?></td></tr>
						<tr><td class="muted">Jabatan Terakhir</td><td><?= html_escape($profile['jabatan_terakhir'] ?: '-') ?></td></tr>
						<tr><td class="muted">Masa Kerja</td><td><?= html_escape($profile['periode_kerja'] ?: '-') ?></td></tr>
						<tr>
							<td class="muted">Gaji Terakhir</td>
							<td>
								<?php if ($can_gaji_pelamar): ?>
									<strong style="color:var(--text)"><?= $profile['gaji_terakhir'] !== NULL ? 'Rp ' . number_format((float)$profile['gaji_terakhir'], 0, ',', '.') : '-' ?></strong>
								<?php else: ?>
									<span class="muted" title="Memerlukan permission LIHAT_GAJI_PELAMAR">🔒 [Data Gaji Terproteksi]</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td class="muted">Gaji Diharapkan</td>
							<td>
								<?php if ($can_gaji_pelamar): ?>
									<strong style="color:var(--accent)"><?= $profile['gaji_diharapkan'] !== NULL ? 'Rp ' . number_format((float)$profile['gaji_diharapkan'], 0, ',', '.') : '-' ?></strong>
								<?php else: ?>
									<span class="muted" title="Memerlukan permission LIHAT_GAJI_PELAMAR">🔒 [Data Gaji Terproteksi]</span>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				<?php else: ?>
					<p class="muted" style="margin:0; font-size:13px">Belum ada riwayat profil pekerjaan.</p>
				<?php endif; ?>
			</div>

			<!-- Riwayat Kesehatan (UU PDP - Perlindungan Khusus) -->
			<div class="card" style="padding:18px 20px">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:10px">
					<h2 style="margin:0; font-size:15px">🩺 Data Kesehatan (Spesifik UU PDP)</h2>
					<span class="tag off" style="font-size:10.5px">Audit Otomatis</span>
				</div>
				<?php if ($can_kesehatan): ?>
					<?php if ($health): ?>
						<table style="font-size:13px; margin:0">
							<tr>
								<td class="muted" style="width:150px">Consent Khusus PDP</td>
								<td>
									<?= $health['consent_khusus'] ? '<span class="tag on">Disetujui</span>' : '<span class="tag off">Belum / Tidak</span>' ?>
									<?php if ($health['consent_pada']): ?>
										<span class="muted" style="font-size:11.5px">&middot; <?= html_escape(substr($health['consent_pada'], 0, 16)) ?></span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td class="muted">Riwayat Penyakit</td>
								<td>
									<?php if (!empty($health['riwayat_penyakit'])): ?>
										<div style="background:var(--surface-2); padding:8px 10px; border-radius:6px; font-size:13px; color:var(--text)">
											<?= nl2br(html_escape($health['riwayat_penyakit'])) ?>
										</div>
									<?php else: ?>
										<span class="muted">Tidak ada riwayat penyakit dilaporkan.</span>
									<?php endif; ?>
								</td>
							</tr>
						</table>
					<?php else: ?>
						<p class="muted" style="margin:0; font-size:13px">Belum ada data kesehatan.</p>
					<?php endif; ?>
				<?php else: ?>
					<div style="background:var(--surface-2); border:1px dashed var(--border-strong); color:var(--text-muted); padding:10px 12px; border-radius:6px; font-size:12.5px">
						🔒 <strong>Data Kesehatan Terproteksi UU PDP 27/2022</strong> &mdash; Khusus HR Supervisor (permission <code>LIHAT_KESEHATAN</code>). Setiap akses tercatat permanen di <span class="mono">ACCESS_LOG_SENSITIF</span>.
					</div>
				<?php endif; ?>
			</div>

			<!-- Data Rekening Bank -->
			<div class="card" style="padding:18px 20px">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:10px">
					<h2 style="margin:0; font-size:15px">🏦 Rekening Bank (Payroll)</h2>
					<span class="tag off" style="font-size:10.5px">HR Spv Only</span>
				</div>
				<?php if ($can_finansial): ?>
					<?php if ($bank): ?>
						<table style="font-size:13px; margin:0">
							<tr><td class="muted" style="width:150px">Nama Bank</td><td><strong><?= html_escape($bank['nama_bank'] ?: '-') ?></strong></td></tr>
							<tr><td class="muted">Nomor Rekening</td><td><span class="mono" style="font-weight:700; color:var(--text)"><?= html_escape($bank['no_rekening'] ?: '-') ?></span></td></tr>
							<tr><td class="muted">Pemilik Rekening</td><td><?= html_escape($bank['nama_pemilik'] ?: '-') ?></td></tr>
						</table>
					<?php else: ?>
						<p class="muted" style="margin:0; font-size:13px">Belum ada data rekening bank yang dimasukkan.</p>
					<?php endif; ?>
				<?php else: ?>
					<div style="background:var(--surface-2); border:1px dashed var(--border-strong); color:var(--text-muted); padding:10px 12px; border-radius:6px; font-size:12.5px">
						🔒 <strong>Data Finansial Terproteksi</strong> &mdash; Memerlukan hak akses <code>LIHAT_FINANSIAL</code> untuk melihat rincian nomor rekening.
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>

	<!-- ================= TABEL RIWAYAT PEKERJAAN MULTI-ITEM (ONBOARDING) ================= -->
	<?php if (!empty($experiences)): ?>
		<div class="card" style="margin-bottom:20px; padding:18px 20px">
			<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:12px">
				<h2 style="margin:0; font-size:15px">🏢 Riwayat Pengalaman Kerja Lengkap</h2>
				<span class="tag on" style="font-size:10.5px"><?= count($experiences) ?> Pengalaman</span>
			</div>
			<div style="overflow-x:auto">
				<table style="margin:0; font-size:13px">
					<thead>
						<tr>
							<th style="width:40px">#</th>
							<th>Perusahaan</th>
							<th>Jabatan</th>
							<th>Periode</th>
							<th>Gaji Terakhir</th>
							<th>Alasan Berhenti</th>
							<th>Deskripsi Pekerjaan</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($experiences as $e): ?>
							<tr>
								<td class="mono"><?= (int) $e['urutan'] ?></td>
								<td><strong><?= html_escape($e['nama_perusahaan']) ?></strong></td>
								<td><?= html_escape($e['posisi_jabatan']) ?></td>
								<td class="faint"><?= html_escape($e['periode_kerja'] ?: '-') ?></td>
								<td>
									<?php if ($can_gaji_pelamar): ?>
										<?= $e['gaji_terakhir'] !== NULL ? 'Rp ' . number_format((float)$e['gaji_terakhir'], 0, ',', '.') : '-' ?>
									<?php else: ?>
										<span class="muted">🔒 [Terproteksi]</span>
									<?php endif; ?>
								</td>
								<td><?= html_escape($e['alasan_keluar'] ?: '-') ?></td>
								<td><?= nl2br(html_escape($e['deskripsi_tugas'] ?: '-')) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>

	<!-- ================= TABEL SUSUNAN KELUARGA MULTI-ITEM (ONBOARDING) ================= -->
	<?php if (!empty($families)): ?>
		<div class="card" style="margin-bottom:20px; padding:18px 20px">
			<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:12px">
				<h2 style="margin:0; font-size:15px">👨‍👩‍👧‍👦 Susunan Anggota Keluarga</h2>
				<span class="tag on" style="font-size:10.5px"><?= count($families) ?> Anggota</span>
			</div>
			<div style="overflow-x:auto">
				<table style="margin:0; font-size:13px">
					<thead>
						<tr>
							<th style="width:40px">#</th>
							<th>Hubungan</th>
							<th>Nama Lengkap</th>
							<th>L/P</th>
							<th>Usia</th>
							<th>Pendidikan</th>
							<th>Pekerjaan</th>
							<th>No. Telepon</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($families as $fam): ?>
							<tr>
								<td class="mono"><?= (int) $fam['urutan'] ?></td>
								<td><strong><?= html_escape($fam['hubungan']) ?></strong></td>
								<td><?= html_escape($fam['nama_lengkap']) ?></td>
								<td><?= html_escape($fam['jenis_kelamin'] ?: '-') ?></td>
								<td><?= $fam['usia'] ? (int)$fam['usia'] . ' th' : '-' ?></td>
								<td><?= html_escape($fam['pendidikan'] ?: '-') ?></td>
								<td><?= html_escape($fam['pekerjaan'] ?: '-') ?></td>
								<td><span class="mono"><?= html_escape($fam['no_telp'] ?: '-') ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>

	<!-- Berkas Dokumen Kandidat -->
	<div class="card" style="margin-bottom:20px; padding:18px 20px">
		<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:12px">
			<h2 style="margin:0; font-size:15px">📁 Berkas Fisik &amp; Dokumen Terlampir</h2>
			<span class="muted" style="font-size:12px">Penyimpanan Terisolasi Di Luar Webroot</span>
		</div>
		<?php if (!empty($documents)): ?>
			<div style="overflow-x:auto">
				<table style="margin:0">
					<thead>
						<tr>
							<th>Dokumen</th>
							<th>Kategori</th>
							<th>Sensitif</th>
							<th>Nama File Asli</th>
							<th>Status Verifikasi</th>
							<th>Verifikator</th>
							<th style="text-align:right">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($documents as $doc): ?>
							<tr>
								<td><strong><?= html_escape($doc['nama_dokumen']) ?></strong></td>
								<td><?= html_escape($doc['kategori'] ?: '-') ?></td>
								<td>
									<?php if ($doc['tingkat_sensitif'] === 'UMUM'): ?>
										<span class="tag">Umum</span>
									<?php elseif ($doc['tingkat_sensitif'] === 'IDENTITAS'): ?>
										<span class="tag on">Identitas</span>
									<?php else: ?>
										<span class="tag err"><?= html_escape($doc['tingkat_sensitif']) ?></span>
									<?php endif; ?>
								</td>
								<td><?= html_escape($doc['nama_file_asli'] ?: '-') ?> <span class="faint">(<?= round($doc['ukuran_byte'] / 1024) ?> KB)</span></td>
								<td><span class="tag <?= $doc['status_verifikasi'] === 'Done' ? 'on' : ($doc['status_verifikasi'] === 'Ditolak' ? 'off' : 'warn') ?>"><?= html_escape($doc['status_verifikasi']) ?></span></td>
								<td><?= html_escape($doc['diverifikasi_oleh_nama'] ?: '-') ?></td>
								<td style="text-align:right">
									<a class="btn btn-sm btn-ghost" target="_blank" href="<?= site_url('documents/open/' . (int) $doc['id_cand_doc']) ?>">Buka Berkas &rarr;</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else: ?>
			<p class="muted" style="margin:0; font-size:13px">Belum ada berkas dokumen yang diunggah kandidat.</p>
		<?php endif; ?>
	</div>

	<!-- History & Audit Trail -->
	<div class="card" style="padding:18px 20px">
		<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:12px">
			<h2 style="margin:0; font-size:15px">⏱️ Audit Trail Aktivitas Lamaran</h2>
			<span class="faint" style="font-size:12px">Immutable Log (APPLICATION_HISTORY)</span>
		</div>
		<?php if (!empty($history)): ?>
			<div style="overflow-x:auto">
				<table style="margin:0">
					<thead>
						<tr>
							<th>Waktu</th>
							<th>Event</th>
							<th>Perubahan Status</th>
							<th>Deskripsi</th>
							<th>Oleh</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($history as $h): ?>
						<tr>
							<td class="mono faint" style="white-space:nowrap"><?= html_escape(substr($h['waktu'], 0, 16)) ?></td>
							<td><strong><?= html_escape($h['jenis_event']) ?></strong></td>
							<td>
								<?php if ($h['tahap_asal'] || $h['tahap_tujuan']): ?>
									<?= html_escape($h['tahap_asal'] ?: '-') ?> &rarr; <?= html_escape($h['tahap_tujuan'] ?: '-') ?>
								<?php endif; ?>
								<?php if ($h['status_ke']): ?><span class="tag info" style="font-size:10px"><?= html_escape($h['status_ke']) ?></span><?php endif; ?>
							</td>
							<td><?= html_escape($h['deskripsi'] ?: '-') ?></td>
							<td><strong><?= html_escape($h['oleh_nama'] ?: 'Sistem') ?></strong></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else: ?>
			<p class="muted" style="margin:0; font-size:13px">Belum ada riwayat aktivitas.</p>
		<?php endif; ?>
	</div>
</div>

<script>
function copyOnboardingLink() {
	var input = document.getElementById('onboarding_link_input');
	if (!input) return;
	input.select();
	input.setSelectionRange(0, 99999);
	navigator.clipboard.writeText(input.value).then(function() {
		alert('Tautan formulir onboarding berhasil disalin ke clipboard!');
	}).catch(function() {
		document.execCommand('copy');
		alert('Tautan formulir onboarding disalin!');
	});
}
</script>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main>
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div style="display:flex; align-items:center; gap:10px">
				<h1 style="margin:0"><?= html_escape($c['nama_lengkap']) ?></h1>
				<span class="tag <?= in_array($c['status_global'], array('Hired','Approved','Sourcing')) ? 'on' : ($c['status_global'] === 'Rejected' ? 'err' : '') ?>">
					<?= html_escape($c['status_global']) ?>
				</span>
			</div>
			<p class="muted" style="margin:4px 0 0">
				Melamar posisi <strong><?= html_escape($c['nama_posisi']) ?></strong> &middot; 
				<?= html_escape($c['nama_departemen'] ?: '-') ?> <?= $c['nama_outlet'] ? '(' . html_escape($c['nama_outlet']) . ')' : '' ?> &middot; 
				MPR: <?= html_escape($c['no_mpr'] ?: '#' . $c['id_req']) ?> &middot;
				Tahap kini: <strong><?= html_escape($c['nama_tahap_kini'] ?: '-') ?></strong>
			</p>
		</div>
		<div style="display:flex; gap:8px">
			<a class="btn-sm btn-ghost" href="<?= site_url('pipeline/index/' . (int) $c['id_req']) ?>">&larr; Kembali ke Pipeline</a>
			<a class="btn-sm btn-ghost" href="<?= site_url('documents/checklist/' . (int) $c['id_lamaran']) ?>">Checklist Berkas</a>
		</div>
	</div>

	<!-- Layout 2 Kolom -->
	<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:18px; margin-bottom:18px">

		<!-- Kolom 1: Biodata & Data Diri -->
		<div class="card" style="margin:0">
			<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--border); padding-bottom:8px">👤 Data Diri & Kontak</h2>
			<table style="font-size:13px; margin:0">
				<tr><td class="muted" style="width:140px">Nomor WhatsApp</td><td><span class="mono"><?= html_escape($c['no_wa_normal'] ?: '-') ?></span></td></tr>
				<tr><td class="muted">Email</td><td><?= html_escape($c['email'] ?: '-') ?></td></tr>
				<tr><td class="muted">Tempat, Tgl Lahir</td><td><?= html_escape($c['tempat_lahir'] ?: '-') ?>, <?= html_escape($c['tanggal_lahir'] ? substr($c['tanggal_lahir'], 0, 10) : '-') ?></td></tr>
				<tr><td class="muted">Jenis Kelamin</td><td><?= $c['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($c['jenis_kelamin'] === 'P' ? 'Perempuan' : '-') ?></td></tr>
				<tr><td class="muted">Status Pernikahan</td><td><?= html_escape($c['status_pernikahan'] ?: '-') ?></td></tr>
				<tr><td class="muted">Pendidikan Terakhir</td><td><?= html_escape($c['pendidikan_terakhir'] ?: '-') ?> <?= $c['jurusan'] ? ' - ' . html_escape($c['jurusan']) : '' ?></td></tr>
				<tr><td class="muted">Sekolah / Kampus</td><td><?= html_escape($c['nama_sekolah'] ?: '-') ?></td></tr>
				<tr><td class="muted">Kota Domisili</td><td><?= html_escape($c['kota_domisili'] ?: '-') ?></td></tr>
				<tr><td class="muted">Alamat Lengkap</td><td><?= nl2br(html_escape($c['alamat_lengkap'] ?: '-')) ?></td></tr>
				<tr><td class="muted">Kontak Darurat</td><td><?= html_escape($c['kontak_darurat_nama'] ?: '-') ?> (<?= html_escape($c['kontak_darurat_hub'] ?: '-') ?>) &middot; <span class="mono"><?= html_escape($c['kontak_darurat_telp'] ?: '-') ?></span></td></tr>
				<tr><td class="muted">Metode Intake</td><td><?= html_escape($c['intake_method'] ?: '-') ?> &middot; <?= html_escape($c['nama_channel'] ?: 'Langsung') ?></td></tr>
				<tr><td class="muted">Tanggal Melamar</td><td><?= html_escape($c['tanggal_lamar'] ? substr($c['tanggal_lamar'], 0, 10) : '-') ?></td></tr>
				<?php if ($c['retensi_sampai']): ?>
					<tr><td class="muted">Retensi Data s/d</td><td><?= html_escape(substr($c['retensi_sampai'], 0, 10)) ?></td></tr>
				<?php endif; ?>
			</table>
		</div>

		<!-- Kolom 2: Pekerjaan & Data Sensitif (Gaji, Bank, Kesehatan) -->
		<div style="display:flex; flex-direction:column; gap:18px">
			
			<!-- Riwayat Kerja & Ekspektasi Gaji -->
			<div class="card" style="margin:0">
				<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--border); padding-bottom:8px">💼 Pengalaman Kerja & Gaji</h2>
				<?php if ($profile): ?>
					<table style="font-size:13px; margin:0">
						<tr><td class="muted" style="width:140px">Perusahaan Terakhir</td><td><?= html_escape($profile['perusahaan_terakhir'] ?: '-') ?></td></tr>
						<tr><td class="muted">Jabatan Terakhir</td><td><?= html_escape($profile['jabatan_terakhir'] ?: '-') ?></td></tr>
						<tr><td class="muted">Periode Kerja</td><td><?= html_escape($profile['periode_kerja'] ?: '-') ?></td></tr>
						<tr>
							<td class="muted">Gaji Terakhir</td>
							<td>
								<?php if ($can_gaji_pelamar): ?>
									<strong style="color:var(--text)"><?= $profile['gaji_terakhir'] !== NULL ? 'Rp ' . number_format((float)$profile['gaji_terakhir'], 0, ',', '.') : '-' ?></strong>
								<?php else: ?>
									<span class="muted" title="Memerlukan hak akses LIHAT_GAJI_PELAMAR">🔒 [Data Gaji Terproteksi]</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td class="muted">Gaji Diharapkan</td>
							<td>
								<?php if ($can_gaji_pelamar): ?>
									<strong style="color:var(--accent)"><?= $profile['gaji_diharapkan'] !== NULL ? 'Rp ' . number_format((float)$profile['gaji_diharapkan'], 0, ',', '.') : '-' ?></strong>
								<?php else: ?>
									<span class="muted" title="Memerlukan hak akses LIHAT_GAJI_PELAMAR">🔒 [Data Gaji Terproteksi]</span>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				<?php else: ?>
					<p class="muted" style="margin:0; font-size:13px">Belum ada riwayat profil pekerjaan yang diisi.</p>
				<?php endif; ?>
			</div>

			<!-- Riwayat Kesehatan (UU PDP - Perlindungan Khusus) -->
			<div class="card" style="margin:0">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:8px">
					<h2 style="margin:0; font-size:16px">🩺 Riwayat Kesehatan (Data Spesifik UU PDP)</h2>
					<span class="tag off" style="font-size:11px">Khusus HR Spv</span>
				</div>
				<?php if ($can_kesehatan): ?>
					<?php if ($health): ?>
						<table style="font-size:13px; margin:0">
							<tr>
								<td class="muted" style="width:140px">Consent Khusus</td>
								<td>
									<?= $health['consent_khusus'] ? '<span class="tag on">Disetujui</span>' : '<span class="tag off">Belum / Tidak</span>' ?>
									<?php if ($health['consent_pada']): ?>
										<span class="muted" style="font-size:12px">(<?= html_escape(substr($health['consent_pada'], 0, 16)) ?>)</span>
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
					<div style="background:var(--surface-2); border:1px dashed var(--border); color:var(--text-muted); padding:10px 12px; border-radius:6px; font-size:12.5px">
						🔒 <strong>Data Kesehatan Terproteksi</strong> — Berdasarkan UU PDP 27/2022 Pasal 4 ayat 2, data kesehatan hanya dapat diakses oleh HR Supervisor (izin <code>LIHAT_KESEHATAN</code>). Setiap akses tercatat di log audit.
					</div>
				<?php endif; ?>
			</div>

			<!-- Data Rekening Bank -->
			<div class="card" style="margin:0">
				<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--border); padding-bottom:8px">🏦 Data Rekening Bank</h2>
				<?php if ($can_finansial): ?>
					<?php if ($bank): ?>
						<table style="font-size:13px; margin:0">
							<tr><td class="muted" style="width:140px">Bank</td><td><?= html_escape($bank['nama_bank'] ?: '-') ?></td></tr>
							<tr><td class="muted">Nomor Rekening</td><td><span class="mono" style="font-weight:600"><?= html_escape($bank['no_rekening'] ?: '-') ?></span></td></tr>
							<tr><td class="muted">Atas Nama</td><td><?= html_escape($bank['nama_pemilik'] ?: '-') ?></td></tr>
						</table>
					<?php else: ?>
						<p class="muted" style="margin:0; font-size:13px">Belum ada data rekening bank yang dimasukkan.</p>
					<?php endif; ?>
				<?php else: ?>
					<div style="background:var(--surface-2); border:1px dashed var(--border); color:var(--text-muted); padding:10px 12px; border-radius:6px; font-size:12.5px">
						🔒 <strong>Data Finansial Terproteksi</strong> — Memerlukan izin <code>LIHAT_FINANSIAL</code> untuk melihat nomor rekening kandidat.
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>

	<!-- Berkas Dokumen Kandidat -->
	<div class="card">
		<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--border); padding-bottom:8px">📁 Berkas & Dokumen Kandidat</h2>
		<?php if (!empty($documents)): ?>
			<table style="font-size:13px">
				<tr>
					<th>Dokumen</th>
					<th>Kategori</th>
					<th>Sensitif</th>
					<th>File Asli</th>
					<th>Status</th>
					<th>Diverifikasi Oleh</th>
					<th>Aksi</th>
				</tr>
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
					<td><?= html_escape($doc['nama_file_asli'] ?: '-') ?> <span class="muted">(<?= round($doc['ukuran_byte'] / 1024) ?> KB)</span></td>
					<td><span class="tag <?= $doc['status_verifikasi'] === 'Done' ? 'on' : ($doc['status_verifikasi'] === 'Ditolak' ? 'err' : 'off') ?>"><?= html_escape($doc['status_verifikasi']) ?></span></td>
					<td><?= html_escape($doc['diverifikasi_oleh_nama'] ?: '-') ?></td>
					<td>
						<a class="btn-sm" target="_blank" href="<?= site_url('documents/open/' . (int) $doc['id_cand_doc']) ?>">Lihat File</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		<?php else: ?>
			<p class="muted" style="margin:0; font-size:13px">Belum ada berkas dokumen yang diunggah kandidat.</p>
		<?php endif; ?>
	</div>

	<!-- Riwayat Seleksi & Hasil Test/Interview/Offer -->
	<div class="card">
		<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--border); padding-bottom:8px">🎯 Perjalanan Seleksi & Hasil</h2>
		
		<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:14px; margin-bottom:16px">
			
			<!-- Interview -->
			<div style="background:var(--surface-2); border-radius:8px; padding:12px">
				<h3 style="margin-top:0; font-size:14px">🎤 Wawancara / Interview</h3>
				<?php if (!empty($interviews)): ?>
					<?php foreach ($interviews as $iv): ?>
						<div style="border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:8px">
							<div style="display:flex; justify-content:space-between">
								<strong><?= html_escape($iv['tipe'] ?: 'Interview') ?> (<?= html_escape($iv['nama_tahap']) ?>)</strong>
								<?php if ($iv['hasil']): ?><span class="tag <?= strtolower($iv['hasil']) ?>"><?= html_escape($iv['hasil']) ?></span><?php endif; ?>
							</div>
							<div class="muted" style="font-size:12px">Jadwal: <?= html_escape($iv['jadwal'] ?: '-') ?></div>
							<?php if ($iv['nama_interviewer']): ?><div class="muted" style="font-size:12px">Pewawancara: <?= html_escape($iv['nama_interviewer']) ?> (<?= html_escape($iv['peran_interviewer']) ?>)</div><?php endif; ?>
							<?php if ($iv['skor'] !== NULL): ?><div style="font-size:12px">Skor: <strong><?= (int)$iv['skor'] ?></strong></div><?php endif; ?>
							<?php if ($iv['catatan']): ?><div style="font-size:12px; margin-top:2px"><?= html_escape($iv['catatan']) ?></div><?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<p class="muted" style="margin:0; font-size:12.5px">Belum ada catatan wawancara.</p>
				<?php endif; ?>
			</div>

			<!-- Psikotes -->
			<div style="background:var(--surface-2); border-radius:8px; padding:12px">
				<h3 style="margin-top:0; font-size:14px">🧠 Hasil Psikotes / Ujian</h3>
				<?php if (!empty($psikotes)): ?>
					<?php foreach ($psikotes as $psi): ?>
						<div style="border-bottom:1px solid var(--border); padding-bottom:8px; margin-bottom:8px">
							<div style="display:flex; justify-content:space-between">
								<strong><?= html_escape($psi['vendor_tes'] ?: 'Psikotes') ?> (<?= html_escape($psi['nama_tahap']) ?>)</strong>
								<?php if ($psi['hasil']): ?><span class="tag <?= strtolower($psi['hasil']) ?>"><?= html_escape($psi['hasil']) ?></span><?php endif; ?>
							</div>
							<div class="muted" style="font-size:12px">Tgl: <?= html_escape($psi['tanggal_tes'] ? substr($psi['tanggal_tes'], 0, 10) : '-') ?></div>
							<?php if ($psi['skor_total'] !== NULL): ?><div style="font-size:12px">Skor Total: <strong><?= (int)$psi['skor_total'] ?></strong></div><?php endif; ?>
							<?php if ($psi['rekomendasi']): ?><div style="font-size:12px; margin-top:2px">Rekomendasi: <?= html_escape($psi['rekomendasi']) ?></div><?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<p class="muted" style="margin:0; font-size:12.5px">Belum ada hasil psikotes.</p>
				<?php endif; ?>
			</div>

			<!-- Offering -->
			<div style="background:var(--surface-2); border-radius:8px; padding:12px">
				<h3 style="margin-top:0; font-size:14px">📝 Penawaran Kerja (Offering)</h3>
				<?php if ($offer): ?>
					<div style="display:flex; justify-content:space-between; margin-bottom:4px">
						<span>Status:</span>
						<span class="tag <?= $offer['status_offer'] === 'Diterima' ? 'on' : ($offer['status_offer'] === 'Ditolak' ? 'err' : 'off') ?>"><?= html_escape($offer['status_offer']) ?></span>
					</div>
					<div style="font-size:12.5px; margin-bottom:4px">
						Gaji Ditawarkan: 
						<?php if ($can_gaji): ?>
							<strong><?= $offer['gaji_ditawarkan'] !== NULL ? 'Rp ' . number_format((float)$offer['gaji_ditawarkan'], 0, ',', '.') : '-' ?></strong>
						<?php else: ?>
							<span class="muted">🔒 [Data Terproteksi]</span>
						<?php endif; ?>
					</div>
					<?php if ($offer['tanggal_penawaran']): ?><div class="muted" style="font-size:12px">Tgl Penawaran: <?= html_escape($offer['tanggal_penawaran']) ?></div><?php endif; ?>
					<?php if ($offer['tanggal_join_disepakati']): ?><div class="muted" style="font-size:12px">Rencana Join: <?= html_escape($offer['tanggal_join_disepakati']) ?></div><?php endif; ?>
					<?php if ($offer['tanggal_join_aktual']): ?><div style="font-size:12px; color:var(--accent)">Join Aktual: <?= html_escape($offer['tanggal_join_aktual']) ?></div><?php endif; ?>
					<?php if ($offer['alasan']): ?><div style="font-size:12px; margin-top:4px">Catatan: <?= html_escape($offer['alasan']) ?></div><?php endif; ?>
				<?php else: ?>
					<p class="muted" style="margin:0; font-size:12.5px">Belum ada penawaran kerja dibuat.</p>
				<?php endif; ?>
			</div>

		</div>

		<!-- Timeline Tahap Lengkap -->
		<h3 style="font-size:14px; margin-bottom:6px">Tahapan Flow Lamaran</h3>
		<table style="font-size:12.5px; margin:0">
			<tr><th>Urut</th><th>Tahap</th><th>Tipe</th><th>Status</th><th>Mulai</th><th>Selesai</th><th>Catatan / Remark</th><th>Diproses Oleh</th></tr>
			<?php foreach ($stages as $st): ?>
			<tr>
				<td><?= (int) $st['urutan'] ?></td>
				<td><strong><?= html_escape($st['nama_tahap']) ?></strong></td>
				<td><span class="tag"><?= html_escape($st['tipe_tahap']) ?></span></td>
				<td><span class="tag <?= $st['status_tahap'] === 'Selesai' ? 'on' : ($st['status_tahap'] === 'Sedang_Jalan' ? 'accent' : 'off') ?>"><?= html_escape($st['status_tahap']) ?></span></td>
				<td><?= html_escape($st['tanggal_mulai'] ? substr($st['tanggal_mulai'], 0, 16) : '-') ?></td>
				<td><?= html_escape($st['tanggal_selesai'] ? substr($st['tanggal_selesai'], 0, 16) : '-') ?></td>
				<td><?= html_escape($st['label_remark'] ?: '-') ?></td>
				<td><?= html_escape($st['diproses_oleh_nama'] ?: '-') ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<!-- History & Audit Trail -->
	<div class="card">
		<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--border); padding-bottom:8px">⏱️ Jejak Aktivitas Lamaran (Audit Trail)</h2>
		<?php if (!empty($history)): ?>
			<table style="font-size:12px; margin:0">
				<tr><th>Waktu</th><th>Event</th><th>Perubahan Status</th><th>Deskripsi</th><th>Oleh</th></tr>
				<?php foreach ($history as $h): ?>
				<tr>
					<td class="muted" style="white-space:nowrap"><?= html_escape(substr($h['waktu'], 0, 16)) ?></td>
					<td><strong><?= html_escape($h['jenis_event']) ?></strong></td>
					<td>
						<?php if ($h['tahap_asal'] || $h['tahap_tujuan']): ?>
							<?= html_escape($h['tahap_asal'] ?: '-') ?> &rarr; <?= html_escape($h['tahap_tujuan'] ?: '-') ?> 
						<?php endif; ?>
						<?php if ($h['status_ke']): ?>[<?= html_escape($h['status_ke']) ?>]<?php endif; ?>
					</td>
					<td><?= html_escape($h['deskripsi'] ?: '-') ?></td>
					<td><?= html_escape($h['oleh_nama'] ?: 'Sistem') ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		<?php else: ?>
			<p class="muted" style="margin:0; font-size:13px">Belum ada riwayat aktivitas.</p>
		<?php endif; ?>
	</div>
</main>

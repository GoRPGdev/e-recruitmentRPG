<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div style="margin-bottom:20px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<a href="<?= site_url('postings/form_settings/' . (int) $posting['id_posting']) ?>" style="font-size:12px; text-decoration:none; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px">
					&larr; Kembali ke Pengaturan Link Form
				</a>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Portal Analytics</span>
			</div>
			<h1 style="margin:0; font-size:22px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				Statistik Portal: <?= html_escape($posting['nama_posisi']) ?>
			</h1>
			<p class="muted" style="margin:4px 0 0; font-size:13px">
				Input data funnel sourcing eksternal (JobStreet, Glints, LinkedIn, dll) untuk kebutuhan audit report.
			</p>
		</div>

		<div style="display:flex; gap:8px; align-items:center">
			<a href="<?= site_url('postings') ?>" class="btn btn-sm btn-ghost" style="padding:7px 14px; font-size:12.5px; text-decoration:none">
				<span>Daftar Form Publik</span>
			</a>
		</div>
	</div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start">
	<!-- Kartu Form Input Statistik -->
	<div class="card" style="padding:20px 24px; margin:0">
		<div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid var(--border)">
			<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Data Agregat Pelamar Eksternal
			</h3>
		</div>

		<div class="muted" style="font-size:12.5px; line-height:1.45; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px 14px; margin-bottom:18px">
			<strong>Catatan Operasional HR:</strong> Metrik di bawah ini digunakan untuk merekam volume agregat pelamar dari portal karir luar yang belum dimasukkan satu per satu ke dalam sistem, guna melengkapi laporan Funnel Rasio Seleksi.
		</div>

		<?= form_open(site_url('postings/stats/' . (int) $posting['id_posting'])) ?>
			<div style="display:flex; flex-direction:column; gap:16px; margin-bottom:18px">
				<div>
					<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
						Jumlah Pelamar Masuk di Portal Karir
					</label>
					<input type="number" name="jumlah_pelamar_masuk" value="<?= (int) ($stats['jumlah_pelamar_masuk'] ?? 0) ?>" min="0" style="font-size:13px; padding:8px 12px" placeholder="Mis. 50">
					<span class="muted" style="display:block; font-size:11px; margin-top:4px">
						Total seluruh pelamar yang melamar di platform sourcing pihak ketiga.
					</span>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
							CV Sesuai Kriteria
						</label>
						<input type="number" name="cv_sesuai" value="<?= (int) ($stats['cv_sesuai'] ?? 0) ?>" min="0" style="font-size:13px; padding:8px 12px" placeholder="Mis. 15">
						<span class="muted" style="display:block; font-size:11px; margin-top:4px">
							Kandidat yang memenuhi kualifikasi dasar.
						</span>
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
							CV Tidak Sesuai Kriteria
						</label>
						<input type="number" name="cv_tidak_sesuai" value="<?= (int) ($stats['cv_tidak_sesuai'] ?? 0) ?>" min="0" style="font-size:13px; padding:8px 12px" placeholder="Mis. 35">
						<span class="muted" style="display:block; font-size:11px; margin-top:4px">
							Kandidat yang gugur saat tahap screening awal.
						</span>
					</div>
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid var(--border)">
				<a href="<?= site_url('postings/form_settings/' . (int) $posting['id_posting']) ?>" class="btn btn-ghost">Batal</a>
				<button type="submit" class="btn btn-primary" style="padding:8px 22px; font-weight:600">
					Simpan Statistik
				</button>
			</div>
		<?= form_close() ?>
	</div>

	<!-- Kartu Informasi & Snapshot Submit -->
	<div style="display:flex; flex-direction:column; gap:14px">
		<div class="card" style="padding:16px 18px; margin:0">
			<div style="font-weight:700; font-size:13.5px; color:var(--text); margin-bottom:10px">
				Metrik Riil Sistem RPG
			</div>
			<div style="display:flex; flex-direction:column; gap:8px; font-size:12.5px">
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">Submit Form Publik</span>
					<span style="font-weight:700; color:var(--accent)"><?= (int) $posting['jumlah_submit'] ?> berkas</span>
				</div>
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">Status Form Lowongan</span>
					<?php if ($posting['form_aktif']): ?>
						<span class="tag on" style="font-size:11px">&#10003; Terbuka</span>
					<?php else: ?>
						<span class="tag off" style="font-size:11px">Ditutup</span>
					<?php endif; ?>
				</div>
				<?php if (!empty($stats['diperbarui_pada'])): ?>
					<div style="display:flex; justify-content:space-between; padding-top:2px">
						<span class="muted">Terakhir Diperbarui</span>
						<span class="muted" style="font-size:11.5px"><?= html_escape(substr($stats['diperbarui_pada'], 0, 16)) ?></span>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="card" style="padding:16px 18px; margin:0">
			<div style="font-weight:700; font-size:13.5px; color:var(--text); margin-bottom:6px">
				Navigasi Terkait
			</div>
			<p class="muted" style="font-size:12px; margin-bottom:12px">
				Lihat alur pelamar yang sudah masuk ke pipeline seleksi.
			</p>
			<?php if (!empty($posting['id_req'])): ?>
				<a href="<?= site_url('pipeline/board/' . (int) $posting['id_req']) ?>" class="btn btn-sm btn-ghost" style="width:100%; text-align:center; text-decoration:none">
					Buka Pipeline Lamaran &rarr;
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

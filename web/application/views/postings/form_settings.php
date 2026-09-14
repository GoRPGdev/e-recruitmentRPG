<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div style="margin-bottom:20px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<a href="<?= site_url('postings') ?>" style="font-size:12px; text-decoration:none; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px">
					&larr; Kembali ke Daftar Form Publik
				</a>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Pengaturan Lowongan</span>
			</div>
			<h1 style="margin:0; font-size:22px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				Kelola Link Form: <?= html_escape($posting['nama_posisi']) ?>
			</h1>
			<p class="muted" style="margin:4px 0 0; font-size:13px">
				Atur ketersediaan penerimaan berkas lamaran publik dan jendela jadwal tayang lowongan.
			</p>
		</div>

		<div style="display:flex; gap:8px; align-items:center">
			<a href="<?= html_escape($public_url) ?>" target="_blank" class="btn btn-sm btn-ghost" style="padding:7px 14px; font-size:12.5px; text-decoration:none">
				<span>Buka Form Publik &nearr;</span>
			</a>
			<a href="<?= site_url('postings/stats/' . (int) $posting['id_posting']) ?>" class="btn btn-sm btn-ghost" style="padding:7px 14px; font-size:12.5px; text-decoration:none">
				<span>Statistik Portal</span>
			</a>
		</div>
	</div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start">
	<!-- Kartu Form Pengaturan -->
	<div class="card" style="padding:20px 24px; margin:0">
		<div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border)">
			<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Konfigurasi Jadwal &amp; Status Lowongan
			</h3>
		</div>

		<?= form_open(site_url('postings/form_settings/' . (int) $posting['id_posting'])) ?>
			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px; margin-bottom:18px">
				<label style="display:flex; align-items:center; gap:10px; margin:0; cursor:pointer; font-size:13.5px; font-weight:600">
					<input type="checkbox" name="form_aktif" value="1" <?= $posting['form_aktif'] ? 'checked' : '' ?> style="width:17px; height:17px; accent-color:var(--accent); cursor:pointer">
					<div>
						<span style="color:var(--text)">Formulir Aktif (Menerima Berkas Lamaran)</span>
						<span class="muted" style="display:block; font-size:12px; font-weight:400; margin-top:2px">
							Jika dinonaktifkan, pelamar yang mengakses tautan akan melihat pemberitahuan bahwa form telah ditutup.
						</span>
					</div>
				</label>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px">
				<div>
					<label for="form_dibuka" style="font-size:12.5px; font-weight:600; margin:0 0 6px">
						Jadwal Buka Mulai (Opsional)
					</label>
					<input type="text" id="form_dibuka" name="form_dibuka" placeholder="YYYY-MM-DD HH:MM"
					       value="<?= html_escape($posting['form_dibuka'] ? substr($posting['form_dibuka'], 0, 16) : '') ?>"
					       style="font-size:13px; padding:8px 12px">
					<span class="muted" style="display:block; font-size:11px; margin-top:4px">
						Kosongkan jika ingin langsung dibuka sekarang.
					</span>
				</div>

				<div>
					<label for="form_ditutup" style="font-size:12.5px; font-weight:600; margin:0 0 6px">
						Jadwal Tutup Sampai (Opsional)
					</label>
					<input type="text" id="form_ditutup" name="form_ditutup" placeholder="YYYY-MM-DD HH:MM"
					       value="<?= html_escape($posting['form_ditutup'] ? substr($posting['form_ditutup'], 0, 16) : '') ?>"
					       style="font-size:13px; padding:8px 12px">
					<span class="muted" style="display:block; font-size:11px; margin-top:4px">
						Kosongkan jika tidak ada batas waktu penutupan otomatis.
					</span>
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid var(--border)">
				<a href="<?= site_url('postings') ?>" class="btn btn-ghost">Batal</a>
				<button type="submit" class="btn btn-primary" style="padding:8px 22px; font-weight:600">
					Simpan Pengaturan
				</button>
			</div>
		<?= form_close() ?>
	</div>

	<!-- Kartu Tautan Publik & Metadata -->
	<div style="display:flex; flex-direction:column; gap:14px">
		<div class="card" style="padding:16px 18px; margin:0">
			<div style="font-weight:700; font-size:13.5px; color:var(--text); margin-bottom:6px">
				Tautan Publik Lamaran
			</div>
			<p class="muted" style="font-size:12px; margin-bottom:12px">
				Tautan langsung ini dapat disebarkan pada portal lowongan (JobStreet, LinkedIn, Glints, Instagram, atau website karir).
			</p>

			<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 12px; margin-bottom:10px">
				<code style="font-size:12px; word-break:break-all; background:none; padding:0; display:block; color:var(--text)">
					<?= html_escape($public_url) ?>
				</code>
			</div>

			<div style="display:flex; gap:8px">
				<button type="button" class="btn btn-sm btn-ghost" style="flex:1" onclick="navigator.clipboard.writeText('<?= $public_url ?>'); this.textContent = 'Tersalin!'; setTimeout(() => this.textContent = 'Salin Tautan', 1800)">
					Salin Tautan
				</button>
				<a href="<?= html_escape($public_url) ?>" target="_blank" class="btn btn-sm btn-ghost" style="text-decoration:none">
					Uji Buka &nearr;
				</a>
			</div>
		</div>

		<div class="card" style="padding:16px 18px; margin:0">
			<div style="font-weight:700; font-size:13.5px; color:var(--text); margin-bottom:10px">
				Informasi Lowongan
			</div>
			<div style="display:flex; flex-direction:column; gap:8px; font-size:12.5px">
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">No. MPR</span>
					<?php if (!empty($posting['id_req']) && !empty($posting['no_mpr'])): ?>
						<a href="<?= site_url('requisitions/view/' . (int) $posting['id_req']) ?>" style="font-weight:600">
							<?= html_escape($posting['no_mpr']) ?>
						</a>
					<?php else: ?>
						<span><?= html_escape($posting['no_mpr'] ?? '-') ?></span>
					<?php endif; ?>
				</div>
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">Departemen</span>
					<span style="font-weight:600"><?= html_escape($posting['departemen'] ?? '-') ?></span>
				</div>
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">Penempatan</span>
					<span style="font-weight:600"><?= html_escape($posting['tipe_penempatan'] ?? '-') ?><?= !empty($posting['nama_outlet']) ? ' (' . html_escape($posting['nama_outlet']) . ')' : '' ?></span>
				</div>
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">Total Submit Form</span>
					<span style="font-weight:700; color:var(--accent)"><?= (int) $posting['jumlah_submit'] ?> kali</span>
				</div>
				<?php
				$is_kadaluarsa = !empty($posting['form_aktif']) && !empty($posting['form_ditutup']) && strtotime($posting['form_ditutup']) < time();
				?>
				<div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:6px">
					<span class="muted">Status Form</span>
					<?php if ($is_kadaluarsa): ?>
						<span class="tag warn" style="font-size:11px; font-weight:700">Kadaluarsa</span>
					<?php elseif ($posting['form_aktif']): ?>
						<span class="tag on" style="font-size:11px">&#10003; Terbuka</span>
					<?php else: ?>
						<span class="tag off" style="font-size:11px">Ditutup</span>
					<?php endif; ?>
				</div>
				<div style="display:flex; justify-content:space-between; align-items:center">
					<span class="muted">Batas Waktu</span>
					<div style="text-align:right">
						<?php if (!empty($posting['form_ditutup'])): ?>
							<span style="font-weight:600; font-size:12px"><?= date('d M Y', strtotime($posting['form_ditutup'])) ?></span>
							<?php if ($posting['form_aktif'] && !$is_kadaluarsa): ?>
								<?php $sisa = max(0, (int) ceil((strtotime($posting['form_ditutup']) - time()) / 86400)); ?>
								<div class="faint" style="font-size:10.5px"><?= $sisa ?> hari lagi</div>
							<?php elseif ($is_kadaluarsa): ?>
								<div style="font-size:10.5px; color:var(--crit); font-weight:600">Lewat batas</div>
							<?php endif; ?>
						<?php else: ?>
							<span class="faint">Tanpa batas</span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Tombol Perpanjang Batas Waktu Lowongan -->
			<div style="margin-top:12px; padding:12px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px">
				<div style="font-size:12px; font-weight:700; margin-bottom:6px">Perpanjang Batas Waktu Lowongan</div>
				<p class="muted" style="font-size:11px; margin:0 0 8px; line-height:1.4">
					Menambah masa aktif lowongan publik untuk kebutuhan penerimaan berkas pelamar.
				</p>
				<?= form_open(site_url('postings/extend/' . (int) $posting['id_posting'])) ?>
					<div style="display:flex; gap:6px">
						<select name="durasi_hari" style="font-size:12px; padding:5px 8px; flex:1; border:1px solid var(--border); border-radius:6px; background:var(--surface)">
							<option value="7">+7 Hari</option>
							<option value="14" selected>+14 Hari</option>
							<option value="30">+30 Hari</option>
						</select>
						<button type="submit" class="btn btn-sm btn-primary" style="font-size:11.5px; padding:5px 12px" onclick="return confirm('Perpanjang lowongan ini?')">
							Perpanjang &rarr;
						</button>
					</div>
				<?= form_close() ?>
			</div>
			</div>
		</div>
	</div>
</div>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div style="margin-bottom:20px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<a href="<?= site_url('candidates/detail/' . (int) ($lamaran['id_kandidat'] ?? 0)) ?>" style="font-size:12px; text-decoration:none; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px">
					&larr; Kembali ke Detail Pelamar
				</a>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Token Berkas Pribadi</span>
			</div>
			<h1 style="margin:0; font-size:22px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				Token Kelengkapan Berkas &mdash; <?= html_escape($lamaran['nama_lengkap'] ?? ('Lamaran #' . $id_lamaran)) ?>
			</h1>
			<p class="muted" style="margin:4px 0 0; font-size:13px">
				Tautan khusus yang aman bagi kandidat untuk melengkapi berkas atau formulir secara mandiri.
			</p>
		</div>

		<div style="display:flex; gap:8px; align-items:center">
			<a href="<?= site_url('postings') ?>" class="btn btn-sm btn-ghost" style="padding:7px 14px; font-size:12.5px; text-decoration:none">
				<span>Daftar Form Publik</span>
			</a>
		</div>
	</div>
</div>

<div style="display:grid; grid-template-columns:360px 1fr; gap:20px; align-items:start">
	<!-- Kartu Buat Token Baru -->
	<div class="card" style="padding:20px; margin:0">
		<div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Buat Token Akses Baru
			</h3>
		</div>

		<?= form_open(site_url('postings/tokens/' . (int) $id_lamaran)) ?>
			<input type="hidden" name="act" value="create">

			<div style="margin-bottom:14px">
				<label for="tujuan" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Tujuan Kelengkapan <span style="color:var(--crit)">*</span>
				</label>
				<select id="tujuan" name="tujuan" required style="font-size:13px; padding:8px 12px; width:100%">
					<option value="UPLOAD_DOKUMEN">Upload Dokumen Persyaratan</option>
					<option value="FORM2">Lengkapi Formulir Lanjutan (Form 2)</option>
				</select>
			</div>

			<div style="margin-bottom:18px">
				<label for="masa_hari" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Masa Berlaku (Hari)
				</label>
				<input type="number" id="masa_hari" name="masa_hari" value="7" min="1" max="90" inputmode="numeric" style="font-size:13px; padding:8px 12px; width:100%">
				<span class="muted" style="display:block; font-size:11px; margin-top:4px">
					Token otomatis tidak berlaku setelah masa kedaluwarsa habis.
				</span>
			</div>

			<button type="submit" class="btn btn-primary" style="width:100%; padding:9px 16px; font-weight:600">
				+ Generate Token Baru
			</button>
		<?= form_close() ?>
	</div>

	<!-- Tabel Riwayat Token -->
	<div class="card" style="padding:0; overflow:hidden; margin:0">
		<div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Daftar Token yang Pernah Dibuat
			</h3>
			<span class="muted" style="font-size:12px">Total <?= count($tokens ?? array()) ?> token</span>
		</div>

		<?php if (empty($tokens)): ?>
			<div style="text-align:center; padding:36px 16px" class="muted">
				<div style="font-size:24px; margin-bottom:6px">&#128273;</div>
				<div style="font-size:13.5px; font-weight:600; color:var(--text)">Belum ada token yang dibuat</div>
				<div style="font-size:12px; margin-top:2px">Buat token baru menggunakan formulir di sebelah kiri.</div>
			</div>
		<?php else: ?>
			<div class="table-responsive-fit" style="overflow-x:visible">
				<table style="width:100%; border-collapse:collapse; table-layout:fixed">
					<thead>
						<tr>
							<th style="padding:10px 14px; font-size:11.5px; text-align:left; background:var(--surface); border-bottom:1px solid var(--border); width:40%">Tautan &amp; Token</th>
							<th style="padding:10px 14px; font-size:11.5px; text-align:left; background:var(--surface); border-bottom:1px solid var(--border); width:22%">Tujuan</th>
							<th style="padding:10px 14px; font-size:11.5px; text-align:left; background:var(--surface); border-bottom:1px solid var(--border); width:20%">Kedaluwarsa</th>
							<th style="padding:10px 14px; font-size:11.5px; text-align:center; background:var(--surface); border-bottom:1px solid var(--border); width:18%">Status &amp; Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tokens as $tok): ?>
							<?php $tok_url = site_url('berkas/' . $tok['token']); ?>
							<tr style="<?= ($tok['is_revoked'] || !empty($tok['dipakai_pada'])) ? 'background:var(--surface-2); opacity:.8;' : '' ?>">
								<td style="padding:10px 14px; border-bottom:1px solid var(--border)">
									<div style="display:flex; align-items:center; gap:6px">
										<code style="font-size:11px; word-break:break-all; max-width:220px; overflow:hidden; text-overflow:ellipsis">/berkas/<?= html_escape(substr($tok['token'], 0, 16)) ?>...</code>
										<button type="button" class="btn-sm btn-ghost" title="Salin URL lengkap" style="padding:2px 6px; font-size:11px; cursor:pointer" onclick="navigator.clipboard.writeText('<?= $tok_url ?>'); this.textContent = 'Tersalin'; setTimeout(() => this.textContent = 'Salin', 1800)">
											Salin
										</button>
									</div>
								</td>
								<td style="padding:10px 14px; border-bottom:1px solid var(--border); font-size:12.5px; font-weight:600">
									<?= html_escape($tok['tujuan']) ?>
								</td>
								<td style="padding:10px 14px; border-bottom:1px solid var(--border); font-size:12px" class="muted">
									<?= !empty($tok['kadaluarsa_pada']) ? html_escape(substr($tok['kadaluarsa_pada'], 0, 16)) : 'Tanpa Batas' ?>
								</td>
								<td style="padding:10px 14px; border-bottom:1px solid var(--border); text-align:center">
									<?php if ($tok['is_revoked']): ?>
										<span class="tag off" style="font-size:10.5px">Dicabut</span>
									<?php elseif (!empty($tok['dipakai_pada'])): ?>
										<span class="tag off" style="font-size:10.5px">Sudah Terpakai</span>
									<?php else: ?>
										<div style="display:inline-flex; align-items:center; gap:6px">
											<span class="tag on" style="font-size:10.5px">Aktif</span>
											<?= form_open(site_url('postings/tokens/' . (int) $id_lamaran), array('class' => 'inline', 'style' => 'display:inline')) ?>
												<input type="hidden" name="act" value="revoke">
												<input type="hidden" name="id_token" value="<?= (int) $tok['id_token'] ?>">
												<button type="submit" class="btn-sm btn-ghost" style="color:var(--crit); padding:2px 6px; font-size:10.5px" onclick="return confirm('Cabut token ini?')">
													Cabut
												</button>
											<?= form_close() ?>
										</div>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>

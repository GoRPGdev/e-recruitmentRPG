<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="card" style="padding:22px 26px; margin-bottom:20px">
	<!-- HEADER LIST PELAMAR -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Database Pelamar &amp; Rekrutmen</div>
			<h1 style="margin:0 0 6px; font-size:22px; font-weight:700">Daftar Semua Kandidat</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Total <strong><?= (int) $total ?></strong> berkas lamaran masuk. Filter berdasarkan status, posisi, sumber, atau pencarian nama/kontak.
			</p>
		</div>
		<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
			<a href="<?= site_url('manual') ?>" class="btn btn-sm btn-primary">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Pelamar Manual</span>
			</a>
			<a href="<?= site_url('requisitions') ?>" class="btn btn-sm btn-ghost">
				<span>Papan Pipeline MPR &rarr;</span>
			</a>
		</div>
	</div>

	<!-- FORM FILTER KOMPREHENSIF -->
	<?= form_open(site_url('candidates'), array('method' => 'get', 'style' => 'background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:14px 16px; margin-bottom:20px')) ?>
		<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; align-items:flex-end">
			<div>
				<label for="f-q" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Pencarian</label>
				<input type="text" id="f-q" name="q" value="<?= html_escape($f['q'] ?? '') ?>" placeholder="Nama, email, WhatsApp, No MPR..." style="margin:0; width:100%; font-size:13px">
			</div>
			<div>
				<label for="f-status" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Status Lamaran</label>
				<select id="f-status" name="status" style="margin:0; width:100%; font-size:13px">
					<option value="">Semua Status</option>
					<?php
					$all_statuses = array('In_Progress', 'Hired', 'Rejected', 'On_Hold', 'Unreachable', 'Withdrawn', 'Offer_Declined', 'No_Show', 'Talent_Pool');
					foreach ($all_statuses as $st): ?>
						<option value="<?= $st ?>" <?= ($f['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="f-posisi" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Posisi Lowongan</label>
				<select id="f-posisi" name="posisi" style="margin:0; width:100%; font-size:13px">
					<option value="">Semua Posisi</option>
					<?php foreach ($positions as $p): ?>
						<option value="<?= (int) $p['id_posisi'] ?>" <?= ((int)($f['posisi'] ?? 0)) === (int)$p['id_posisi'] ? 'selected' : '' ?>>
							<?= html_escape($p['nama_posisi']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php if (current_user_dept() === NULL): ?>
			<div>
				<label for="f-dept" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Departemen</label>
				<select id="f-dept" name="dept" style="margin:0; width:100%; font-size:13px">
					<option value="">Semua Departemen</option>
					<?php foreach ($departments as $d): ?>
						<option value="<?= (int) $d['id_departemen'] ?>" <?= ((int)($f['dept'] ?? 0)) === (int)$d['id_departemen'] ? 'selected' : '' ?>>
							<?= html_escape($d['nama']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>
			<div>
				<label for="f-intake" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Metode Intake</label>
				<select id="f-intake" name="intake" style="margin:0; width:100%; font-size:13px">
					<option value="">Semua Jalur</option>
					<option value="FORM_PUBLIC" <?= ($f['intake'] ?? '') === 'FORM_PUBLIC' ? 'selected' : '' ?>>Form Publik</option>
					<option value="MANUAL" <?= ($f['intake'] ?? '') === 'MANUAL' ? 'selected' : '' ?>>Manual Walk-In</option>
					<option value="IMPORT_FILE" <?= ($f['intake'] ?? '') === 'IMPORT_FILE' ? 'selected' : '' ?>>Import Portal</option>
				</select>
			</div>
			<div>
				<label for="f-dari" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Tanggal Daftar (Dari)</label>
				<input type="date" id="f-dari" name="dari" value="<?= html_escape($f['dari'] ?? '') ?>" style="margin:0; width:100%; font-size:13px">
			</div>
			<div>
				<label for="f-sampai" style="font-size:12px; font-weight:600; margin-bottom:4px; display:block">Tanggal Daftar (Sampai)</label>
				<input type="date" id="f-sampai" name="sampai" value="<?= html_escape($f['sampai'] ?? '') ?>" style="margin:0; width:100%; font-size:13px">
			</div>
			<div style="display:flex; gap:6px">
				<button type="submit" class="btn btn-primary btn-sm" style="flex:1; height:34px">Terapkan Filter</button>
				<a href="<?= site_url('candidates') ?>" class="btn btn-ghost btn-sm" style="height:34px" title="Reset filter">Reset</a>
			</div>
		</div>
	<?= form_close() ?>

	<!-- TABEL DAFTAR PELAMAR FIT-TO-SCREEN -->
	<div class="table-responsive-fit" style="overflow-x:visible">
		<table style="width:100%; table-layout:fixed; font-size:13px; margin:0">
			<thead>
				<tr style="border-bottom:2px solid var(--border)">
					<th style="width:26%; text-align:left; padding:10px 8px">Kandidat &amp; Kontak</th>
					<th style="width:26%; text-align:left; padding:10px 8px">Posisi &amp; Requisition</th>
					<th style="width:16%; text-align:left; padding:10px 8px">Tahap Seleksi</th>
					<th style="width:14%; text-align:center; padding:10px 8px">Status Lamaran</th>
					<th style="width:10%; text-align:center; padding:10px 8px">Tgl Daftar</th>
					<th style="width:8%; text-align:right; padding:10px 8px">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($rows)): ?>
					<tr>
						<td colspan="6" style="text-align:center; padding:36px 16px; color:var(--text-muted)">
							Tidak ada data kandidat yang cocok dengan kriteria filter saat ini.
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($rows as $r): ?>
						<tr style="border-bottom:1px solid var(--border)">
							<!-- Kolom Kandidat & Kontak -->
							<td style="padding:10px 8px; word-break:break-word">
								<a href="<?= site_url('candidates/detail/' . (int) $r['id_lamaran']) ?>" style="font-weight:700; color:var(--text); text-decoration:none; font-size:13.5px">
									<?= html_escape($r['nama_lengkap']) ?>
								</a>
								<div class="muted mono" style="font-size:12px; margin-top:2px">
									<?= html_escape($r['no_wa_normal'] ?: '-') ?>
								</div>
								<?php if ($r['email']): ?>
									<div class="faint" style="font-size:11.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?= html_escape($r['email']) ?>">
										<?= html_escape($r['email']) ?>
									</div>
								<?php endif; ?>
							</td>

							<!-- Kolom Posisi & Requisition -->
							<td style="padding:10px 8px; word-break:break-word">
								<div style="font-weight:600; color:var(--text)">
									<?= html_escape($r['nama_posisi']) ?>
								</div>
								<div class="muted" style="font-size:12px; margin-top:2px">
									<?= html_escape($r['nama_departemen'] ?: '-') ?> &middot;
									<span class="mono"><?= html_escape($r['no_mpr'] ?: '#' . $r['id_req']) ?></span>
								</div>
								<div style="margin-top:2px">
									<span class="tag" style="font-size:10px; padding:1px 5px">
										<?= html_escape($r['intake_method'] ?: 'FORM_PUBLIC') ?>
									</span>
								</div>
							</td>

							<!-- Kolom Tahap Seleksi -->
							<td style="padding:10px 8px; word-break:break-word">
								<?php if ($r['nama_tahap_kini']): ?>
									<strong style="color:var(--accent); font-size:12.5px">
										<?= html_escape($r['nama_tahap_kini']) ?>
									</strong>
									<div class="faint" style="font-size:11px">
										<?= html_escape($r['tipe_tahap_kini'] ?: '-') ?>
									</div>
								<?php else: ?>
									<span class="muted">-</span>
								<?php endif; ?>
							</td>

							<!-- Kolom Status Lamaran -->
							<td style="padding:10px 8px; text-align:center">
								<?php
								$st_class = 'off';
								if (in_array($r['status_global'], array('Hired', 'Approved'))) {
									$st_class = 'on';
								} elseif ($r['status_global'] === 'In_Progress') {
									$st_class = 'info';
								} elseif (in_array($r['status_global'], array('On_Hold', 'Unreachable'))) {
									$st_class = 'warn';
								} elseif ($r['status_global'] === 'Rejected') {
									$st_class = 'off';
								}
								?>
								<span class="tag <?= $st_class ?>" style="font-size:11px; white-space:normal; display:inline-block; max-width:100%">
									<?= html_escape($r['status_global']) ?>
								</span>
							</td>

							<!-- Kolom Tgl Daftar -->
							<td style="padding:10px 8px; text-align:center; font-size:12px" class="mono faint">
								<?= html_escape($r['tanggal_lamar'] ? substr($r['tanggal_lamar'], 0, 10) : '-') ?>
							</td>

							<!-- Kolom Aksi Kebab Menu -->
							<td style="padding:10px 8px; text-align:right">
								<div style="position:relative; display:inline-block">
									<button type="button" class="btn btn-sm btn-ghost mpr-menu-btn"
										onclick="toggleMenu(event, 'menu-cand-<?= (int) $r['id_lamaran'] ?>')"
										aria-label="Aksi"
										style="padding:3px 8px; font-size:16px; line-height:1; font-weight:700">
										&#8942;
									</button>
									<div id="menu-cand-<?= (int) $r['id_lamaran'] ?>" class="mpr-dropdown"
										style="display:none; position:absolute; right:0; top:100%; z-index:1000; min-width:160px; background:var(--surface); border:1px solid var(--border); border-radius:6px; box-shadow:var(--shadow); text-align:left; padding:4px 0">
										<a href="<?= site_url('candidates/detail/' . (int) $r['id_lamaran']) ?>"
											style="display:block; padding:7px 12px; font-size:12.5px; color:var(--text); text-decoration:none">
											👤 Profil Lengkap
										</a>
										<a href="<?= site_url('pipeline/index/' . (int) $r['id_req']) ?>"
											style="display:block; padding:7px 12px; font-size:12.5px; color:var(--text); text-decoration:none">
											🎯 Buka di Pipeline
										</a>
										<a href="<?= site_url('documents/checklist/' . (int) $r['id_lamaran']) ?>"
											style="display:block; padding:7px 12px; font-size:12.5px; color:var(--text); text-decoration:none">
											📁 Checklist Berkas
										</a>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- PAGINASI HALAMAN -->
	<?php if ($pages > 1): ?>
		<div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; flex-wrap:wrap; gap:10px">
			<div class="muted" style="font-size:12.5px">
				Halaman <strong><?= (int) $page ?></strong> dari <strong><?= (int) $pages ?></strong> (Total <?= (int) $total ?> kandidat)
			</div>
			<div style="display:flex; gap:6px">
				<?php if ($page > 1): ?>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $page - 1)))) ?>" class="btn btn-sm btn-ghost">&larr; Sebelumnya</a>
				<?php endif; ?>
				<?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $i)))) ?>"
						class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>">
						<?= $i ?>
					</a>
				<?php endfor; ?>
				<?php if ($page < $pages): ?>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $page + 1)))) ?>" class="btn btn-sm btn-ghost">Berikutnya &rarr;</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

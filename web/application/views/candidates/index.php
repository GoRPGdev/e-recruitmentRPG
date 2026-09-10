<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: candidates/index.php -- Daftar Database Kandidat & Manajemen Pelamar Masuk
 *
 * Fungsi:
 * - Menampilkan daftar seluruh berkas pelamar dengan fitur pencarian dan filter mendalam (posisi, status, tanggal).
 * - Menampilkan ringkasan metrik cepat status operasional kandidat (In Progress, Hired, Rejected).
 * - Menyediakan pagination dan aksi cepat (lihat detail, proses seleksi, export data).
 */
?>

<div style="margin-bottom:24px">
	<!-- Page Header -->
	<div style="margin-bottom:18px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Database Pelamar &amp; Rekrutmen</div>
			<h1 style="margin:0 0 4px; font-size:22px; font-weight:700">Daftar Semua Kandidat</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Total <strong><?= (int) $total ?></strong> berkas lamaran masuk. Filter berdasarkan status, posisi, sumber, atau pencarian nama/kontak.
			</p>
		</div>
	</div>

	<!-- Ringkasan Metrik / Quick Stat Cards (Di Atas Filter: 3 Status Operasional Utama) -->
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:20px">
		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--surface-2); display:grid; place-items:center; flex:none; color:var(--accent)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Total Kandidat</div>
				<div style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px"><?= (int) ($stats['total'] ?? $total) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">berkas</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--info-soft); display:grid; place-items:center; flex:none; color:var(--info)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Sedang Proses</div>
				<div style="font-size:20px; font-weight:700; color:var(--info); line-height:1.2; margin-top:2px"><?= (int) ($stats['n_in_progress'] ?? 0) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">aktif</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--good-soft); display:grid; place-items:center; flex:none; color:var(--good)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Diterima (Hired)</div>
				<div style="font-size:20px; font-weight:700; color:var(--good); line-height:1.2; margin-top:2px"><?= (int) ($stats['n_hired'] ?? 0) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">orang</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--crit-soft); display:grid; place-items:center; flex:none; color:var(--crit)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Ditolak / Gugur</div>
				<div style="font-size:20px; font-weight:700; color:var(--crit); line-height:1.2; margin-top:2px"><?= (int) ($stats['n_rejected'] ?? 0) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">kandidat</span></div>
			</div>
		</div>
	</div>

	<!-- Filter Bar (Di Bawah Card) -->
	<div class="card" style="padding:16px 18px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px">
		<?= form_open(site_url('candidates'), array('method' => 'get', 'style' => 'margin:0')) ?>
			<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px; align-items:flex-end">
				<div>
					<label for="f-q" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Pencarian</label>
					<input type="text" id="f-q" name="q" value="<?= html_escape($f['q'] ?? '') ?>" placeholder="Nama, email, WhatsApp, No MPR..." style="margin:0; width:100%; padding:6px 10px; font-size:13px; background:var(--surface)">
				</div>
				<div>
					<label for="f-status" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Status Lamaran</label>
					<select id="f-status" name="status" style="margin:0; width:100%; padding:6px 10px; font-size:13px; background:var(--surface)">
						<option value="">Semua Status</option>
						<option value="In_Progress" <?= ($f['status'] ?? '') === 'In_Progress' ? 'selected' : '' ?>>Sedang Proses</option>
						<option value="Hired" <?= ($f['status'] ?? '') === 'Hired' ? 'selected' : '' ?>>Diterima (Hired)</option>
						<option value="Rejected" <?= ($f['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Ditolak / Gugur</option>
					</select>
				</div>
				<div>
					<label for="f-posisi" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Posisi Lowongan</label>
					<select id="f-posisi" name="posisi" style="margin:0; width:100%; padding:6px 10px; font-size:13px; background:var(--surface)">
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
					<label for="f-dept" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Departemen</label>
					<select id="f-dept" name="dept" style="margin:0; width:100%; padding:6px 10px; font-size:13px; background:var(--surface)">
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
					<label for="f-status-mpr" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Status MPR</label>
					<select id="f-status-mpr" name="status_mpr" style="margin:0; width:100%; padding:6px 10px; font-size:13px; background:var(--surface)">
						<option value="">Semua MPR</option>
						<option value="BUKA" <?= ($f['status_mpr'] ?? '') === 'BUKA' ? 'selected' : '' ?>>Masih Dibuka (Aktif)</option>
						<option value="TUTUP" <?= ($f['status_mpr'] ?? '') === 'TUTUP' ? 'selected' : '' ?>>Sudah Ditutup</option>
					</select>
				</div>
				<div>
					<label for="f-dari" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Tanggal Daftar (Dari)</label>
					<input type="date" id="f-dari" name="dari" value="<?= html_escape($f['dari'] ?? '') ?>" style="margin:0; width:100%; padding:6px 8px; font-size:13px; background:var(--surface)">
				</div>
				<div>
					<label for="f-sampai" style="font-size:11.5px; font-weight:600; margin:0 0 4px; display:block" class="eyebrow">Tanggal Daftar (Sampai)</label>
					<input type="date" id="f-sampai" name="sampai" value="<?= html_escape($f['sampai'] ?? '') ?>" style="margin:0; width:100%; padding:6px 8px; font-size:13px; background:var(--surface)">
				</div>
				<div style="display:flex; gap:6px">
					<button type="submit" class="btn btn-primary btn-sm" style="flex:1; height:35px; font-weight:600">Terapkan Filter</button>
					<a href="<?= site_url('candidates') ?>" class="btn btn-ghost btn-sm" style="height:35px; padding:0 12px; line-height:33px" title="Reset filter">Reset</a>
				</div>
			</div>
		<?= form_close() ?>
	</div>

	<!-- Tabel Data Kandidat (Di Bawah Filter) -->
	<div class="card" style="padding:0; overflow:hidden">
		<div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); flex-wrap:wrap; gap:10px">
			<div style="display:flex; align-items:center; gap:10px">
				<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
				<div>
					<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
						Daftar Berkas Pelamar Masuk
					</h3>
					<span class="muted" style="font-size:12px">Menampilkan <?= count($rows ?? array()) ?> dari <?= (int) $total ?> kandidat</span>
				</div>
			</div>
		</div>

		<div class="table-responsive-fit" style="overflow-x:visible">
			<table style="width:100%; table-layout:fixed; font-size:13px; margin:0">
				<thead>
					<tr>
						<th style="width:28%; text-align:left; padding:12px 14px">Kandidat &amp; Kontak</th>
						<th style="width:26%; text-align:left; padding:12px 14px">Posisi &amp; Requisition</th>
						<th style="width:18%; text-align:left; padding:12px 14px">Tahap Seleksi</th>
						<th style="width:12%; text-align:center; padding:12px 14px">Status Lamaran</th>
						<th style="width:10%; text-align:center; padding:12px 14px">Tgl Daftar</th>
						<th style="width:6%; text-align:right; padding:12px 14px">Aksi</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($rows)): ?>
						<tr>
							<td colspan="6" style="text-align:center; padding:40px 16px; color:var(--text-muted); background:var(--surface)">
								Tidak ada data kandidat yang cocok dengan kriteria filter saat ini.
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($rows as $r): ?>
							<tr>
								<!-- Kolom Kandidat & Kontak -->
								<td style="padding:12px 14px; word-break:break-word">
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
								<td style="padding:12px 14px; word-break:break-word">
									<div style="font-weight:600; color:var(--text)">
										<?= html_escape($r['nama_posisi']) ?>
									</div>
									<div class="muted" style="font-size:12px; margin-top:2px">
										<?= html_escape($r['nama_departemen'] ?: '-') ?> &middot;
										<span class="mono"><?= html_escape($r['no_mpr'] ?: '#' . $r['id_req']) ?></span> <?php if (in_array($r['status_req'] ?? '', array('Sourcing', 'Approved', 'Sourcing_Ulang'))): ?><span class="tag on" style="font-size:9.5px; padding:1px 5px; font-weight:700" title="Status: <?= html_escape($r['status_req'] ?? '') ?>">Jalan</span><?php else: ?><span class="tag off" style="font-size:9.5px; padding:1px 5px; font-weight:700" title="Status: <?= html_escape($r['status_req'] ?? 'Closed') ?>">Closed</span><?php endif; ?>
									</div>
									<div style="margin-top:4px">
										<span class="tag" style="font-size:10px; padding:1px 6px">
											<?= html_escape(label_intake($r['intake_method'] ?: 'FORM_PUBLIC')) ?>
										</span>
									</div>
								</td>

								<!-- Kolom Tahap Seleksi -->
								<td style="padding:12px 14px; word-break:break-word">
									<?php if ($r['nama_tahap_kini']): ?>
										<strong style="color:var(--accent); font-size:12.5px">
											<?= html_escape($r['nama_tahap_kini']) ?>
										</strong>
										<div class="faint" style="font-size:11px; margin-top:2px">
											<?= html_escape($r['tipe_tahap_kini'] ?: '-') ?>
										</div>
										<?php if (! empty($r['form_dipakai_pada'])): ?>
											<div style="margin-top:4px">
												<span class="tag on" style="font-size:10px; padding:1px 6px" title="Form pelamar diisi pada <?= html_escape(substr($r['form_dipakai_pada'], 0, 10)) ?>">Form Terisi</span>
											</div>
										<?php elseif (! empty($r['form_token_ada']) && empty($r['form_revoked'])): ?>
											<div style="margin-top:4px">
												<span class="tag info" style="font-size:10px; padding:1px 6px" title="Link form aktif, menunggu diisi kandidat">Menunggu Isi</span>
											</div>
										<?php endif; ?>
									<?php else: ?>
										<span class="muted">-</span>
									<?php endif; ?>
								</td>

								<!-- Kolom Status Lamaran (3 Kategori Operasional Bersih) -->
								<td style="padding:12px 14px; text-align:center">
									<?php
									$st = $r['status_global'];
									if (in_array($st, array('Hired', 'Approved'))) {
										$st_class = 'on';
										$st_label = 'Diterima';
									} elseif (in_array($st, array('In_Progress', 'On_Hold', 'Unreachable', 'Sourcing'))) {
										$st_class = 'info';
										$st_label = 'Sedang Proses';
									} else {
										$st_class = 'off';
										$st_label = 'Ditolak';
									}
									?>
									<span class="tag <?= $st_class ?>" style="font-size:11px; font-weight:600" title="Detail Sistem: <?= html_escape($st) ?>">
										<?= $st_label ?>
									</span>
								</td>

								<!-- Kolom Tgl Daftar -->
								<td style="padding:12px 14px; text-align:center; font-size:12px" class="mono faint">
									<?= html_escape($r['tanggal_lamar'] ? substr($r['tanggal_lamar'], 0, 10) : '-') ?>
								</td>

								<!-- Kolom Aksi Kebab Menu -->
								<td style="padding:12px 14px; text-align:right; position:relative; overflow:visible">
									<div style="position:relative; display:inline-block">
										<button type="button" class="btn btn-sm btn-ghost mpr-menu-btn"
											onclick="toggleMenu(event, 'menu-cand-<?= (int) $r['id_lamaran'] ?>')"
											aria-label="Aksi"
											style="padding:2px 8px; font-size:16px; line-height:1; font-weight:700; border-radius:6px">
											&#8942;
										</button>
										<div id="menu-cand-<?= (int) $r['id_lamaran'] ?>" class="mpr-dropdown"
											style="display:none; position:absolute; right:0; top:calc(100% + 4px); z-index:99; min-width:180px; background:var(--surface); border:1px solid var(--border); border-radius:8px; box-shadow:var(--shadow); text-align:left; overflow:hidden">
											<a href="<?= site_url('candidates/cv/' . (int) $r['id_lamaran']) ?>" target="_blank"
										style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--text); text-decoration:none; border-bottom:1px solid var(--surface-2)">
										<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
												<span>Lihat CV</span>
									</a>
									<a href="<?= site_url('candidates/detail/' . (int) $r['id_lamaran']) ?>"
										style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--text); text-decoration:none; border-bottom:1px solid var(--surface-2)">
										<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
										<span>Profil Lengkap</span>
											</a>
											<a href="<?= site_url('pipeline/index/' . (int) $r['id_req']) ?>"
												style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--accent); font-weight:600; text-decoration:none">
												<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
												<span>Buka di Pipeline</span>
											</a>
											<?php
											$is_form_stage = (strtoupper(trim($r['tipe_tahap_kini'] ?? '')) === 'FORM'
												|| stripos($r['nama_tahap_kini'] ?? '', 'form') !== FALSE
												|| strtoupper(trim($r['tipe_tahap_kini'] ?? '')) === 'ONBOARD'
												|| stripos($r['nama_tahap_kini'] ?? '', 'onboard') !== FALSE);
											?>
											<?php if ($is_form_stage && has_permission('KELOLA_REKRUTMEN')): ?>
												<a href="<?= site_url('candidates/generate_onboarding_link/' . (int) $r['id_lamaran'] . '?redirect_to=' . rawurlencode(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''))) ?>"
													onclick="openOnboardingModal(event, <?= (int) $r['id_lamaran'] ?>, '<?= html_escape(addslashes($r['nama_lengkap'])) ?>'); return false;"
													style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--good); font-weight:600; text-decoration:none; border-top:1px solid var(--surface-2)">
													<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
													<span>Generate Link Form Pelamar</span>
												</a>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- BAR PAGINASI HALAMAN -->
		<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-top:1px solid var(--border); background:var(--surface); flex-wrap:wrap; gap:12px">
			<div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap">
				<div class="muted" style="font-size:12.5px">
					Menampilkan <strong><?= count($rows ?? array()) ?></strong> dari <strong><?= (int) $total ?></strong> kandidat (Halaman <?= (int) $page ?> dari <?= (int) $pages ?>)
				</div>
				<div style="display:flex; align-items:center; gap:6px; font-size:12px" class="muted">
					<span>Tampilkan:</span>
					<select onchange="location.href=this.value" style="padding:3px 6px; font-size:12px; width:auto; border-radius:6px; background:var(--surface-2)">
						<?php foreach (array(10, 20, 50) as $opt): ?>
							<option value="<?= site_url('candidates?' . http_build_query(array_merge($f, array('per' => $opt, 'page' => 1)))) ?>" <?= ((int)($per ?? 10)) === $opt ? 'selected' : '' ?>>
								<?= $opt ?> baris
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div style="display:flex; align-items:center; gap:4px">
				<?php if ($page > 1): ?>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => 1)))) ?>" class="btn btn-sm btn-ghost" title="Halaman Pertama" style="padding:4px 8px">&laquo;</a>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $page - 1)))) ?>" class="btn btn-sm btn-ghost" style="padding:4px 10px">&larr; Prev</a>
				<?php else: ?>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 8px">&laquo;</button>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 10px">&larr; Prev</button>
				<?php endif; ?>

				<?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $i)))) ?>"
						class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>" style="min-width:30px; text-align:center; padding:4px 8px">
						<?= $i ?>
					</a>
				<?php endfor; ?>

				<?php if ($page < $pages): ?>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $page + 1)))) ?>" class="btn btn-sm btn-ghost" style="padding:4px 10px">Next &rarr;</a>
					<a href="<?= site_url('candidates?' . http_build_query(array_merge($f, array('page' => $pages)))) ?>" class="btn btn-sm btn-ghost" title="Halaman Terakhir" style="padding:4px 8px">&raquo;</a>
				<?php else: ?>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 10px">Next &rarr;</button>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 8px">&raquo;</button>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<!-- ================= MODAL DIALOG POPUP GENERATE LINK ONBOARDING ================= -->
<style>
	#dlg-onboarding-link {
		border: 1px solid var(--border);
		border-radius: 14px;
		padding: 0;
		max-width: 520px;
		width: 92%;
		background: var(--surface);
		color: var(--text);
		box-shadow: 0 20px 48px rgba(0, 0, 0, 0.28);
		overflow: hidden;
	}
	#dlg-onboarding-link[open] {
		display: flex;
		flex-direction: column;
	}
	#dlg-onboarding-link::backdrop {
		background: rgba(12, 18, 14, 0.55);
		backdrop-filter: blur(3px);
	}
	.ob-modal-header {
		padding: 16px 20px;
		border-bottom: 1px solid var(--border);
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--surface-2);
	}
	.ob-modal-body {
		padding: 20px;
	}
	.ob-modal-footer {
		padding: 12px 20px;
		border-top: 1px solid var(--border);
		display: flex;
		justify-content: flex-end;
		gap: 8px;
		background: var(--surface-2);
	}
</style>

<dialog id="dlg-onboarding-link">
	<div class="ob-modal-header">
		<div style="display:flex; align-items:center; gap:10px">
			<div style="width:34px; height:34px; border-radius:8px; background:var(--accent-soft); display:grid; place-items:center; color:var(--accent)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
			</div>
			<div>
				<h3 style="margin:0; font-size:15px; font-weight:700">Tautan Formulir Pelamar</h3>
				<div class="muted" id="ob-modal-subtitle" style="font-size:12px">Kandidat Ratu Pertiwi Group</div>
			</div>
		</div>
		<button type="button" onclick="closeOnboardingModal()" class="btn btn-sm btn-ghost" style="padding:4px 8px; font-size:16px; line-height:1" title="Tutup">&times;</button>
	</div>

		<div class="ob-modal-body">
		<!-- State Loading -->
		<div id="ob-state-loading" style="display:none; text-align:center; padding:30px 10px">
			<div style="font-size:24px; margin-bottom:8px">⏳</div>
			<div id="ob-loading-text" style="font-weight:600; font-size:13.5px">Memeriksa tautan formulir...</div>
			<div class="muted" style="font-size:12px; margin-top:4px">Menghubungkan ke server RPG</div>
		</div>

		<!-- State Sukses / Tautan Jadi atau Tautan Lama Aktif -->
		<div id="ob-state-success" style="display:none">
			<div id="ob-alert-box" style="background:var(--accent-soft); color:var(--accent-ink); padding:10px 12px; border-radius:8px; font-size:12.5px; margin-bottom:14px; display:flex; align-items:center; gap:8px">
				<span id="ob-alert-icon" style="font-size:15px; font-weight:bold">ℹ</span>
				<span id="ob-success-msg" style="font-weight:600">Tautan formulir pelamar aktif ditemukan.</span>
			</div>

			<div style="margin-bottom:12px">
				<label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:4px">URL Formulir Pelamar</label>
				<div style="display:flex; gap:6px">
					<input type="text" id="ob-input-url" readonly style="flex:1; font-size:12px; font-family:monospace; padding:8px 10px; background:var(--surface-2); border:1px solid var(--border); border-radius:6px" onclick="this.select()">
					<button type="button" id="ob-btn-copy" onclick="copyObUrl()" class="btn btn-sm btn-primary" style="white-space:nowrap; font-size:12px; font-weight:600">
						Salin
					</button>
				</div>
				<div id="ob-copy-feedback" style="display:none; color:var(--good); font-size:11.5px; margin-top:4px; font-weight:600">Berhasil disalin ke clipboard!</div>
			</div>

			<div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px">
				<a id="ob-btn-wa" href="#" target="_blank" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; color:var(--good); font-size:12.5px; font-weight:600; text-decoration:none; border:1px solid var(--border)">
					Bagikan ke WhatsApp
				</a>
				<a id="ob-btn-open" href="#" target="_blank" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; text-decoration:none; border:1px solid var(--border)">
					Buka Halaman Form &rarr;
				</a>
			</div>
		</div>
	</div>

	<div class="ob-modal-footer" style="display:flex; justify-content:space-between; align-items:center">
		<div>
			<button type="button" id="ob-btn-regen" onclick="submitGenerateOnboarding(true)" class="btn btn-sm btn-ghost" style="display:none; color:var(--text-muted); font-size:12px" title="Cabut link lama dan buat link baru">
				↻ Buat Ulang Link Baru
			</button>
		</div>
		<div style="display:flex; gap:8px">
			<button type="button" onclick="closeOnboardingModal()" class="btn btn-sm btn-ghost">Tutup</button>
		</div>
	</div>
</dialog>

<script>
var _currentObId = null;
var _currentObName = '';
var _hasGeneratedNew = false;

function openOnboardingModal(e, idLamaran, namaKandidat) {
	var ddowns = document.querySelectorAll('.mpr-dropdown');
	ddowns.forEach(function(d){ d.style.display = 'none'; });
	if (e) {
		e.preventDefault();
		e.stopPropagation();
	}

	_currentObId = idLamaran;
	_currentObName = namaKandidat;
	_hasGeneratedNew = false;

	document.getElementById('ob-modal-subtitle').textContent = 'Kandidat: ' + namaKandidat;

	document.getElementById('ob-state-success').style.display = 'none';
	document.getElementById('ob-btn-regen').style.display = 'none';
	document.getElementById('ob-copy-feedback').style.display = 'none';

	document.getElementById('ob-loading-text').textContent = 'Memeriksa tautan formulir...';
	document.getElementById('ob-state-loading').style.display = 'block';

	var dlg = document.getElementById('dlg-onboarding-link');
	if (dlg && typeof dlg.showModal === 'function') {
		dlg.showModal();
	} else if (dlg) {
		dlg.setAttribute('open', '');
	}

	// Otomatis periksa dan tampilkan token yang sudah ada tanpa generate baru
	loadOnboardingToken(false);
}

function closeOnboardingModal() {
	var dlg = document.getElementById('dlg-onboarding-link');
	if (dlg && typeof dlg.close === 'function') {
		dlg.close();
	} else if (dlg) {
		dlg.removeAttribute('open');
	}
}

function submitGenerateOnboarding(forceNew) {
	if (forceNew) {
		if (!confirm('Apakah Anda yakin ingin membuat ulang tautan baru?\nTautan lama akan dicabut dan kandidat harus menggunakan tautan yang baru.')) {
			return;
		}
	}
	loadOnboardingToken(forceNew);
}

function loadOnboardingToken(forceNew) {
	if (!_currentObId) return;

	document.getElementById('ob-state-success').style.display = 'none';
	document.getElementById('ob-btn-regen').style.display = 'none';

	document.getElementById('ob-loading-text').textContent = forceNew ? 'Membuat tautan formulir baru...' : 'Memeriksa tautan formulir...';
	document.getElementById('ob-state-loading').style.display = 'block';

	var url = '<?= site_url("candidates/generate_onboarding_link/") ?>' + _currentObId + '?format=json' + (forceNew ? '&force_new=1' : '');

	fetch(url, {
		method: 'GET',
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		}
	})
	.then(function(res){
		return res.json();
	})
	.then(function(data){
		document.getElementById('ob-state-loading').style.display = 'none';

		if (data && data.success) {
			_hasGeneratedNew = true;
			document.getElementById('ob-state-success').style.display = 'block';
			document.getElementById('ob-input-url').value = data.onboarding_url;
			document.getElementById('ob-success-msg').textContent = data.message || 'Tautan formulir pelamar siap digunakan.';

			var alertBox = document.getElementById('ob-alert-box');
			var alertIcon = document.getElementById('ob-alert-icon');
			if (data.is_existing) {
				alertBox.style.background = 'var(--accent-soft)';
				alertBox.style.color = 'var(--accent-ink)';
				alertBox.style.border = '1px solid var(--border)';
				alertIcon.textContent = '';
			} else {
				alertBox.style.background = 'var(--good-soft)';
				alertBox.style.color = 'var(--good)';
				alertBox.style.border = 'none';
				alertIcon.textContent = '';
			}

			var btnWa = document.getElementById('ob-btn-wa');
			if (data.wa_link) {
				btnWa.href = data.wa_link;
				btnWa.style.display = 'inline-flex';
			} else {
				btnWa.style.display = 'none';
			}

			var btnOpen = document.getElementById('ob-btn-open');
			btnOpen.href = data.onboarding_url;

			// Tampilkan opsi Buat Ulang Link jika diperlukan
			document.getElementById('ob-btn-regen').style.display = 'inline-block';

		} else {
			alert(data.message || 'Gagal memuat tautan onboarding.');
			closeOnboardingModal(false);
		}
	})
	.catch(function(err){
		document.getElementById('ob-state-loading').style.display = 'none';
		alert('Terjadi kesalahan jaringan saat memproses link onboarding.');
		closeOnboardingModal(false);
	});
}

function copyObUrl() {
	var input = document.getElementById('ob-input-url');
	if (!input) return;
	input.select();
	input.setSelectionRange(0, 99999);
	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(input.value).then(function(){
			showCopyFeedback();
		}).catch(function(){
			document.execCommand('copy');
			showCopyFeedback();
		});
	} else {
		document.execCommand('copy');
		showCopyFeedback();
	}
}

function showCopyFeedback() {
	var fb = document.getElementById('ob-copy-feedback');
	var btn = document.getElementById('ob-btn-copy');
	if (fb) fb.style.display = 'block';
	if (btn) btn.textContent = 'Tersalin!';
	setTimeout(function(){
		if (fb) fb.style.display = 'none';
		if (btn) btn.textContent = 'Salin';
	}, 3000);
}
</script>
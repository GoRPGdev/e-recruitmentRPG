<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: postings/index.php -- Pengelolaan Lowongan Kerja Publik (Job Postings)
 *
 * Fungsi:
 * - Menampilkan daftar lowongan kerja aktif dan terbit berdasarkan dokumen MPR yang disetujui.
 * - Mengatur status publikasi lowongan (buka / tutup tautan pendaftaran).
 * - Menghasilkan tautan publik, QR code, dan token pelacakan sumber kampanye rekrutmen.
 */
?>

<div style="margin-bottom:24px">
	<!-- Page Header -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<span class="eyebrow" style="margin:0; font-size:11px">Portal Publik</span>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Sourcing &amp; Penerimaan Berkas</span>
			</div>
			<h1 style="margin:0; font-size:24px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				Form Publik &amp; Lowongan Kerja
			</h1>
			<p class="muted" style="margin:4px 0 0; font-size:13.5px">
				Kelola link formulir lamaran online publik, jendela waktu tayang, dan pemantauan pelamar masuk per posisi.
			</p>
		</div>
	</div>

	<!-- Ringkasan Metrik / Quick Stat Cards -->
	<?php
	$n_aktif   = 0;
	$n_tutup   = 0;
	$tot_sub   = 0;
	$tot_lam   = 0;
	foreach ($rows as $item) {
		if (!empty($item['form_aktif'])) {
			$n_aktif++;
		} else {
			$n_tutup++;
		}
		$tot_sub += (int) ($item['jumlah_submit'] ?? 0);
		$tot_lam += (int) ($item['n_lamaran'] ?? 0);
	}
	?>
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:20px">
		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:36px; height:36px; border-radius:10px; background:var(--surface-2); display:grid; place-items:center; flex:none; color:var(--accent)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Total Lowongan</div>
				<div style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px"><?= (int) $total ?></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:36px; height:36px; border-radius:10px; background:var(--accent-soft); display:grid; place-items:center; flex:none; color:var(--accent-ink)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Form Terbuka (Menerima)</div>
				<div style="font-size:20px; font-weight:700; color:var(--accent-ink); line-height:1.2; margin-top:2px"><?= $n_aktif ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">posisi</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:36px; height:36px; border-radius:10px; background:var(--surface-2); display:grid; place-items:center; flex:none; color:var(--text-muted)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Form Ditutup</div>
				<div style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px"><?= $n_tutup ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">posisi</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:36px; height:36px; border-radius:10px; background:var(--surface-2); display:grid; place-items:center; flex:none; color:var(--accent)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Pelamar Masuk</div>
				<div style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px"><?= $tot_lam ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">berkas</span></div>
			</div>
		</div>
	</div>
</div>

<!-- ================= TABEL UTAMA FORM PUBLIK & LOWONGAN ================= -->
<div class="card" style="padding:0; overflow:visible; margin-bottom:24px">
	<div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); flex-wrap:wrap; gap:10px">
		<div style="display:flex; align-items:center; gap:10px">
			<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
			<div>
				<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
					Daftar Tautan Form Publik &amp; Sourcing
				</h3>
				<span class="muted" style="font-size:12px">Menampilkan <?= count($rows ?? array()) ?> lowongan pada halaman ini</span>
			</div>
		</div>
		<div style="display:flex; align-items:center; gap:8px">
			<span class="tag on" style="font-size:11px">&#10003; Live Public Access</span>
		</div>
	</div>

	<div class="table-responsive-fit" style="overflow-x:visible">
		<table style="width:100%; border-collapse:collapse; table-layout:fixed">
			<thead>
				<tr>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:left; background:var(--surface); border-bottom:1px solid var(--border); width:26%">
						Posisi &amp; Formasi
					</th>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:left; background:var(--surface); border-bottom:1px solid var(--border); width:20%">
						Referensi Dokumen MPR
					</th>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:left; background:var(--surface); border-bottom:1px solid var(--border); width:24%">
						Tautan Form Publik
					</th>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:center; background:var(--surface); border-bottom:1px solid var(--border); width:12%">
						Status Form
					</th>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:center; background:var(--surface); border-bottom:1px solid var(--border); width:10%">
						Pelamar
					</th>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:right; background:var(--surface); border-bottom:1px solid var(--border); width:8%">
						Aksi
					</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($rows)): ?>
					<tr>
						<td colspan="6" style="text-align:center; padding:44px 16px" class="muted">
							<div style="font-size:28px; margin-bottom:8px">&#128196;</div>
							<div style="font-size:14px; font-weight:600; color:var(--text)">Belum ada lowongan publik yang dibuat</div>
							<div style="font-size:12.5px; margin-top:4px">Buka menu MPR yang telah disetujui untuk membuat job posting baru.</div>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($rows as $r): ?>
						<tr style="<?= empty($r['form_aktif']) ? 'background:var(--surface-2); opacity:.85;' : '' ?>">
							<!-- Posisi & Formasi -->
							<td style="padding:12px 14px; border-bottom:1px solid var(--border)">
								<div style="font-weight:700; color:var(--text); font-size:13.5px; word-break:break-word">
									<?= html_escape($r['nama_posisi']) ?>
								</div>
								<?php if (!empty($r['judul_posting']) && $r['judul_posting'] !== $r['nama_posisi']): ?>
									<div class="muted" style="font-size:12px; margin-top:1px">
										<?= html_escape($r['judul_posting']) ?>
									</div>
								<?php endif; ?>
								<div style="display:flex; align-items:center; gap:6px; margin-top:4px; flex-wrap:wrap">
									<?php if (!empty($r['departemen'])): ?>
										<span class="tag" style="font-size:10.5px"><?= html_escape($r['departemen']) ?></span>
									<?php endif; ?>
									<?php if (!empty($r['tipe_penempatan'])): ?>
										<span class="tag" style="font-size:10.5px"><?= html_escape($r['tipe_penempatan']) ?><?= !empty($r['nama_outlet']) ? ' &bull; ' . html_escape($r['nama_outlet']) : '' ?></span>
									<?php endif; ?>
								</div>
							</td>

							<!-- No MPR & Status MPR -->
							<td style="padding:12px 14px; border-bottom:1px solid var(--border)">
								<?php if (!empty($r['no_mpr']) && !empty($r['id_req'])): ?>
									<a href="<?= site_url('requisitions/view/' . (int) $r['id_req']) ?>" style="text-decoration:none; font-weight:700; font-size:13px; color:var(--accent); display:inline-flex; align-items:center; gap:4px">
										<span><?= html_escape($r['no_mpr']) ?></span>
										<svg style="width:11px; height:11px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
									</a>
								<?php elseif (!empty($r['no_mpr'])): ?>
									<strong style="font-size:13px"><?= html_escape($r['no_mpr']) ?></strong>
								<?php else: ?>
									<span class="faint">-</span>
								<?php endif; ?>
								<div style="margin-top:4px">
									<?php
									$st_req = $r['status_req'] ?? 'Draft';
									$tag_class = 'warn';
									if (in_array($st_req, array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi'))) $tag_class = 'on';
									if (in_array($st_req, array('Dibatalkan', 'Kadaluarsa', 'Ditolak_HR', 'Ditolak_BOD'))) $tag_class = 'off';
									?>
									<span class="tag <?= $tag_class ?>" style="font-size:10.5px"><?= html_escape($st_req) ?></span>
								</div>
							</td>

							<!-- Tautan Publik (Hanya tombol Salin & Buka Langsung) -->
							<td style="padding:12px 14px; border-bottom:1px solid var(--border)">
								<?php if (!empty($r['url_slug'])): ?>
									<?php $pub_url = site_url('lamar/' . $r['url_slug']); ?>
									<div style="display:inline-flex; align-items:center; gap:6px">
										<button type="button" class="btn btn-sm btn-ghost" title="Salin Tautan Formulir Publik" style="padding:4px 10px; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:5px" onclick="copyPublicUrl('<?= $pub_url ?>', this)">
											<svg style="width:12px; height:12px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
											<span>Salin Link</span>
										</button>
										<a href="<?= $pub_url ?>" target="_blank" class="btn btn-sm btn-ghost" title="Buka form di tab baru" style="padding:4px 10px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:5px">
											<svg style="width:12px; height:12px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
											<span>Buka &nearr;</span>
										</a>
									</div>

									<!-- Info Jadwal Tayang Ringkas -->
									<?php if (!empty($r['form_dibuka']) || !empty($r['form_ditutup'])): ?>
										<div class="muted" style="font-size:11px; margin-top:5px; display:flex; align-items:center; gap:4px">
											<svg style="width:11px; height:11px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
											<span>
												<?= $r['form_dibuka'] ? html_escape(substr($r['form_dibuka'], 0, 10)) : 'Sekarang' ?> &mdash; <?= $r['form_ditutup'] ? html_escape(substr($r['form_ditutup'], 0, 10)) : 'Seterusnya' ?>
											</span>
										</div>
									<?php endif; ?>
								<?php else: ?>
									<span class="muted" style="font-size:12px"><em>(Belum ada URL)</em></span>
								<?php endif; ?>
							</td>

							<!-- Status Form (Toggle Status) -->
							<td style="padding:12px 14px; border-bottom:1px solid var(--border); text-align:center">
								<?php
								$is_kadaluarsa = !empty($r['is_kadaluarsa']) || (!empty($r['form_aktif']) && !empty($r['form_ditutup']) && strtotime($r['form_ditutup']) < time());
								?>
								<?php if ($is_kadaluarsa): ?>
									<span class="tag warn" style="font-size:11.5px; padding:3px 9px; font-weight:700">
										Kadaluarsa
									</span>
									<span class="muted" style="font-size:11px; display:block; margin-top:3px; color:var(--crit)">Lewat deadline</span>
								<?php elseif ($r['form_aktif']): ?>
									<span class="tag on" style="font-size:11.5px; padding:3px 9px; font-weight:700">
										&#10003; Terbuka
									</span>
									<span class="muted" style="font-size:11px; display:block; margin-top:3px">Menerima lamaran</span>
								<?php else: ?>
									<span class="tag off" style="font-size:11.5px; padding:3px 9px; font-weight:600">
										Ditutup
									</span>
									<span class="muted" style="font-size:11px; display:block; margin-top:3px">Penerimaan nonaktif</span>
								<?php endif; ?>
							</td>

							<!-- Jumlah Pelamar Masuk -->
							<td style="padding:12px 14px; border-bottom:1px solid var(--border); text-align:center">
								<div style="font-size:16px; font-weight:700; color:var(--text)">
									<?= (int) $r['n_lamaran'] ?>
								</div>
								<div class="muted" style="font-size:11px">
									<?= (int) $r['jumlah_submit'] ?> submit
								</div>
							</td>

							<!-- Aksi Menu Dropdown (Kebab Menu titik 3) -->
							<td style="padding:12px 14px; border-bottom:1px solid var(--border); text-align:right; position:relative; overflow:visible">
								<div style="position:relative; display:inline-block">
									<button type="button" class="btn btn-sm btn-ghost mpr-menu-btn"
									        onclick="toggleMenu(event, 'menu-post-<?= (int) $r['id_posting'] ?>')"
									        style="padding:2px 8px; font-weight:700; line-height:1; font-size:16px; border-radius:6px; cursor:pointer"
									        title="Pilihan Aksi">&#8942;</button>

									<div id="menu-post-<?= (int) $r['id_posting'] ?>" class="mpr-dropdown"
									     style="display:none; position:absolute; right:0; top:calc(100% + 4px); background:var(--surface); border:1px solid var(--border); border-radius:8px; box-shadow:var(--shadow); min-width:170px; z-index:99; text-align:left; overflow:hidden">

										<!-- Statistik Portal Funnel -->
										<a href="<?= site_url('postings/stats/' . (int) $r['id_posting']) ?>"
										   style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--text); text-decoration:none; border-bottom:1px solid var(--surface-2)">
											<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
											<span>Statistik Portal</span>
										</a>

										<!-- Buka Pipeline Lamaran (jika ada id_req) -->
										<?php if (!empty($r['id_req'])): ?>
											<a href="<?= site_url('pipeline/index/' . (int) $r['id_req']) ?>"
											   style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--accent); font-weight:600; text-decoration:none; border-bottom:1px solid var(--surface-2)">
												<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
												<span>Buka Pipeline</span>
											</a>
										<?php endif; ?>

										<!-- Toggle Buka / Tutup Form -->
										<?php $disabled = in_array($r['status_req'], array('Dibatalkan', 'Kadaluarsa')); ?>
										<?php if (!$disabled || $r['form_aktif']): ?>
											<?= form_open(site_url('postings/toggle/' . (int) $r['id_posting']), array('style' => 'margin:0; padding:0')) ?>
												<input type="hidden" name="form_aktif" value="<?= ($r['form_aktif'] && !$is_kadaluarsa) ? '0' : '1' ?>">
												<?php if ($r['form_aktif'] && !$is_kadaluarsa): ?>
													<button type="submit" style="width:100%; border:none; background:none; display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--warn-ink); text-align:left; cursor:pointer"
													        onclick="return confirm('Tutup form lamaran publik ini?')">
														<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
														<span>Tutup Form</span>
													</button>
												<?php else: ?>
													<button type="submit" style="width:100%; border:none; background:none; display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--accent-ink); text-align:left; cursor:pointer">
														<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
														<span><?= $is_kadaluarsa ? 'Buka Kembali' : 'Buka Form' ?></span>
													</button>
												<?php endif; ?>
											<?= form_close() ?>

											<!-- Perpanjang Masa Aktif Lowongan -->
											<?= form_open(site_url('postings/extend/' . (int) $r['id_posting']), array('style' => 'margin:0; padding:0; border-top:1px solid var(--surface-2)')) ?>
												<input type="hidden" name="durasi_hari" value="14">
												<input type="hidden" name="redirect_to" value="postings">
												<button type="submit" style="width:100%; border:none; background:none; display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--accent); text-align:left; cursor:pointer"
												        onclick="return confirm('Perpanjang lowongan +14 hari?')">
													<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
													<span>Perpanjang (+14 Hari)</span>
												</button>
											<?= form_close() ?>
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

	<!-- Paginasi -->
	<?php if ($pages > 1): ?>
		<div style="padding:12px 20px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); flex-wrap:wrap; gap:8px">
			<span class="muted" style="font-size:12px">
				Halaman <strong><?= $page ?></strong> dari <strong><?= $pages ?></strong> (Total <?= (int) $total ?> posting)
			</span>
			<div style="display:flex; gap:6px">
				<?php if ($page > 1): ?>
					<a href="?page=<?= $page - 1 ?>" class="btn-sm btn-ghost" style="font-size:12px">&larr; Sebelumnya</a>
				<?php endif; ?>
				<?php if ($page < $pages): ?>
					<a href="?page=<?= $page + 1 ?>" class="btn-sm btn-ghost" style="font-size:12px">Berikutnya &rarr;</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<script>
function copyPublicUrl(url, btn) {
	if (!navigator.clipboard) {
		var input = document.createElement('input');
		input.value = url;
		document.body.appendChild(input);
		input.select();
		document.execCommand('copy');
		document.body.removeChild(input);
	} else {
		navigator.clipboard.writeText(url);
	}

	var origHtml = btn.innerHTML;
	btn.innerHTML = '<span style="color:var(--accent); font-weight:600">&#10003; Tersalin!</span>';
	setTimeout(function() {
		btn.innerHTML = origHtml;
	}, 1800);
}
</script>

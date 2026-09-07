<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $qs = function ($extra = array()) use ($f) {
	return '?' . http_build_query(array_merge(array_filter((array) $f), $extra));
}; ?>

<div style="margin-bottom:24px">
	<!-- Page Header -->
	<div style="margin-bottom:18px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Manpower Requisition (MPR)</div>
			<h1 style="margin:0 0 4px; font-size:22px; font-weight:700">Daftar Permintaan Tenaga Kerja</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Ditemukan <strong><?= (int) $total ?></strong> dokumen MPR di database. Kelola dan pantau status persetujuan serta pemenuhan formasi.
			</p>
		</div>
	</div>

	<!-- Ringkasan Metrik / Quick Stat Cards (Di Atas Filter) -->
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:20px">
		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--surface-2); display:grid; place-items:center; flex:none; color:var(--accent)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Total MPR</div>
				<div style="font-size:20px; font-weight:700; color:var(--text); line-height:1.2; margin-top:2px"><?= (int) ($stats['total'] ?? $total) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">dokumen</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--accent-soft); display:grid; place-items:center; flex:none; color:var(--accent-ink)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Aktif &amp; Sourcing</div>
				<div style="font-size:20px; font-weight:700; color:var(--accent-ink); line-height:1.2; margin-top:2px"><?= (int) ($stats['n_aktif'] ?? 0) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">lowongan</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--warn-soft); display:grid; place-items:center; flex:none; color:var(--warn)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Review &amp; Menunggu</div>
				<div style="font-size:20px; font-weight:700; color:var(--warn); line-height:1.2; margin-top:2px"><?= (int) ($stats['n_review'] ?? 0) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">dokumen</span></div>
			</div>
		</div>

		<div class="card" style="padding:14px 16px; display:flex; align-items:center; gap:12px; margin:0">
			<div style="width:38px; height:38px; border-radius:10px; background:var(--good-soft); display:grid; place-items:center; flex:none; color:var(--good)">
				<svg style="width:18px; height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			</div>
			<div>
				<div class="muted" style="font-size:11.5px; font-weight:600">Terpenuhi &amp; Selesai</div>
				<div style="font-size:20px; font-weight:700; color:var(--good); line-height:1.2; margin-top:2px"><?= (int) ($stats['n_terpenuhi'] ?? 0) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted)">dokumen</span></div>
			</div>
		</div>
	</div>

	<!-- Filter Bar (Di Bawah Card) -->
	<div class="card" style="padding:14px 18px; margin-bottom:20px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px">
		<form method="get" action="<?= site_url('requisitions') ?>" style="margin:0">
			<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
				<div>
					<label style="margin:0 0 4px; font-size:11.5px; font-weight:600" class="eyebrow">Status</label>
					<select name="status" style="width:auto; padding:6px 10px; font-size:13px; background:var(--surface)">
						<option value="">Semua Status</option>
						<?php foreach (array('Draft','Review_HR','Menunggu_BOD','Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian','Terpenuhi','Ditolak_HR','Ditolak_BOD','Dibatalkan','Kadaluarsa') as $s): ?>
							<option value="<?= $s ?>" <?= (isset($f['status']) && $f['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label style="margin:0 0 4px; font-size:11.5px; font-weight:600" class="eyebrow">Departemen</label>
					<select name="dept" style="width:auto; min-width:150px; padding:6px 10px; font-size:13px; background:var(--surface)">
						<option value="">Semua Departemen</option>
						<?php foreach ($depts as $d): ?>
							<option value="<?= (int) $d['id_departemen'] ?>" <?= (isset($f['dept']) && $f['dept'] == $d['id_departemen']) ? 'selected' : '' ?>><?= html_escape($d['nama']) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label style="margin:0 0 4px; font-size:11.5px; font-weight:600" class="eyebrow">Posisi</label>
					<select name="posisi" style="width:auto; min-width:150px; padding:6px 10px; font-size:13px; background:var(--surface)">
						<option value="">Semua Posisi</option>
						<?php foreach ($positions as $p): ?>
							<option value="<?= (int) $p['id_posisi'] ?>" <?= (isset($f['posisi']) && $f['posisi'] == $p['id_posisi']) ? 'selected' : '' ?>><?= html_escape($p['nama_posisi']) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label style="margin:0 0 4px; font-size:11.5px; font-weight:600" class="eyebrow">Periode Dibuat</label>
					<div style="display:flex; gap:6px">
						<input type="date" name="dari" value="<?= html_escape(isset($f['dari']) ? $f['dari'] : '') ?>" style="width:135px; padding:6px 8px; font-size:13px; background:var(--surface)">
						<input type="date" name="sampai" value="<?= html_escape(isset($f['sampai']) ? $f['sampai'] : '') ?>" style="width:135px; padding:6px 8px; font-size:13px; background:var(--surface)">
					</div>
				</div>
				<div style="display:flex; gap:6px; margin-left:auto">
					<button type="submit" class="btn btn-sm btn-primary" style="height:35px; padding:0 14px">Filter</button>
					<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') ?>" style="height:35px; padding:0 12px; line-height:33px">Reset</a>
				</div>
			</div>
		</form>
	</div>

	<!-- Tabel Data MPR (Di Bawah Filter) -->
	<div class="card" style="padding:0; overflow:hidden">
		<div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); flex-wrap:wrap; gap:10px">
			<div style="display:flex; align-items:center; gap:10px">
				<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
				<div>
					<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
						Daftar Dokumen Permintaan Tenaga Kerja
					</h3>
					<span class="muted" style="font-size:12px">Menampilkan <?= count($rows ?? array()) ?> dari <?= (int) $total ?> total dokumen</span>
				</div>
			</div>
		</div>

		<?php if ( ! $rows): ?>
			<div style="padding:40px 20px; text-align:center; background:var(--surface)">
				<p class="muted" style="margin:0">Tidak ada data MPR yang sesuai dengan filter.</p>
			</div>
		<?php else: ?>
			<div class="table-responsive-fit" style="overflow-x:visible">
				<table style="margin:0; width:100%; table-layout:fixed">
					<thead>
						<tr>
							<th style="width:35%; padding:12px 14px">Posisi Lowongan</th>
							<th style="width:20%; padding:12px 14px">Pemohon</th>
							<th style="width:25%; padding:12px 14px">Penempatan</th>
							<th style="width:12%; text-align:center; padding:12px 14px">Status</th>
							<th style="width:8%; text-align:right; padding:12px 14px">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $r): ?>
						<tr>
							<td style="padding:12px 14px">
								<strong style="color:var(--text)"><?= html_escape($r['nama_posisi']) ?></strong>
								<div class="muted" style="font-size:11.5px; margin-top:2px"><?= html_escape($r['no_mpr']) ?></div>
							</td>
							<td style="padding:12px 14px"><?= html_escape($r['pemohon']) ?></td>
							<td style="padding:12px 14px"><span class="tag" style="word-break:break-word; white-space:normal; display:inline-block; max-width:100%"><?= html_escape($r['tipe_penempatan'] . ($r['nama_outlet'] ? ' / ' . $r['nama_outlet'] : '')) ?></span></td>
							<td style="padding:12px 14px; text-align:center">
								<span class="tag <?= in_array($r['status_req'], array('Sourcing','Approved','Terpenuhi','Terpenuhi_Sebagian')) ? 'on' : (in_array($r['status_req'], array('Review_HR','Menunggu_BOD')) ? 'warn' : (in_array($r['status_req'], array('Ditolak_HR','Ditolak_BOD','Dibatalkan')) ? 'crit' : 'off')) ?>">
									<?= html_escape($r['status_req']) ?>
								</span>
							</td>
							<td style="padding:12px 14px; text-align:right; position:relative; overflow:visible">
								<?php
								$can_view_pipeline = (current_user_dept() === NULL || (isset($r['id_departemen']) && (int) $r['id_departemen'] === (int) current_user_dept()));
								if ($can_view_pipeline): ?>
									<div style="position:relative; display:inline-block">
										<button type="button" class="btn btn-sm btn-ghost mpr-menu-btn" onclick="toggleMenu(event, 'menu-<?= (int) $r['id_req'] ?>')" style="padding:2px 8px; font-weight:700; line-height:1; font-size:16px; border-radius:6px" title="Pilihan Aksi">&#8942;</button>
										<div id="menu-<?= (int) $r['id_req'] ?>" class="mpr-dropdown" style="display:none; position:absolute; right:0; top:calc(100% + 4px); background:var(--surface); border:1px solid var(--border); border-radius:8px; box-shadow:var(--shadow); min-width:160px; z-index:99; text-align:left; overflow:hidden">
											<a href="<?= site_url('requisitions/view/' . (int) $r['id_req']) ?>" style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--text); text-decoration:none; border-bottom:1px solid var(--surface-2)">
												<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
												<span>Lihat Detail</span>
											</a>
											<?php if ($can_view_pipeline): ?>
												<a href="<?= site_url('pipeline/index/' . (int) $r['id_req']) ?>" style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--accent); font-weight:600; text-decoration:none; border-bottom:1px solid var(--surface-2)">
													<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
													<span>Buka Pipeline</span>
												</a>
											<?php endif; ?>
											<a href="<?= site_url('export/candidates?req=' . (int) $r['id_req']) ?>" style="display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12.5px; color:var(--text); text-decoration:none">
												<svg style="width:13px; height:13px; flex:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
												<span>Export Pelamar</span>
											</a>
										</div>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($pages > 1): ?>
				<div style="display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-top:1px solid var(--border); background:var(--surface)">
					<span class="muted" style="font-size:12.5px">Halaman <?= $page ?> dari <?= $pages ?></span>
					<div style="display:flex; gap:6px">
						<?php if ($page > 1): ?>
							<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') . $qs(array('page' => $page - 1)) ?>">&larr; Sebelumnya</a>
						<?php endif; ?>
						<?php if ($page < $pages): ?>
							<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') . $qs(array('page' => $page + 1)) ?>">Selanjutnya &rarr;</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

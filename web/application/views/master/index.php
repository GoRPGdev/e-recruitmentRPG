<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: master/index.php -- Pengelolaan Master Data Referensi Organisasi RPG
 *
 * Fungsi:
 * - Menangani CRUD master referensi: Departemen, Posisi/Jabatan, Lokasi Kerja, Sumber Lamaran, dan Alasan Penolakan.
 * - Mendukung penyusunan urutan posisi/hirarki secara interaktif (drag & drop).
 * - Menjaga integritas data referensi dengan pendekatan soft delete (is_aktif = 0).
 */
?>

<div style="margin-bottom:24px">
	<!-- Page Header -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<span class="eyebrow" style="margin:0; font-size:11px">Konfigurasi Sistem</span>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Master Data RPG</span>
			</div>
			<h1 style="margin:0; font-size:24px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				Master Data &mdash; <?= html_escape($types[$t]['label']) ?>
			</h1>
			<p class="muted" style="margin:4px 0 0; font-size:13.5px">
				<?= html_escape($types[$t]['desc'] ?? 'Kelola entitas data master dan referensi sistem rekrutmen.') ?>
			</p>
		</div>

		<div>
			<button type="button" class="btn btn-primary" onclick="openAddModal()" style="display:inline-flex; align-items:center; gap:6px; font-weight:600; padding:8px 16px; border-radius:8px">
				<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah <?= html_escape($types[$t]['label']) ?></span>
			</button>
		</div>
	</div>

	<!-- Sub-Menu Navigation Tabs / Cards -->
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:20px">
		<?php
		$menu_icons = array(
			'posisi'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
			'departemen'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
			'level_organisasi' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6"/>',
			'outlet'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
			'tahap'            => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
			'dokumen'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
			'remark'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>',
		);
		foreach ($types as $k => $v):
			if ( ! empty($v['hidden'])) continue;
			$is_curr = ($k === $t);
		?>
			<a href="<?= site_url('master/index/' . $k) ?>" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-radius:10px; border:1px solid <?= $is_curr ? 'var(--accent)' : 'var(--border)' ?>; background:<?= $is_curr ? 'var(--accent-soft)' : 'var(--surface)' ?>; transition:all .15s ease; box-shadow:<?= $is_curr ? '0 1px 3px rgba(0,0,0,0.06)' : 'none' ?>">
				<div style="display:flex; align-items:center; gap:10px; min-width:0">
					<div style="width:32px; height:32px; border-radius:8px; display:grid; place-items:center; background:<?= $is_curr ? 'var(--accent)' : 'var(--surface-2)' ?>; color:<?= $is_curr ? 'var(--accent-contrast)' : 'var(--text-muted)' ?>; flex:none">
						<svg style="width:16px; height:16px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
							<?= $menu_icons[$k] ?? '' ?>
						</svg>
					</div>
					<div style="min-width:0">
						<div style="font-size:13.5px; font-weight:<?= $is_curr ? '700' : '600' ?>; color:<?= $is_curr ? 'var(--accent-ink)' : 'var(--text)' ?>; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
							<?= html_escape($v['label']) ?>
						</div>
						<div class="muted" style="font-size:11px; margin-top:1px">
							CRUD Master
						</div>
					</div>
				</div>
				<span class="tag <?= $is_curr ? 'on' : '' ?>" style="font-size:11px; margin-left:6px; flex:none; padding:2px 7px">
					<?= $counts[$k] ?? 0 ?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</div>

<?php
// Kolom tabel per tipe
$col = array(
	'posisi'           => array('Nama Posisi', 'Departemen', 'Level Organisasi', 'Template Jabatan (HR)'),
	'departemen'       => array('Kode Departemen', 'Nama Departemen'),
	'level_organisasi' => array('', 'Kode Level', 'Nama Level Organisasi', 'Keterangan'),
	'outlet'           => array('Kode Outlet', 'Nama Outlet / Cabang', 'Brand', 'Wilayah / Region'),
	'tahap'            => array('Kode Stage', 'Nama Tahap', 'Tipe (Report)', 'Izin Sisipan', 'Terminal', 'Sistem'),
	'dokumen'          => array('Nama Dokumen Persyaratan', 'Kategori Berkas', 'Tingkat Sensitif PDP', 'Wajib Default'),
	'remark'           => array('Tahap Alur', 'Kode', 'Label Keputusan', 'Efek Status Seleksi', 'Urutan'),
);
$levels = ! empty($levels) ? $levels : array('MP','Staff','Staff_Krusial','Spv','Manager','Senior_Manager');
$kat    = array('IDENTITAS','PENDIDIKAN','FINANSIAL','LAMARAN');
$sens   = array('UMUM','IDENTITAS','FINANSIAL');
$tipe_tahap_list = ! empty($tipe_tahap_list) ? $tipe_tahap_list : array('SCREENING','KONTAK','FORM','TEST','INTERVIEW','OFFER','ONBOARD');
?>

<!-- ================= TABEL DATA UTAMA (FULL WIDTH FIT 1 LAYAR) ================= -->
<div class="card" style="padding:0; overflow:hidden; margin-bottom:24px">
	<div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); flex-wrap:wrap; gap:10px">
		<div style="display:flex; align-items:center; gap:10px">
			<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
			<div>
				<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
					Daftar Data <?= html_escape($types[$t]['label']) ?>
				</h3>
				<span class="muted" style="font-size:12px">Total <?= count($rows ?? array()) ?> data terdaftar pada sistem</span>
			</div>
		</div>
		<div style="display:flex; align-items:center; gap:8px">
			<?php if ($t === 'level_organisasi'): ?>
				<span class="tag info" style="font-size:11px" title="Klik dan geser baris tabel untuk mengubah urutan level organisasi">&#8645; Drag &amp; Drop Reorder</span>
			<?php endif; ?>
			<span class="tag on" style="font-size:11px">&#10003; Soft Delete Protected</span>
			<button type="button" class="btn btn-sm btn-primary" onclick="openAddModal()" style="display:inline-flex; align-items:center; gap:5px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>Tambah <?= html_escape($types[$t]['label']) ?></span>
			</button>
		</div>
	</div>

<div class="table-responsive-fit" style="overflow-x:visible">
		<table style="width:100%; border-collapse:collapse; table-layout:fixed">
			<thead>
				<tr>
					<?php foreach ($col[$t] as $ci => $c): ?>
						<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:left; background:var(--surface); border-bottom:1px solid var(--border)<?= ($t === 'level_organisasi' && $ci === 0) ? '; width:44px; text-align:center' : '' ?>">
							<?= $c ?>
						</th>
					<?php endforeach; ?>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:center; width:95px; background:var(--surface); border-bottom:1px solid var(--border)">
						Status
					</th>
					<th style="padding:12px 14px; font-size:12px; font-weight:600; text-align:right; width:140px; background:var(--surface); border-bottom:1px solid var(--border)">
						Aksi
					</th>
				</tr>
			</thead>
			<tbody <?= ($t === 'level_organisasi') ? 'id="level-organisasi-tbody"' : '' ?>>
				<?php if (empty($rows)): ?>
					<tr>
						<td colspan="<?= count($col[$t]) + 2 ?>" style="text-align:center; padding:40px 16px" class="muted">
							<div style="font-size:28px; margin-bottom:8px">&#128194;</div>
							<div style="font-size:14px; font-weight:600; color:var(--text)">Belum ada data <?= html_escape(strtolower($types[$t]['label'])) ?></div>
							<div style="font-size:12.5px; margin-top:4px">Klik tombol "Tambah <?= html_escape($types[$t]['label']) ?>" untuk menambahkan data baru melalui form pop up.</div>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($rows as $r): ?>
						<?php
							$row_id = (int) ($r['id_posisi'] ?? ($r['id_departemen'] ?? ($r['id_level_organisasi'] ?? ($r['id_outlet'] ?? ($r['id_stage'] ?? ($r['id_dokumen'] ?? ($r['id_remark'] ?? 0)))))));
							$json_data = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
						?>
						<tr <?= ($t === 'level_organisasi') ? 'class="level-org-row" data-id="' . $row_id . '" draggable="true"' : '' ?> style="<?= empty($r['is_aktif']) ? 'opacity:.55; background:var(--surface-2);' : '' ?><?= ($t === 'level_organisasi') ? ' cursor:grab; transition:background .15s ease;' : '' ?>">
							<?php if ($t === 'posisi'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<div style="font-weight:600; color:var(--text); word-break:break-word"><?= html_escape($r['nama_posisi']) ?></div>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:13px; word-break:break-word">
									<?= html_escape($r['departemen']) ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<span class="tag" style="font-size:11px"><?= html_escape($r['level_posisi']) ?></span>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12px">
									<?php
									$has_jd   = !empty(trim($r['job_desc'] ?? ''));
									$has_kual = !empty(trim($r['kualifikasi'] ?? ''));
									$has_req  = !empty($r['pendidikan_minimal']) || !empty($r['pengalaman_minimal_tahun']);
									?>
									<?php if ($has_jd && $has_kual): ?>
										<span class="tag on" style="font-size:11px" title="Job Desc & Kualifikasi lengkap terisi">&#10003; Lengkap</span>
									<?php elseif ($has_jd || $has_kual || $has_req): ?>
										<span class="tag" style="font-size:11px; color:var(--warn-ink); background:var(--warn-soft); border-color:var(--warn)" title="Sebagian template terisi">Parsial</span>
									<?php else: ?>
										<span class="muted" style="font-size:11.5px">Belum diatur</span>
									<?php endif; ?>
									<?php if (!empty($r['pendidikan_minimal'])): ?>
										<span class="muted" style="display:block; font-size:11px; margin-top:2px"><?= html_escape($r['pendidikan_minimal']) ?><?= !empty($r['pengalaman_minimal_tahun']) ? ', ' . (int)$r['pengalaman_minimal_tahun'] . ' thn' : '' ?></span>
									<?php endif; ?>
								</td>

							<?php elseif ($t === 'departemen'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<code><?= html_escape($r['kode']) ?></code>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-weight:600; color:var(--text); word-break:break-word">
									<?= html_escape($r['nama']) ?>
								</td>

							<?php elseif ($t === 'level_organisasi'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); width:44px; text-align:center; user-select:none">
									<div style="display:inline-flex; align-items:center; justify-content:center; cursor:grab" title="Klik dan geser untuk mengubah urutan">
										<svg style="width:13px; height:13px; color:var(--text-muted); opacity:.6; flex-shrink:0" viewBox="0 0 24 24" fill="currentColor">
											<circle cx="9" cy="5" r="1.6"/><circle cx="15" cy="5" r="1.6"/>
											<circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/>
											<circle cx="9" cy="19" r="1.6"/><circle cx="15" cy="19" r="1.6"/>
										</svg>
									</div>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<code><?= html_escape($r['kode_level']) ?></code>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-weight:600; color:var(--text); word-break:break-word">
									<?= html_escape($r['nama_level']) ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12.5px; color:var(--text-muted); word-break:break-word">
									<?= html_escape($r['keterangan'] ?: '-') ?>
								</td>

							<?php elseif ($t === 'outlet'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<code><?= html_escape($r['kode_outlet']) ?></code>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-weight:600; color:var(--text); word-break:break-word">
									<?= html_escape($r['nama_outlet']) ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:13px">
									<?= html_escape($r['brand'] ?: '-') ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:13px">
									<?= html_escape($r['region'] ?: '-') ?>
								</td>

							<?php elseif ($t === 'tahap'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<code><?= html_escape($r['kode_stage']) ?></code>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-weight:600; color:var(--text); word-break:break-word">
									<?= html_escape($r['nama_tahap']) ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<span class="tag info" style="font-size:11px"><?= html_escape($r['tipe_tahap']) ?></span>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12.5px">
									<?= ! empty($r['is_sisipan_allowed']) ? '<span style="color:var(--accent); font-weight:600">&#10003; Boleh</span>' : '<span class="muted">&#10005; Tidak</span>' ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12.5px">
									<?= ! empty($r['is_terminal']) ? '<span class="tag off" style="font-size:10.5px">Terminal</span>' : '<span class="muted">-</span>' ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12px">
									<?= ! empty($r['is_sistem']) ? '<span class="tag on" style="font-size:10.5px" title="Tahap bawaan sistem">&#128274; Sistem</span>' : '<span class="muted">Kustom</span>' ?>
								</td>

							<?php elseif ($t === 'dokumen'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-weight:600; color:var(--text); word-break:break-word">
									<?= html_escape($r['nama_dokumen']) ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<span class="tag" style="font-size:11px"><?= html_escape($r['kategori']) ?></span>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<span class="tag <?= $r['tingkat_sensitif'] === 'FINANSIAL' ? 'warn' : ($r['tingkat_sensitif'] === 'IDENTITAS' ? 'info' : '') ?>" style="font-size:11px">
										<?= html_escape($r['tingkat_sensitif']) ?>
									</span>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12.5px">
									<?= $r['is_mandatory_default'] ? '<span style="color:var(--accent); font-weight:600">&#10003; Wajib</span>' : '<span class="muted">Opsional</span>' ?>
								</td>

							<?php elseif ($t === 'remark'): ?>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:13px; word-break:break-word">
									<?= html_escape($r['nama_tahap'] ?? '-') ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<code><?= html_escape($r['kode_remark']) ?></code>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-weight:600; color:var(--text); word-break:break-word">
									<?= html_escape($r['label']) ?>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border)">
									<span class="tag <?= in_array($r['efek_status'], array('HIRED','LANJUT')) ? 'on' : 'off' ?>" style="font-size:11px">
										<?= html_escape($r['efek_status']) ?>
									</span>
								</td>
								<td style="padding:11px 14px; border-bottom:1px solid var(--border); font-size:12.5px">
									<?= (int) $r['urutan'] ?>
								</td>
							<?php endif; ?>

							<!-- Status Aktif / Nonaktif -->
							<td style="padding:11px 14px; border-bottom:1px solid var(--border); text-align:center">
								<span class="tag <?= $r['is_aktif'] ? 'on' : 'off' ?>" style="font-size:11px">
									<?= $r['is_aktif'] ? 'Aktif' : 'Nonaktif' ?>
								</span>
							</td>

							<!-- Kolom Aksi -->
							<td style="padding:11px 14px; border-bottom:1px solid var(--border); text-align:right; white-space:nowrap">
								<button type="button" class="btn-sm btn-ghost" onclick='openEditModal(<?= $json_data ?>)' style="display:inline-flex; align-items:center; gap:4px; padding:4px 8px; font-size:12px; cursor:pointer" title="Edit data ini">
									<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
									<span>Edit</span>
								</button>
								<?= form_open(site_url('master/toggle/' . $t), array('class' => 'inline', 'style' => 'display:inline; margin-left:4px', 'onsubmit' => "return confirm('" . ($r['is_aktif'] ? 'Nonaktifkan data ini? Data tidak akan tampil di pilihan aktif.' : 'Aktifkan kembali data ini?') . "');")) ?>
									<input type="hidden" name="id" value="<?= $row_id ?>">
									<input type="hidden" name="is_aktif" value="<?= $r['is_aktif'] ? 0 : 1 ?>">
									<button type="submit" class="btn-sm btn-ghost" style="padding:4px 8px; font-size:12px; color:<?= $r['is_aktif'] ? 'var(--warn-ink)' : 'var(--accent)' ?>" title="<?= $r['is_aktif'] ? 'Nonaktifkan item ini' : 'Aktifkan kembali item ini' ?>">
										<?= $r['is_aktif'] ? 'Nonaktif' : 'Aktifkan' ?>
									</button>
								<?= form_close() ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- ================= MODAL DIALOG POPUP FORM TAMBAH / EDIT ================= -->
<style>
	#dlg-master {
		border: 1px solid var(--border);
		border-radius: 14px;
		padding: 0;
		max-width: 660px;
		width: 94%;
		max-height: 88vh;
		background: var(--surface);
		color: var(--text);
		box-shadow: 0 20px 48px rgba(0, 0, 0, 0.28);
		overflow: hidden;
	}
	#dlg-master[open] {
		display: flex;
		flex-direction: column;
	}
	#dlg-master::backdrop {
		background: rgba(12, 18, 14, 0.55);
		backdrop-filter: blur(3px);
	}
	.modal-header-bar {
		padding: 16px 24px;
		border-bottom: 1px solid var(--border);
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--surface-2);
		flex: none;
	}
	.modal-form-scroll {
		padding: 20px 24px;
		overflow-y: auto;
		flex: 1;
		min-height: 0;
	}
	.modal-form-scroll::-webkit-scrollbar {
		width: 6px;
	}
	.modal-form-scroll::-webkit-scrollbar-thumb {
		background: var(--border);
		border-radius: 4px;
	}
	.modal-footer-dock {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		gap: 10px;
		padding: 14px 24px;
		border-top: 1px solid var(--border);
		background: var(--surface-2);
		flex: none;
	}
</style>

<dialog id="dlg-master">
	<!-- Modal Header (Fixed Top) -->
	<div class="modal-header-bar">
		<div>
			<h3 id="modal-title" style="margin:0; font-size:16px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px">
				Tambah <?= html_escape($types[$t]['label']) ?> Baru
			</h3>
			<span id="modal-sub" class="muted" style="font-size:12px; margin-top:2px; display:block">
				Lengkapi formulir di bawah ini untuk menyimpan data.
			</span>
		</div>
		<button type="button" onclick="closeMasterModal()" style="background:none; border:none; color:var(--text-muted); font-size:24px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px; transition:color .15s" title="Tutup pop up">&times;</button>
	</div>

	<!-- Modal Form (Wraps Body & Docked Footer) -->
	<?= form_open(site_url('master/save/' . $t), array('id' => 'form-master', 'style' => 'display:flex; flex-direction:column; flex:1; min-height:0; margin:0')) ?>
		<!-- Modal Body Form (Scrollable Area) -->
		<div class="modal-form-scroll">
			<?= validation_errors('<div class="flash err" style="margin-bottom:14px; font-size:12.5px; padding:8px 12px">', '</div>') ?>

			<!-- Hidden Primary Key Field -->
			<input type="hidden" name="id" id="field-id" value="">
			<input type="hidden" name="id_posisi" id="field-id-posisi" value="">
			<input type="hidden" name="id_departemen" id="field-id-departemen" value="">
			<input type="hidden" name="id_level_organisasi" id="field-id-level-organisasi" value="">
			<input type="hidden" name="id_outlet" id="field-id-outlet" value="">
			<input type="hidden" name="id_stage" id="field-id-stage-pk" value="">
			<input type="hidden" name="id_dokumen" id="field-id-dokumen" value="">
			<input type="hidden" name="id_remark" id="field-id-remark" value="">

			<div style="display:flex; flex-direction:column; gap:14px">

			<!-- ================= FORM POSISI ================= -->
			<?php if ($t === 'posisi'): ?>
				<div>
					<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Nama Posisi / Jabatan <span style="color:var(--crit)">*</span></label>
					<input type="text" name="nama_posisi" id="field-nama-posisi" placeholder="Mis. SPV Area, Staff Finance" required style="width:100%">
				</div>

				<div style="grid-template-columns:1fr 1fr; display:grid; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Departemen <span style="color:var(--crit)">*</span></label>
						<select name="id_departemen" id="field-id-dept" required style="width:100%">
							<option value="">-- Pilih Departemen --</option>
							<?php foreach ($depts as $d): ?>
								<option value="<?= (int) $d['id_departemen'] ?>">
									<?= html_escape($d['nama']) ?> (<?= html_escape($d['kode']) ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Level Organisasi <span style="color:var(--crit)">*</span></label>
						<select name="level_posisi" id="field-level-posisi" required style="width:100%">
							<?php foreach ($levels as $l): ?>
								<?php $val = is_array($l) ? ($l["kode_level"] ?? "") : $l; ?>
								<?php $lbl = is_array($l) ? (($l["nama_level"] ?? $val) . " (" . $val . ")") : $l; ?>
								<option value="<?= html_escape($val) ?>"><?= html_escape($lbl) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div style="grid-column: span 2">
						<div class="muted" style="font-size:12px; padding:6px 10px; background:var(--surface-2); border:1px solid var(--border); border-radius:6px">
							<span style="color:var(--accent); font-weight:600">&#10003; Alur Seleksi:</span> Otomatis menggunakan <strong>Alur Standar Rekrutmen RPG</strong>
						</div>
					</div>
				</div>

				<!-- Master Job Specification (Template Jabatan HR) -->
				<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:14px; margin-top:2px">
					<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; padding-bottom:8px; border-bottom:1px dashed var(--border)">
						<div style="display:flex; align-items:center; gap:7px">
							<div style="width:7px; height:7px; border-radius:50%; background:var(--accent)"></div>
							<div style="font-size:13px; font-weight:700; color:var(--text)">Template Standar Jabatan (HR)</div>
						</div>
						<span class="tag" style="font-size:10.5px">Auto-fill di Form MPR & Form Publik</span>
					</div>

					<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
						<div>
							<label style="display:block; font-size:12px; font-weight:600; margin:0 0 5px">Pendidikan Minimal</label>
							<select name="pendidikan_minimal" id="field-pendidikan-posisi" style="width:100%; font-size:13px">
								<option value="">- Fleksibel / Tidak Ditentukan -</option>
								<option value="SMA/SMK">SMA/SMK Sederajat</option>
								<option value="D3">Diploma (D3)</option>
								<option value="S1">Sarjana (S1 / D4)</option>
								<option value="S2">Magister (S2)</option>
							</select>
						</div>
						<div>
							<label style="display:block; font-size:12px; font-weight:600; margin:0 0 5px">Pengalaman Minimal (Tahun)</label>
							<input type="number" name="pengalaman_minimal_tahun" id="field-pengalaman-posisi" min="0" max="30" placeholder="0 = Fresh graduate" style="width:100%; font-size:13px">
						</div>
					</div>

					<div style="margin-bottom:12px">
						<label style="display:block; font-size:12px; font-weight:600; margin:0 0 5px">Deskripsi Pekerjaan (Job Description)</label>
						<textarea name="job_desc" id="field-job-desc-posisi" rows="4" placeholder="Uraian tugas, fungsi utama, dan tanggung jawab kerja posisi ini..." style="width:100%; font-size:12.5px; line-height:1.45; resize:vertical"></textarea>
					</div>

					<div>
						<label style="display:block; font-size:12px; font-weight:600; margin:0 0 5px">Kualifikasi & Persyaratan Standar</label>
						<textarea name="kualifikasi" id="field-kualifikasi-posisi" rows="4" placeholder="Keahlian teknis, kompetensi soft skill, sertifikasi, atau kriteria khusus..." style="width:100%; font-size:12.5px; line-height:1.45; resize:vertical"></textarea>
					</div>
				</div>

			<!-- ================= FORM DEPARTEMEN ================= -->
			<?php elseif ($t === 'departemen'): ?>
				<div style="display:grid; grid-template-columns:140px 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Kode Dept <span style="color:var(--crit)">*</span></label>
						<input type="text" name="kode" id="field-kode-dept" placeholder="Mis. FIN" required style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Nama Departemen <span style="color:var(--crit)">*</span></label>
						<input type="text" name="nama" id="field-nama-dept" placeholder="Mis. Finance & Accounting" required style="width:100%">
					</div>
				</div>

			<!-- ================= FORM LEVEL ORGANISASI ================= -->
			<?php elseif ($t === 'level_organisasi'): ?>
				<div style="display:grid; grid-template-columns:140px 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Kode Level <span style="color:var(--crit)">*</span></label>
						<input type="text" name="kode_level" id="field-kode-level" placeholder="Mis. Staff, Spv" required style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Nama Level Organisasi <span style="color:var(--crit)">*</span></label>
						<input type="text" name="nama_level" id="field-nama-level" placeholder="Mis. Staff (Headquarters / Back-Office)" required style="width:100%">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:120px 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Urutan Jenjang</label>
						<input type="number" name="urutan" id="field-urutan-level" value="0" style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Keterangan / Deskripsi</label>
						<input type="text" name="keterangan" id="field-keterangan-level" placeholder="Mis. Tenaga kerja staf umum kantor pusat" style="width:100%">
					</div>
				</div>

			<!-- ================= FORM OUTLET ================= -->
			<?php elseif ($t === 'outlet'): ?>
				<div style="display:grid; grid-template-columns:140px 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Kode Outlet <span style="color:var(--crit)">*</span></label>
						<input type="text" name="kode" id="field-kode-outlet" placeholder="Mis. OUT-01" required style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Nama Outlet / Cabang <span style="color:var(--crit)">*</span></label>
						<input type="text" name="nama" id="field-nama-outlet" placeholder="Mis. RPG Central Park" required style="width:100%">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Brand Unit</label>
						<input type="text" name="brand" id="field-brand" placeholder="Mis. Ratu Pertiwi" style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Region / Wilayah</label>
						<input type="text" name="region" id="field-region" placeholder="Mis. Jabodetabek" style="width:100%">
					</div>
				</div>

			<!-- ================= FORM TAHAP SELEKSI ================= -->
			<?php elseif ($t === 'tahap'): ?>
				<div style="display:grid; grid-template-columns:140px 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Kode Stage <span style="color:var(--crit)">*</span></label>
						<input type="text" name="kode_stage" id="field-kode-stage" placeholder="Mis. PSY_TEST" required style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Nama Tahap Seleksi <span style="color:var(--crit)">*</span></label>
						<input type="text" name="nama_tahap" id="field-nama-stage" placeholder="Mis. Psikotes & Tes Logika" required style="width:100%">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Tipe Tahap (Sumbu Laporan) <span style="color:var(--crit)">*</span></label>
						<select name="tipe_tahap" id="field-tipe-stage" required style="width:100%">
							<?php foreach ($tipe_tahap_list as $tp): ?>
								<option value="<?= $tp ?>"><?= $tp ?></option>
							<?php endforeach; ?>
						</select>
						<span class="muted" style="font-size:11px; margin-top:3px; display:block">Tipe tahap wajib mengacu pada 7 sumbu tetap RPG (SCREENING, KONTAK, FORM, TEST, INTERVIEW, OFFER, ONBOARD).</span>
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:4px; padding:12px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px">
					<label style="display:flex; align-items:flex-start; gap:8px; font-size:12.5px; cursor:pointer">
						<input type="checkbox" name="is_sisipan_allowed" id="field-is-sisipan" value="1" checked style="margin-top:2px">
						<div>
							<span style="font-weight:600">Izin Sisipan Ad-Hoc</span>
							<span class="muted" style="display:block; font-size:11px">Boleh dipilih saat rekruter menambah tahap sisipan di pipeline.</span>
						</div>
					</label>
					<label style="display:flex; align-items:flex-start; gap:8px; font-size:12.5px; cursor:pointer">
						<input type="checkbox" name="is_terminal" id="field-is-terminal" value="1" style="margin-top:2px">
						<div>
							<span style="font-weight:600">Tahap Terminal</span>
							<span class="muted" style="display:block; font-size:11px">Merupakan titik akhir proses seleksi kandidat.</span>
						</div>
					</label>
				</div>

				<div id="notice-sistem-stage" style="display:none; padding:8px 12px; background:var(--accent-soft); border:1px solid var(--accent); border-radius:6px; font-size:12px; color:var(--accent-ink)">
					&#128274; <strong>Tahap Sistem:</strong> Kode stage dan tipe tahap terkunci untuk melindungi integritas reporting sistem.
				</div>

			<!-- ================= FORM DOKUMEN ================= -->
			<?php elseif ($t === 'dokumen'): ?>
				<div>
					<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Nama Dokumen Persyaratan <span style="color:var(--crit)">*</span></label>
					<input type="text" name="nama" id="field-nama-dokumen" placeholder="Mis. KTP, Ijazah Terakhir, NPWP" required style="width:100%">
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Kategori Berkas <span style="color:var(--crit)">*</span></label>
						<select name="kategori" id="field-kategori-dokumen" required style="width:100%">
							<?php foreach ($kat as $k): ?>
								<option value="<?= $k ?>"><?= $k ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Tingkat Sensitifitas PDP <span style="color:var(--crit)">*</span></label>
						<select name="tingkat_sensitif" id="field-tingkat-sensitif" required style="width:100%">
							<?php foreach ($sens as $s): ?>
								<option value="<?= $s ?>"><?= $s ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div style="margin-top:2px">
					<label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer">
						<input type="checkbox" name="is_mandatory_default" id="field-is-mandatory" value="1">
						<span>Wajib dikumpulkan secara default pada saat lamar</span>
					</label>
				</div>

			<!-- ================= FORM REMARK ALUR ================= -->
			<?php elseif ($t === 'remark'): ?>
				<div>
					<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Tahap Alur Seleksi <span style="color:var(--crit)">*</span></label>
					<select name="id_stage" id="field-id-stage" required style="width:100%">
						<option value="">-- Pilih Tahap --</option>
						<?php foreach ($all_stages as $st): ?>
							<option value="<?= (int) $st['id_stage'] ?>">
								<?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['tipe_tahap']) ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="display:grid; grid-template-columns:150px 1fr; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Kode Remark <span style="color:var(--crit)">*</span></label>
						<input type="text" name="kode_remark" id="field-kode-remark" placeholder="Mis. SCV_PASS" required style="width:100%">
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Label Keputusan <span style="color:var(--crit)">*</span></label>
						<input type="text" name="label" id="field-label-remark" placeholder="Mis. Lolos Screening Berkas" required style="width:100%">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 120px; gap:12px">
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Efek Status Seleksi <span style="color:var(--crit)">*</span></label>
						<select name="efek_status" id="field-efek-status" required style="width:100%">
							<?php foreach ($efek as $e): ?>
								<option value="<?= $e ?>"><?= $e ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">Urutan Tampil</label>
						<input type="number" name="urutan" id="field-urutan-remark" value="0" style="width:100%">
					</div>
				</div>
			<?php endif; ?>

			</div>
		</div>

		<!-- Modal Footer Aksi (Docked Bottom) -->
		<div class="modal-footer-dock">
			<button type="button" class="btn btn-ghost" onclick="closeMasterModal()">Batal</button>
			<button type="submit" id="btn-submit-modal" class="btn btn-primary" style="padding:9px 22px; font-weight:600">
				Simpan Data
			</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= JAVASCRIPT LOGIC MODAL POP UP ================= -->
<script>
var dlgMaster = document.getElementById('dlg-master');
var currentType = '<?= $t ?>';

function openAddModal() {
	if (!dlgMaster) return;

	// Reset form
	document.getElementById('form-master').reset();
	document.getElementById('field-id').value = '';
	if (document.getElementById('field-id-posisi')) document.getElementById('field-id-posisi').value = '';
	if (document.getElementById('field-id-departemen')) document.getElementById('field-id-departemen').value = '';
	if (document.getElementById('field-id-level-organisasi')) document.getElementById('field-id-level-organisasi').value = '';
	if (document.getElementById('field-id-outlet')) document.getElementById('field-id-outlet').value = '';
	if (document.getElementById('field-id-stage-pk')) document.getElementById('field-id-stage-pk').value = '';
	if (document.getElementById('field-id-dokumen')) document.getElementById('field-id-dokumen').value = '';
	if (document.getElementById('field-id-remark')) document.getElementById('field-id-remark').value = '';

	if (currentType === 'tahap') {
		var kodeSt = document.getElementById('field-kode-stage');
		var tipeSt = document.getElementById('field-tipe-stage');
		var noticeSt = document.getElementById('notice-sistem-stage');
		if (kodeSt) kodeSt.readOnly = false;
		if (tipeSt) tipeSt.disabled = false;
		if (noticeSt) noticeSt.style.display = 'none';
		if (document.getElementById('field-is-sisipan')) document.getElementById('field-is-sisipan').checked = true;
		if (document.getElementById('field-is-terminal')) document.getElementById('field-is-terminal').checked = false;
	}

	document.getElementById('modal-title').textContent = 'Tambah ' + <?= json_encode($types[$t]['label']) ?> + ' Baru';
	document.getElementById('modal-sub').textContent = 'Lengkapi formulir di bawah ini untuk menyimpan data baru.';
	document.getElementById('btn-submit-modal').textContent = '+ Tambahkan Data';

	dlgMaster.showModal();
}

function openEditModal(data) {
	if (!dlgMaster || !data) return;

	// Reset form awal
	document.getElementById('form-master').reset();

	var labelTipe = <?= json_encode($types[$t]['label']) ?>;
	var pkVal = data.id_posisi || data.id_departemen || data.id_level_organisasi || data.id_outlet || data.id_stage || data.id_dokumen || data.id_remark || '';

	document.getElementById('field-id').value = pkVal;
	document.getElementById('modal-title').textContent = 'Edit ' + labelTipe + ' #' + pkVal;
	document.getElementById('modal-sub').textContent = 'Perbarui informasi data ' + labelTipe.toLowerCase() + ' yang dipilih.';
	document.getElementById('btn-submit-modal').textContent = 'Simpan Perubahan';

	if (currentType === 'posisi') {
		document.getElementById('field-id-posisi').value = data.id_posisi || '';
		document.getElementById('field-nama-posisi').value = data.nama_posisi || '';
		document.getElementById('field-id-dept').value = data.id_departemen || '';
		document.getElementById('field-level-posisi').value = data.level_posisi || 'Staff';
		if (document.getElementById('field-default-flow')) document.getElementById('field-default-flow').value = data.default_flow || '';
		if (document.getElementById('field-pendidikan-posisi')) document.getElementById('field-pendidikan-posisi').value = data.pendidikan_minimal || '';
		if (document.getElementById('field-pengalaman-posisi')) document.getElementById('field-pengalaman-posisi').value = (data.pengalaman_minimal_tahun !== null && data.pengalaman_minimal_tahun !== undefined) ? data.pengalaman_minimal_tahun : '';
		if (document.getElementById('field-job-desc-posisi')) document.getElementById('field-job-desc-posisi').value = data.job_desc || '';
		if (document.getElementById('field-kualifikasi-posisi')) document.getElementById('field-kualifikasi-posisi').value = data.kualifikasi || '';
	} else if (currentType === 'departemen') {
		document.getElementById('field-id-departemen').value = data.id_departemen || '';
		document.getElementById('field-kode-dept').value = data.kode || '';
		document.getElementById('field-nama-dept').value = data.nama || '';
	} else if (currentType === 'level_organisasi') {
		document.getElementById('field-id-level-organisasi').value = data.id_level_organisasi || '';
		document.getElementById('field-kode-level').value = data.kode_level || '';
		document.getElementById('field-nama-level').value = data.nama_level || '';
		document.getElementById('field-urutan-level').value = (data.urutan !== undefined && data.urutan !== null) ? data.urutan : '0';
		document.getElementById('field-keterangan-level').value = data.keterangan || '';
	} else if (currentType === 'outlet') {
		document.getElementById('field-id-outlet').value = data.id_outlet || '';
		document.getElementById('field-kode-outlet').value = data.kode_outlet || '';
		document.getElementById('field-nama-outlet').value = data.nama_outlet || '';
		document.getElementById('field-brand').value = data.brand || '';
		document.getElementById('field-region').value = data.region || '';
	} else if (currentType === 'tahap') {
		document.getElementById('field-id-stage-pk').value = data.id_stage || '';
		var kodeSt = document.getElementById('field-kode-stage');
		var namaSt = document.getElementById('field-nama-stage');
		var tipeSt = document.getElementById('field-tipe-stage');
		var sisipSt = document.getElementById('field-is-sisipan');
		var termSt = document.getElementById('field-is-terminal');
		var noticeSt = document.getElementById('notice-sistem-stage');

		if (kodeSt) kodeSt.value = data.kode_stage || '';
		if (namaSt) namaSt.value = data.nama_tahap || '';
		if (tipeSt) tipeSt.value = data.tipe_tahap || 'SCREENING';
		if (sisipSt) sisipSt.checked = (data.is_sisipan_allowed == 1);
		if (termSt) termSt.checked = (data.is_terminal == 1);

		var isSistem = (data.is_sistem == 1);
		if (kodeSt) kodeSt.readOnly = isSistem;
		if (tipeSt) {
			tipeSt.disabled = isSistem;
			if (isSistem) {
				// Pastikan nilai tetap terkirim saat form submit meski select disabled
				var hiddenTipe = document.getElementById('field-hidden-tipe-stage');
				if (!hiddenTipe) {
					hiddenTipe = document.createElement('input');
					hiddenTipe.type = 'hidden';
					hiddenTipe.name = 'tipe_tahap';
					hiddenTipe.id = 'field-hidden-tipe-stage';
					tipeSt.parentNode.appendChild(hiddenTipe);
				}
				hiddenTipe.value = data.tipe_tahap || 'SCREENING';
			} else {
				var hiddenTipe = document.getElementById('field-hidden-tipe-stage');
				if (hiddenTipe) hiddenTipe.remove();
			}
		}
		if (noticeSt) noticeSt.style.display = isSistem ? 'block' : 'none';
	} else if (currentType === 'dokumen') {
		document.getElementById('field-id-dokumen').value = data.id_dokumen || '';
		document.getElementById('field-nama-dokumen').value = data.nama_dokumen || '';
		document.getElementById('field-kategori-dokumen').value = data.kategori || 'IDENTITAS';
		document.getElementById('field-tingkat-sensitif').value = data.tingkat_sensitif || 'UMUM';
		document.getElementById('field-is-mandatory').checked = (data.is_mandatory_default == 1);
	} else if (currentType === 'remark') {
		document.getElementById('field-id-remark').value = data.id_remark || '';
		document.getElementById('field-id-stage').value = data.id_stage || '';
		document.getElementById('field-kode-remark').value = data.kode_remark || '';
		document.getElementById('field-label-remark').value = data.label || '';
		document.getElementById('field-efek-status').value = data.efek_status || 'LANJUT';
		document.getElementById('field-urutan-remark').value = data.urutan || '0';
	}

	dlgMaster.showModal();
}

function closeMasterModal() {
	if (dlgMaster) dlgMaster.close();
}

// Tutup modal jika user mengklik backdrop luar
if (dlgMaster) {
	dlgMaster.addEventListener('click', function(e) {
		if (e.target === dlgMaster) {
			dlgMaster.close();
		}
	});
}

// Auto open modal jika ada parameter edit_id dari server
<?php if (!empty($edit_row)): ?>
window.addEventListener('DOMContentLoaded', function() {
	openEditModal(<?= json_encode($edit_row) ?>);
});
<?php endif; ?>

/* =========================================================================
   NATIVE HTML5 DRAG AND DROP REORDERING (KHUSUS LEVEL ORGANISASI)
   ========================================================================= */
<?php if ($t === 'level_organisasi'): ?>
document.addEventListener('DOMContentLoaded', function() {
	var tbody = document.getElementById('level-organisasi-tbody');
	if (!tbody) return;

	var draggedRow = null;
	var csrfTokenName = '<?= $this->security->get_csrf_token_name() ?>';
	var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
	var reorderUrl = '<?= site_url('master/reorder_level_organisasi') ?>';

	function updateSequenceNumbers() {
		var rows = tbody.querySelectorAll('.level-org-row');
		var addUrutanInput = document.getElementById('field-urutan-level');
		if (addUrutanInput && (!addUrutanInput.value || addUrutanInput.value === '0')) {
			addUrutanInput.value = rows.length + 1;
		}
	}

	function saveNewOrder() {
		var rows = tbody.querySelectorAll('.level-org-row');
		var orderIds = [];
		rows.forEach(function(row) {
			var id = row.getAttribute('data-id');
			if (id) orderIds.push(parseInt(id, 10));
		});

		if (orderIds.length === 0) return;

		var formData = new URLSearchParams();
		formData.append(csrfTokenName, csrfHash);
		orderIds.forEach(function(id) {
			formData.append('order[]', id);
		});

		fetch(reorderUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: formData.toString()
		})
		.then(function(res) {
			return res.json();
		})
		.then(function(data) {
			if (data && data.success) {
				showToast('Susunan urutan level organisasi berhasil diperbarui.', 'ok');
			} else {
				showToast('Gagal menyimpan urutan: ' + ((data && data.error) ? data.error : 'Terjadi kesalahan sistem'), 'err', 6000);
			}
		})
		.catch(function(err) {
			console.error('Reorder error:', err);
			showToast('Terjadi kesalahan jaringan saat menyimpan urutan.', 'err', 6000);
		});
	}

	function attachRowEvents(row) {
		row.addEventListener('dragstart', function(e) {
			draggedRow = row;
			row.classList.add('dragging');
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/plain', row.getAttribute('data-id'));
		});

		row.addEventListener('dragend', function() {
			row.classList.remove('dragging');
			var allRows = tbody.querySelectorAll('.level-org-row');
			allRows.forEach(function(r) {
				r.classList.remove('drag-over-top', 'drag-over-bottom');
			});
			draggedRow = null;
		});

		row.addEventListener('dragover', function(e) {
			e.preventDefault();
			if (!draggedRow || draggedRow === row) return;

			var rect = row.getBoundingClientRect();
			var midY = rect.top + rect.height / 2;
			if (e.clientY < midY) {
				row.classList.add('drag-over-top');
				row.classList.remove('drag-over-bottom');
			} else {
				row.classList.add('drag-over-bottom');
				row.classList.remove('drag-over-top');
			}
		});

		row.addEventListener('dragleave', function() {
			row.classList.remove('drag-over-top', 'drag-over-bottom');
		});

		row.addEventListener('drop', function(e) {
			e.preventDefault();
			row.classList.remove('drag-over-top', 'drag-over-bottom');
			if (!draggedRow || draggedRow === row) return;

			var rect = row.getBoundingClientRect();
			var midY = rect.top + rect.height / 2;
			if (e.clientY < midY) {
				tbody.insertBefore(draggedRow, row);
			} else {
				tbody.insertBefore(draggedRow, row.nextSibling);
			}

			updateSequenceNumbers();
			saveNewOrder();
		});
	}

	var rows = tbody.querySelectorAll('.level-org-row');
	rows.forEach(attachRowEvents);
});
<?php endif; ?>
</script>

<style>
.level-org-row.dragging {
	opacity: 0.4;
	background: var(--surface-2) !important;
}
.level-org-row.drag-over-top {
	border-top: 3px solid var(--primary) !important;
}
.level-org-row.drag-over-bottom {
	border-bottom: 3px solid var(--primary) !important;
}
</style>

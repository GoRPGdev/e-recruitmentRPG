<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Agregasi statistik kandidat untuk pipeline toolbar
$total_kandidat    = 0;
$total_in_progress = 0;
$total_hired       = 0;
$total_overdue     = 0;

foreach ($stages as $s) {
	foreach ($s['cards'] as $c) {
		$total_kandidat++;
		if ($c['status_global'] === 'In_Progress') {
			$total_in_progress++;
		} elseif ($c['status_global'] === 'Hired') {
			$total_hired++;
		}
		if ((int) $c['hari_di_tahap'] > 7) {
			$total_overdue++;
		}
	}
}
?>

<style>
/* CSS Modernisasi Pipeline, Kolom, Catatan, & Modal */
.pipeline-stat-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 14px 18px;
	display: flex;
	flex-direction: column;
	gap: 4px;
	transition: border-color .15s ease, box-shadow .15s ease;
}
.pipeline-stat-card:hover {
	border-color: var(--accent);
	box-shadow: var(--shadow-sm);
}
.stage-section {
	margin-top: 24px;
	border: 1px solid var(--border);
	border-radius: 10px;
	background: var(--surface);
	box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.stage-header-bar {
	background: var(--surface-2);
	border-bottom: 1px solid var(--border);
	padding: 12px 18px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap: 12px;
	border-top-left-radius: 9px;
	border-top-right-radius: 9px;
}
.candidate-row {
	border-bottom: 1px solid var(--border);
	transition: background-color .12s ease;
}
.candidate-row:hover {
	background-color: var(--surface-2);
}
.candidate-row:last-child {
	border-bottom: none;
}
.candidate-row td {
	vertical-align: top;
}

/* Card Catatan Tahap */
.pipeline-note-card {
	background: var(--surface-2);
	border: 1px solid var(--border);
	border-radius: 7px;
	padding: 8px 10px;
	margin-bottom: 6px;
	position: relative;
}
.pipeline-note-card:hover {
	border-color: var(--border-strong);
}
.pipeline-note-card.empty {
	background: transparent;
	border: 1px dashed var(--border);
	padding: 7px 10px;
	color: var(--text-faint);
	font-style: italic;
	font-size: 11.5px;
}
.pipeline-note-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 6px;
	margin-bottom: 4px;
	flex-wrap: wrap;
}
.pipeline-note-content {
	font-size: 12px;
	color: var(--text);
	line-height: 1.45;
	word-break: break-word;
	white-space: pre-wrap;
}

/* Tombol Aksi & Pill */
.icon-pill {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-size: 11.5px;
	font-weight: 600;
	padding: 4px 9px;
	border-radius: 6px;
	border: 1px solid var(--border);
	background: var(--surface);
	color: var(--text);
	text-decoration: none;
	cursor: pointer;
	transition: all .12s ease;
	line-height: 1.2;
}
.icon-pill:hover {
	border-color: var(--accent);
	color: var(--accent);
	background: var(--surface-2);
}
.btn-advance-submit {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 6px 12px;
	font-size: 11.5px;
	font-weight: 600;
	border-radius: 6px;
	border: none;
	background: var(--accent);
	color: var(--accent-contrast);
	cursor: pointer;
	transition: background-color .15s ease, transform .05s ease;
	white-space: nowrap;
}
.btn-advance-submit:hover {
	background: var(--accent-ink);
}
.btn-advance-submit:active {
	transform: scale(0.98);
}

/* Modal Dialog Standar RPG */
.rpg-modal {
	border: 1px solid var(--border);
	border-radius: 14px;
	padding: 0;
	max-width: 540px;
	width: 94%;
	max-height: 88vh;
	background: var(--surface);
	color: var(--text);
	box-shadow: 0 20px 48px rgba(0, 0, 0, 0.28);
	overflow: hidden;
}
.rpg-modal[open] {
	display: flex;
	flex-direction: column;
}
.rpg-modal::backdrop {
	background: rgba(12, 18, 14, 0.55);
	backdrop-filter: blur(3px);
}
.rpg-modal-header {
	padding: 14px 20px;
	border-bottom: 1px solid var(--border);
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: var(--surface-2);
	flex: none;
}
.rpg-modal-body {
	padding: 20px;
	overflow-y: auto;
	flex: 1;
	min-height: 0;
}
.rpg-modal-body::-webkit-scrollbar {
	width: 6px;
}
.rpg-modal-body::-webkit-scrollbar-thumb {
	background: var(--border);
	border-radius: 4px;
}
.rpg-modal-footer {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
	padding: 12px 20px;
	border-top: 1px solid var(--border);
	background: var(--surface-2);
	flex: none;
}
.rpg-modal-close {
	background: none;
	border: none;
	font-size: 20px;
	line-height: 1;
	color: var(--text-muted);
	cursor: pointer;
	padding: 2px 6px;
	border-radius: 4px;
}
.rpg-modal-close:hover {
	color: var(--text);
	background: var(--border);
}
</style>

<div style="margin-bottom:24px">
	<!-- HEADER UTAMA PIPELINE -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:16px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px">
				<a href="<?= site_url('requisitions') ?>" style="font-size:12px; text-decoration:none; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px">
					<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
					<span>Daftar Requisition</span>
				</a>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Papan Seleksi Rekrutmen</span>
			</div>

			<h1 style="margin:0 0 8px; font-size:24px; font-weight:700; color:var(--text); letter-spacing:-.02em">
				<?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?> &mdash; <?= html_escape($req['nama_posisi']) ?>
			</h1>

			<div class="muted" style="font-size:13px; display:flex; gap:12px; flex-wrap:wrap; align-items:center">
				<span>Departemen: <strong><?= html_escape($req['departemen'] ?: '-') ?></strong></span>
				<span>&bull;</span>
				<span>Kebutuhan: <strong><?= (int) $req['jumlah_dibutuhkan'] ?></strong> orang</span>
				<span>&bull;</span>
				<span>Terpenuhi: <strong style="color:var(--good)"><?= (int) $req['jumlah_terpenuhi'] ?></strong> orang</span>
				<span>&bull;</span>
				<span class="tag <?= in_array($req['status_req'], array('Sourcing','Approved','Terpenuhi')) ? 'on' : 'warn' ?>" style="font-size:11px">
					<?= html_escape($req['status_req']) ?>
				</span>
			</div>
		</div>

		<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
			<a href="<?= site_url('manual') ?>" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-weight:600">
				<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>Tambah Pelamar</span>
			</a>
			<a href="<?= site_url('requisitions/view/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px">
				<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
				<span>Detail MPR</span>
			</a>
		</div>
	</div>

	<!-- RINGKASAN METRIK OPERASIONAL -->
	<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:22px">
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Total Kandidat Aktif</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:var(--text); line-height:1.2"><?= $total_kandidat ?></div>
			<div class="muted" style="font-size:11.5px">Berada di seluruh alur seleksi</div>
		</div>
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Sedang Berjalan</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:var(--info); line-height:1.2"><?= $total_in_progress ?></div>
			<div class="muted" style="font-size:11.5px">Status In_Progress aktif</div>
		</div>
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Diterima / Hired</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:var(--good); line-height:1.2"><?= $total_hired ?></div>
			<div class="muted" style="font-size:11.5px">Lolos tahapan seleksi</div>
		</div>
		<div class="pipeline-stat-card">
			<div class="faint" style="font-size:11.5px; text-transform:uppercase; font-weight:700; letter-spacing:0.04em">Tertahan (>7 Hari)</div>
			<div class="mono" style="font-size:24px; font-weight:700; color:<?= $total_overdue > 0 ? 'var(--crit)' : 'var(--text-muted)' ?>; line-height:1.2">
				<?= $total_overdue ?>
			</div>
			<div class="muted" style="font-size:11.5px">Memerlukan tindak lanjut SLA</div>
		</div>
	</div>

	<!-- TOOLBAR FILTER & NAVIGASI TAHAP -->
	<div style="display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:18px; flex-wrap:wrap; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:10px 14px">
		<div style="display:flex; gap:12px; align-items:center; flex:1; min-width:280px; flex-wrap:wrap">
			<div style="position:relative; width:100%; max-width:340px">
				<input type="text" id="pipeline-search" placeholder="Cari nama kandidat, nomor WA, status..." oninput="filterPipelineRows()"
					style="width:100%; padding:7px 10px 7px 32px; font-size:12.5px; border-radius:6px; margin:0">
				<svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:13px; height:13px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
				</svg>
			</div>
			<label style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; margin:0; cursor:pointer; user-select:none; white-space:nowrap">
				<input type="checkbox" id="filter-overdue-only" onchange="filterPipelineRows()" style="margin:0; width:auto">
				<span>Hanya tertahan (>7 hari)</span>
			</label>
		</div>

		<!-- STAGE JUMP BUTTONS -->
		<div style="display:flex; gap:6px; align-items:center; overflow-x:auto; padding:2px 0; max-width:100%">
			<span class="faint" style="font-size:11.5px; white-space:nowrap; margin-right:2px">Lompat:</span>
			<?php foreach ($stages as $urut => $s): ?>
				<a href="#stage-sec-<?= (int) $urut ?>" class="btn btn-sm btn-ghost" style="padding:4px 9px; font-size:11px; white-space:nowrap; border-radius:6px; display:inline-flex; align-items:center; gap:5px">
					<span style="font-weight:700; color:var(--accent)"><?= (int) $urut ?></span>
					<span><?= html_escape($s['nama']) ?></span>
					<span style="background:var(--surface-2); border:1px solid var(--border); padding:0 5px; border-radius:8px; font-size:10px; font-weight:700">
						<?= count($s['cards']) ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( ! $stages): ?>
		<div style="padding:48px 20px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:10px">
			<svg style="width:38px; height:38px; margin:0 auto 12px; color:var(--text-faint)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
			<h3 style="margin:0 0 6px; font-size:15px; font-weight:700">Belum ada kandidat di alur pipeline lowongan ini</h3>
			<p class="muted" style="font-size:13px; margin:0 0 16px">Kandidat yang melamar via form publik atau ditambahkan manual akan muncul di sini.</p>
			<a href="<?= site_url('manual') ?>" class="btn btn-sm btn-primary">+ Tambah Pelamar Manual</a>
		</div>
	<?php else: ?>
		<!-- ================= DAFTAR TAHAP SELEKSI ================= -->
		<div id="pipeline-table-container">
			<?php foreach ($stages as $urut => $s): ?>
			<?php
				$id_stage    = (int) $s['id_stage'];
				$total_cards = count($s['cards']);
			?>
			<div id="stage-sec-<?= (int) $urut ?>" class="stage-section">
				<!-- Header Bar Tahap -->
				<div class="stage-header-bar">
					<div style="display:flex; align-items:center; gap:10px">
						<span style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:6px; background:var(--accent); color:var(--accent-contrast); font-weight:700; font-size:12px; font-family:'IBM Plex Mono', monospace">
							<?= (int) $urut ?>
						</span>
						<h2 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
							<?= html_escape($s['nama']) ?>
						</h2>
						<span class="tag info" style="font-size:10.5px; text-transform:uppercase">
							<?= html_escape($s['tipe']) ?>
						</span>
						<span class="muted mono" style="font-size:12px">
							(<?= $total_cards ?> kandidat)
						</span>
					</div>
				</div>

				<?php if ( ! $total_cards): ?>
					<div style="padding:24px 16px; text-align:center; color:var(--text-faint); font-size:13px; font-style:italic">
						Tidak ada kandidat pada tahap <?= html_escape($s['nama']) ?> saat ini.
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="width:100%; overflow-x:auto">
						<table style="width:100%; border-collapse:collapse; margin:0; table-layout:fixed">
							<thead>
								<tr style="background:var(--surface); font-size:11.5px; color:var(--text-muted); border-bottom:1px solid var(--border)">
									<th style="width:34px; padding:10px 6px; text-align:center">#</th>
									<th style="width:25%; padding:10px 12px; text-align:left">Kandidat &amp; Kontak</th>
									<th style="width:11%; padding:10px 8px; text-align:center">Durasi di Tahap</th>
									<th style="width:35%; padding:10px 12px; text-align:left">Catatan &amp; Evaluasi Tahap</th>
									<?php if ($can_aksi): ?>
										<th style="width:29%; padding:10px 12px; text-align:left">Aksi &amp; Transisi Alur</th>
									<?php endif; ?>
								</tr>
							</thead>
							<tbody>
								<?php
								$no = 1;
								foreach ($s['cards'] as $c):
									$id_app_stage = (int) $c['id_app_stage'];
									$id_lamaran   = (int) $c['id_lamaran'];
									$hari         = (int) $c['hari_di_tahap'];

									$cur_ivs   = isset($interviews[$id_app_stage]) ? $interviews[$id_app_stage] : array();
									$latest_iv = ! empty($cur_ivs) ? $cur_ivs[0] : NULL;

									$cur_psi    = isset($psikotes[$id_app_stage]) ? $psikotes[$id_app_stage] : array();
									$latest_psi = ! empty($cur_psi) ? $cur_psi[0] : NULL;

									$cur_off = isset($offers[$id_lamaran]) ? $offers[$id_lamaran] : NULL;

									$has_catatan = ! empty($c['catatan']);
									$has_remark  = ! empty($c['label_remark']);
								?>
								<tr class="candidate-row" data-overdue="<?= $hari > 7 ? '1' : '0' ?>"
									data-search="<?= strtolower(html_escape($c['nama_lengkap'] . ' ' . $c['no_wa_normal'] . ' ' . $c['status_global'] . ' ' . ($c['catatan'] ?? '') . ' ' . ($c['label_remark'] ?? '') . ' ' . ($latest_iv['hasil'] ?? '') . ' ' . ($latest_psi['hasil'] ?? ''))) ?>">

									<td style="padding:12px 6px; text-align:center" class="muted mono"><?= $no++ ?></td>

									<!-- Kolom 1: Kandidat & Kontak -->
									<td style="padding:12px 12px; word-break:break-word">
										<div style="font-weight:700; font-size:13.5px; line-height:1.3; margin-bottom:3px">
											<a href="<?= site_url('candidates/detail/' . $id_lamaran) ?>" title="Buka Profil Pelamar" style="color:var(--text); text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
												<?= html_escape($c['nama_lengkap']) ?>
											</a>
										</div>
										<div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:5px">
											<span class="mono faint" style="font-size:11px">#<?= $id_lamaran ?></span>
											<span class="muted" style="font-size:11px">&bull;</span>
											<span class="tag <?= $c['status_global'] === 'In_Progress' ? 'on' : ($c['status_global'] === 'Hired' ? 'on' : 'off') ?>" style="font-size:10px; padding:1px 6px">
												<?= html_escape($c['status_global']) ?>
											</span>
											<?php if (! empty($c['intake_method'])): ?>
												<span class="faint" style="font-size:10px; text-transform:uppercase"><?= html_escape($c['intake_method']) ?></span>
											<?php endif; ?>
										</div>
										<div style="font-size:12px; margin-top:2px" class="mono">
											<?php if ($c['no_wa_normal']): ?>
												<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['no_wa_normal']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--good); text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600" title="Chat WhatsApp Pelamar">
													<svg style="width:13px; height:13px; flex:none" fill="currentColor" viewBox="0 0 24 24">
														<path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.586-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/>
													</svg>
													<span><?= html_escape($c['no_wa_normal']) ?></span>
												</a>
											<?php else: ?>
												<span class="faint">-</span>
											<?php endif; ?>
										</div>
									</td>

									<!-- Kolom 2: Durasi di Tahap -->
									<td style="padding:12px 8px; text-align:center">
										<?php if ($hari > 7): ?>
											<span class="tag off" title="Tertahan lebih dari 7 hari di tahap ini" style="font-size:11px; font-weight:700; padding:3px 8px">
												<?= $hari ?> hari !
											</span>
										<?php elseif ($hari >= 4): ?>
											<span class="tag warn" style="font-size:11px; padding:3px 8px">
												<?= $hari ?> hari
											</span>
										<?php else: ?>
											<span class="tag info" style="font-size:11px; padding:3px 8px">
												<?= $hari ?> hari
											</span>
										<?php endif; ?>
										<?php if (! empty($c['tanggal_mulai'])): ?>
											<div class="faint mono" style="font-size:10.5px; margin-top:4px">
												Mulai: <?= html_escape(substr($c['tanggal_mulai'], 0, 10)) ?>
											</div>
										<?php endif; ?>
									</td>

									<!-- Kolom 3: Catatan & Evaluasi Tahap -->
									<td style="padding:12px 12px">
										<!-- Box Catatan Tahap Terkini -->
										<?php if ($has_catatan || $has_remark): ?>
											<div class="pipeline-note-card">
												<div class="pipeline-note-header">
													<div style="display:flex; align-items:center; gap:5px; flex-wrap:wrap">
														<span style="font-size:10.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; display:inline-flex; align-items:center; gap:3px">
															<svg style="width:11px; height:11px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
															<span>Catatan Tahap</span>
														</span>
														<?php if ($has_remark): ?>
															<span class="tag accent" style="font-size:10px; padding:1px 6px">
																<?= html_escape($c['label_remark']) ?>
															</span>
														<?php endif; ?>
													</div>
													<?php if ($can_aksi): ?>
														<button type="button" style="background:none; border:none; padding:0; color:var(--accent); font-size:11px; font-weight:600; cursor:pointer"
															onclick='openAdvanceModal(<?= (int) $id_app_stage ?>, <?= (int) $id_stage ?>, <?= json_encode($c["nama_lengkap"]) ?>, <?= (int) ($c["id_remark"] ?? 0) ?>, <?= json_encode($c["catatan"] ?? "") ?>)'>
															Edit Catatan
														</button>
													<?php endif; ?>
												</div>
												<div class="pipeline-note-content"><?= html_escape($c['catatan'] ?: 'Remark: ' . $c['label_remark']) ?></div>
											</div>
										<?php else: ?>
											<div class="pipeline-note-card empty" style="display:flex; justify-content:space-between; align-items:center">
												<span>Belum ada catatan di tahap ini.</span>
												<?php if ($can_aksi): ?>
													<button type="button" style="background:none; border:none; padding:0; color:var(--accent); font-size:11px; font-weight:600; cursor:pointer"
														onclick='openAdvanceModal(<?= (int) $id_app_stage ?>, <?= (int) $id_stage ?>, <?= json_encode($c["nama_lengkap"]) ?>, 0, "")'>
														+ Catatan
													</button>
												<?php endif; ?>
											</div>
										<?php endif; ?>

										<!-- Ringkasan Asesmen Bertipe (Interview & Offering) -->
										<div style="display:flex; flex-direction:column; gap:4px; margin-top:6px">
											<?php if ($latest_iv): ?>
												<div style="display:flex; align-items:center; gap:6px; font-size:11.5px; background:var(--surface); border:1px solid var(--border); border-radius:6px; padding:4px 8px">
													<span style="font-weight:700; color:var(--text-muted); font-size:10.5px">INTERVIEW:</span>
													<span class="tag <?= $latest_iv['hasil'] === 'Lulus' ? 'on' : ($latest_iv['hasil'] === 'Tidak_Lulus' ? 'off' : 'warn') ?>" style="font-size:10px; padding:1px 6px">
														<?= html_escape($latest_iv['hasil'] ?: 'Terjadwal') ?>
													</span>
													<?php if ($latest_iv['skor']): ?>
														<span class="mono" style="font-size:11px; font-weight:600">Skor: <?= (int)$latest_iv['skor'] ?></span>
													<?php endif; ?>
													<?php if ($latest_iv['jadwal']): ?>
														<span class="faint mono" style="font-size:11px"><?= html_escape(substr($latest_iv['jadwal'], 0, 16)) ?></span>
													<?php endif; ?>
												</div>
											<?php endif; ?>

											<?php if ($cur_off): ?>
												<div style="display:flex; align-items:center; gap:6px; font-size:11.5px; background:var(--surface); border:1px solid var(--border); border-radius:6px; padding:4px 8px">
													<span style="font-weight:700; color:var(--text-muted); font-size:10.5px">OFFERING:</span>
													<span class="tag <?= $cur_off['status_offer'] === 'Accepted' ? 'on' : 'warn' ?>" style="font-size:10px; padding:1px 6px">
														<?= html_escape($cur_off['status_offer']) ?>
													</span>
													<?php if ($cur_off['tanggal_join_disepakati']): ?>
														<span class="faint mono" style="font-size:11px">Join: <?= html_escape($cur_off['tanggal_join_disepakati']) ?></span>
													<?php endif; ?>
												</div>
											<?php endif; ?>
										</div>
									</td>

									<!-- Kolom 4: Aksi & Transisi Alur -->
									<?php if ($can_aksi): ?>
									<td style="padding:12px 12px">
										<div style="display:flex; flex-direction:column; gap:8px">
											<!-- Form Eksekusi Alur Tahap Cepat -->
											<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('style' => 'display:flex; gap:6px; align-items:center; margin:0; width:100%')) ?>
												<input type="hidden" name="id_app_stage" value="<?= $id_app_stage ?>">

												<select name="id_remark" required style="flex:1; padding:6px 8px; font-size:12px; border-radius:6px; border:1px solid var(--border-strong); margin:0">
													<option value="">- Pilih Remark -</option>
													<?php if ( ! empty($remarks[$id_stage])): ?>
														<?php foreach ($remarks[$id_stage] as $rmk): ?>
															<option value="<?= (int) $rmk['id_remark'] ?>">
																<?= html_escape($rmk['label']) ?> (<?= html_escape($rmk['efek_status']) ?>)
															</option>
														<?php endforeach; ?>
													<?php endif; ?>
												</select>

												<button type="submit" class="btn-advance-submit" title="Proses Transisi Alur">
													<span>Proses &rarr;</span>
												</button>
											<?= form_close() ?>

											<!-- Baris Aksi Per Kandidat: Sisip Tahap & Evaluasi Bertipe -->
											<div style="display:flex; gap:5px; align-items:center; flex-wrap:wrap">
												<button type="button" class="icon-pill" title="Sisipkan tahap seleksi ad-hoc untuk kandidat ini"
													onclick='openAdHocModal(<?= (int) $id_lamaran ?>, <?= json_encode($c["nama_lengkap"]) ?>)'>
													<svg style="width:12px; height:12px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
													<span>Sisip Tahap</span>
												</button>

												<?php if ($s['tipe'] === 'INTERVIEW'): ?>
													<button type="button" class="icon-pill" title="Jadwalkan atau catat evaluasi interview"
														onclick='openInterviewModal(<?= json_encode(array(
															"id_app_stage" => $id_app_stage,
															"nama" => $c["nama_lengkap"],
															"interview" => $latest_iv
														)) ?>)'>
														<svg style="width:12px; height:12px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
														<span>Interview</span>
													</button>
												<?php endif; ?>

												<?php if ($s['tipe'] === 'OFFER' || $cur_off): ?>
													<button type="button" class="icon-pill" title="Kelola penawaran kerja (Offering)"
														onclick='openOfferModal(<?= json_encode(array(
															"id_lamaran" => $id_lamaran,
															"nama" => $c["nama_lengkap"],
															"offer" => $cur_off
														)) ?>)'>
														<svg style="width:12px; height:12px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
														<span>Offer</span>
													</button>
												<?php endif; ?>

												<?php if ($s['tipe'] === 'KONTAK'): ?>
													<button type="button" class="icon-pill" title="Catat respon kontak WA"
														onclick='openContactModal(<?= (int) $id_lamaran ?>, <?= json_encode($c["nama_lengkap"]) ?>)'>
														<svg style="width:12px; height:12px; color:var(--accent)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
														<span>Log WA</span>
													</button>
												<?php endif; ?>
											</div>
										</div>
									</td>
									<?php endif; ?>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- ================= ARSIP KANDIDAT FINAL & TERMINAL ================= -->
	<div id="sec-final-candidates" style="margin-top:28px; border:1px solid var(--border); border-radius:10px; background:var(--surface); overflow:hidden">
		<details <?= ! empty($final_candidates) ? 'open' : '' ?> style="border:none; margin:0; padding:0">
			<summary style="background:var(--surface-2); border-bottom:1px solid var(--border); padding:14px 18px; display:flex; justify-content:space-between; align-items:center; cursor:pointer; list-style:none; user-select:none">
				<div style="display:flex; align-items:center; gap:10px">
					<span style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:6px; background:var(--surface); border:1px solid var(--border); color:var(--text-muted)">
						<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
					</span>
					<div>
						<span style="font-size:14.5px; font-weight:700; color:var(--text)">
							Arsip Pelamar Selesai &amp; Ditolak
						</span>
						<span class="muted" style="font-size:12.5px; margin-left:6px">
							(<?= count($final_candidates ?? array()) ?> kandidat status final)
						</span>
					</div>
				</div>
				<div style="display:flex; align-items:center; gap:8px">
					<span class="tag off" style="font-size:10.5px">Terminal State</span>
					<span class="muted" style="font-size:12px">&#9662; Buka / Tutup</span>
				</div>
			</summary>

			<div style="padding:0">
				<?php if (empty($final_candidates)): ?>
					<div style="padding:32px 16px; text-align:center; color:var(--text-muted); font-size:13px">
						Belum ada kandidat berstatus final (Ditolak, Mengundurkan Diri, atau Diterima) pada lowongan ini.
					</div>
				<?php else: ?>
					<div class="table-responsive-fit" style="overflow-x:auto">
						<table style="width:100%; table-layout:fixed; font-size:13px; margin:0; border-collapse:collapse">
							<thead>
								<tr style="border-bottom:1px solid var(--border); background:var(--surface)">
									<th style="width:30%; text-align:left; padding:10px 14px">Kandidat &amp; Kontak</th>
									<th style="width:20%; text-align:left; padding:10px 12px">Tahap Terakhir</th>
									<th style="width:24%; text-align:left; padding:10px 12px">Alasan / Remark</th>
									<th style="width:14%; text-align:center; padding:10px 12px">Status Akhir</th>
									<th style="width:12%; text-align:right; padding:10px 14px">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($final_candidates as $fc): ?>
									<?php
									$fc_status = $fc['status_global'];
									$fc_badge = 'off';
									if (in_array($fc_status, array('Hired', 'Approved'))) {
										$fc_badge = 'on';
									} elseif ($fc_status === 'Withdrawn' || $fc_status === 'Offer_Declined') {
										$fc_badge = 'warn';
									} elseif ($fc_status === 'Talent_Pool') {
										$fc_badge = 'info';
									}
									?>
									<tr style="border-bottom:1px solid var(--border)">
										<td style="padding:10px 14px; word-break:break-word">
											<a href="<?= site_url('candidates/detail/' . (int) $fc['id_lamaran']) ?>" style="font-weight:700; color:var(--text); text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
												<?= html_escape($fc['nama_lengkap']) ?>
											</a>
											<div class="muted mono" style="font-size:11.5px; margin-top:2px">
												<?= html_escape($fc['no_wa_normal'] ?: '-') ?>
											</div>
											<?php if ($fc['email']): ?>
												<div class="faint" style="font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
													<?= html_escape($fc['email']) ?>
												</div>
											<?php endif; ?>
										</td>
										<td style="padding:10px 12px; word-break:break-word">
											<span style="font-weight:600; color:var(--text)">
												<?= html_escape($fc['tahap_terakhir'] ?: '-') ?>
											</span>
											<div class="faint" style="font-size:11px; margin-top:2px">
												Tgl Lamar: <?= html_escape($fc['tanggal_lamar'] ? substr($fc['tanggal_lamar'], 0, 10) : '-') ?>
											</div>
										</td>
										<td style="padding:10px 12px; word-break:break-word">
											<?php if ($fc['label_remark_terakhir']): ?>
												<span style="color:var(--crit); font-weight:600; font-size:12px">
													<?= html_escape($fc['label_remark_terakhir']) ?>
												</span>
											<?php else: ?>
												<span class="muted faint" style="font-size:12px">-</span>
											<?php endif; ?>
										</td>
										<td style="padding:10px 12px; text-align:center">
											<span class="tag <?= $fc_badge ?>" style="font-size:11px">
												<?= html_escape($fc['status_global']) ?>
											</span>
										</td>
										<td style="padding:10px 14px; text-align:right">
											<a href="<?= site_url('candidates/detail/' . (int) $fc['id_lamaran']) ?>" class="btn btn-sm btn-ghost" style="padding:4px 8px; font-size:11.5px" title="Lihat Profil Pelamar">
												Profil
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</details>
	</div>
</div>

<!-- ================= MODAL ADVANCE & CATATAN TAHAP LENGKAP ================= -->
<dialog id="dlg-advance" class="rpg-modal">
	<div class="rpg-modal-header">
		<div>
			<h3 id="dlg-adv-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Catatan &amp; Detail Tahap
			</h3>
			<div id="dlg-adv-subtitle" class="muted" style="font-size:12px; margin-top:2px">Kandidat: -</div>
		</div>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-advance').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/advance/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_app_stage" id="adv-id-app-stage">
		<div class="rpg-modal-body">
			<div style="margin-bottom:16px">
				<label for="adv-id-remark" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Pilih Keputusan / Remark <span style="color:var(--crit)">*</span>
				</label>
				<select name="id_remark" id="adv-id-remark" required style="width:100%; font-size:13px; padding:8px 10px; border-radius:7px">
					<!-- Diisi dinamis via openAdvanceModal -->
				</select>
				<div id="adv-remark-info" class="faint" style="font-size:11.5px; margin-top:4px">
					Pilih remark untuk menerapkan efek status alur (Lulus, Tidak Lulus, On Hold, dsb).
				</div>
			</div>

			<div>
				<label for="adv-catatan" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Catatan Evaluasi / Alasan Keputusan
				</label>
				<textarea name="catatan" id="adv-catatan" rows="4" style="width:100%; font-size:13px; padding:8px 10px; border-radius:7px" placeholder="Tuliskan catatan evaluasi, ulasan hasil seleksi, atau alasan mutasi alur kandidat..."></textarea>
				<div class="faint" style="font-size:11px; margin-top:3px">Catatan ini akan tersimpan pada tahap kandidat dan riwayat seleksi.</div>
			</div>
		</div>
		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-advance').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan &amp; Proses Tahap</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL SISIP TAHAP AD-HOC (PER KANDIDAT) ================= -->
<dialog id="dlg-adhoc" class="rpg-modal" style="max-width:480px">
	<div class="rpg-modal-header">
		<div>
			<h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
				Sisip Tahap Ad-Hoc
			</h3>
			<div class="muted" style="font-size:12px; margin-top:2px">
				Kandidat: <strong id="adhoc-nama-kandidat" style="color:var(--text)">-</strong>
			</div>
		</div>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-adhoc').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/insert_stage/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_lamaran" id="adhoc-id-lamaran">
		<div class="rpg-modal-body">
			<div style="margin-bottom:14px">
				<label for="adhoc-stage" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Pilih Tahap yang Akan Disisipkan <span style="color:var(--crit)">*</span>
				</label>
				<select name="id_stage" id="adhoc-stage" required style="width:100%; font-size:13px; padding:8px 10px; border-radius:6px">
					<?php foreach ($all_stages as $st): ?>
						<option value="<?= (int) $st['id_stage'] ?>"><?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['tipe_tahap']) ?>)</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div>
				<label for="adhoc-catatan" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Catatan / Alasan Sisip Tahap
				</label>
				<textarea name="catatan" id="adhoc-catatan" rows="3" style="font-size:12.5px; padding:8px 10px; width:100%; border-radius:6px" placeholder="Mis. Perlu wawancara tambahan dengan user / test teknis ulang..."></textarea>
			</div>
		</div>
		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-adhoc').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Sisipkan Sekarang</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL LOG KONTAK WA ================= -->
<dialog id="dlg-contact" class="rpg-modal" style="max-width:440px">
	<div class="rpg-modal-header">
		<h3 id="dlg-contact-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
			Log Kontak WA Pelamar
		</h3>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-contact').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/contact/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_lamaran" id="contact-id-lamaran">
		<input type="hidden" name="metode" value="WA">
		<div class="rpg-modal-body">
			<div style="margin-bottom:14px">
				<label for="contact-hasil" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Hasil Hubungan Kontak <span style="color:var(--crit)">*</span>
				</label>
				<select name="hasil" id="contact-hasil" required style="width:100%; font-size:13px; padding:7px 10px">
					<option value="Respon">Dibalas (Respon)</option>
					<option value="Tidak_Respon">Tidak Respon</option>
					<option value="Nomor_Salah">Nomor Salah</option>
					<option value="Menolak">Menolak</option>
				</select>
			</div>
			<div>
				<label for="contact-catatan" style="display:block; font-size:12.5px; font-weight:600; margin:0 0 6px">
					Catatan Respon (Opsional)
				</label>
				<textarea name="catatan" id="contact-catatan" rows="2" style="font-size:13px; padding:7px 10px; width:100%" placeholder="Catatan jam telepon, alasan tolak, dll..."></textarea>
			</div>
		</div>
		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-contact').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan Kontak</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL INTERVIEW ================= -->
<dialog id="dlg-interview" class="rpg-modal">
	<div class="rpg-modal-header">
		<h3 id="dlg-iv-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
			Jadwal &amp; Hasil Evaluasi Interview
		</h3>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-interview').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/save_interview/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_interview" id="iv-id-interview">
		<input type="hidden" name="id_app_stage" id="iv-id-app-stage">

		<div class="rpg-modal-body">
			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="iv-tipe" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Tipe Interview</label>
					<select name="tipe" id="iv-tipe" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="Online">Online</option>
						<option value="Offline">Offline</option>
					</select>
				</div>
				<div>
					<label for="iv-jadwal" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Jadwal Wawancara</label>
					<input type="text" name="jadwal" id="iv-jadwal" placeholder="YYYY-MM-DD HH:MM" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			</div>

			<div style="margin-bottom:12px">
				<label for="iv-lokasi" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Lokasi / Tautan Meeting</label>
				<input type="text" name="lokasi_atau_link" id="iv-lokasi" placeholder="Mis. Google Meet link / Ruang HR Lt. 2" style="width:100%; font-size:12.5px; padding:6px 10px">
			</div>

			<div style="display:grid; grid-template-columns:1.5fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="iv-interviewer" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Pewawancara</label>
					<select name="id_interviewer" id="iv-interviewer" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="">- Pilih Pewawancara -</option>
						<?php foreach ($interviewers as $usr): ?>
							<option value="<?= (int) $usr['id_user'] ?>"><?= html_escape($usr['nama_lengkap']) ?> (<?= html_escape($usr['role']) ?>)</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="iv-peran" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Peran Interviewer</label>
					<select name="peran_interviewer" id="iv-peran" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="HR">HR</option>
						<option value="User">User</option>
						<option value="BOD">BOD</option>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1.5fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="iv-hasil" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Hasil Evaluasi</label>
					<select name="hasil" id="iv-hasil" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="">(Belum Ada / Terjadwal)</option>
						<option value="Lulus">Lulus</option>
						<option value="Tidak_Lulus">Tidak Lulus</option>
						<option value="Dipertimbangkan">Dipertimbangkan</option>
						<option value="Reschedule">Reschedule</option>
						<option value="No_Show">No Show</option>
					</select>
				</div>
				<div>
					<label for="iv-skor" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Skor (0-100)</label>
					<input type="number" name="skor" id="iv-skor" min="0" max="100" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			</div>

			<div>
				<label for="iv-catatan" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Catatan Interview</label>
				<textarea name="catatan" id="iv-catatan" rows="3" placeholder="Ulasan kompetensi, sikap, kelebihan, kekurangan..." style="width:100%; font-size:12.5px; padding:6px 10px"></textarea>
			</div>
		</div>

		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-interview').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan Interview</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- ================= MODAL OFFER ================= -->
<dialog id="dlg-offer" class="rpg-modal">
	<div class="rpg-modal-header">
		<h3 id="dlg-off-title" style="margin:0; font-size:15px; font-weight:700; color:var(--text)">
			Penawaran Kerja (Offering)
		</h3>
		<button type="button" class="rpg-modal-close" onclick="document.getElementById('dlg-offer').close()">&times;</button>
	</div>
	<?= form_open(site_url('pipeline/save_offer/' . (int) $req['id_req']), array('style' => 'margin:0; display:flex; flex-direction:column; flex:1; min-height:0')) ?>
		<input type="hidden" name="id_offer" id="off-id-offer">
		<input type="hidden" name="id_lamaran" id="off-id-lamaran">

		<div class="rpg-modal-body">
			<?php if ($can_gaji): ?>
				<div style="margin-bottom:12px">
					<label for="off-gaji" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Gaji yang Ditawarkan (IDR)</label>
					<input type="number" name="gaji_ditawarkan" id="off-gaji" placeholder="Mis. 5000000" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			<?php endif; ?>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="off-tgl-penawaran" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Tanggal Penawaran</label>
					<input type="date" name="tanggal_penawaran" id="off-tgl-penawaran" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
				<div>
					<label for="off-status" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Status Offer</label>
					<select name="status_offer" id="off-status" style="width:100%; font-size:12.5px; padding:6px 10px">
						<option value="Nego">Nego / Ditawarkan</option>
						<option value="Accepted">Accepted (Diterima)</option>
						<option value="Declined">Declined (Ditolak Kandidat)</option>
						<option value="Canceled">Canceled (Dibatalkan RPG)</option>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px">
				<div>
					<label for="off-tgl-join-sepakat" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Target Join Sepakat</label>
					<input type="date" name="tanggal_join_disepakati" id="off-tgl-join-sepakat" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
				<div>
					<label for="off-tgl-join-aktual" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Join Aktual</label>
					<input type="date" name="tanggal_join_aktual" id="off-tgl-join-aktual" style="width:100%; font-size:12.5px; padding:6px 10px">
				</div>
			</div>

			<div>
				<label for="off-alasan" style="display:block; font-size:12px; font-weight:600; margin:0 0 4px">Catatan / Alasan</label>
				<textarea name="alasan" id="off-alasan" rows="2" placeholder="Catatan negosiasi gaji, tunjangan, fasilitas, atau alasan tolak..." style="width:100%; font-size:12.5px; padding:6px 10px"></textarea>
			</div>
		</div>

		<div class="rpg-modal-footer">
			<button type="button" class="btn btn-ghost" onclick="document.getElementById('dlg-offer').close()">Batal</button>
			<button type="submit" class="btn btn-primary" style="padding:7px 18px; font-weight:600">Simpan Offer</button>
		</div>
	<?= form_close() ?>
</dialog>

<!-- SCRIPT FILTER & MODAL HANDLER -->
<script>
// Kamus remarks per id_stage
var stageRemarksMap = <?= json_encode($remarks) ?>;

function filterPipelineRows() {
	var query = (document.getElementById('pipeline-search').value || '').toLowerCase().trim();
	var overdueOnly = document.getElementById('filter-overdue-only').checked;
	var rows = document.querySelectorAll('.candidate-row');

	rows.forEach(function(row) {
		var text = row.getAttribute('data-search') || '';
		var isOverdue = row.getAttribute('data-overdue') === '1';

		var matchQuery = !query || text.indexOf(query) !== -1;
		var matchOverdue = !overdueOnly || isOverdue;

		if (matchQuery && matchOverdue) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
}

function openAdvanceModal(idAppStage, idStage, namaKandidat, currentRemarkId, currentCatatan) {
	var dlg = document.getElementById('dlg-advance');
	document.getElementById('dlg-adv-subtitle').textContent = 'Kandidat: ' + namaKandidat;
	document.getElementById('adv-id-app-stage').value = idAppStage;
	document.getElementById('adv-catatan').value = currentCatatan || '';

	var sel = document.getElementById('adv-id-remark');
	sel.innerHTML = '<option value="">- Pilih Keputusan / Remark -</option>';
	var rmkList = stageRemarksMap[idStage] || [];
	rmkList.forEach(function(r) {
		var opt = document.createElement('option');
		opt.value = r.id_remark;
		opt.textContent = r.label + ' (' + r.efek_status + ')';
		opt.setAttribute('data-efek', r.efek_status);
		if (currentRemarkId && parseInt(currentRemarkId) === parseInt(r.id_remark)) {
			opt.selected = true;
		}
		sel.appendChild(opt);
	});

	dlg.showModal();
}

function openAdHocModal(idLamaran, namaKandidat) {
	document.getElementById('adhoc-id-lamaran').value = idLamaran;
	document.getElementById('adhoc-nama-kandidat').textContent = namaKandidat + ' (#' + idLamaran + ')';
	document.getElementById('adhoc-catatan').value = '';
	document.getElementById('dlg-adhoc').showModal();
}

function openContactModal(idLamaran, namaLengkap) {
	document.getElementById('dlg-contact-title').textContent = 'Log Kontak: ' + namaLengkap;
	document.getElementById('contact-id-lamaran').value = idLamaran;
	document.getElementById('contact-hasil').value = 'Respon';
	document.getElementById('contact-catatan').value = '';
	document.getElementById('dlg-contact').showModal();
}

function openInterviewModal(data) {
	var dlg = document.getElementById('dlg-interview');
	document.getElementById('dlg-iv-title').textContent = 'Interview: ' + data.nama;
	document.getElementById('iv-id-app-stage').value = data.id_app_stage;
	var iv = data.interview || {};
	document.getElementById('iv-id-interview').value = iv.id_interview || '';
	document.getElementById('iv-tipe').value = iv.tipe || 'Online';
	document.getElementById('iv-jadwal').value = iv.jadwal || '';
	document.getElementById('iv-lokasi').value = iv.lokasi_atau_link || '';
	document.getElementById('iv-interviewer').value = iv.id_interviewer || '';
	document.getElementById('iv-peran').value = iv.peran_interviewer || 'HR';
	document.getElementById('iv-hasil').value = iv.hasil || '';
	document.getElementById('iv-skor').value = iv.skor !== null && iv.skor !== undefined ? iv.skor : '';
	document.getElementById('iv-catatan').value = iv.catatan || '';
	dlg.showModal();
}

function openOfferModal(data) {
	var dlg = document.getElementById('dlg-offer');
	document.getElementById('dlg-off-title').textContent = 'Offering: ' + data.nama;
	document.getElementById('off-id-lamaran').value = data.id_lamaran;
	var off = data.offer || {};
	document.getElementById('off-id-offer').value = off.id_offer || '';
	var gInput = document.getElementById('off-gaji');
	if (gInput) {
		gInput.value = off.gaji_ditawarkan ? Math.round(off.gaji_ditawarkan) : '';
	}
	document.getElementById('off-tgl-penawaran').value = off.tanggal_penawaran || '';
	document.getElementById('off-status').value = off.status_offer || 'Nego';
	document.getElementById('off-tgl-join-sepakat').value = off.tanggal_join_disepakati || '';
	document.getElementById('off-tgl-join-aktual').value = off.tanggal_join_aktual || '';
	document.getElementById('off-alasan').value = off.alasan || '';
	dlg.showModal();
}
</script>
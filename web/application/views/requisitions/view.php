<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: requisitions/view.php -- Lembar Detail Dokumen MPR & Panel Evaluasi HR/BOD
 *
 * Tampilan terstruktur, rapi, dan formal:
 * - Hero header dengan nomor MPR, posisi, departemen, dan aksi cepat.
 * - Metric cards formasi (Dibutuhkan, Disetujui, Terpenuhi, Sisa Kuota).
 * - Grid informasi umum, spesifikasi jabatan, dan job description.
 * - Panel interaktif alur keputusan (Review HR / Review BOD / Revisi / Penolakan).
 * - Tabel manajemen link form publik dan kontrol batas waktu/sourcing ulang.
 */

$status = $req['status_req'];
$is_approved_group = in_array($status, array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian', 'Terpenuhi'));
$is_review_group   = in_array($status, array('Review_HR', 'Review_BOD', 'Revisi_HR', 'Revisi_BOD'));
$is_reject_group   = in_array($status, array('Ditolak_HR', 'Ditolak_BOD', 'Dibatalkan'));

$tag_class = 'off';
if ($is_approved_group) {
	$tag_class = 'on';
} elseif ($is_review_group) {
	$tag_class = 'warn';
} elseif ($is_reject_group) {
	$tag_class = 'crit';
}

$dibutuhkan = (int) $req['jumlah_dibutuhkan'];
$disetujui  = $req['jumlah_disetujui'] !== NULL ? (int) $req['jumlah_disetujui'] : NULL;
$terpenuhi  = (int) $req['jumlah_terpenuhi'];
$kuota_ref  = $disetujui !== NULL ? $disetujui : $dibutuhkan;
$sisa_kuota = max(0, $kuota_ref - $terpenuhi);
?>

<style>
.mpr-hero {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 12px;
	padding: 22px 26px;
	margin-bottom: 20px;
	box-shadow: var(--shadow-sm);
	position: relative;
	overflow: hidden;
}
.mpr-hero::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 3px;
	background: var(--accent);
}
.mpr-stat-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 14px;
	margin-bottom: 20px;
}
.mpr-stat-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 14px 18px;
	box-shadow: var(--shadow-sm);
}
.mpr-stat-num {
	font-size: 24px;
	font-weight: 700;
	color: var(--text);
	line-height: 1.2;
	font-family: 'Archivo', sans-serif;
}
.mpr-section-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 18px 22px;
	margin-bottom: 20px;
	box-shadow: var(--shadow-sm);
}
.mpr-grid-2 {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 18px;
}
@media (max-width: 860px) {
	.mpr-grid-2 {
		grid-template-columns: 1fr;
	}
}
.kv-row {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	padding: 8px 0;
	border-bottom: 1px solid var(--border);
	font-size: 13px;
	gap: 12px;
}
.kv-row:last-child {
	border-bottom: none;
	padding-bottom: 0;
}
.kv-label {
	color: var(--text-muted);
	font-weight: 500;
	flex: 0 0 140px;
}
.kv-val {
	color: var(--text);
	font-weight: 600;
	text-align: right;
	flex: 1;
	word-break: break-word;
}
</style>

<!-- ================= HERO HEADER ================= -->
<div class="mpr-hero">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px">
				<span class="eyebrow" style="margin:0">Permintaan Tenaga Kerja (MPR)</span>
				<span class="muted">&bull;</span>
				<span class="muted" style="font-size:12px">Diajukan <?= !empty($req['created_at']) ? html_escape(date('d M Y', strtotime(is_object($req['created_at']) ? $req['created_at']->format('Y-m-d') : substr($req['created_at'], 0, 10)))) : '-' ?></span>
			</div>
			<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:6px">
				<h1 style="margin:0; font-size:24px; font-weight:700">
					<?= html_escape($req['nama_posisi']) ?>
				</h1>
				<span class="mono" style="font-size:14px; background:var(--surface-2); padding:3px 8px; border-radius:6px; font-weight:600; border:1px solid var(--border)">
					<?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?>
				</span>
			</div>
			<div class="muted" style="font-size:13px; display:flex; align-items:center; gap:12px; flex-wrap:wrap">
				<span>Departemen: <strong style="color:var(--text)"><?= html_escape($req['departemen'] ?: '-') ?></strong></span>
				<span class="faint">&bull;</span>
				<span>Penempatan: <strong style="color:var(--text)"><?= html_escape($req['tipe_penempatan'] . ($req['nama_outlet'] ? ' (' . $req['nama_outlet'] . ')' : ' — Headquarters')) ?></strong></span>
				<span class="faint">&bull;</span>
				<span>Pemohon: <strong style="color:var(--text)"><?= html_escape($req['pemohon'] ?: '-') ?></strong></span>
			</div>
		</div>

		<!-- Action Buttons & Status Badge -->
		<div style="display:flex; flex-direction:column; align-items:flex-end; gap:10px">
			<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap">
				<span class="tag <?= $tag_class ?>" style="font-size:13px; padding:4px 12px; font-weight:700">
					<?= html_escape(label_status_req($req['status_req'])) ?>
				</span>
			</div>

			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
				<a class="btn btn-sm btn-ghost" href="<?= site_url('requisitions') ?>" style="text-decoration:none">
					&larr; Daftar MPR
				</a>
				<?php if (in_array($req['status_req'], array('Draft', 'Revisi_HR', 'Revisi_BOD'))): ?>
					<a href="<?= site_url('requisitions/edit/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-ghost" style="text-decoration:none">
						<svg style="width:12px; height:12px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
						<span>Edit Data</span>
					</a>
				<?php endif; ?>
				<?php if ($is_approved_group): ?>
					<a class="btn btn-sm btn-primary" href="<?= site_url('pipeline/index/' . (int) $req['id_req']) ?>" style="text-decoration:none">
						<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
						<span>Buka Pipeline Pelamar &rarr;</span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<!-- ================= STATISTIK FORMASI REKRUTMEN ================= -->
<div class="mpr-stat-grid">
	<div class="mpr-stat-card">
		<div class="muted" style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px">
			Dibutuhkan Pemohon
		</div>
		<div class="mpr-stat-num">
			<?= $dibutuhkan ?> <span style="font-size:13px; font-weight:500; color:var(--text-muted)">orang</span>
		</div>
	</div>
	<div class="mpr-stat-card">
		<div class="muted" style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px">
			Disetujui BOD
		</div>
		<div class="mpr-stat-num" style="color:<?= $disetujui !== NULL ? 'var(--text)' : 'var(--text-faint)' ?>">
			<?= $disetujui !== NULL ? $disetujui : '-' ?> <span style="font-size:13px; font-weight:500; color:var(--text-muted)">orang</span>
		</div>
	</div>
	<div class="mpr-stat-card">
		<div class="muted" style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px">
			Telah Terpenuhi
		</div>
		<div class="mpr-stat-num" style="color:var(--good)">
			<?= $terpenuhi ?> <span style="font-size:13px; font-weight:500; color:var(--text-muted)">orang</span>
		</div>
	</div>
	<div class="mpr-stat-card">
		<div class="muted" style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px">
			Sisa Kebutuhan
		</div>
		<div class="mpr-stat-num" style="color:<?= $sisa_kuota > 0 ? 'var(--accent)' : 'var(--text-muted)' ?>">
			<?= $sisa_kuota ?> <span style="font-size:13px; font-weight:500; color:var(--text-muted)">orang</span>
		</div>
	</div>
</div>

<!-- ================= BANNER PENOLAKAN (JIKA ADA) ================= -->
<?php if (in_array($req['status_req'], array('Ditolak_HR', 'Ditolak_BOD'))): ?>
	<?php
	$is_ditolak_hr  = ($req['status_req'] === 'Ditolak_HR');
	$judul_penolakan = $is_ditolak_hr ? 'Permintaan Tenaga Kerja Ditolak oleh Tim HR' : 'Permintaan Tenaga Kerja Ditolak oleh Direksi (BOD)';
	$alasan_teks    = $is_ditolak_hr
		? (!empty($req['catatan_hr']) ? $req['catatan_hr'] : 'Tidak ada keterangan alasan penolakan dari Tim HR.')
		: (!empty($req['catatan_bod']) ? $req['catatan_bod'] : 'Tidak ada keterangan alasan penolakan dari BOD.');
	$oleh_siapa     = $is_ditolak_hr ? 'Tim HR' : (!empty($req['penolak_bod']) ? $req['penolak_bod'] : 'Direksi (BOD)');
	?>
	<div style="margin-bottom:20px; padding:18px 20px; border-radius:10px; border:1.5px solid var(--crit); background:rgba(239, 68, 68, 0.08); display:flex; gap:16px; align-items:flex-start">
		<div style="width:36px; height:36px; border-radius:50%; background:var(--crit); color:#fff; display:grid; place-items:center; flex:none; margin-top:2px">
			<svg style="width:20px; height:20px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
		</div>
		<div style="flex:1">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:6px">
				<div style="font-size:15px; font-weight:700; color:var(--crit)">
					<?= html_escape($judul_penolakan) ?>
				</div>
				<span class="tag crit" style="font-size:11px; padding:3px 8px; font-weight:600">
					Status: <?= html_escape(label_status_req($req['status_req'])) ?>
				</span>
			</div>
			<div style="font-size:12px; color:var(--text-muted); margin-bottom:10px">
				Ditolak oleh: <strong><?= html_escape($oleh_siapa) ?></strong>
			</div>
			<div style="padding:12px 14px; background:var(--surface); border:1px solid rgba(239, 68, 68, 0.25); border-radius:6px">
				<div class="eyebrow" style="color:var(--crit); font-size:10.5px; margin-bottom:4px">Alasan Penolakan:</div>
				<div style="font-size:13.5px; color:var(--text); line-height:1.6; white-space:pre-line; word-break:break-word; font-weight:500">
					<?= html_escape($alasan_teks) ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- ================= BANNER CATATAN / ARAHAN REVISI HR ================= -->
<?php if (!empty($req['catatan_hr']) || ($can_kelola && in_array($req['status_req'], array('Draft', 'Review_HR', 'Revisi_HR', 'Ditolak_HR')))): ?>
<div style="padding:16px 20px; margin-bottom:20px; border-radius:10px; border:1px solid <?= $req['status_req'] === 'Revisi_HR' ? 'var(--warn)' : ($req['status_req'] === 'Ditolak_HR' ? 'var(--crit)' : 'var(--border)') ?>; background:<?= $req['status_req'] === 'Revisi_HR' ? 'var(--warn-soft)' : ($req['status_req'] === 'Ditolak_HR' ? 'rgba(239, 68, 68, 0.06)' : 'var(--surface-2)') ?>">
	<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px">
		<div style="display:flex; align-items:center; gap:8px">
			<div class="eyebrow" style="font-size:11px; margin:0; color:<?= $req['status_req'] === 'Revisi_HR' ? 'var(--warn)' : ($req['status_req'] === 'Ditolak_HR' ? 'var(--crit)' : 'var(--text-muted)') ?>">
				<?= $req['status_req'] === 'Revisi_HR' ? 'Arahan Revisi HR' : ($req['status_req'] === 'Ditolak_HR' ? 'Alasan Penolakan HR' : 'Catatan / Feedback Tim HR') ?>
			</div>
			<?php if (!empty($req['catatan_hr'])): ?>
				<span class="tag <?= $req['status_req'] === 'Revisi_HR' ? 'warn' : ($req['status_req'] === 'Ditolak_HR' ? 'crit' : 'off') ?>" style="font-size:10px; padding:2px 6px">
					<?= html_escape(label_status_req($req['status_req'])) ?>
				</span>
	<?php endif; ?>
		</div>
		<?php if ($can_kelola): ?>
			<button type="button" class="btn btn-sm btn-ghost" style="font-size:11px; padding:2px 8px" onclick="document.getElementById('form-edit-catatan-hr').hidden = !document.getElementById('form-edit-catatan-hr').hidden">
				<?= !empty($req['catatan_hr']) ? 'Edit Catatan HR' : '+ Beri Catatan / Feedback HR' ?>
			</button>
		<?php endif; ?>
	</div>

	<?php if (!empty($req['catatan_hr'])): ?>
		<div style="font-size:13px; line-height:1.6; color:var(--text); white-space:pre-line; word-break:break-word">
			<?= html_escape($req['catatan_hr']) ?>
		</div>
	<?php else: ?>
		<div class="muted" style="font-size:12.5px; font-style:italic">
			Belum ada catatan feedback dari Tim HR.
		</div>
	<?php endif; ?>

	<?php if ($can_kelola): ?>
		<div id="form-edit-catatan-hr" hidden style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border)">
			<?= form_open(site_url('requisitions/update_catatan_hr/' . (int) $req['id_req'])) ?>
				<label for="catatan_hr_input" style="font-size:12px; font-weight:600; display:block; margin-bottom:4px">Catatan / Arahan Tim HR</label>
				<textarea name="catatan_hr" id="catatan_hr_input" rows="3" placeholder="Masukkan catatan, evaluasi beban kerja, atau arahan revisi untuk pemohon..." style="width:100%; font-size:12.5px; margin-bottom:8px"><?= html_escape($req['catatan_hr'] ?? '') ?></textarea>
				<div style="display:flex; justify-content:flex-end; gap:8px">
					<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('form-edit-catatan-hr').hidden = true">Batal</button>
					<button type="submit" class="btn btn-sm btn-primary">Simpan Catatan HR</button>
				</div>
			<?= form_close() ?>
		</div>
	<?php endif; ?>
</div>
<?php endif; ?>

<!-- ================= BANNER CATATAN / ARAHAN REVISI BOD ================= -->
<?php if (!empty($req['catatan_bod']) && in_array($req['status_req'], array('Revisi_BOD', 'Review_HR', 'Review_BOD', 'Draft'))): ?>
<div style="padding:16px 20px; margin-bottom:20px; border-radius:10px; border:1px solid <?= $req['status_req'] === 'Revisi_BOD' ? 'var(--warn)' : 'var(--border)' ?>; background:<?= $req['status_req'] === 'Revisi_BOD' ? 'var(--warn-soft)' : 'var(--surface-2)' ?>">
	<div style="display:flex; align-items:center; gap:8px; margin-bottom:8px">
		<div class="eyebrow" style="font-size:11px; margin:0; color:<?= $req['status_req'] === 'Revisi_BOD' ? 'var(--warn)' : 'var(--text-muted)' ?>">
			<?= $req['status_req'] === 'Revisi_BOD' ? 'Arahan Revisi dari Direksi (BOD)' : 'Catatan / Feedback Direksi (BOD)' ?>
		</div>
		<span class="tag <?= $req['status_req'] === 'Revisi_BOD' ? 'warn' : 'off' ?>" style="font-size:10px; padding:2px 6px">
			<?= html_escape(label_status_req($req['status_req'])) ?>
		</span>
	</div>
	<div style="font-size:13px; line-height:1.6; color:var(--text); white-space:pre-line; word-break:break-word">
		<?= html_escape($req['catatan_bod']) ?>
	</div>
</div>
<?php endif; ?>

<!-- ================= PANEL AKSI KEPUTUSAN ALUR ================= -->

<!-- 1. Pemohon: Ajukan ke HR (Draft / Revisi_HR / Revisi_BOD / Ditolak) -->
<?php if (in_array($req['status_req'], array('Draft', 'Revisi_HR', 'Revisi_BOD', 'Ditolak_HR', 'Ditolak_BOD'))): ?>
	<div style="padding:18px 22px; background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--accent); border-radius:10px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; box-shadow:var(--shadow-sm)">
		<div>
			<strong style="color:var(--text); font-size:14.5px; display:block; margin-bottom:2px">
				<?= in_array($req['status_req'], array('Revisi_HR', 'Revisi_BOD')) ? 'Ajukan Kembali ke Tim HR setelah Revisi?' : 'Ajukan Permintaan Tenaga Kerja ke Tim HR?' ?>
			</strong>
			<div class="muted" style="font-size:12.5px; line-height:1.4">
				<?php if ($req['status_req'] === 'Revisi_HR'): ?>
					Permintaan ini berstatus <strong>Revisi HR</strong>. Pastikan formasi atau spesifikasi telah disesuaikan dengan catatan HR sebelum diajukan kembali.
				<?php elseif ($req['status_req'] === 'Revisi_BOD'): ?>
					Permintaan ini berstatus <strong>Revisi BOD</strong>. Pastikan data formasi telah disesuaikan dengan arahan catatan Direksi sebelum diajukan kembali.
				<?php elseif (in_array($req['status_req'], array('Ditolak_HR', 'Ditolak_BOD'))): ?>
					Permintaan ini sebelumnya <strong><?= html_escape(label_status_req($req['status_req'])) ?></strong>. Setelah revisi data formasi, Anda dapat mengajukannya kembali.
				<?php else: ?>
					Dokumen MPR akan dievaluasi oleh HR (analisis beban kerja & alokasi budget) sebelum diteruskan ke Direksi (BOD).
				<?php endif; ?>
			</div>
		</div>
		<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
			<a href="<?= site_url('requisitions/edit/' . (int) $req['id_req']) ?>" class="btn btn-sm btn-ghost">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
				<span>Edit Data MPR</span>
			</a>
			<?= form_open(site_url('requisitions/submit_hr/' . (int) $req['id_req']), array('class' => 'inline')) ?>
				<button type="submit" class="btn btn-sm btn-primary">
					<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
					<span><?= in_array($req['status_req'], array('Revisi_HR', 'Revisi_BOD')) ? 'Ajukan Kembali ke HR (Review HR) &rarr;' : 'Ajukan ke HR (Review HR) &rarr;' ?></span>
				</button>
			<?= form_close() ?>
		</div>
	</div>
<?php endif; ?>

<!-- 2. HR: Evaluasi & Keputusan HR (Review_HR) -->
<?php if ($req['status_req'] === 'Review_HR'): ?>
	<div style="padding:18px 22px; background:var(--warn-soft); border:1px solid var(--warn); border-radius:10px; margin-bottom:20px; box-shadow:var(--shadow-sm)">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px">
			<div>
				<div class="eyebrow" style="color:var(--warn); margin-bottom:3px">Evaluasi &amp; Validasi Tim HR</div>
				<strong style="font-size:15px; color:var(--text); display:block; margin-bottom:3px">Permintaan Tenaga Kerja Sedang Dalam Tahap Review HR</strong>
				<div class="muted" style="font-size:12.5px; line-height:1.4">
					Validasi analisis beban kerja, urgensi penambahan posisi, dan ketersediaan alokasi budget sebelum diteruskan ke Direksi.
				</div>
			</div>
			<?php if ($can_kelola): ?>
				<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
					<!-- Tombol Tolak HR -->
					<button type="button" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit)" onclick="document.getElementById('modal-tolak-hr').hidden = false; document.getElementById('modal-revisi-hr').hidden = true">
						Tolak HR
					</button>

					<!-- Tombol Minta Revisi HR -->
					<button type="button" class="btn btn-sm btn-ghost" style="color:var(--warn); border-color:var(--warn)" onclick="document.getElementById('modal-revisi-hr').hidden = false; document.getElementById('modal-tolak-hr').hidden = true">
						Minta Revisi
					</button>

					<!-- Tombol Teruskan ke BOD -->
					<?= form_open(site_url('requisitions/submit/' . (int) $req['id_req']), array('class' => 'inline')) ?>
						<button type="submit" class="btn btn-sm btn-primary">
							<span>Setujui &amp; Teruskan ke BOD &rarr;</span>
						</button>
					<?= form_close() ?>
				</div>
			<?php else: ?>
				<span class="tag warn" style="font-size:12px; padding:4px 8px">Menunggu Keputusan HR</span>
			<?php endif; ?>
		</div>

		<!-- Dialog Revisi HR -->
		<?php if ($can_kelola): ?>
			<div id="modal-revisi-hr" hidden style="margin-top:14px; padding:14px; background:var(--surface); border:1px solid var(--border); border-radius:8px">
				<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
					<input type="hidden" name="status_baru" value="Revisi_HR">
					<div class="faint" style="font-size:11px; font-weight:700; color:var(--warn); margin-bottom:6px">CATATAN ARAHAN REVISI UNTUK PEMOHON (WAJIB DIISI):</div>
					<textarea name="catatan" rows="3" placeholder="e.g. Mohon lengkapi kualifikasi teknis dan perjelas justifikasi penambahan personil dibandingkan beban kerja saat ini..." required style="width:100%; font-size:12.5px; margin-bottom:10px"></textarea>
					<div style="display:flex; justify-content:flex-end; gap:8px">
						<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-revisi-hr').hidden = true">Batal</button>
						<button type="submit" class="btn btn-sm btn-primary">Kirim Arahan Revisi ke Pemohon</button>
					</div>
				<?= form_close() ?>
			</div>

			<!-- Dialog Tolak HR -->
			<div id="modal-tolak-hr" hidden style="margin-top:14px; padding:14px; background:var(--surface); border:1px solid var(--border); border-radius:8px">
				<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
					<input type="hidden" name="status_baru" value="Ditolak_HR">
					<div class="faint" style="font-size:11px; font-weight:700; color:var(--crit); margin-bottom:6px">ALASAN PENOLAKAN PERMINTAAN OLEH HR (WAJIB DIISI):</div>
					<textarea name="catatan" rows="2" placeholder="e.g. Alokasi budget belum tersedia pada kuartal ini / Analisis beban kerja departemen belum mencukupi" required style="font-size:12.5px; margin-bottom:10px; width:100%"></textarea>
					<div style="display:flex; justify-content:flex-end; gap:8px">
						<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-tolak-hr').hidden = true">Batal</button>
						<button type="submit" class="btn btn-sm btn-primary" style="background:var(--crit); border-color:var(--crit)">Konfirmasi Tolak HR</button>
					</div>
				<?= form_close() ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>

<!-- 3. BOD: Evaluasi & Persetujuan Direksi (Review_BOD) -->
<?php if ($req['status_req'] === 'Review_BOD'): ?>
	<div style="padding:18px 22px; background:var(--warn-soft); border:1px solid var(--warn); border-radius:10px; margin-bottom:20px; box-shadow:var(--shadow-sm)">
		<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px">
			<div>
				<div class="eyebrow" style="color:var(--warn); margin-bottom:3px">Evaluasi &amp; Persetujuan Direksi</div>
				<strong style="font-size:15px; color:var(--text); display:block; margin-bottom:3px">Permintaan Tenaga Kerja Sedang Dalam Tahap Review BOD</strong>
				<div class="muted" style="font-size:12.5px; line-height:1.4">
					Menunggu keputusan Direksi (BOD) untuk persetujuan penambahan/penggantian formasi tenaga kerja.
				</div>
			</div>
			<?php if ($can_kelola): ?>
				<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
					<!-- Tombol Tolak BOD -->
					<button type="button" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit)" onclick="document.getElementById('modal-tolak-bod').hidden = false; document.getElementById('modal-revisi-bod').hidden = true">
						Tolak BOD
					</button>

					<!-- Tombol Minta Revisi BOD -->
					<button type="button" class="btn btn-sm btn-ghost" style="color:var(--warn); border-color:var(--warn)" onclick="document.getElementById('modal-revisi-bod').hidden = false; document.getElementById('modal-tolak-bod').hidden = true">
						Minta Revisi BOD
					</button>

					<!-- Tombol Setujui BOD -->
					<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req']), array('class' => 'inline')) ?>
						<input type="hidden" name="status_baru" value="Approved">
						<input type="hidden" name="catatan" value="Disetujui oleh Direksi (BOD)">
						<button type="submit" class="btn btn-sm btn-primary" style="background:var(--good); border-color:var(--good)" onclick="return confirm('Konfirmasi bahwa Direksi (BOD) telah menyetujui permintaan tenaga kerja ini?')">
							<span>Setujui (Approved) &rarr;</span>
						</button>
					<?= form_close() ?>
				</div>
			<?php else: ?>
				<span class="tag warn" style="font-size:12px; padding:4px 8px">Menunggu Keputusan BOD</span>
			<?php endif; ?>
		</div>

		<!-- Dialog Revisi BOD -->
		<?php if ($can_kelola): ?>
			<div id="modal-revisi-bod" hidden style="margin-top:14px; padding:14px; background:var(--surface); border:1px solid var(--border); border-radius:8px">
				<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
					<input type="hidden" name="status_baru" value="Revisi_BOD">
					<div class="faint" style="font-size:11px; font-weight:700; color:var(--warn); margin-bottom:6px">CATATAN ARAHAN REVISI DARI BOD (WAJIB DIISI):</div>
					<textarea name="catatan" rows="3" placeholder="e.g. Mohon kaji ulang estimasi budget atau sesuaikan kualifikasi level posisi..." required style="width:100%; font-size:12.5px; margin-bottom:10px"></textarea>
					<div style="display:flex; justify-content:flex-end; gap:8px">
						<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-revisi-bod').hidden = true">Batal</button>
						<button type="submit" class="btn btn-sm btn-primary">Kirim Arahan Revisi ke Pemohon</button>
					</div>
				<?= form_close() ?>
			</div>

			<!-- Dialog Tolak BOD -->
			<div id="modal-tolak-bod" hidden style="margin-top:14px; padding:14px; background:var(--surface); border:1px solid var(--border); border-radius:8px">
				<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
					<input type="hidden" name="status_baru" value="Ditolak_BOD">
					<div class="faint" style="font-size:11px; font-weight:700; color:var(--crit); margin-bottom:6px">ALASAN PENOLAKAN PERMINTAAN OLEH BOD (WAJIB DIISI):</div>
					<textarea name="catatan" rows="2" placeholder="e.g. Penambahan headcount ditunda hingga Q4 sesuai arahan manajemen" required style="font-size:12.5px; margin-bottom:10px; width:100%"></textarea>
					<div style="display:flex; justify-content:flex-end; gap:8px">
						<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-tolak-bod').hidden = true">Batal</button>
						<button type="submit" class="btn btn-sm btn-primary" style="background:var(--crit); border-color:var(--crit)">Konfirmasi Tolak BOD</button>
					</div>
				<?= form_close() ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>

<!-- ================= INFORMASI DETAIL & SPESIFIKASI ================= -->
<div class="mpr-grid-2">
	<!-- Kolom Kiri: Informasi Permintaan & Alur Rekrutmen -->
	<div class="mpr-section-card" style="margin-bottom:0">
		<div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div style="width:7px; height:7px; border-radius:50%; background:var(--accent)"></div>
			<h3 style="margin:0; font-size:14px; font-weight:700; color:var(--text)">
				Informasi Umum &amp; Alur Rekrutmen
			</h3>
		</div>

		<div class="kv-row">
			<span class="kv-label">No. Dokumen MPR</span>
			<span class="kv-val mono"><?= html_escape($req['no_mpr'] ?: '#' . $req['id_req']) ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Jabatan / Posisi</span>
			<span class="kv-val"><?= html_escape($req['nama_posisi']) ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Departemen</span>
			<span class="kv-val"><?= html_escape($req['departemen'] ?: '-') ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Tipe Penempatan</span>
			<span class="kv-val"><?= html_escape($req['tipe_penempatan'] . ($req['nama_outlet'] ? ' — ' . $req['nama_outlet'] : ' (HQ)')) ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Status Hubungan Kerja</span>
			<span class="kv-val"><?= html_escape($req['status_karyawan'] ?: 'Karyawan Tetap') ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Target Join</span>
			<span class="kv-val"><?= !empty($req['target_tanggal_join']) ? html_escape(date('d M Y', strtotime(substr($req['target_tanggal_join'], 0, 10)))) : '<span class="faint">Belum ditentukan</span>' ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Tingkat Urgensi</span>
			<span class="kv-val">
				<?php if (!empty($req['urgensi']) && in_array(strtolower($req['urgensi']), array('tinggi', 'urgent', 'segera'))): ?>
					<span class="tag crit" style="font-size:11px"><?= html_escape($req['urgensi']) ?></span>
				<?php else: ?>
					<span><?= html_escape($req['urgensi'] ?: 'Normal') ?></span>
				<?php endif; ?>
			</span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Template Alur Seleksi</span>
			<span class="kv-val mono"><?= html_escape($req['kode_flow'] ?: '(Belum ditentukan)') ?> <?= !empty($req['nama_flow']) ? ' &middot; ' . html_escape($req['nama_flow']) : '' ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">User Pemohon</span>
			<span class="kv-val"><?= html_escape($req['pemohon'] ?: '-') ?></span>
		</div>
	</div>

	<!-- Kolom Kanan: Alasan Permintaan & Kualifikasi Minimum -->
	<div class="mpr-section-card" style="margin-bottom:0">
		<div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid var(--border)">
			<div style="width:7px; height:7px; border-radius:50%; background:var(--accent)"></div>
			<h3 style="margin:0; font-size:14px; font-weight:700; color:var(--text)">
				Alasan Kebutuhan &amp; Spesifikasi Jabatan
			</h3>
		</div>

		<div class="kv-row">
			<span class="kv-label">Alasan Permintaan</span>
			<span class="kv-val"><?= html_escape($req['alasan_permintaan'] ?: 'Penambahan Formasi Baru') ?></span>
		</div>
		<?php if (!empty($req['nik_digantikan'])): ?>
		<div class="kv-row">
			<span class="kv-label">Karyawan Digantikan</span>
			<span class="kv-val mono"><?= html_escape($req['nik_digantikan']) ?></span>
		</div>
		<?php endif; ?>
		<div class="kv-row">
			<span class="kv-label">Pendidikan Minimal</span>
			<span class="kv-val"><?= html_escape($req['pendidikan_minimal'] ?: 'SMA / SMK Sederajat') ?></span>
		</div>
		<div class="kv-row">
			<span class="kv-label">Pengalaman Minimal</span>
			<span class="kv-val"><?= $req['pengalaman_minimal_tahun'] !== NULL ? (int) $req['pengalaman_minimal_tahun'] . ' tahun' : 'Fresh Graduate dipertimbangkan' ?></span>
		</div>
		<?php if (!empty($req['preferensi_internal'])): ?>
		<div class="kv-row">
			<span class="kv-label">Preferensi Internal</span>
			<span class="kv-val"><?= html_escape($req['preferensi_internal']) ?></span>
		</div>
		<?php endif; ?>
		<?php if (!empty($req['catatan_alasan'])): ?>
		<div style="margin-top:12px; padding:10px 12px; background:var(--surface-2); border-radius:6px; font-size:12.5px">
			<div class="faint" style="font-size:11px; font-weight:700; margin-bottom:4px">CATATAN JUSTIFIKASI PEMOHON:</div>
			<div style="color:var(--text); line-height:1.5; white-space:pre-line"><?= nl2br(html_escape($req['catatan_alasan'])) ?></div>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- ================= JOB DESCRIPTION & PERSYARATAN ================= -->
<?php if (!empty($req['job_desc']) || !empty($req['kualifikasi'])): ?>
<div class="mpr-grid-2" style="margin-top:20px">
	<?php if (!empty($req['job_desc'])): ?>
	<div class="mpr-section-card" style="margin-bottom:0">
		<div class="eyebrow" style="margin-bottom:8px; font-size:11px">Job Description &amp; Tanggung Jawab</div>
		<div style="font-size:13px; color:var(--text); line-height:1.6; white-space:pre-line; word-break:break-word">
			<?= html_escape($req['job_desc']) ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if (!empty($req['kualifikasi'])): ?>
	<div class="mpr-section-card" style="margin-bottom:0">
		<div class="eyebrow" style="margin-bottom:8px; font-size:11px">Kualifikasi &amp; Persyaratan Khusus</div>
		<div style="font-size:13px; color:var(--text); line-height:1.6; white-space:pre-line; word-break:break-word">
			<?= html_escape($req['kualifikasi']) ?>
		</div>
	</div>
	<?php endif; ?>
</div>
<?php endif; ?>

<!-- ================= TAUTAN FORM PUBLIK & SOURCING ================= -->
<?php if ($is_approved_group): ?>
<div class="mpr-section-card" style="margin-top:24px">
	<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:12px; padding-bottom:12px; border-bottom:1px solid var(--border)">
		<div>
			<div style="display:flex; align-items:center; gap:8px">
				<div style="width:8px; height:8px; border-radius:50%; background:var(--accent)"></div>
				<h2 style="font-size:16px; font-weight:700; margin:0">Lowongan Publik &amp; Form Pendaftaran Pelamar</h2>
			</div>
			<div class="muted" style="font-size:12px; margin-top:3px">
				Kontrol publikasi link form online, durasi masa tayang aktif, serta perpanjangan batch sourcing ulang.
			</div>
		</div>

		<?php if ($can_kelola && in_array($req['status_req'], array('Approved','Sourcing','Sourcing_Ulang','Terpenuhi_Sebagian'))): ?>
			<button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('form-tambah-posting').hidden = !document.getElementById('form-tambah-posting').hidden">
				+ Buat Link Lowongan Baru
			</button>
		<?php endif; ?>
	</div>

	<!-- Form Generator Link Baru -->
	<div id="form-tambah-posting" hidden style="margin-bottom:18px; padding:16px 18px; background:var(--surface-2); border-radius:8px; border:1px solid var(--border)">
		<div style="font-size:13px; font-weight:700; margin-bottom:10px">Publikasi Link Lowongan Baru</div>
		<?= form_open(site_url('requisitions/post_job/' . (int) $req['id_req'])) ?>
			<div style="display:grid; grid-template-columns: 2fr 1fr auto; gap:12px; align-items:end">
				<div>
					<label for="judul_posting" style="font-size:12px; margin-top:0">Judul Lowongan *</label>
					<input type="text" id="judul_posting" name="judul_posting" value="<?= html_escape($req['nama_posisi']) ?>" required style="font-size:13px">
				</div>
				<div>
					<label for="durasi_hari" style="font-size:12px; margin-top:0">Batas Waktu (Deadline) *</label>
					<select id="durasi_hari" name="durasi_hari" style="font-size:13px; padding:7px 10px; width:100%; border:1px solid var(--border); border-radius:6px; background:var(--surface)">
						<option value="7">7 Hari (1 Minggu)</option>
						<option value="14" selected>14 Hari (2 Minggu)</option>
						<option value="30">30 Hari (1 Bulan)</option>
						<option value="60">60 Hari (2 Bulan)</option>
					</select>
				</div>
				<button type="submit" class="btn btn-sm btn-primary" style="height:36px">
					Generate Link &rarr;
				</button>
			</div>
		<?= form_close() ?>
	</div>

	<?php if (empty($postings)): ?>
		<div style="padding:28px 16px; text-align:center; background:var(--surface-2); border:1px dashed var(--border); border-radius:8px">
			<div style="font-size:28px; margin-bottom:6px; opacity:.4">📋</div>
			<div class="muted" style="font-size:13px">Belum ada link form lowongan publik untuk MPR ini.</div>
			<div class="faint" style="font-size:12px; margin-top:2px">Klik tombol <strong>+ Buat Link Lowongan Baru</strong> di atas untuk memulai proses sourcing.</div>
		</div>
	<?php else: ?>
		<div style="display:flex; flex-direction:column; gap:14px">
			<?php foreach ($postings as $idx => $p): ?>
			<?php
				$is_kadaluarsa = !empty($p['is_kadaluarsa']) || (!empty($p['form_aktif']) && !empty($p['form_ditutup']) && strtotime($p['form_ditutup']) < time());
				$batch = !empty($p['batch_ke']) ? (int) $p['batch_ke'] : 1;
				$border_color = $is_kadaluarsa ? 'var(--warn)' : ($p['form_aktif'] ? 'var(--good)' : 'var(--border)');
			?>
			<div style="background:var(--surface-2); border:1px solid var(--border); border-left:4px solid <?= $border_color ?>; border-radius:8px; padding:16px 20px">

				<!-- Baris 1: Header — judul, badge status, tanggal posting -->
				<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:12px">
					<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap">
						<strong style="font-size:14px; color:var(--text)"><?= html_escape($p['judul_posting']) ?></strong>
						<?php if ($batch > 1): ?>
							<span class="tag accent" style="font-size:10px">Batch <?= $batch ?></span>
						<?php endif; ?>
						<?php if (!empty($p['tanggal_posting'])): ?>
							<span class="faint" style="font-size:11px">&middot; Diposting <?= html_escape(date('d M Y', strtotime(substr($p['tanggal_posting'], 0, 10)))) ?></span>
						<?php endif; ?>
					</div>
					<div>
						<?php if ($is_kadaluarsa): ?>
							<span class="tag warn" style="font-size:11px; font-weight:700">⏰ Kadaluarsa</span>
						<?php elseif ($p['form_aktif']): ?>
							<span class="tag on" style="font-size:11px">&#10003; Aktif &amp; Menerima Lamaran</span>
						<?php else: ?>
							<span class="tag off" style="font-size:11px">Ditutup</span>
						<?php endif; ?>
					</div>
				</div>

				<!-- Baris 2: Tautan URL slug + tombol salin -->
				<div style="margin-bottom:12px; padding:10px 14px; background:var(--surface); border:1px solid var(--border); border-radius:6px">
					<?php if ($p['url_slug']): ?>
						<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap">
							<div style="flex:1; min-width:0">
								<div class="faint" style="font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px">Tautan Form Publik</div>
								<code style="font-size:12px; word-break:break-all; color:var(--accent-ink)"><?= site_url('lamar/' . $p['url_slug']) ?></code>
							</div>
							<div style="display:flex; gap:6px; flex-shrink:0">
								<a href="<?= site_url('lamar/' . $p['url_slug']) ?>" target="_blank" class="btn btn-sm btn-ghost" style="font-size:11px; padding:4px 10px; text-decoration:none" title="Buka form di tab baru">
									Buka &nearr;
								</a>
								<button type="button" class="btn btn-sm btn-primary" style="font-size:11px; padding:4px 10px" title="Salin URL ke clipboard" onclick="navigator.clipboard.writeText('<?= site_url('lamar/' . $p['url_slug']) ?>'); this.textContent='Tersalin ✓'; setTimeout(()=>this.textContent='Salin Link', 2000)">
									Salin Link
								</button>
							</div>
						</div>
					<?php else: ?>
						<span class="faint" style="font-size:12px">(URL slug belum digenerate — akan otomatis dibuat saat halaman dimuat ulang)</span>
					<?php endif; ?>
				</div>

				<!-- Baris 3: Metrik ringkas + kontrol aksi -->
				<div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px">
					<!-- Metrik mini -->
					<div style="display:flex; gap:20px; flex-wrap:wrap">
						<div>
							<div class="faint" style="font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.04em">Batas Waktu</div>
							<?php if (!empty($p['form_ditutup'])): ?>
								<div style="font-size:13px; font-weight:600; color:var(--text)"><?= html_escape(date('d M Y', strtotime($p['form_ditutup']))) ?></div>
								<?php if ($p['form_aktif'] && !$is_kadaluarsa): ?>
									<?php $sisa = max(0, (int) ceil((strtotime($p['form_ditutup']) - time()) / 86400)); ?>
									<div style="font-size:11px; color:var(--accent); font-weight:600"><?= $sisa ?> hari lagi</div>
								<?php elseif ($is_kadaluarsa): ?>
									<div style="font-size:11px; color:var(--crit); font-weight:600">Sudah lewat batas</div>
								<?php endif; ?>
							<?php else: ?>
								<div style="font-size:13px; color:var(--text-muted)">Tanpa batas waktu</div>
							<?php endif; ?>
						</div>
						<div>
							<div class="faint" style="font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.04em">Form Submit</div>
							<div style="font-size:13px; font-weight:600; color:var(--text)"><?= (int) $p['jumlah_submit'] ?></div>
						</div>
						<div>
							<div class="faint" style="font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.04em">Pelamar Masuk</div>
							<div style="font-size:13px; font-weight:700; color:var(--accent)"><?= (int) $p['n_lamaran'] ?></div>
						</div>
					</div>

					<!-- Tombol aksi -->
					<div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap">
						<?php if ($can_kelola): ?>
							<!-- Perpanjang (Sourcing Ulang) -->
							<details style="position:relative; display:inline-block">
								<summary class="btn btn-sm btn-ghost" style="font-size:11px; padding:4px 10px; cursor:pointer" title="Perpanjang batas waktu lowongan">
									+ Perpanjang
								</summary>
								<div style="position:absolute; right:0; bottom:calc(100% + 6px); z-index:40; background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow); border-radius:8px; padding:12px; min-width:210px; text-align:left">
									<?= form_open(site_url('requisitions/extend_posting/' . (int) $req['id_req'] . '/' . (int) $p['id_posting'])) ?>
										<div class="faint" style="font-size:10.5px; margin-bottom:6px; font-weight:700">TAMBAH HARI TAYANG:</div>
										<select name="durasi_hari" style="font-size:12px; padding:5px 8px; width:100%; margin-bottom:8px; border:1px solid var(--border); border-radius:6px; background:var(--surface)">
											<option value="7">+7 Hari (1 Minggu)</option>
											<option value="14" selected>+14 Hari (2 Minggu)</option>
											<option value="30">+30 Hari (1 Bulan)</option>
										</select>
										<button type="submit" class="btn btn-sm btn-primary" style="width:100%; font-size:11px" onclick="return confirm('Perpanjang lowongan ini? Status MPR akan beralih menjadi Sourcing Ulang.')">
											Perpanjang &rarr; Sourcing Ulang
										</button>
									<?= form_close() ?>
								</div>
							</details>

							<!-- Toggle Buka/Tutup -->
							<?= form_open(site_url('requisitions/toggle_posting/' . (int) $req['id_req'] . '/' . (int) $p['id_posting']), array('style' => 'display:inline')) ?>
								<input type="hidden" name="form_aktif" value="<?= ($p['form_aktif'] && !$is_kadaluarsa) ? '0' : '1' ?>">
								<?php if ($p['form_aktif'] && !$is_kadaluarsa): ?>
									<button type="submit" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit); font-size:11px; padding:4px 10px" onclick="return confirm('Tutup form lamaran publik ini? Pelamar tidak akan bisa mengisi formulir.')">
										Tutup Form
									</button>
								<?php else: ?>
									<button type="submit" class="btn btn-sm btn-primary" style="font-size:11px; padding:4px 10px" <?= in_array($req['status_req'], array('Dibatalkan', 'Kadaluarsa')) ? 'disabled title="MPR Dibatalkan/Kadaluarsa"' : '' ?>>
										<?= $is_kadaluarsa ? 'Buka Kembali' : 'Buka Form' ?>
									</button>
								<?php endif; ?>
							<?= form_close() ?>
						<?php endif; ?>


					</div>
				</div>

			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
<?php endif; ?>

<!-- ================= OPSI PENUTUPAN & PEMBATALAN MPR ================= -->
<?php if ($can_kelola && in_array($req['status_req'], array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian'))): ?>
<div style="margin-top:24px; padding:16px 20px; border:1px solid var(--border); border-radius:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; background:var(--surface); box-shadow:var(--shadow-sm)">
	<div>
		<strong style="font-size:13.5px; color:var(--text); display:block; margin-bottom:2px">Selesai / Tutup Periode Pencarian (Kadaluarsa)</strong>
		<div class="muted" style="font-size:12px; line-height:1.4">Jika proses intake pelamar telah dihentikan atau recruitment campaign ditutup tanpa menambah kandidat lagi. Seluruh form publik otomatis dinonaktifkan.</div>
	</div>
	<div>
		<button type="button" class="btn btn-sm btn-ghost" style="color:var(--warn); border-color:var(--warn)" onclick="document.getElementById('modal-kadaluarsa-mpr').hidden = !document.getElementById('modal-kadaluarsa-mpr').hidden">
			Tutup Periode (Kadaluarsa)...
		</button>
	</div>
</div>
<div id="modal-kadaluarsa-mpr" hidden style="margin-top:10px; padding:16px; background:var(--surface); border:1px solid var(--warn); border-radius:8px">
	<?= form_open(site_url('requisitions/update_status/' . (int) $req['id_req'])) ?>
		<input type="hidden" name="status_baru" value="Kadaluarsa">
		<div class="faint" style="font-size:11px; font-weight:700; color:var(--warn); margin-bottom:6px">CATATAN PENUTUPAN / KADALUARSA PERIODE SOURCING (OPSIONAL):</div>
		<textarea name="catatan" rows="2" placeholder="e.g. Pencarian dihentikan karena target formasi telah ditutup manajemen / batas waktu recruitment campaign selesai" style="width:100%; font-size:12.5px; margin-bottom:10px"></textarea>
		<div style="display:flex; justify-content:flex-end; gap:8px">
			<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-kadaluarsa-mpr').hidden = true">Batal</button>
			<button type="submit" class="btn btn-sm btn-primary" style="background:var(--warn); border-color:var(--warn)" onclick="return confirm('Konfirmasi menutup periode pencarian MPR ini menjadi Kadaluarsa? Seluruh form publik yang aktif akan dinonaktifkan.')">Konfirmasi Tutup Periode (Kadaluarsa)</button>
		</div>
	<?= form_close() ?>
</div>
<?php endif; ?>

<!-- Opsi Pembatalan Permintaan Rekrutmen -->
<?php if (in_array($req['status_req'], array('Draft', 'Review_BOD', 'Sourcing', 'Sourcing_Ulang'))): ?>
<div style="margin-top:16px; padding:14px 18px; border:1px solid var(--border); border-radius:8px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; background:var(--surface)">
	<div>
		<span style="font-size:13px; font-weight:600; color:var(--crit)">Batalkan Permintaan Rekrutmen Ini?</span>
		<div class="faint" style="font-size:12px">Jika posisi tidak lagi dibutuhkan atau terjadi pembatalan formasi anggaran.</div>
	</div>
	<div>
		<button type="button" class="btn btn-sm btn-ghost" style="color:var(--crit); border-color:var(--crit); cursor:pointer" onclick="document.getElementById('modal-batal-mpr').hidden = !document.getElementById('modal-batal-mpr').hidden">
			Batalkan MPR...
		</button>
	</div>
</div>
<div id="modal-batal-mpr" hidden style="margin-top:10px; padding:14px; background:var(--surface); border:1px solid var(--crit); border-radius:8px">
	<?= form_open(site_url('requisitions/cancel/' . (int) $req['id_req'])) ?>
		<div class="faint" style="font-size:11px; margin-bottom:6px; font-weight:600; color:var(--crit)">KONFIRMASI PEMBATALAN:</div>
		<label for="alasan_batal" style="font-size:12px; margin-top:0">Alasan Pembatalan</label>
		<input type="text" name="alasan_batal" id="alasan_batal" placeholder="e.g. Pembatalan budget / restrukturisasi" required style="font-size:12.5px; margin-bottom:10px; width:100%">
		<div style="display:flex; justify-content:flex-end; gap:8px">
			<button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('modal-batal-mpr').hidden = true">Batal</button>
			<button type="submit" class="btn btn-sm btn-primary" style="background:var(--crit); border-color:var(--crit)" onclick="return confirm('Yakin membatalkan MPR ini?')">Ya, Batalkan MPR</button>
		</div>
	<?= form_close() ?>
</div>
<?php endif; ?>
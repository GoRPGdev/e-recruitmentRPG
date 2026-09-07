<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card" style="padding:22px 26px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<a href="<?= site_url('flowbuilder') ?>" class="muted" style="font-size:12px; text-decoration:none">&larr; Flow Builder</a>
				<span class="muted">&bull;</span>
				<a href="<?= site_url('flowbuilder/remarks') ?>" class="muted" style="font-size:12px; text-decoration:none">Kelola Remark &rarr;</a>
			</div>
			<h1 style="margin:0 0 4px; font-size:20px; font-weight:700">Tahap Seleksi Rekrutmen</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Katalog master tahap seleksi. <code>tipe_tahap</code> adalah 7 sumbu analitik tetap untuk seluruh laporan eksekutif.
			</p>
		</div>
		<div>
			<button type="button" class="btn btn-sm btn-primary" onclick="openAddStageModal()" style="display:inline-flex; align-items:center; gap:6px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Tahap</span>
			</button>
		</div>
	</div>

	<?= validation_errors('<div class="flash err" style="margin-bottom:14px">', '</div>') ?>

	<div style="overflow-x:auto">
		<table style="width:100%; border-collapse:collapse">
			<thead>
				<tr>
					<th>Kode</th>
					<th>Nama Tahap</th>
					<th>Tipe (Report)</th>
					<th style="text-align:center">Terminal</th>
					<th style="text-align:center">Sistem</th>
					<th>Penggunaan</th>
					<th style="text-align:center">Status</th>
					<th style="text-align:right">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<?php $json_stage = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8'); ?>
				<tr style="<?= empty($r['is_aktif']) ? 'opacity:.55' : '' ?>">
					<td><code><?= html_escape($r['kode_stage']) ?></code></td>
					<td><strong><?= html_escape($r['nama_tahap']) ?></strong></td>
					<td><span class="tag on"><?= html_escape($r['tipe_tahap']) ?></span></td>
					<td style="text-align:center"><?= $r['is_terminal'] ? '<span class="tag info">&#10003; Ya</span>' : '<span class="muted">-</span>' ?></td>
					<td style="text-align:center"><?= $r['is_sistem'] ? '<span class="tag off" title="Tahap inti bawaan sistem">Sistem</span>' : '<span class="muted">Custom</span>' ?></td>
					<td>
						<span class="muted" style="font-size:12px">
							<?= (int) $r['n_flow'] ?> flow &middot;
							<a href="<?= site_url('flowbuilder/remarks/' . (int) $r['id_stage']) ?>"><?= (int) $r['n_remark'] ?> remark</a>
						</span>
					</td>
					<td style="text-align:center">
						<span class="tag <?= $r['is_aktif'] ? 'on' : 'off' ?>"><?= $r['is_aktif'] ? 'Aktif' : 'Nonaktif' ?></span>
					</td>
					<td style="text-align:right; white-space:nowrap">
						<button type="button" class="btn-sm btn-ghost" onclick='openEditStageModal(<?= $json_stage ?>)' style="display:inline-flex; align-items:center; gap:4px; padding:3px 7px; font-size:11.5px">
							Edit
						</button>
						<?= form_open(site_url('flowbuilder/toggle_stage'), array('class' => 'inline', 'style' => 'display:inline; margin-left:4px')) ?>
							<input type="hidden" name="id" value="<?= (int) $r['id_stage'] ?>">
							<input type="hidden" name="is_aktif" value="<?= $r['is_aktif'] ? 0 : 1 ?>">
							<button type="submit" class="btn-sm btn-ghost" style="padding:3px 7px; font-size:11.5px; color:<?= $r['is_aktif'] ? 'var(--warn-ink)' : 'var(--accent)' ?>">
								<?= $r['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
							</button>
						<?= form_close() ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</main>

<!-- ================= MODAL DIALOG POPUP FORM TAHAP ================= -->
<dialog id="dlg-stage" style="border:1px solid var(--border); border-radius:12px; padding:0; max-width:540px; width:92%; background:var(--surface); color:var(--text); box-shadow:var(--shadow); overflow:hidden">
	<div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<h3 id="stage-modal-title" style="margin:0; font-size:16px; font-weight:700; color:var(--text)">
				Tambah Tahap Seleksi
			</h3>
			<span class="muted" style="font-size:12px">
				Tahap baru akan tersedia untuk dirangkai pada template alur rekrutmen.
			</span>
		</div>
		<button type="button" onclick="closeStageModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup pop up">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/save_stage'), array('id' => 'form-stage', 'style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<input type="hidden" name="id_stage" id="stg-id-stage" value="">

			<div>
				<label for="stg-kode" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Kode Stage *</label>
				<input type="text" name="kode_stage" id="stg-kode" placeholder="MIS. TES_KODING" required style="text-transform:uppercase; width:100%">
			</div>

			<div>
				<label for="stg-nama" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Tahap *</label>
				<input type="text" name="nama_tahap" id="stg-nama" placeholder="Mis. Tes Koding Praktik" required style="width:100%">
			</div>

			<div>
				<label for="stg-tipe" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tipe Tahap (Sumbu Report &mdash; 7 Nilai Wajib) *</label>
				<select name="tipe_tahap" id="stg-tipe" required style="width:100%">
					<?php foreach ($tipe_tahap as $tp): ?>
						<option value="<?= $tp ?>"><?= $tp ?></option>
					<?php endforeach; ?>
				</select>
				<div id="stg-sistem-note" class="muted" style="display:none; font-size:11.5px; margin-top:4px">
					Tahap inti sistem: kode &amp; tipe dikunci demi kelancaran integrasi laporan.
				</div>
			</div>

			<div style="margin-top:2px">
				<label style="display:inline-flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer">
					<input type="checkbox" name="is_terminal" id="stg-is-terminal" value="1">
					<span>Tahap terminal (mencapai tahap ini menandakan lamaran selesai, mis. Onboard)</span>
				</label>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeStageModal()">Batal</button>
				<button type="submit" id="stg-btn-submit" class="btn btn-primary" style="padding:8px 18px">
					Simpan Tahap
				</button>
			</div>
		<?= form_close() ?>
	</div>
</dialog>

<script>
var dlgStage = document.getElementById('dlg-stage');
function openAddStageModal() {
	if (!dlgStage) return;
	document.getElementById('form-stage').reset();
	document.getElementById('stg-id-stage').value = '';
	document.getElementById('stg-kode').removeAttribute('readonly');
	document.getElementById('stg-kode').style.background = '';
	document.getElementById('stg-tipe').removeAttribute('disabled');
	document.getElementById('stg-sistem-note').style.display = 'none';
	document.getElementById('stage-modal-title').textContent = 'Tambah Tahap Seleksi';
	document.getElementById('stg-btn-submit').textContent = '+ Tambah Tahap';
	dlgStage.showModal();
}

function openEditStageModal(data) {
	if (!dlgStage || !data) return;
	document.getElementById('form-stage').reset();
	document.getElementById('stg-id-stage').value = data.id_stage || '';
	document.getElementById('stg-kode').value = data.kode_stage || '';
	document.getElementById('stg-nama').value = data.nama_tahap || '';
	document.getElementById('stg-tipe').value = data.tipe_tahap || 'SCREENING';
	document.getElementById('stg-is-terminal').checked = (data.is_terminal == 1);

	if (data.is_sistem == 1) {
		document.getElementById('stg-kode').setAttribute('readonly', 'readonly');
		document.getElementById('stg-kode').style.background = 'var(--surface-2)';
		document.getElementById('stg-tipe').setAttribute('disabled', 'disabled');
		document.getElementById('stg-sistem-note').style.display = 'block';
	} else {
		document.getElementById('stg-kode').removeAttribute('readonly');
		document.getElementById('stg-kode').style.background = '';
		document.getElementById('stg-tipe').removeAttribute('disabled');
		document.getElementById('stg-sistem-note').style.display = 'none';
	}

	document.getElementById('stage-modal-title').textContent = 'Edit Tahap #' + data.id_stage;
	document.getElementById('stg-btn-submit').textContent = 'Simpan Perubahan';
	dlgStage.showModal();
}

function closeStageModal() {
	if (dlgStage) dlgStage.close();
}

if (dlgStage) {
	dlgStage.addEventListener('click', function(e) {
		if (e.target === dlgStage) dlgStage.close();
	});
}

<?php if (!empty($edit_row)): ?>
window.addEventListener('DOMContentLoaded', function() {
	openEditStageModal(<?= json_encode($edit_row) ?>);
});
<?php endif; ?>
</script>

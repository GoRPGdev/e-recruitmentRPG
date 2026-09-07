<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card" style="padding:22px 26px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<a href="<?= site_url('flowbuilder') ?>" class="muted" style="font-size:12px; text-decoration:none">&larr; Flow Builder</a>
				<span class="muted">&bull;</span>
				<a href="<?= site_url('flowbuilder/stages') ?>" class="muted" style="font-size:12px; text-decoration:none">Katalog Tahap &rarr;</a>
			</div>
			<h1 style="margin:0 0 4px; font-size:20px; font-weight:700">
				Remark Keputusan <?= $id_stage ? '&mdash; Tahap #' . (int) $id_stage : '(Semua Tahap)' ?>
			</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Daftar alasan dan keputusan mutasi lamaran pelamar pada alur rekrutmen RPG.
			</p>
		</div>
		<div>
			<button type="button" class="btn btn-sm btn-primary" onclick="openAddRemarkModal()" style="display:inline-flex; align-items:center; gap:6px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Remark</span>
			</button>
		</div>
	</div>

	<?php if ($this->session->flashdata('ok')): ?>
		<div class="flash ok" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('ok')) ?></div>
	<?php endif; ?>
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>
	<?= validation_errors('<div class="flash err" style="margin-bottom:14px">', '</div>') ?>

	<div style="overflow-x:auto">
		<table style="width:100%; border-collapse:collapse">
			<thead>
				<tr>
					<th>Tahap Alur</th>
					<th>Kode Remark</th>
					<th>Label Keputusan</th>
					<th>Efek Status</th>
					<th style="text-align:center">Urutan</th>
					<th style="text-align:center">Status</th>
					<th style="text-align:right">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<?php $json_rem = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8'); ?>
				<tr style="<?= $r['is_aktif'] ? '' : 'opacity:.55' ?>">
					<td><strong><?= html_escape($r['nama_tahap']) ?></strong></td>
					<td><code><?= html_escape($r['kode_remark']) ?></code></td>
					<td><?= html_escape($r['label']) ?></td>
					<td>
						<span class="tag <?= in_array($r['efek_status'], array('HIRED','LANJUT')) ? 'on' : (in_array($r['efek_status'], array('TOLAK','WITHDRAWN','OFFER_DECLINED','NO_SHOW')) ? 'off' : 'warn') ?>">
							<?= html_escape($r['efek_status']) ?>
						</span>
					</td>
					<td style="text-align:center"><?= (int) $r['urutan'] ?></td>
					<td style="text-align:center">
						<span class="tag <?= $r['is_aktif'] ? 'on' : 'off' ?>"><?= $r['is_aktif'] ? 'Aktif' : 'Nonaktif' ?></span>
					</td>
					<td style="text-align:right; white-space:nowrap">
						<button type="button" class="btn-sm btn-ghost" onclick='openEditRemarkModal(<?= $json_rem ?>)' style="display:inline-flex; align-items:center; gap:4px; padding:3px 7px; font-size:11.5px">
							Edit
						</button>
						<?= form_open(site_url('flowbuilder/toggle_remark/' . (int) $r['id_remark']), array('class' => 'inline', 'style' => 'display:inline; margin-left:4px')) ?>
							<input type="hidden" name="id_stage" value="<?= (int) $r['id_stage'] ?>">
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

<!-- ================= MODAL DIALOG POPUP FORM REMARK ================= -->
<dialog id="dlg-remark" style="border:1px solid var(--border); border-radius:12px; padding:0; max-width:540px; width:92%; background:var(--surface); color:var(--text); box-shadow:var(--shadow); overflow:hidden">
	<div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<h3 id="rem-modal-title" style="margin:0; font-size:16px; font-weight:700; color:var(--text)">
				Tambah Remark Keputusan
			</h3>
			<span class="muted" style="font-size:12px">
				Remark digunakan user rekruter saat memproses lamaran kandidat.
			</span>
		</div>
		<button type="button" onclick="closeRemarkModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup pop up">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/save_remark'), array('id' => 'form-remark', 'style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<input type="hidden" name="id_remark" id="rem-id-remark" value="">

			<div>
				<label for="rem-id-stage" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tahap Alur Seleksi *</label>
				<select name="id_stage" id="rem-id-stage" required style="width:100%">
					<?php foreach ($all_stages as $st): ?>
						<option value="<?= (int) $st['id_stage'] ?>" <?= ($id_stage == $st['id_stage']) ? 'selected' : '' ?>>
							<?= html_escape($st['nama_tahap']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="display:grid; grid-template-columns:150px 1fr; gap:12px">
				<div>
					<label for="rem-kode" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Kode Remark *</label>
					<input type="text" name="kode_remark" id="rem-kode" placeholder="MIS. SCV_PASS" required style="text-transform:uppercase; width:100%">
				</div>
				<div>
					<label for="rem-label" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Label Keputusan *</label>
					<input type="text" name="label" id="rem-label" placeholder="Mis. Lolos Seleksi Berkas" required style="width:100%">
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 120px; gap:12px">
				<div>
					<label for="rem-efek" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Efek Status *</label>
					<select name="efek_status" id="rem-efek" required style="width:100%">
						<?php foreach ($efek as $e): ?>
							<option value="<?= $e ?>"><?= $e ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="rem-urutan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Urutan Tampil</label>
					<input type="number" name="urutan" id="rem-urutan" value="0" style="width:100%">
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeRemarkModal()">Batal</button>
				<button type="submit" id="rem-btn-submit" class="btn btn-primary" style="padding:8px 18px">
					Simpan Remark
				</button>
			</div>
		<?= form_close() ?>
	</div>
</dialog>

<script>
var dlgRemark = document.getElementById('dlg-remark');
var defaultStageId = '<?= (int) $id_stage ?>';

function openAddRemarkModal() {
	if (!dlgRemark) return;
	document.getElementById('form-remark').reset();
	document.getElementById('rem-id-remark').value = '';
	if (defaultStageId && defaultStageId !== '0') {
		document.getElementById('rem-id-stage').value = defaultStageId;
	}
	document.getElementById('rem-modal-title').textContent = 'Tambah Remark Keputusan';
	document.getElementById('rem-btn-submit').textContent = '+ Tambah Remark';
	dlgRemark.showModal();
}

function openEditRemarkModal(data) {
	if (!dlgRemark || !data) return;
	document.getElementById('form-remark').reset();
	document.getElementById('rem-id-remark').value = data.id_remark || '';
	document.getElementById('rem-id-stage').value = data.id_stage || '';
	document.getElementById('rem-kode').value = data.kode_remark || '';
	document.getElementById('rem-label').value = data.label || '';
	document.getElementById('rem-efek').value = data.efek_status || 'LANJUT';
	document.getElementById('rem-urutan').value = data.urutan || '0';

	document.getElementById('rem-modal-title').textContent = 'Edit Remark #' + data.id_remark;
	document.getElementById('rem-btn-submit').textContent = 'Simpan Perubahan';
	dlgRemark.showModal();
}

function closeRemarkModal() {
	if (dlgRemark) dlgRemark.close();
}

if (dlgRemark) {
	dlgRemark.addEventListener('click', function(e) {
		if (e.target === dlgRemark) dlgRemark.close();
	});
}

<?php if (!empty($edit_remark)): ?>
window.addEventListener('DOMContentLoaded', function() {
	openEditRemarkModal(<?= json_encode($edit_remark) ?>);
});
<?php endif; ?>
</script>

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: flow/index.php -- Konfigurasi Master Template Alur Seleksi Rekrutmen
 *
 * Fungsi:
 * - Menampilkan daftar template alur seleksi (M_FLOW) aktif dalam organisasi.
 * - Mengatur tahap seleksi, urutan pelaksanaan, batas SLA hari, dan parameter kontak.
 * - Dilindungi hak akses khusus IT_ADMIN / HR_SPV (EDIT_FLOW_TEMPLATE).
 */
?>
<main class="card" style="padding:22px 26px">
	<!-- Flash Notifications -->
	<?php if ($this->session->flashdata('ok')): ?>
		<div class="flash ok" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('ok')) ?></div>
	<?php endif; ?>
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>

	<!-- Header Halaman Flow Builder -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; flex-wrap:wrap; gap:12px">
		<div>
			<h1 style="margin:0 0 4px; font-size:20px; font-weight:700">Alur Seleksi Rekrutmen (Flow Builder)</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Konfigurasi 1 alur seleksi standar RPG. Atur urutan tahapan cukup dengan geser (drag and drop) baris tabel.
			</p>
		</div>
		<div style="display:flex; gap:8px; flex-wrap:wrap">
			<a href="<?= site_url('flowbuilder/stages') ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
				<span>Katalog Master Tahap</span>
			</a>
			<a href="<?= site_url('flowbuilder/remarks') ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
				<span>Kelola Remark</span>
			</a>
			<button type="button" class="btn btn-sm btn-primary" onclick="openAddStageModal()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Tahap ke Alur</span>
			</button>
		</div>
	</div>

	<!-- Kartu Ringkasan Parameter 1 Flow Standar Baku RPG -->
	<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:16px 20px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<span class="tag on" style="font-size:10.5px; font-weight:700; letter-spacing:0.5px">ALUR STANDAR BAKU</span>
				<span class="mono muted" style="font-size:12px; font-weight:600">Versi Alur: v<span id="flow-version-label"><?= (int) $flow['versi'] ?></span></span>
				<span class="muted">&bull;</span>
				<span class="mono muted" style="font-size:12px">Kode: <?= html_escape($flow['kode_flow']) ?></span>
			</div>
			<h2 style="margin:0 0 6px; font-size:17px; font-weight:700">
				<?= html_escape($flow['nama_flow']) ?>
			</h2>
			<div class="muted" style="font-size:12.5px; display:flex; gap:14px; flex-wrap:wrap">
				<span>Total Tahapan: <strong style="color:var(--text)" id="flow-total-stages"><?= count($stages) ?> Tahap</strong></span>
				<span>&bull;</span>
				<span>Maks. Kontak WA: <strong style="color:var(--text)"><?= (int) $flow['maks_upaya_kontak'] ?> Kali</strong></span>
				<span>&bull;</span>
				<span>Penyusunan: <strong style="color:var(--primary)">Drag &amp; Drop Baris</strong></span>
			</div>
		</div>
		<div>
			<button type="button" class="btn btn-sm btn-ghost" onclick="openEditHeaderModal()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
				<span>Ubah Parameter Alur</span>
			</button>
		</div>
	</div>

	<!-- Tabel Urutan Tahapan Alur Rekrutmen RPG (Fitur Drag and Drop) -->
	<div style="overflow-x:auto">
		<table id="table-flow-stages" style="width:100%; border-collapse:collapse; margin:0">
			<thead>
				<tr>
					<th style="width:75px; text-align:center">Urutan</th>
					<th>Tahap Seleksi</th>
					<th style="width:140px">Tipe Sumbu</th>
					<th style="width:130px; text-align:center">Kewajiban</th>
					<th style="width:180px; text-align:right">Aksi Tahap</th>
				</tr>
			</thead>
			<tbody id="flow-stage-tbody">
				<?php if (empty($stages)): ?>
				<tr id="empty-row">
					<td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted)">
						Belum ada tahapan yang dirangkai dalam alur standar ini. Silakan klik <strong>+ Tambah Tahap ke Alur</strong>.
					</td>
				</tr>
				<?php else: ?>
				<?php
				foreach ($stages as $idx => $s):
					$curr_urutan = (int) $s['urutan'];
					$fs_id = (int) $s['id_flow_stage'];
					$json_stage = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8');
				?>
				<tr class="flow-stage-row" data-id="<?= $fs_id ?>" draggable="true" style="cursor:grab; transition:background .15s ease">
					<!-- Kolom Urutan & Drag Handle Grip -->
					<td style="text-align:center; user-select:none">
						<div style="display:inline-flex; align-items:center; justify-content:center; gap:6px" title="Klik dan geser baris ini untuk mengubah susunan urutan alur">
							<svg style="width:13px; height:13px; color:var(--text-muted); opacity:.6; flex-shrink:0" viewBox="0 0 24 24" fill="currentColor">
								<circle cx="9" cy="5" r="1.6"/><circle cx="15" cy="5" r="1.6"/>
								<circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/>
								<circle cx="9" cy="19" r="1.6"/><circle cx="15" cy="19" r="1.6"/>
							</svg>
							<span class="stage-seq-num" style="font-weight:700; font-size:13px; min-width:18px"><?= $curr_urutan ?></span>
						</div>
					</td>

					<!-- Nama Tahap & Kode -->
					<td>
						<strong style="color:var(--text); font-size:13.5px"><?= html_escape($s['nama_tahap']) ?></strong>
						<div class="mono muted" style="font-size:11.5px"><?= html_escape($s['kode_stage']) ?></div>
					</td>

					<!-- Tipe Sumbu Analitik -->
					<td>
						<span class="tag on" style="font-size:11px"><?= html_escape($s['tipe_tahap']) ?></span>
					</td>

					<!-- Status Wajib / Opsional -->
					<td style="text-align:center">
						<?php if ($s['is_wajib']): ?>
							<span class="tag info" style="font-size:11px">&#10003; Wajib</span>
						<?php else: ?>
							<span class="tag off" style="font-size:11px">Opsional</span>
						<?php endif; ?>
					</td>

					<!-- Aksi Tahap -->
					<td style="text-align:right; white-space:nowrap">
						<button type="button" class="btn-sm btn-ghost" onclick='openEditStageModal(<?= $json_stage ?>)' style="padding:4px 8px; font-size:11.5px">
							Edit
						</button>
						<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('class' => 'inline', 'style' => 'display:inline; margin-left:4px', 'onsubmit' => "return confirm('Hapus tahap ini dari alur seleksi? Perubahan ini akan menaikkan versi alur.');")) ?>
							<input type="hidden" name="aksi" value="REMOVE">
							<input type="hidden" name="id_flow_stage" value="<?= $fs_id ?>">
							<button type="submit" class="btn-sm btn-ghost" style="padding:4px 8px; font-size:11.5px; color:var(--crit)">
								Hapus
							</button>
						<?= form_close() ?>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</main>

<!-- ================= MODAL DIALOG: TAMBAH TAHAP KE ALUR ================= -->
<dialog id="dlg-add-stage" style="border:1px solid var(--border); border-radius:12px; padding:0; max-width:540px; width:92%; background:var(--surface); color:var(--text); box-shadow:var(--shadow); overflow:hidden">
	<div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text)">
				Tambah Tahap ke Alur Standar
			</h3>
			<span class="muted" style="font-size:12px">
				Pilih tahap dari katalog master. Tahap baru otomatis ditaruh di posisi paling akhir alur.
			</span>
		</div>
		<button type="button" onclick="closeAddStageModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('style' => 'display:flex; flex-direction:column; gap:14px')) ?>
			<input type="hidden" name="aksi" value="ADD">
			<!-- Urutan otomatis selalu di urutan paling akhir (count + 1) -->
			<input type="hidden" name="urutan" id="add_urutan" value="<?= count($stages) + 1 ?>">

			<div>
				<label for="add_id_stage" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Pilih Tahap Seleksi <span style="color:var(--crit)">*</span></label>
				<select name="id_stage" id="add_id_stage" required style="width:100%">
					<?php if (empty($all_stages)): ?>
						<option value="">-- Semua tahap katalog sudah terpasang di alur --</option>
					<?php else: ?>
						<option value="">-- Pilih Tahap dari Katalog Master --</option>
						<?php foreach ($all_stages as $st): ?>
							<option value="<?= (int) $st['id_stage'] ?>">
								<?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['kode_stage']) ?> &mdash; <?= html_escape($st['tipe_tahap']) ?>)
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</div>

			<div style="padding:4px 0">
				<label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; margin:0">
					<input type="checkbox" name="is_wajib" value="1" checked>
					<span>Tahap Wajib (Harus Dijalankan)</span>
				</label>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:6px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeAddStageModal()">Batal</button>
				<button type="submit" class="btn btn-primary" style="padding:8px 18px">
					+ Tambahkan ke Alur
				</button>
			</div>
		<?= form_close() ?>
	</div>
</dialog>

<!-- ================= MODAL DIALOG: EDIT PARAMETER TAHAP ================= -->
<dialog id="dlg-edit-stage" style="border:1px solid var(--border); border-radius:12px; padding:0; max-width:540px; width:92%; background:var(--surface); color:var(--text); box-shadow:var(--shadow); overflow:hidden">
	<div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<h3 id="edit-stage-title" style="margin:0; font-size:16px; font-weight:700; color:var(--text)">
				Edit Parameter Tahap
			</h3>
			<span id="edit-stage-sub" class="muted" style="font-size:12px">
				Perbarui aturan kewajiban tahap ini.
			</span>
		</div>
		<button type="button" onclick="closeEditStageModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('id' => 'form-edit-stage', 'style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<input type="hidden" name="aksi" value="UPDATE">
			<input type="hidden" name="id_flow_stage" id="edt_id_flow_stage" value="">

			<div style="padding:10px 0">
				<label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
					<input type="checkbox" name="is_wajib" id="edt_is_wajib" value="1">
					<span>Tahap Wajib (Harus Dijalankan)</span>
				</label>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeEditStageModal()">Batal</button>
				<button type="submit" class="btn btn-primary" style="padding:8px 18px">
					Simpan Perubahan
				</button>
			</div>
		<?= form_close() ?>
	</div>
</dialog>

<!-- ================= MODAL DIALOG: UBAH PARAMETER ALUR STANDAR ================= -->
<dialog id="dlg-flow-header" style="border:1px solid var(--border); border-radius:12px; padding:0; max-width:540px; width:92%; background:var(--surface); color:var(--text); box-shadow:var(--shadow); overflow:hidden">
	<div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text)">
				Pengaturan Parameter Alur Standar RPG
			</h3>
			<span class="muted" style="font-size:12px">
				Ubah nama alur dan batas kontak WhatsApp.
			</span>
		</div>
		<button type="button" onclick="closeEditHeaderModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/save_header'), array('style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<input type="hidden" name="id_flow" value="<?= (int) $flow['id_flow'] ?>">
			<input type="hidden" name="kode_flow" value="<?= html_escape($flow['kode_flow']) ?>">
			<input type="hidden" name="tipe_penempatan" value="<?= html_escape($flow['tipe_penempatan']) ?>">

			<div>
				<label for="hdr_nama_flow" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Alur Rekrutmen <span style="color:var(--crit)">*</span></label>
				<input type="text" name="nama_flow" id="hdr_nama_flow" value="<?= html_escape($flow['nama_flow']) ?>" required style="width:100%">
			</div>

			<div>
				<label for="hdr_maks_upaya" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Maks. Kontak WA (Kali) <span style="color:var(--crit)">*</span></label>
				<input type="number" name="maks_upaya_kontak" id="hdr_maks_upaya" value="<?= (int) $flow['maks_upaya_kontak'] ?>" min="1" max="10" required style="width:100%">
				<span class="muted" style="font-size:11px">Standar RPG: 3 kali</span>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeEditHeaderModal()">Batal</button>
				<button type="submit" class="btn btn-primary" style="padding:8px 18px">
					Simpan Pengaturan
				</button>
			</div>
		<?= form_close() ?>
	</div>
</dialog>

<!-- Style Khusus Drag and Drop Baris Tabel -->
<style>
.flow-stage-row.dragging {
	opacity: 0.4;
	background: var(--surface-2) !important;
}
.flow-stage-row.drag-over-top {
	border-top: 3px solid var(--primary) !important;
}
.flow-stage-row.drag-over-bottom {
	border-bottom: 3px solid var(--primary) !important;
}
</style>

<!-- Script Handler Modal Pop-Up & Native HTML5 Drag and Drop Reordering -->
<script>
var dlgAddStage  = document.getElementById('dlg-add-stage');
var dlgEditStage = document.getElementById('dlg-edit-stage');
var dlgFlowHdr   = document.getElementById('dlg-flow-header');

function openAddStageModal() {
	if (dlgAddStage) dlgAddStage.showModal();
}
function closeAddStageModal() {
	if (dlgAddStage) dlgAddStage.close();
}

function openEditStageModal(data) {
	if (!dlgEditStage || !data) return;
	document.getElementById('form-edit-stage').reset();
	document.getElementById('edt_id_flow_stage').value = data.id_flow_stage || '';
	document.getElementById('edt_is_wajib').checked = (data.is_wajib == 1);

	document.getElementById('edit-stage-title').textContent = 'Edit Tahap: ' + (data.nama_tahap || '');
	dlgEditStage.showModal();
}
function closeEditStageModal() {
	if (dlgEditStage) dlgEditStage.close();
}

function openEditHeaderModal() {
	if (dlgFlowHdr) dlgFlowHdr.showModal();
}
function closeEditHeaderModal() {
	if (dlgFlowHdr) dlgFlowHdr.close();
}

[dlgAddStage, dlgEditStage, dlgFlowHdr].forEach(function(dlg) {
	if (dlg) {
		dlg.addEventListener('click', function(e) {
			if (e.target === dlg) dlg.close();
		});
	}
});

/* =========================================================================
   NATIVE HTML5 DRAG AND DROP REORDERING
   ========================================================================= */
document.addEventListener('DOMContentLoaded', function() {
	var tbody = document.getElementById('flow-stage-tbody');
	if (!tbody) return;

	var draggedRow = null;
	var csrfTokenName = '<?= $this->security->get_csrf_token_name() ?>';
	var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
	var reorderUrl = '<?= site_url('flowbuilder/reorder/' . (int) $flow['id_flow']) ?>';

	function updateSequenceNumbers() {
		var rows = tbody.querySelectorAll('.flow-stage-row');
		rows.forEach(function(row, idx) {
			var numElem = row.querySelector('.stage-seq-num');
			if (numElem) {
				numElem.textContent = idx + 1;
			}
		});
		// Perbarui juga nilai input tambah tahap urutan paling akhir
		var addUrutanInput = document.getElementById('add_urutan');
		if (addUrutanInput) {
			addUrutanInput.value = rows.length + 1;
		}
	}

	function saveNewOrder() {
		var rows = tbody.querySelectorAll('.flow-stage-row');
		var orderIds = [];
		rows.forEach(function(row) {
			var id = row.getAttribute('data-id');
			if (id) orderIds.push(parseInt(id, 10));
		});

		if (orderIds.length === 0) return;

		// Kirim via Fetch API / AJAX
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
				showToast('Susunan urutan tahapan alur seleksi berhasil diperbarui.', 'ok');
				// Versi alur naik otomatis di server
				var verEl = document.getElementById('flow-version-label');
				if (verEl) {
					var curV = parseInt(verEl.textContent, 10) || 1;
					verEl.textContent = curV + 1;
				}
			} else {
				showToast('Gagal menyimpan urutan alur: ' + ((data && data.error) ? data.error : 'Terjadi kesalahan sistem'), 'err', 6000);
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
			var allRows = tbody.querySelectorAll('.flow-stage-row');
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

	var rows = tbody.querySelectorAll('.flow-stage-row');
	rows.forEach(attachRowEvents);
});
</script>

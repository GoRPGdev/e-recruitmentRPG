<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<main class="card" style="padding:22px 26px">
	<!-- Header Bar -->
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Konfigurasi Alur Seleksi RPG</div>
			<h1 style="margin:0 0 4px; font-size:20px; font-weight:700">
				Alur Seleksi Standar RPG
			</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Sistem rekrutmen RPG menggunakan 1 alur seleksi standar baku yang fleksibel diatur tahapannya oleh tim HR.
			</p>
		</div>
		<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center">
			<a href="<?= site_url('flowbuilder/stages') ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
				<span>Katalog Tahap &rarr;</span>
			</a>
			<a href="<?= site_url('flowbuilder/remarks') ?>" class="btn btn-sm btn-ghost" style="display:inline-flex; align-items:center; gap:6px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
				<span>Katalog Remark &rarr;</span>
			</a>
			<button type="button" class="btn btn-sm btn-primary" onclick="openAddStageModal()" style="display:inline-flex; align-items:center; gap:6px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Tahap ke Alur</span>
			</button>
		</div>
	</div>

	<!-- Notifikasi Flash -->
	<?php if ($this->session->flashdata('ok')): ?>
		<div class="flash ok" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('ok')) ?></div>
	<?php endif; ?>
	<?php if ($this->session->flashdata('error')): ?>
		<div class="flash err" style="margin-bottom:14px"><?= html_escape($this->session->flashdata('error')) ?></div>
	<?php endif; ?>

	<!-- Kartu Ringkasan Parameter 1 Flow Standar Baku RPG -->
	<div style="background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:16px 20px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px">
		<div>
			<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
				<span class="tag on" style="font-size:10.5px; font-weight:700; letter-spacing:0.5px">ALUR STANDAR BAKU</span>
				<span class="mono muted" style="font-size:12px; font-weight:600">Versi Alur: v<?= (int) $flow['versi'] ?></span>
				<span class="muted">&bull;</span>
				<span class="mono muted" style="font-size:12px">Kode: <?= html_escape($flow['kode_flow']) ?></span>
			</div>
			<h2 style="margin:0 0 6px; font-size:17px; font-weight:700">
				<?= html_escape($flow['nama_flow']) ?>
			</h2>
			<div class="muted" style="font-size:12.5px; display:flex; gap:14px; flex-wrap:wrap">
				<span>Total Tahapan: <strong style="color:var(--text)"><?= count($stages) ?> Tahap</strong></span>
				<span>&bull;</span>
				<span>Maks. Kontak WA: <strong style="color:var(--text)"><?= (int) $flow['maks_upaya_kontak'] ?> Kali</strong></span>
				<span>&bull;</span>
				<span>Waktu Alur: <strong style="color:var(--text)">Fleksibel</strong></span>
			</div>
		</div>
		<div>
			<button type="button" class="btn btn-sm btn-ghost" onclick="openEditHeaderModal()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
				<span>Ubah Parameter Alur</span>
			</button>
		</div>
	</div>

	<!-- Tabel Urutan Tahapan Alur Rekrutmen RPG (100% Fit-to-screen) -->
	<div style="overflow-x:auto">
		<table style="width:100%; border-collapse:collapse; margin:0">
			<thead>
				<tr>
					<th style="width:60px; text-align:center">Urutan</th>
					<th>Tahap Seleksi</th>
					<th style="width:130px">Tipe Sumbu</th>
					<th style="width:110px; text-align:center">Kewajiban</th>
										<th style="width:130px">PIC Role</th>
					<th>Dokumen Terkait</th>
					<th style="width:190px; text-align:right">Aksi Tahap</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($stages)): ?>
				<tr>
					<td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted)">
						Belum ada tahapan yang dirangkai dalam alur standar ini. Silakan klik <strong>+ Tambah Tahap ke Alur</strong>.
					</td>
				</tr>
				<?php else: ?>
				<?php
				$total_stages = count($stages);
				foreach ($stages as $idx => $s):
					$curr_urutan = (int) $s['urutan'];
					$fs_id = (int) $s['id_flow_stage'];
					$docs = $docs_by_stage[$fs_id] ?? array();
					$json_stage = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8');
				?>
				<tr>
					<!-- Urutan & Tombol Reorder Cepat -->
					<td style="text-align:center">
						<div style="display:inline-flex; align-items:center; gap:4px">
							<span style="font-weight:700; font-size:13px"><?= $curr_urutan ?></span>
							<div style="display:flex; flex-direction:column; gap:2px">
								<?php if ($idx > 0): ?>
									<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('style' => 'margin:0; line-height:1')) ?>
										<input type="hidden" name="aksi" value="MOVE">
										<input type="hidden" name="id_flow_stage" value="<?= $fs_id ?>">
										<input type="hidden" name="urutan" value="<?= $curr_urutan - 1 ?>">
										<button type="submit" title="Geser naik" style="background:none; border:none; padding:1px 2px; cursor:pointer; color:var(--text-muted); font-size:10px; line-height:1">&#9650;</button>
									<?= form_close() ?>
								<?php endif; ?>
								<?php if ($idx < $total_stages - 1): ?>
									<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('style' => 'margin:0; line-height:1')) ?>
										<input type="hidden" name="aksi" value="MOVE">
										<input type="hidden" name="id_flow_stage" value="<?= $fs_id ?>">
										<input type="hidden" name="urutan" value="<?= $curr_urutan + 1 ?>">
										<button type="submit" title="Geser turun" style="background:none; border:none; padding:1px 2px; cursor:pointer; color:var(--text-muted); font-size:10px; line-height:1">&#9660;</button>
									<?= form_close() ?>
								<?php endif; ?>
							</div>
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

					<!-- PIC Role -->
					<td>
						<?php if (!empty($s['role_pic'])): ?>
							<span class="tag" style="font-size:11.5px"><?= html_escape($s['role_pic']) ?></span>
						<?php else: ?>
							<span class="muted" style="font-size:12px">&mdash; Terbuka / HR &mdash;</span>
						<?php endif; ?>
					</td>

					<!-- Dokumen Wajib / Terkait -->
					<td>
						<?php if (empty($docs)): ?>
							<span class="muted" style="font-size:12px">Tidak ada syarat berkas</span>
						<?php else: ?>
							<div style="display:flex; flex-wrap:wrap; gap:4px">
								<?php foreach ($docs as $d): ?>
									<span class="tag <?= $d['is_wajib'] ? 'warn' : '' ?>" style="font-size:11px" title="<?= $d['is_wajib'] ? 'Wajib diunggah' : 'Opsional' ?>">
										<?= html_escape($d['nama_dokumen']) ?><?= $d['is_wajib'] ? ' *' : '' ?>
									</span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</td>

					<!-- Aksi Tahap -->
					<td style="text-align:right; white-space:nowrap">
						<button type="button" class="btn-sm btn-ghost" onclick='openEditStageModal(<?= $json_stage ?>)' style="padding:4px 8px; font-size:11.5px">
							Edit
						</button>
						<a href="<?= site_url('flowbuilder/remarks/' . (int) $s['id_stage']) ?>" class="btn-sm btn-ghost" style="padding:4px 8px; font-size:11.5px; text-decoration:none">
							Remark
						</a>
						<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('class' => 'inline', 'style' => 'display:inline; margin-left:4px', 'onsubmit' => "return confirm('Hapus tahap " . html_escape($s['nama_tahap']) . " dari alur seleksi?');")) ?>
							<input type="hidden" name="aksi" value="REMOVE">
							<input type="hidden" name="id_flow_stage" value="<?= $fs_id ?>">
							<button type="submit" class="btn-sm" style="padding:4px 7px; font-size:11.5px; color:var(--crit); background:transparent; border:1px solid color-mix(in srgb, var(--crit) 35%, transparent)">
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
				Pilih tahap seleksi dari katalog untuk dirangkai ke dalam alur RPG.
			</span>
		</div>
		<button type="button" onclick="closeAddStageModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<input type="hidden" name="aksi" value="ADD">

			<div>
				<label for="add_id_stage" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Tahap Seleksi <span style="color:var(--crit)">*</span></label>
				<select name="id_stage" id="add_id_stage" required style="width:100%">
					<option value="">-- Pilih Tahap Seleksi --</option>
					<?php foreach ($all_stages as $st): ?>
						<option value="<?= (int) $st['id_stage'] ?>">
							<?= html_escape($st['nama_tahap']) ?> (<?= html_escape($st['kode_stage']) ?> &mdash; <?= html_escape($st['tipe_tahap']) ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="display:grid; grid-template-columns:120px 1fr; gap:12px">
				<div>
					<label for="add_urutan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Urutan Tahap <span style="color:var(--crit)">*</span></label>
					<input type="number" name="urutan" id="add_urutan" value="<?= count($stages) + 1 ?>" min="1" required style="width:100%">
				</div>
				<div>
					<label for="add_role_pic" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">PIC Role yang Menangani</label>
					<select name="role_pic" id="add_role_pic" style="width:100%">
						<option value="">-- Bebas / Ditangani Tim HR --</option>
						<?php foreach ($roles as $r): ?>
							<option value="<?= html_escape($r['kode_role']) ?>">
								<?= html_escape($r['nama_role']) ?> (<?= html_escape($r['kode_role']) ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:center">
				<div>
					<label for="add_sla_hari" style="display:none">Target</label>
					<input type="hidden" name="sla_hari" id="add_sla_hari" value="">
				</div>
				<div style="padding-top:18px">
					<label style="display:inline-flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer">
						<input type="checkbox" name="is_wajib" value="1" checked>
						<span>Tahap Wajib (Harus Dijalankan)</span>
					</label>
				</div>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeAddStageModal()">Batal</button>
				<button type="submit" class="btn btn-primary" style="padding:8px 18px">
					Tambahkan ke Alur
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
				Perbarui aturan kewajiban dan role PIC.
			</span>
		</div>
		<button type="button" onclick="closeEditStageModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('flowbuilder/stage_action/' . (int) $flow['id_flow']), array('id' => 'form-edit-stage', 'style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<input type="hidden" name="aksi" value="UPDATE">
			<input type="hidden" name="id_flow_stage" id="edt_id_flow_stage" value="">

			<div>
				<label for="edt_role_pic" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">PIC Role yang Menangani</label>
				<select name="role_pic" id="edt_role_pic" style="width:100%">
					<option value="">-- Bebas / Ditangani Tim HR --</option>
					<?php foreach ($roles as $r): ?>
						<option value="<?= html_escape($r['kode_role']) ?>">
							<?= html_escape($r['nama_role']) ?> (<?= html_escape($r['kode_role']) ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:center">
				<div>
					<label for="edt_sla_hari" style="display:none">Target</label>
					<input type="hidden" name="sla_hari" id="edt_sla_hari" value="">
				</div>
				<div style="padding-top:18px">
					<label style="display:inline-flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer">
						<input type="checkbox" name="is_wajib" id="edt_is_wajib" value="1">
						<span>Tahap Wajib (Harus Dijalankan)</span>
					</label>
				</div>
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

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
				<div>
					<label for="hdr_maks_upaya" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Maks. Kontak WA (Kali) <span style="color:var(--crit)">*</span></label>
					<input type="number" name="maks_upaya_kontak" id="hdr_maks_upaya" value="<?= (int) $flow['maks_upaya_kontak'] ?>" min="1" max="10" required style="width:100%">
					<span class="muted" style="font-size:11px">Standar RPG: 3 kali</span>
				</div>
				<div>
					<label for="hdr_sla_total" style="display:none">Waktu</label>
					<input type="hidden" name="sla_total_hari" id="hdr_sla_total" value="">
					<span style="display:none"></span>
				</div>
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

<!-- Script Handler Modal Pop-Up -->
<script>
var dlgAddStage = document.getElementById('dlg-add-stage');
var dlgEditStage = document.getElementById('dlg-edit-stage');
var dlgFlowHdr = document.getElementById('dlg-flow-header');

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
	document.getElementById('edt_role_pic').value = data.role_pic || '';
	if (document.getElementById('edt_sla_hari')) document.getElementById('edt_sla_hari').value = '';
	document.getElementById('edt_is_wajib').checked = (data.is_wajib == 1);

	document.getElementById('edit-stage-title').textContent = 'Edit Tahap: ' + (data.nama_tahap || '');
	document.getElementById('edit-stage-sub').textContent = 'Kode: ' + (data.kode_stage || '') + ' | Tipe: ' + (data.tipe_tahap || '');
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

// Backdrop Click to Close
[dlgAddStage, dlgEditStage, dlgFlowHdr].forEach(function(d) {
	if (d) {
		d.addEventListener('click', function(e) {
			if (e.target === d) d.close();
		});
	}
});
</script>

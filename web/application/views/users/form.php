<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$is_edit = !empty($user['id_user']);
$action_url = $is_edit ? site_url('users/update/' . (int) $user['id_user']) : site_url('users/store');
?>
<div class="card" style="padding:22px 26px; max-width:640px; margin:0 auto">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Konfigurasi Pengguna</div>
			<h1 style="margin:0; font-size:20px"><?= $is_edit ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></h1>
		</div>
		<a href="<?= site_url('users') ?>" class="btn btn-sm btn-ghost">&larr; Kembali ke Daftar</a>
	</div>

	<?php if ($this->input->get('error')): ?>
		<div class="flash err" style="margin-bottom:14px">
			<?= html_escape($this->input->get('error')) ?>
		</div>
	<?php endif; ?>

	<?= form_open($action_url) ?>
		<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
			<div>
				<label for="username">Username <span style="color:var(--crit)">*</span></label>
				<input type="text" id="username" name="username" value="<?= html_escape($user['username'] ?? '') ?>" required <?= $is_edit ? 'readonly style="background:var(--surface-2)"' : '' ?>>
			</div>
			<div>
				<label for="password">Password <?= $is_edit ? '<span class="faint">(Kosongkan bila tak diubah)</span>' : '<span style="color:var(--crit)">*</span>' ?></label>
				<input type="password" id="password" name="password" <?= $is_edit ? '' : 'required' ?>>
			</div>
		</div>

		<label for="nama_snapshot">Nama Lengkap <span style="color:var(--crit)">*</span></label>
		<input type="text" id="nama_snapshot" name="nama_snapshot" value="<?= html_escape($user['nama_snapshot'] ?? '') ?>" required>

		<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
			<div>
				<label for="nik_karyawan">NIK Karyawan (Opsional)</label>
				<input type="text" id="nik_karyawan" name="nik_karyawan" value="<?= html_escape($user['nik_karyawan'] ?? '') ?>" placeholder="e.g. 20240901">
				<div style="font-size:11px; color:var(--text-faint); margin-top:3px">Harus sama persis dengan NIK di Payroll -- dipakai untuk login otomatis (SSO) dari sana.</div>
			</div>
			<div>
				<label for="id_role">Peran (Role) <span style="color:var(--crit)">*</span></label>
				<select id="id_role" name="id_role" required onchange="checkDeptVisibility(this.value)">
					<?php foreach ($roles as $r): ?>
						<option value="<?= (int) $r['id_role'] ?>" data-kode="<?= html_escape($r['kode_role']) ?>" <?= (isset($user['id_role']) && (int) $user['id_role'] === (int) $r['id_role']) ? 'selected' : '' ?>>
							<?= html_escape($r['nama_role']) ?> (<?= html_escape($r['kode_role']) ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div id="dept-group">
			<label for="id_departemen">Departemen Scoping</label>
			<select id="id_departemen" name="id_departemen" onchange="checkScopeExclusive('dept')">
				<option value="">-- Lintas Departemen / Tanpa Batasan (SUPER_ADMIN) --</option>
				<?php foreach ($departemen as $d): ?>
					<option value="<?= (int) $d['id_departemen'] ?>" <?= (isset($user['id_departemen']) && (int) $user['id_departemen'] === (int) $d['id_departemen']) ? 'selected' : '' ?>>
						<?= html_escape($d['nama']) ?> (<?= html_escape($d['kode']) ?>)
					</option>
				<?php endforeach; ?>
			</select>
			<div class="muted" style="font-size:12px; margin-top:3px">
				Untuk Manager Departemen (HQ) -- satu departemen saja. Isi ini <b>atau</b> Wilayah di bawah, tidak dua-duanya.
			</div>
		</div>

		<div id="region-group" style="margin-top:14px">
			<label for="region">Wilayah Scoping (Regional Manager / Area Leader)</label>
			<select id="region" name="region" onchange="checkScopeExclusive('region')">
				<option value="">-- Tidak dibatasi wilayah --</option>
				<?php foreach ($regions as $rgn): ?>
					<option value="<?= html_escape($rgn) ?>" <?= (isset($user['region']) && (string) $user['region'] === (string) $rgn) ? 'selected' : '' ?>>
						<?= html_escape($rgn) ?>
					</option>
				<?php endforeach; ?>
			</select>
			<div class="muted" style="font-size:12px; margin-top:3px">
				Untuk yang mengawasi banyak outlet sekaligus di satu wilayah (setara "Area Leader" di Payroll) -- dicocokkan ke wilayah outlet, lintas departemen.
			</div>
		</div>

		<div style="margin-top:14px">
			<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer">
				<input type="checkbox" name="is_aktif" value="1" <?= (!isset($user['is_aktif']) || $user['is_aktif']) ? 'checked' : '' ?>>
				<span>Akun berstatus aktif (bisa login ke sistem)</span>
			</label>
		</div>

		<div style="margin-top:20px; display:flex; justify-content:flex-end; gap:8px">
			<a href="<?= site_url('users') ?>" class="btn btn-sm btn-ghost">Batal</a>
			<button type="submit" class="btn btn-sm btn-primary"><?= $is_edit ? 'Simpan Perubahan' : 'Buat Pengguna' ?></button>
		</div>
	<?= form_close() ?>
</div>

<script>
function checkDeptVisibility(roleId) {
	var sel = document.getElementById('id_role');
	var opt = sel.options[sel.selectedIndex];
	var kode = opt ? opt.getAttribute('data-kode') : '';
	if (kode === 'SUPER_ADMIN') {
		document.getElementById('id_departemen').value = '';
		document.getElementById('region').value = '';
	}
}
// Departemen dan Wilayah saling eksklusif (lihat CK_MUSERS_scope di database)
function checkScopeExclusive(justChanged) {
	if (justChanged === 'dept' && document.getElementById('id_departemen').value !== '') {
		document.getElementById('region').value = '';
	}
	if (justChanged === 'region' && document.getElementById('region').value !== '') {
		document.getElementById('id_departemen').value = '';
	}
}
</script>

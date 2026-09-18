<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: users/index.php -- Manajemen Akun Pengguna Internal & Hak Akses
 *
 * Fungsi:
 * - Menampilkan daftar akun pengguna sistem, role penugasan, dan status keaktifan.
 * - Mengelola pembuatan akun baru, pembaruan data kredensial, dan penonaktifan akun via SP.
 * - Terproteksi hak akses administrator sistem (IT_ADMIN / HR_SPV).
 */
?>
<div class="card" style="padding:22px 26px">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px">
		<div>
			<div class="eyebrow" style="margin-bottom:3px">Konfigurasi Hak Akses</div>
			<h1 style="margin:0 0 4px; font-size:20px">Manajemen Pengguna</h1>
			<p class="muted" style="margin:0; font-size:13px">
				Kelola akun pengguna, penetapan peran (role), dan scoping departemen kerja.
			</p>
		</div>
		<div>
			<button type="button" class="btn btn-sm btn-primary" onclick="openAddUserModal()" style="display:inline-flex; align-items:center; gap:6px">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Pengguna</span>
			</button>
		</div>
	</div>

	<?php if ($this->input->get('error')): ?>
		<div class="flash err" style="margin-bottom:14px">
			<?= html_escape($this->input->get('error')) ?>
		</div>
	<?php endif; ?>

	<?php if ($this->input->get('success')): ?>
		<div class="flash ok" style="margin-bottom:14px">
			<?php
			$s = $this->input->get('success');
			if ($s === 'created') echo 'Pengguna baru berhasil dibuat.';
			elseif ($s === 'updated') echo 'Data pengguna berhasil diperbarui.';
			elseif ($s === 'toggled') echo 'Status pengguna berhasil diubah.';
			elseif ($s === 'deleted') echo 'Pengguna berhasil dinonaktifkan / dihapus.';
			else echo 'Operasi berhasil dilakukan.';
			?>
		</div>
	<?php endif; ?>

	<!-- Filter Bar -->
	<form method="get" action="<?= site_url('users') ?>" style="margin:0 0 18px; padding:12px 14px; background:var(--surface-2); border-radius:8px">
		<div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
			<div style="flex:1; min-width:180px">
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Cari Pengguna</label>
				<input type="text" name="q" value="<?= html_escape($filter['q'] ?? '') ?>" placeholder="Username, Nama, atau NIK..." style="padding:5px 8px; font-size:12.5px">
			</div>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Peran (Role)</label>
				<select name="role" style="width:auto; min-width:130px; padding:5px 8px; font-size:12.5px">
					<option value="">Semua Role</option>
					<?php foreach ($roles as $r): ?>
						<option value="<?= html_escape($r['kode_role']) ?>" <?= (isset($filter['role']) && $filter['role'] === $r['kode_role']) ? 'selected' : '' ?>>
							<?= html_escape($r['nama_role']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label style="margin:0 0 3px; font-size:11px" class="eyebrow">Status Akun</label>
				<select name="aktif" style="width:auto; padding:5px 8px; font-size:12.5px">
					<option value="">Semua Status</option>
					<option value="1" <?= (isset($filter['is_aktif']) && $filter['is_aktif'] === '1') ? 'selected' : '' ?>>Aktif</option>
					<option value="0" <?= (isset($filter['is_aktif']) && $filter['is_aktif'] === '0') ? 'selected' : '' ?>>Non-Aktif</option>
				</select>
			</div>
			<div style="display:flex; gap:6px; margin-left:auto">
				<button type="submit" class="btn btn-sm">Filter</button>
				<a class="btn btn-sm btn-ghost" href="<?= site_url('users') ?>">Reset</a>
			</div>
		</div>
	</form>

	<div style="overflow-x:auto">
		<table style="margin:0">
			<thead>
				<tr>
					<th>Pengguna</th>
					<th>Peran (Role)</th>
					<th>Departemen</th>
					<th>NIK</th>
					<th>Status</th>
					<th style="text-align:right">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($users)): ?>
				<tr>
					<td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted)">
						Tidak ada data pengguna ditemukan.
					</td>
				</tr>
				<?php else: ?>
				<?php foreach ($users as $u): ?>
				<tr style="<?= empty($u['is_aktif']) ? 'opacity:0.55' : '' ?>">
					<td>
						<strong style="color:var(--text); font-size:13.5px"><?= html_escape($u['nama_snapshot']) ?></strong>
						<div class="mono faint" style="font-size:12px">@<?= html_escape($u['username']) ?></div>
					</td>
					<td>
						<span class="tag <?= $u['kode_role'] === 'SUPER_ADMIN' ? 'on' : '' ?>" style="font-weight:600">
							<?= html_escape($u['kode_role']) ?>
						</span>
					</td>
					<td>
						<?php if (!empty($u['nama_dept'])): ?>
							<span class="tag tag--info"><?= html_escape($u['nama_dept']) ?></span>
						<?php elseif ($u['id_departemen'] === null): ?>
							<span class="muted" style="font-size:12px">&mdash; Lintas Departemen (Global) &mdash;</span>
						<?php else: ?>
							<span class="muted"><?= html_escape($u['departemen_snapshot'] ?: '-') ?></span>
						<?php endif; ?>
					</td>
					<td class="mono"><?= html_escape($u['nik_karyawan'] ?: '-') ?></td>
					<td>
						<span class="tag <?= $u['is_aktif'] ? 'on' : 'off' ?>">
							<?= $u['is_aktif'] ? 'Aktif' : 'Non-Aktif' ?>
						</span>
					</td>
					<td style="text-align:right; white-space:nowrap">
						<a class="btn btn-sm btn-ghost" href="<?= site_url('users/edit/' . (int) $u['id_user']) ?>">Edit</a>
						<?= form_open(site_url('users/toggle/' . (int) $u['id_user']), array('class' => 'inline')) ?>
							<input type="hidden" name="is_aktif" value="<?= $u['is_aktif'] ? 0 : 1 ?>">
							<button type="submit" class="btn btn-sm <?= $u['is_aktif'] ? 'btn-ghost' : 'btn-primary' ?>" style="font-size:11px; padding:3px 7px">
								<?= $u['is_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
							</button>
						<?= form_close() ?>
						<?php if ((int)$u['id_user'] !== (int)($this->session->userdata('auth_user')['id_user'] ?? 0)): ?>
						<?= form_open(site_url('users/delete/' . (int) $u['id_user']), array('class' => 'inline', 'onsubmit' => "return confirm('Hapus / nonaktifkan pengguna ini?');")) ?>
							<button type="submit" class="btn btn-sm" style="font-size:11px; padding:3px 7px; color:var(--crit); background:transparent; border:1px solid color-mix(in srgb, var(--crit) 40%, transparent)">
								Hapus
							</button>
						<?= form_close() ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
		<!-- BAR PAGINASI HALAMAN STANDAR -->
		<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-top:1px solid var(--border); background:var(--surface); flex-wrap:wrap; gap:12px">
			<div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap">
				<div class="muted" style="font-size:12.5px">
					Menampilkan <strong><?= count($users ?? array()) ?></strong> dari <strong><?= (int) ($total ?? 0) ?></strong> pengguna (Halaman <?= (int) ($page ?? 1) ?> dari <?= (int) ($pages ?? 1) ?>)
				</div>
				<div style="display:flex; align-items:center; gap:6px; font-size:12px" class="muted">
					<span>Tampilkan:</span>
					<select onchange="location.href=this.value" style="padding:3px 6px; font-size:12px; width:auto; border-radius:6px; background:var(--surface-2)">
						<?php foreach (array(10, 20, 50) as $opt): ?>
							<option value="<?= site_url('users?' . http_build_query(array_merge($filter, array('per' => $opt, 'page' => 1)))) ?>" <?= ((int)($per ?? 10)) === $opt ? 'selected' : '' ?>>
								<?= $opt ?> baris
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div style="display:flex; align-items:center; gap:4px">
				<?php if (($page ?? 1) > 1): ?>
					<a href="<?= site_url('users?' . http_build_query(array_merge($filter, array('page' => 1)))) ?>" class="btn btn-sm btn-ghost" title="Halaman Pertama" style="padding:4px 8px">&laquo;</a>
					<a href="<?= site_url('users?' . http_build_query(array_merge($filter, array('page' => $page - 1)))) ?>" class="btn btn-sm btn-ghost" style="padding:4px 10px">&larr; Prev</a>
				<?php else: ?>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 8px">&laquo;</button>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 10px">&larr; Prev</button>
				<?php endif; ?>

				<?php for ($i = max(1, ($page ?? 1) - 2); $i <= min(($pages ?? 1), ($page ?? 1) + 2); $i++): ?>
					<a href="<?= site_url('users?' . http_build_query(array_merge($filter, array('page' => $i)))) ?>"
						class="btn btn-sm <?= $i === ($page ?? 1) ? 'btn-primary' : 'btn-ghost' ?>" style="min-width:30px; text-align:center; padding:4px 8px">
						<?= $i ?>
					</a>
				<?php endfor; ?>

				<?php if (($page ?? 1) < ($pages ?? 1)): ?>
					<a href="<?= site_url('users?' . http_build_query(array_merge($filter, array('page' => $page + 1)))) ?>" class="btn btn-sm btn-ghost" style="padding:4px 10px">Next &rarr;</a>
					<a href="<?= site_url('users?' . http_build_query(array_merge($filter, array('page' => $pages)))) ?>" class="btn btn-sm btn-ghost" title="Halaman Terakhir" style="padding:4px 8px">&raquo;</a>
				<?php else: ?>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 10px">Next &rarr;</button>
					<button type="button" class="btn btn-sm btn-ghost" disabled style="opacity:0.4; padding:4px 8px">&raquo;</button>
				<?php endif; ?>
			</div>
		</div>
</div>

<!-- ================= MODAL DIALOG POPUP TAMBAH PENGGUNA ================= -->
<dialog id="dlg-user-add" style="border:1px solid var(--border); border-radius:12px; padding:0; max-width:560px; width:92%; background:var(--surface); color:var(--text); box-shadow:var(--shadow); overflow:hidden">
	<div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface-2)">
		<div>
			<h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text)">
				Tambah Pengguna Baru
			</h3>
			<span class="muted" style="font-size:12px">
				Lengkapi data akun dan role untuk akses sistem.
			</span>
		</div>
		<button type="button" onclick="closeAddUserModal()" style="background:none; border:none; color:var(--text-muted); font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px" title="Tutup pop up">&times;</button>
	</div>

	<div style="padding:20px">
		<?= form_open(site_url('users/store'), array('style' => 'display:flex; flex-direction:column; gap:12px')) ?>
			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
				<div>
					<label for="u_username" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Username <span style="color:var(--crit)">*</span></label>
					<input type="text" id="u_username" name="username" required style="width:100%" placeholder="mis. andi.hr">
				</div>
				<div>
					<label for="u_password" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Password <span style="color:var(--crit)">*</span></label>
					<input type="password" id="u_password" name="password" required style="width:100%" placeholder="Min. 6 karakter">
				</div>
			</div>

			<div>
				<label for="u_nama_snapshot" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Nama Lengkap <span style="color:var(--crit)">*</span></label>
				<input type="text" id="u_nama_snapshot" name="nama_snapshot" required style="width:100%" placeholder="Nama lengkap sesuai KTP/identitas">
			</div>

			<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
				<div>
					<label for="u_nik_karyawan" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">NIK Karyawan (Opsional)</label>
					<input type="text" id="u_nik_karyawan" name="nik_karyawan" placeholder="e.g. 20240901" style="width:100%">
					<div style="font-size:11px; color:var(--text-faint); margin-top:3px">Harus sama persis dengan NIK di Payroll -- dipakai untuk login otomatis (SSO) dari sana.</div>
				</div>
				<div>
					<label for="u_id_role" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Peran (Role) <span style="color:var(--crit)">*</span></label>
					<select id="u_id_role" name="id_role" required style="width:100%">
						<?php foreach ($roles as $r): ?>
							<option value="<?= (int) $r['id_role'] ?>">
								<?= html_escape($r['nama_role']) ?> (<?= html_escape($r['kode_role']) ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div>
				<label for="u_id_departemen" style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px">Departemen Scoping</label>
				<select id="u_id_departemen" name="id_departemen" style="width:100%">
					<option value="">-- Lintas Departemen / Tanpa Batasan (SUPER_ADMIN / HR) --</option>
					<?php foreach ($departemen as $d): ?>
						<option value="<?= (int) $d['id_departemen'] ?>">
							<?= html_escape($d['nama']) ?> (<?= html_escape($d['kode']) ?>)
						</option>
					<?php endforeach; ?>
				</select>
				<div class="muted" style="font-size:11.5px; margin-top:3px">
					Wajib untuk <code>USER_DEPT</code> agar pelamar dan formasi terisolasi ke divisi yang bersangkutan.
				</div>
			</div>

			<div style="margin-top:2px">
				<label style="display:inline-flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer">
					<input type="checkbox" name="is_aktif" value="1" checked>
					<span>Akun berstatus aktif (dapat langsung login)</span>
				</label>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px; padding-top:14px; border-top:1px solid var(--border)">
				<button type="button" class="btn btn-ghost" onclick="closeAddUserModal()">Batal</button>
				<button type="submit" class="btn btn-primary" style="padding:8px 18px">
					Buat Pengguna
				</button>
			</div>
		<?= form_close() ?>
	</div>
</dialog>

<script>
var dlgUserAdd = document.getElementById('dlg-user-add');
function openAddUserModal() {
	if (dlgUserAdd) dlgUserAdd.showModal();
}
function closeAddUserModal() {
	if (dlgUserAdd) dlgUserAdd.close();
}
if (dlgUserAdd) {
	dlgUserAdd.addEventListener('click', function(e) {
		if (e.target === dlgUserAdd) dlgUserAdd.close();
	});
}
</script>

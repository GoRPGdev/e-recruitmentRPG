<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
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
			<a href="<?= site_url('users/create') ?>" class="btn btn-sm btn-primary">
				<svg style="width:13px; height:13px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
				<span>+ Tambah Pengguna</span>
			</a>
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
</div>

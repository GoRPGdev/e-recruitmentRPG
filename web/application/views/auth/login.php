<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View: auth/login.php -- Halaman Otentikasi & Masuk Portal Internal E-Recruitment RPG
 *
 * Fungsi:
 * - Menyediakan form login bagi seluruh user internal RPG (IT Admin, HR Admin, HR Spv, User Dept, BOD, Viewer).
 * - Menangani verifikasi kredensial berbasis password hash terenkripsi.
 * - Menginisialisasi session RBAC (permissions) saat otentikasi berhasil.
 */
?>
<main class="card" style="box-shadow:var(--shadow); border-radius:12px; padding:28px 30px">
	<div style="display:flex; align-items:center; gap:12px; margin-bottom:18px">
		<div style="width:38px; height:38px; border-radius:10px; background:var(--accent); color:var(--accent-contrast); display:grid; place-items:center; font-family:'Archivo', sans-serif; font-weight:700; font-size:16px">
			RPG
		</div>
		<div>
			<h1 style="font-size:19px; margin:0 0 2px">e-Recruitment</h1>
			<div class="muted" style="font-size:12px">Ratu Pertiwi Group &middot; Internal Portal</div>
		</div>
	</div>

	<p class="muted" style="font-size:13px; margin-bottom:16px">
		Masuk menggunakan akun pengguna terdaftar Anda.
	</p>

	<?= validation_errors('<div class="flash err" style="margin-bottom:14px">', '</div>') ?>

	<?= form_open(site_url('auth/login'), array('id' => 'loginForm')) ?>
		<div style="margin-bottom:14px">
			<label for="username" style="margin:0 0 4px">Username</label>
			<input type="text" id="username" name="username" value="<?= set_value('username') ?>" autocomplete="username" placeholder="Masukkan username" autofocus required>
		</div>

		<div style="margin-bottom:18px">
			<label for="password" style="margin:0 0 4px">Password</label>
			<input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
		</div>

		<button type="submit" style="width:100%; padding:10px; font-size:14px">Masuk ke Sistem</button>
	<?= form_close() ?>

	<div style="margin-top:24px; padding-top:16px; border-top:1px solid var(--border)">
		<div class="eyebrow" style="margin-bottom:8px; display:flex; justify-content:space-between; align-items:center">
			<span>Akses Cepat Pengujian (Role Aktif)</span>
			<span style="color:var(--text-faint); font-weight:normal; text-transform:none">pass: demo123</span>
		</div>
		<div style="display:flex; flex-direction:column; gap:8px">
			<button type="button" class="btn btn-sm btn-primary" onclick="fillLogin('demo_super_admin')" style="width:100%; justify-content:center; padding:9px 12px; font-size:12.5px; background:var(--accent-ink); color:#fff">
				<svg style="width:15px; height:15px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
				<strong>SUPER_ADMIN (Akses Penuh Semua Modul & Departemen)</strong>
			</button>
			<button type="button" class="btn btn-sm btn-ghost" onclick="fillLogin('demo_user_dept')" style="width:100%; justify-content:center; padding:8px 12px; font-size:12px">
				<svg style="width:14px; height:14px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
				<strong>USER_DEPT (Departemen Marketing)</strong>
			</button>
		</div>
	</div>
</main>

<script>
function fillLogin(u) {
	document.getElementById('username').value = u;
	document.getElementById('password').value = 'demo123';
	document.getElementById('loginForm').submit();
}
</script>

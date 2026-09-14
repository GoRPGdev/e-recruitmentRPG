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
</main>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div style="text-align:center; margin-bottom:20px">
	<div style="display:inline-flex; align-items:center; gap:12px">
		<div style="width:48px; height:48px; border-radius:10px; overflow:hidden; background:#000000; box-shadow:0 3px 10px rgba(0,0,0,0.15); flex:none; display:flex; align-items:center; justify-content:center">
			<img src="<?= base_url('assets/img/logo-sm.png') ?>" alt="Logo RPG" style="width:100%; height:100%; object-fit:contain; display:block">
		</div>
		<div style="text-align:left">
			<div style="font-family:'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif; font-weight:700; font-size:17px; color:var(--text); line-height:1.2">Ratu Pertiwi Group</div>
			<div style="font-size:11.5px; color:var(--text-faint)">Formulir Kelengkapan Data Pelamar &amp; Onboarding</div>
		</div>
	</div>
</div>

<div class="card" style="padding:32px 28px; text-align:center; max-width:540px; margin:20px auto 40px; border-top:4px solid var(--crit)">
	<div style="width:54px; height:54px; border-radius:50%; background:var(--crit-soft); color:var(--crit); display:grid; place-items:center; margin:0 auto 16px; font-size:24px">
		✕
	</div>

	<h1 style="font-size:20px; font-weight:700; color:var(--text); margin-bottom:8px">
		Tautan Tidak Dapat Digunakan
	</h1>

	<p class="muted" style="font-size:13.5px; line-height:1.6; margin-bottom:20px">
		<?php if (!empty($t['dipakai_pada'])): ?>
			Tautan formulir pelamar ini sudah pernah digunakan untuk mengirim data pada <strong><?= html_escape(substr($t['dipakai_pada'], 0, 16)) ?></strong>.
		<?php elseif (!empty($t['is_revoked'])): ?>
			Tautan formulir pelamar ini telah dinonaktifkan / dicabut oleh Tim HR Ratu Pertiwi Group.
		<?php else: ?>
			Masa berlaku tautan formulir ini telah kedaluwarsa.
		<?php endif; ?>
	</p>

	<div style="background:var(--surface-2); padding:14px; border-radius:8px; font-size:12.5px; color:var(--text-muted); margin-bottom:20px">
		Jika Anda memerlukan akses kembali atau ingin memperbarui data, silakan hubungi tim HR Ratu Pertiwi Group melalui WhatsApp atau email resmi.
	</div>

	<a href="<?= site_url('auth/login') ?>" class="btn btn-ghost" style="font-size:13px; text-decoration:none">
		Kembali ke Beranda
	</a>
</div>

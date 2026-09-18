<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* -------------------------------------------------------------------------
 * Konfigurasi khusus E-Recruitment RPG (di-autoload).
 * Tidak ada rahasia di sini -- override lewat environment variable.
 * ------------------------------------------------------------------------- */

// Penyimpanan file kandidat -- WAJIB di luar webroot. Samakan dengan
// 'storage' di tools/koneksi.local.php.
$config['erec_storage_path'] = getenv('EREC_STORAGE_PATH') ?: 'D:/erecruitment-storage';

// Upload CV
$config['erec_cv_max_bytes'] = 5 * 1024 * 1024;   // 5 MB
$config['erec_cv_ext']       = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
$config['erec_cv_mime']      = array(
	'application/pdf',
	'application/msword',
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'image/jpeg',
	'image/png',
);

// Rate limit form publik (per url_slug + IP)
$config['erec_rate_window_min'] = 10;
$config['erec_rate_max']        = 3;

// Versi teks consent yang sedang berlaku (disimpan ke CANDIDATES.consent_versi)
$config['erec_consent_versi'] = 'v1-2026';

// SSO dari aplikasi Payroll RPG (lihat controllers/Sso.php).
// WAJIB diisi via environment variable di server produksi (Apache SetEnv /
// IIS web.config) -- JANGAN PERNAH taruh nilai asli di file ini / commit ke Git.
// Kalau kosong, semua percobaan SSO ditolak (fail closed, bukan fail open).
// Nilai ini HARUS identik dengan secret yang dipasang di sisi Payroll.
$config['erec_sso_secret']  = getenv('EREC_SSO_SECRET') ?: '';
$config['erec_sso_max_age'] = 90; // detik -- toleransi delay jaringan + selisih jam server

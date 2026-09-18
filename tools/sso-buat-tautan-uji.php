<?php
/**
 * Alat bantu TESTING LOKAL SAJA -- meniru apa yang nanti dilakukan Payroll
 * saat user klik menu "E-Recruitment": membuat tautan SSO yang sah.
 *
 * TIDAK menyentuh Payroll sama sekali. TIDAK butuh database Payroll.
 * Berguna untuk menguji sisi e-recruitment (Sso.php) secara terpisah,
 * sebelum kode di sisi Payroll benar-benar dibuat.
 *
 * Pakai:
 *   php tools/sso-buat-tautan-uji.php <SECRET> <NIK> [base-url]
 *
 * Contoh:
 *   php tools/sso-buat-tautan-uji.php dev-secret-testing-saja 20240901
 *   php tools/sso-buat-tautan-uji.php dev-secret-testing-saja 20240901 http://localhost:8080
 *
 * SECRET harus PERSIS SAMA dengan EREC_SSO_SECRET yang dipasang di
 * lingkungan e-recruitment lokal Anda (tools/koneksi.local.php tidak
 * menyimpan ini -- pasang lewat environment variable, lihat
 * web/application/config/erecruitment.php).
 *
 * NIK harus sudah terdaftar (dan aktif) di M_USERS lewat layar
 * Manajemen Pengguna e-recruitment -- kalau belum, tautan akan sah tapi
 * tetap ditolak dengan pesan "Akun Anda belum didaftarkan" (itu memang
 * perilaku yang benar, bukan bug).
 */

if (php_sapi_name() !== 'cli') { die("Jalankan dari command line.\n"); }

$secret   = isset($argv[1]) ? $argv[1] : null;
$nik      = isset($argv[2]) ? $argv[2] : null;
$base_url = isset($argv[3]) ? rtrim($argv[3], '/') : 'http://localhost';

if (!$secret || !$nik) {
    echo "Pakai: php tools/sso-buat-tautan-uji.php <SECRET> <NIK> [base-url]\n";
    echo "Contoh: php tools/sso-buat-tautan-uji.php dev-secret-testing-saja 20240901 http://localhost:8080\n";
    exit(1);
}

$exp = time() + 60;
$sig = hash_hmac('sha256', $nik . '|' . $exp, $secret);

$url = $base_url . '/sso?' . http_build_query(array(
    'nik' => $nik,
    'exp' => $exp,
    'sig' => $sig,
));

echo "\n";
echo "Tautan berlaku ~60 detik dari sekarang -- buka SEGERA di browser:\n\n";
echo "  $url\n\n";
echo "Kalau NIK '$nik' sudah terdaftar aktif di M_USERS -> langsung masuk (dashboard/requisitions).\n";
echo "Kalau belum terdaftar               -> halaman \"Akun Anda belum didaftarkan\".\n";
echo "Kalau dibuka setelah >60 detik       -> halaman \"tautan kedaluwarsa\", coba generate lagi.\n\n";

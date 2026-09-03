<?php
/**
 * Salin file ini jadi tools/koneksi.local.php lalu isi sesuai mesin masing-masing.
 * koneksi.local.php SUDAH di-gitignore — jangan pernah di-commit.
 */
return array(
    'host'     => 'localhost\\SQLEXPRESS', // atau '192.168.1.10,1433'
    'database' => 'RPG_EREC_DEV_KIKI',     // Kahfi: RPG_EREC_DEV_KAHFI
    'user'     => 'sa',
    'password' => 'GANTI_DENGAN_PASSWORD',
    // Folder penyimpanan file kandidat — WAJIB di luar webroot.
    'storage'  => 'D:\\erecruitment-storage',
);

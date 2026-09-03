<?php
/**
 * Bikin / reset user login. Belum ada halaman registrasi -- ini jalan masuk
 * user pertama untuk mengetes skeleton.
 *
 *   php tools/mkuser.php <username> <password> <kode_role>
 *   php tools/mkuser.php admin rahasia123 IT_ADMIN
 *
 * kode_role harus salah satu yang ada di M_ROLES (IT_ADMIN, HR_ADMIN,
 * HR_SPV, USER_DEPT, BOD, VIEWER). Hash pakai password_hash() PHP.
 */
if (php_sapi_name() !== 'cli') { die("Jalankan dari command line.\n"); }

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) { die("tools/koneksi.local.php belum ada.\n"); }
$cfg = require $cfgPath;

array_shift($argv);
$username = isset($argv[0]) ? $argv[0] : null;
$password = isset($argv[1]) ? $argv[1] : null;
$role     = isset($argv[2]) ? $argv[2] : null;
if ($username === null || $password === null || $role === null) {
    die("Usage: php tools/mkuser.php <username> <password> <kode_role>\n");
}

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'], 'UID' => $cfg['user'], 'PWD' => $cfg['password'],
    'CharacterSet' => 'UTF-8',
));
if ($conn === false) { die("Koneksi gagal: " . print_r(sqlsrv_errors(), true)); }

$rc = sqlsrv_query($conn, "SELECT id_role FROM dbo.M_ROLES WHERE kode_role = ?", array($role));
if ($rc === false || !sqlsrv_fetch_array($rc)) {
    die("kode_role '$role' tidak ada di M_ROLES.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$chk = sqlsrv_query($conn, "SELECT id_user FROM dbo.M_USERS WHERE username = ?", array($username));
$exists = ($chk !== false && sqlsrv_fetch_array($chk));

if ($exists) {
    $ok = sqlsrv_query($conn,
        "UPDATE dbo.M_USERS
            SET password_hash = ?, is_aktif = 1,
                id_role = (SELECT id_role FROM dbo.M_ROLES WHERE kode_role = ?)
          WHERE username = ?",
        array($hash, $role, $username));
    $aksi = 'diperbarui';
} else {
    $ok = sqlsrv_query($conn,
        "INSERT INTO dbo.M_USERS (username, password_hash, nama_snapshot, id_role)
         VALUES (?, ?, ?, (SELECT id_role FROM dbo.M_ROLES WHERE kode_role = ?))",
        array($username, $hash, $username, $role));
    $aksi = 'dibuat';
}

if ($ok === false) { die("Gagal: " . print_r(sqlsrv_errors(), true)); }
echo "User '$username' $aksi (role $role).\n";

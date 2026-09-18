<?php
/**
 * Bikin / reset user login via NIK.
 *
 *   php tools/mkuser.php <nik_karyawan> <password> <nama> <kode_role> [kode_departemen]
 *   php tools/mkuser.php EMP-001 rahasia123 "Administrator IT" SUPER_ADMIN
 *   php tools/mkuser.php EMP-010 rahasia123 "Budi Marketing" USER_DEPT MKT
 */
if (php_sapi_name() !== 'cli') { die("Jalankan dari command line.\n"); }

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) { die("tools/koneksi.local.php belum ada.\n"); }
$cfg = require $cfgPath;

array_shift($argv);
$nik      = isset($argv[0]) ? $argv[0] : null;
$password = isset($argv[1]) ? $argv[1] : null;
$nama     = isset($argv[2]) ? $argv[2] : null;
$role     = isset($argv[3]) ? $argv[3] : null;
$dept     = isset($argv[4]) ? $argv[4] : null;
if ($nik === null || $password === null || $nama === null || $role === null) {
    die("Usage: php tools/mkuser.php <nik_karyawan> <password> <nama> <kode_role> [kode_departemen]\n");
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

$id_dept = null;
if ($dept !== null) {
    $dc = sqlsrv_query($conn, "SELECT id_departemen FROM dbo.M_DEPARTEMEN WHERE kode = ?", array($dept));
    $dr = ($dc !== false) ? sqlsrv_fetch_array($dc, SQLSRV_FETCH_NUMERIC) : false;
    if (!$dr) { die("kode_departemen '$dept' tidak ada di M_DEPARTEMEN.\n"); }
    $id_dept = (int) $dr[0];
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$chk = sqlsrv_query($conn, "SELECT id_user FROM dbo.M_USERS WHERE nik_karyawan = ?", array($nik));
$exists = ($chk !== false && sqlsrv_fetch_array($chk));

if ($exists) {
    $ok = sqlsrv_query($conn,
        "UPDATE dbo.M_USERS
            SET password_hash = ?, nama_snapshot = ?, is_aktif = 1,
                id_role = (SELECT id_role FROM dbo.M_ROLES WHERE kode_role = ?),
                id_departemen = ?
          WHERE nik_karyawan = ?",
        array($hash, $nama, $role, $id_dept, $nik));
    $aksi = 'diperbarui';
} else {
    $ok = sqlsrv_query($conn,
        "INSERT INTO dbo.M_USERS (nik_karyawan, password_hash, nama_snapshot, id_role, id_departemen, is_aktif)
         VALUES (?, ?, ?, (SELECT id_role FROM dbo.M_ROLES WHERE kode_role = ?), ?, 1)",
        array($nik, $hash, $nama, $role, $id_dept));
    $aksi = 'dibuat';
}

if ($ok === false) { die("Gagal: " . print_r(sqlsrv_errors(), true)); }
echo "User NIK '$nik' ($nama) $aksi (role $role" . ($dept !== null ? ", dept $dept" : "") . ").\n";

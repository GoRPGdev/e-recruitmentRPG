<?php
/**
 * Utilitas Provisioning Akun Nyata (Production User Setup)
 * E-Recruitment Ratu Pertiwi Group (RPG)
 *
 * Penggunaan via CLI:
 *   php tools/setup-real-users.php --list
 *   php tools/setup-real-users.php --seed-initial
 *   php tools/setup-real-users.php --disable-demo
 *   php tools/setup-real-users.php --add-user
 */

if (php_sapi_name() !== 'cli') {
    die("Akses CLI saja.\n");
}

$k = require __DIR__ . '/koneksi.local.php';
$conn = sqlsrv_connect($k['host'], array(
    'Database'     => $k['database'],
    'UID'          => $k['user'],
    'PWD'          => $k['password'],
    'CharacterSet' => 'UTF-8'
));

if (!$conn) {
    die("Gagal terhubung ke database: " . print_r(sqlsrv_errors(), true) . "\n");
}

function listUsers($conn) {
    echo "\n========================================================================================\n";
    echo "  DAFTAR PENGGUNA SISTEM (dbo.M_USERS)\n";
    echo "========================================================================================\n";
    printf("  %-5s | %-18s | %-24s | %-14s | %-16s | %-6s\n", "ID", "Username", "Nama", "Role", "Departemen", "Status");
    echo "----------------------------------------------------------------------------------------\n";

    $sql = "SELECT u.id_user, u.username, u.nama_snapshot, r.kode_role, d.nama AS nama_dept, u.is_aktif
            FROM dbo.M_USERS u
            JOIN dbo.M_ROLES r ON r.id_role = u.id_role
            LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = u.id_departemen
            ORDER BY u.is_aktif DESC, u.id_user ASC";
    $r = sqlsrv_query($conn, $sql);
    while ($u = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
        $st = $u['is_aktif'] ? 'AKTIF' : 'NONAKTIF';
        $dept = $u['nama_dept'] ? $u['nama_dept'] : '(Cross-Dept)';
        printf("  %-5d | %-18s | %-24s | %-14s | %-16s | %-6s\n",
            $u['id_user'], $u['username'], $u['nama_snapshot'], $u['kode_role'], $dept, $st);
    }
    echo "========================================================================================\n\n";
}

function createUserDirect($conn, $username, $password, $nama, $roleKode, $deptKode = null, $nik = null) {
    // 1. Cek username
    $c = sqlsrv_query($conn, "SELECT id_user FROM dbo.M_USERS WHERE username = ?", array($username));
    if ($row = sqlsrv_fetch_array($c, SQLSRV_FETCH_ASSOC)) {
        echo "  [SKIP] Pengguna '$username' sudah ada (ID: {$row['id_user']}).\n";
        return;
    }

    // 2. Ambil id_role
    $r = sqlsrv_query($conn, "SELECT id_role FROM dbo.M_ROLES WHERE kode_role = ?", array($roleKode));
    $roleRow = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC);
    if (!$roleRow) {
        echo "  [ERROR] Role '$roleKode' tidak ditemukan di database.\n";
        return;
    }
    $idRole = (int) $roleRow['id_role'];

    // 3. Ambil id_departemen jika ada
    $idDept = null;
    $deptNama = null;
    if ($deptKode) {
        $d = sqlsrv_query($conn, "SELECT id_departemen, nama FROM dbo.M_DEPARTEMEN WHERE kode = ?", array($deptKode));
        if ($dRow = sqlsrv_fetch_array($d, SQLSRV_FETCH_ASSOC)) {
            $idDept = (int) $dRow['id_departemen'];
            $deptNama = $dRow['nama'];
        }
    }

    // 4. Hash password
    $pwdHash = password_hash($password, PASSWORD_DEFAULT);
    $idOut = 0;

    $spParams = array(
        null,
        $username,
        $pwdHash,
        $nama,
        $nik,
        $deptNama,
        $idRole,
        $idDept,
        1,
        null,
        array(&$idOut, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );

    $stmt = sqlsrv_query($conn, "{CALL dbo.sp_SaveUser(?,?,?,?,?,?,?,?,?,?,?)}", $spParams);
    if ($stmt === false) {
        echo "  [FAIL] Gagal membuat user '$username': " . print_r(sqlsrv_errors(), true) . "\n";
    } else {
        echo "  [OK] Berhasil membuat user '$username' (Nama: $nama, Role: $roleKode, ID: $idOut)\n";
    }
}

function disableDemoAccounts($conn) {
    echo "\n>> Menonaktifkan akun demo bawaan sistem (is_aktif = 0)...\n";
    $stmt = sqlsrv_query($conn, "UPDATE dbo.M_USERS SET is_aktif = 0 WHERE username IN ('demo_super_admin', 'demo_user_dept')");
    if ($stmt !== false) {
        echo "  [OK] Akun demo_super_admin dan demo_user_dept telah dinonaktifkan.\n";
    } else {
        echo "  [FAIL] Gagal menonaktifkan akun demo: " . print_r(sqlsrv_errors(), true) . "\n";
    }
}

function seedInitialRealAccounts($conn) {
    echo "\n>> Menyiapkan Akun Standar Organisasi Ratu Pertiwi Group (RPG)...\n";

    // 1. Super Admin Utama (HR / IT)
    createUserDirect($conn, 'admin.rpg', 'RpgAdmin2026!#', 'Administrator HR RPG', 'SUPER_ADMIN', null, 'EMP-001');

    // 2. Akun HR Operasional (Contoh: Mas Fachri)
    createUserDirect($conn, 'fachri.hr', 'RpgFachri2026!#', 'Fachri - HR Recruitment', 'SUPER_ADMIN', 'HRD', 'EMP-002');

    // 3. User Departemen - Marketing
    createUserDirect($conn, 'dept.marketing', 'Marketing2026!#', 'Head of Marketing', 'USER_DEPT', 'MKT', 'EMP-010');

    // 4. User Departemen - Operasional & Outlet
    createUserDirect($conn, 'dept.operasional', 'Operasional2026!#', 'Head of Operations', 'USER_DEPT', 'OPS', 'EMP-011');

    // 5. User Departemen - Finance & Accounting
    createUserDirect($conn, 'dept.finance', 'Finance2026!#', 'Head of Finance', 'USER_DEPT', 'FIN', 'EMP-012');

    echo "\n>> Selesai menyiapkan akun. Harap catat atau ubah password default saat pertama kali login.\n";
}

// Router Perintah CLI
$arg = isset($argv[1]) ? $argv[1] : '--list';

switch ($arg) {
    case '--seed-initial':
        seedInitialRealAccounts($conn);
        listUsers($conn);
        break;

    case '--disable-demo':
        disableDemoAccounts($conn);
        listUsers($conn);
        break;

    case '--list':
    default:
        listUsers($conn);
        echo "Pilihan perintah:\n";
        echo "  php tools/setup-real-users.php --list          -> Tampilkan seluruh user aktif & nonaktif\n";
        echo "  php tools/setup-real-users.php --seed-initial   -> Buat akun awal tim HR & Kepala Departemen\n";
        echo "  php tools/setup-real-users.php --disable-demo  -> Nonaktifkan akun demo bawaan\n\n";
        break;
}

sqlsrv_close($conn);

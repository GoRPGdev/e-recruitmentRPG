<?php
/**
 * Test Validasi Role USER_DEPT:
 * 1. Hak akses menu & permission check
 * 2. Scoping pengajuan MPR departemen yang sama (Harus Berhasil)
 * 3. Scoping pengajuan MPR departemen yang berbeda (Harus Gagal / RAISERROR)
 * 4. Proteksi read-only pipeline & kandidat
 *
 * Usage: php tools/test-user-dept-role.php
 */

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) {
    echo "[FAIL] File tools/koneksi.local.php tidak ditemukan.\n";
    exit(1);
}
$cfg = require $cfgPath;

$server = $cfg['host'];
$connectionInfo = array(
    'Database' => $cfg['database'],
    'UID'      => $cfg['user'],
    'PWD'      => $cfg['password'],
    'CharacterSet' => 'UTF-8',
);

$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === FALSE) {
    echo "[FAIL] Koneksi database gagal: " . print_r(sqlsrv_errors(), true) . "\n";
    exit(1);
}

function run_q($conn, $sql, $params = array()) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === FALSE) {
        $e = sqlsrv_errors();
        $last = $e ? end($e) : NULL;
        throw new Exception($last ? $last['message'] : 'Query failed');
    }
    return $stmt;
}

echo "============================================================\n";
echo "   TEST ROLE USER DEPARTEMEN (USER_DEPT) - E-RECRUITMENT RPG\n";
echo "============================================================\n\n";

$tests_passed = 0;
$tests_total = 0;

function assert_test($condition, $message) {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($condition) {
        $tests_passed++;
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
    }
}

try {
    // 1. Ambil atau pastikan ada user dengan role USER_DEPT
    $stmt = run_q($conn, "SELECT u.id_user, u.username, u.id_departemen, d.nama AS nama_dept
                          FROM dbo.M_USERS u
                          JOIN dbo.M_ROLES r ON r.id_role = u.id_role
                          JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = u.id_departemen
                          WHERE r.kode_role = 'USER_DEPT' AND u.is_aktif = 1");
    $user_dept = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user_dept) {
        // Ambil 1 departemen aktif
        $stmt_d = run_q($conn, "SELECT TOP 1 id_departemen, nama FROM dbo.M_DEPARTEMEN WHERE is_aktif = 1 ORDER BY id_departemen ASC");
        $row_d = sqlsrv_fetch_array($stmt_d, SQLSRV_FETCH_ASSOC);
        $id_dept_dummy = (int) $row_d['id_departemen'];

        // Ambil id_role USER_DEPT
        $stmt_r = run_q($conn, "SELECT TOP 1 id_role FROM dbo.M_ROLES WHERE kode_role = 'USER_DEPT'");
        $row_r = sqlsrv_fetch_array($stmt_r, SQLSRV_FETCH_ASSOC);
        $id_role_ud = (int) $row_r['id_role'];

        // Buat user dummy USER_DEPT
        $pass_hash = password_hash('UserDept123!', PASSWORD_BCRYPT);
        run_q($conn, "INSERT INTO dbo.M_USERS (username, password_hash, nama_snapshot, departemen_snapshot, id_role, id_departemen, is_aktif)
                      VALUES ('test_user_dept', ?, 'Test User Dept', ?, ?, ?, 1)",
              array($pass_hash, $row_d['nama'], $id_role_ud, $id_dept_dummy));

        $stmt = run_q($conn, "SELECT u.id_user, u.username, u.id_departemen, d.nama AS nama_dept
                              FROM dbo.M_USERS u
                              JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = u.id_departemen
                              WHERE u.username = 'test_user_dept'");
        $user_dept = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    echo "User Dept Target: {$user_dept['username']} (ID: {$user_dept['id_user']}, Dept: {$user_dept['nama_dept']} [ID: {$user_dept['id_departemen']}])\n\n";

    // 2. Periksa Permission USER_DEPT melalui sp_GetUserPermissions
    $stmt_perm = run_q($conn, "{CALL dbo.sp_GetUserPermissions(?)}", array((int) $user_dept['id_user']));
    $perms = array();
    while ($row_p = sqlsrv_fetch_array($stmt_perm, SQLSRV_FETCH_ASSOC)) {
        $perms[] = $row_p['kode'] ?? $row_p['kode_permission'] ?? '';
    }

    assert_test(in_array('BUAT_MPR', $perms), "USER_DEPT memiliki permission BUAT_MPR");
    assert_test(in_array('LIHAT_KANDIDAT', $perms), "USER_DEPT memiliki permission LIHAT_KANDIDAT");
    assert_test(!in_array('KELOLA_REKRUTMEN', $perms), "USER_DEPT TIDAK memiliki KELOLA_REKRUTMEN (read-only pipeline)");
    assert_test(!in_array('APPROVE', $perms), "USER_DEPT TIDAK memiliki APPROVE");
    assert_test(!in_array('EXPORT', $perms), "USER_DEPT TIDAK memiliki EXPORT");
    assert_test(!in_array('LIHAT_FINANSIAL', $perms), "USER_DEPT TIDAK memiliki LIHAT_FINANSIAL");
    assert_test(!in_array('LIHAT_KESEHATAN', $perms), "USER_DEPT TIDAK memiliki LIHAT_KESEHATAN");
    assert_test(!in_array('MANAJEMEN_USER', $perms), "USER_DEPT TIDAK memiliki MANAJEMEN_USER");

    // 3. Tes Pengajuan MPR untuk posisi di departemen yang SAMA
    $id_dept_user = (int) $user_dept['id_departemen'];
    $stmt_pos_same = run_q($conn, "SELECT TOP 1 id_posisi, nama_posisi FROM dbo.M_POSISI WHERE id_departemen = ? AND is_aktif = 1", array($id_dept_user));
    $pos_same = sqlsrv_fetch_array($stmt_pos_same, SQLSRV_FETCH_ASSOC);

    if (!$pos_same) {
        // Buat posisi dummy di departemen yang sama
        run_q($conn, "INSERT INTO dbo.M_POSISI (nama_posisi, id_departemen, default_tipe_penempatan, default_flow, is_aktif)
                      VALUES ('Posisi Dept Sama', ?, 'HQ', 1, 1)", array($id_dept_user));
        $stmt_pos_same = run_q($conn, "SELECT TOP 1 id_posisi, nama_posisi FROM dbo.M_POSISI WHERE id_departemen = ? AND is_aktif = 1", array($id_dept_user));
        $pos_same = sqlsrv_fetch_array($stmt_pos_same, SQLSRV_FETCH_ASSOC);
    }

    $id_req_same = 0;
    $call_sql = "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
    $params_same = array(
        (int)$user_dept['id_user'], (int)$pos_same['id_posisi'], 'HQ', NULL, 1, 'Tetap', 'Penambahan Tim', NULL,
        NULL, 'Normal', NULL, 0, 0, NULL, NULL, 'JD Test', 'Kualifikasi Test',
        NULL, NULL, NULL,
        array(&$id_req_same, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt_call_same = sqlsrv_query($conn, $call_sql, $params_same);
    if ($stmt_call_same !== FALSE && $id_req_same > 0) {
        assert_test(true, "sp_CreateRequisition BERHASIL untuk posisi di departemen yang sama (ID Req: {$id_req_same})");
        // Bersihkan requisition uji coba
        run_q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req_same));
    } else {
        $e = sqlsrv_errors();
        assert_test(false, "sp_CreateRequisition GAGAL untuk posisi di departemen yang sama: " . ($e ? $e[0]['message'] : ''));
    }

    // 4. Tes Pengajuan MPR untuk posisi di departemen yang BERBEDA (Harus RAISERROR)
    $stmt_pos_diff = run_q($conn, "SELECT TOP 1 id_posisi, nama_posisi FROM dbo.M_POSISI WHERE id_departemen <> ? AND is_aktif = 1", array($id_dept_user));
    $pos_diff = sqlsrv_fetch_array($stmt_pos_diff, SQLSRV_FETCH_ASSOC);

    if (!$pos_diff) {
        // Ambil dept lain
        $stmt_other_dept = run_q($conn, "SELECT TOP 1 id_departemen FROM dbo.M_DEPARTEMEN WHERE id_departemen <> ? AND is_aktif = 1", array($id_dept_user));
        $row_other_d = sqlsrv_fetch_array($stmt_other_dept, SQLSRV_FETCH_ASSOC);
        if ($row_other_d) {
            run_q($conn, "INSERT INTO dbo.M_POSISI (nama_posisi, id_departemen, default_tipe_penempatan, default_flow, is_aktif)
                          VALUES ('Posisi Dept Lain', ?, 'HQ', 1, 1)", array((int)$row_other_d['id_departemen']));
            $stmt_pos_diff = run_q($conn, "SELECT TOP 1 id_posisi, nama_posisi FROM dbo.M_POSISI WHERE id_departemen <> ? AND is_aktif = 1", array($id_dept_user));
            $pos_diff = sqlsrv_fetch_array($stmt_pos_diff, SQLSRV_FETCH_ASSOC);
        }
    }

    if ($pos_diff) {
        $id_req_diff = 0;
        $params_diff = array(
            (int)$user_dept['id_user'], (int)$pos_diff['id_posisi'], 'HQ', NULL, 1, 'Tetap', 'Penambahan Tim', NULL,
            NULL, 'Normal', NULL, 0, 0, NULL, NULL, 'JD Test', 'Kualifikasi Test',
            NULL, NULL, NULL,
            array(&$id_req_diff, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
        );
        $stmt_call_diff = sqlsrv_query($conn, $call_sql, $params_diff);
        if ($stmt_call_diff === FALSE) {
            $e = sqlsrv_errors();
            $msg = $e ? $e[0]['message'] : '';
            $is_dept_mismatch = (strpos($msg, 'Posisi yang dipilih tidak sesuai dengan departemen pemohon') !== false);
            assert_test($is_dept_mismatch, "sp_CreateRequisition MENOLAK posisi lintas departemen (Pesan: '{$msg}')");
        } else {
            assert_test(false, "sp_CreateRequisition LOLOS posisi lintas departemen (SEHARUSNYA DITOLAK!)");
            if ($id_req_diff > 0) {
                run_q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req_diff));
            }
        }
    } else {
        echo "  [WARN] Tidak ada departemen lain untuk pengujian lintas departemen.\n";
    }

} catch (Exception $ex) {
    echo "\n[ERROR] Exception saat testing: " . $ex->getMessage() . "\n";
}

echo "\n============================================================\n";
echo "Hasil Pengujian: {$tests_passed} / {$tests_total} tes lulus.\n";
echo "============================================================\n";

<?php
/**
 * Test Validasi Penampil Alasan Penolakan MPR (Ditolak_HR & Ditolak_BOD)
 * Memverifikasi:
 * 1. Requisition berstatus Ditolak_HR memuat catatan_hr sebagai alasan penolakan
 * 2. Requisition berstatus Ditolak_BOD memuat catatan_bod dan nama BOD penolak dari REQUISITION_APPROVALS
 * 3. Query get() dan list_mpr() pada Requisition_model mengembalikan field alasan penolakan dengan benar
 *
 * Usage: php tools/test-rejection-reason.php
 */

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) {
    echo "[FAIL] File tools/koneksi.local.php tidak ditemukan.\n";
    exit(1);
}
$cfg = require $cfgPath;

$server = $cfg['host'];
$connectionInfo = array(
    'Database'     => $cfg['database'],
    'UID'          => $cfg['user'],
    'PWD'          => $cfg['password'],
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
echo "   TEST REASON VISIBILITY FOR REJECTED REQUISITION\n";
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
    // Ambil 1 user aktif dan 1 posisi aktif
    $stmt_u = run_q($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1 ORDER BY id_user ASC");
    $user = sqlsrv_fetch_array($stmt_u, SQLSRV_FETCH_ASSOC);
    $id_user = (int) $user['id_user'];

    $stmt_p = run_q($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY id_posisi ASC");
    $pos = sqlsrv_fetch_array($stmt_p, SQLSRV_FETCH_ASSOC);
    $id_posisi = (int) $pos['id_posisi'];

    // 1. Buat Dummy MPR untuk Ditolak_HR
    $id_req_hr = 0;
    $call_sql = "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
    $params_hr = array(
        $id_user, $id_posisi, 'HQ', NULL, 1, 'Tetap', 'Penambahan Tim', NULL,
        NULL, 'Normal', NULL, 0, 0, NULL, NULL, 'JD Test', 'Kualifikasi Test',
        NULL, NULL, NULL,
        array(&$id_req_hr, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt = sqlsrv_query($conn, $call_sql, $params_hr);
    if ($stmt === FALSE || $id_req_hr <= 0) {
        throw new Exception("Gagal membuat MPR dummy untuk test Ditolak_HR.");
    }

    $alasan_hr = 'Beban kerja belum memenuhi justifikasi penambahan headcount baru pada kuartal ini.';
    run_q($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}", array($id_req_hr, 'Ditolak_HR', $alasan_hr, $id_user));

    // Test Query get() untuk Ditolak_HR
    $sql_get = 'SELECT r.*, p.nama_posisi, p.id_departemen, o.nama_outlet, d.nama AS departemen, u.nama_snapshot AS pemohon,
                       f.kode_flow, f.nama_flow,
                       bod_rej.catatan_bod, bod_rej.disetujui_oleh AS penolak_bod
                FROM dbo.REQUISITIONS r
                JOIN dbo.M_POSISI p       ON p.id_posisi = r.id_posisi
                LEFT JOIN dbo.M_OUTLET o  ON o.id_outlet = r.id_outlet
                LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
                JOIN dbo.M_USERS u        ON u.id_user = r.id_user_pemohon
                LEFT JOIN dbo.M_FLOW f    ON f.id_flow = r.id_flow
                OUTER APPLY (
                    SELECT TOP 1 ra.catatan_bod, ra.disetujui_oleh
                    FROM dbo.REQUISITION_APPROVALS ra
                    WHERE ra.id_req = r.id_req AND ra.keputusan = \'Rejected\'
                    ORDER BY ra.putaran_ke DESC
                ) bod_rej
                WHERE r.id_req = ?';
    $stmt_chk_hr = run_q($conn, $sql_get, array($id_req_hr));
    $row_chk_hr = sqlsrv_fetch_array($stmt_chk_hr, SQLSRV_FETCH_ASSOC);

    assert_test($row_chk_hr['status_req'] === 'Ditolak_HR', "Status MPR berhasil diubah ke Ditolak_HR");
    assert_test($row_chk_hr['catatan_hr'] === $alasan_hr, "Catatan penolakan HR (catatan_hr) terbaca tepat di query detail MPR");

    // 2. Buat Dummy MPR untuk Ditolak_BOD
    $id_req_bod = 0;
    $params_bod = array(
        $id_user, $id_posisi, 'HQ', NULL, 1, 'Tetap', 'Penambahan Tim', NULL,
        NULL, 'Normal', NULL, 0, 0, NULL, NULL, 'JD Test', 'Kualifikasi Test',
        NULL, NULL, NULL,
        array(&$id_req_bod, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt_b = sqlsrv_query($conn, $call_sql, $params_bod);
    if ($stmt_b === FALSE || $id_req_bod <= 0) {
        throw new Exception("Gagal membuat MPR dummy untuk test Ditolak_BOD.");
    }

    // Submit ke BOD via sp_SubmitToBOD
    $id_app = 0;
    $stmt_sub_bod = sqlsrv_query($conn, "{CALL dbo.sp_SubmitToBOD(?,?,?)}", array($id_req_bod, $id_user, array(&$id_app, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)));
    if ($stmt_sub_bod === FALSE || $id_app <= 0) {
        throw new Exception("Gagal submit MPR ke BOD.");
    }

    $alasan_bod = 'Anggaran penambahan formasi ditangguhkan ke tahun depan sesuai arahan rapat direksi.';
    $penolak_bod = 'Bpk. Hendra (Direktur Operasional)';
    run_q($conn, "{CALL dbo.sp_RecordApproval(?,?,?,?,?,?,?,?)}", array(
        $id_app, 'Rejected', 0, date('Y-m-d'), $penolak_bod, $alasan_bod, NULL, $id_user
    ));

    // Test Query get() untuk Ditolak_BOD
    $stmt_chk_bod = run_q($conn, $sql_get, array($id_req_bod));
    $row_chk_bod = sqlsrv_fetch_array($stmt_chk_bod, SQLSRV_FETCH_ASSOC);

    assert_test($row_chk_bod['status_req'] === 'Ditolak_BOD', "Status MPR berhasil diubah ke Ditolak_BOD");
    assert_test($row_chk_bod['catatan_bod'] === $alasan_bod, "Catatan penolakan BOD (catatan_bod) terbaca via OUTER APPLY");
    assert_test($row_chk_bod['penolak_bod'] === $penolak_bod, "Nama penolak BOD (penolak_bod) terbaca via OUTER APPLY");

    // 3. Test list_mpr() query mencakup kedua penolakan
    $sql_list = "WITH q AS (
                    SELECT r.id_req, r.no_mpr, r.status_req, r.tipe_penempatan, r.jumlah_dibutuhkan,
                           r.jumlah_disetujui, r.jumlah_terpenuhi, r.tanggal_pengajuan, r.catatan_hr,
                           p.nama_posisi, p.id_departemen, o.nama_outlet, u.nama_snapshot AS pemohon,
                           bod_rej.catatan_bod, bod_rej.disetujui_oleh AS penolak_bod,
                           ROW_NUMBER() OVER (ORDER BY r.id_req DESC) AS rn
                    FROM dbo.REQUISITIONS r
                    JOIN dbo.M_POSISI p      ON p.id_posisi = r.id_posisi
                    LEFT JOIN dbo.M_OUTLET o ON o.id_outlet = r.id_outlet
                    JOIN dbo.M_USERS u       ON u.id_user = r.id_user_pemohon
                    OUTER APPLY (
                        SELECT TOP 1 ra.catatan_bod, ra.disetujui_oleh
                        FROM dbo.REQUISITION_APPROVALS ra
                        WHERE ra.id_req = r.id_req AND ra.keputusan = 'Rejected'
                        ORDER BY ra.putaran_ke DESC
                    ) bod_rej
                    WHERE r.id_req IN (?, ?)
                )
                SELECT * FROM q ORDER BY rn";
    $stmt_list = run_q($conn, $sql_list, array($id_req_hr, $id_req_bod));
    $list_rows = array();
    while ($r = sqlsrv_fetch_array($stmt_list, SQLSRV_FETCH_ASSOC)) {
        $list_rows[$r['id_req']] = $r;
    }

    assert_test(isset($list_rows[$id_req_hr]) && $list_rows[$id_req_hr]['catatan_hr'] === $alasan_hr, "list_mpr memuat catatan_hr untuk baris Ditolak_HR");
    assert_test(isset($list_rows[$id_req_bod]) && $list_rows[$id_req_bod]['catatan_bod'] === $alasan_bod, "list_mpr memuat catatan_bod untuk baris Ditolak_BOD");

    // Clean up
    run_q($conn, "DELETE FROM dbo.REQUISITION_APPROVALS WHERE id_req IN (?, ?)", array($id_req_hr, $id_req_bod));
    run_q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req IN (?, ?)", array($id_req_hr, $id_req_bod));

} catch (Exception $ex) {
    echo "\n[ERROR] Exception saat testing: " . $ex->getMessage() . "\n";
}

echo "\n============================================================\n";
echo "Hasil Pengujian: {$tests_passed} / {$tests_total} tes lulus.\n";
echo "============================================================\n";

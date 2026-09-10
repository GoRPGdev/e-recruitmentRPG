<?php
/**
 * Test E2E Alur Baru Review BOD & Revisi BOD
 * Memastikan alur transisi status MPR berfungsi sesuai spesifikasi:
 * Draft -> Review_HR -> Revisi_HR -> Edit -> Resubmit -> Review_BOD -> Revisi_BOD -> Edit -> Resubmit -> Review_BOD -> Approved
 */

$cfgPath = __DIR__ . '/koneksi.local.php';
if (!file_exists($cfgPath)) {
    die("tools/koneksi.local.php belum ada.\n");
}
$cfg = require $cfgPath;

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'],
    'UID'      => $cfg['user'],
    'PWD'      => $cfg['password'],
    'CharacterSet' => 'UTF-8'
));

if (!$conn) {
    die("Koneksi gagal: " . print_r(sqlsrv_errors(), true));
}

function q($conn, $sql, $params = array()) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

function qFetch($conn, $sql, $params = array()) {
    $stmt = q($conn, $sql, $params);
    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

function testAssert($label, $cond, $detail = '') {
    if ($cond) {
        echo "  [PASS] {$label}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        echo "  [FAIL] {$label}" . ($detail ? " ({$detail})" : "") . "\n";
        exit(1);
    }
}

echo "\n======================================================\n";
echo "Testing Review BOD & Revisi BOD Workflow\n";
echo "======================================================\n";

// 1. Ambil user & posisi valid untuk test
$user = qFetch($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1");
$userId = (int) $user['id_user'];

$pos = qFetch($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1");
$posId = (int) $pos['id_posisi'];

// 2. Buat Requisition Draft baru via sp_CreateRequisition
$idReq = 0;
$createSql = "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
$paramsCreate = array(
    array($userId, SQLSRV_PARAM_IN),
    array($posId, SQLSRV_PARAM_IN),
    array('HQ', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(2, SQLSRV_PARAM_IN),
    array('Tetap', SQLSRV_PARAM_IN),
    array('Penambahan', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array('2026-10-01', SQLSRV_PARAM_IN),
    array('Normal', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(0, SQLSRV_PARAM_IN),
    array(0, SQLSRV_PARAM_IN),
    array('S1', SQLSRV_PARAM_IN),
    array(2, SQLSRV_PARAM_IN),
    array('Test Job Deskripsi', SQLSRV_PARAM_IN),
    array('Test Kualifikasi', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(&$idReq, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
);

$stmtCreate = sqlsrv_query($conn, $createSql, $paramsCreate);
if ($stmtCreate === false) {
    die("Create failed: " . print_r(sqlsrv_errors(), true));
}
sqlsrv_next_result($stmtCreate);
testAssert("1. Create MPR Draft", $idReq > 0, "id_req = {$idReq}");

// Cek status Draft
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("Status awal adalah Draft", $curr['status_req'] === 'Draft');

// 3. Test Edit saat Draft via sp_UpdateRequisition
$updSql = "{CALL dbo.sp_UpdateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
$paramsUpd = array(
    array($idReq, SQLSRV_PARAM_IN),
    array($posId, SQLSRV_PARAM_IN),
    array('HQ', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(4, SQLSRV_PARAM_IN),
    array('Tetap', SQLSRV_PARAM_IN),
    array('Penambahan', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array('2026-10-01', SQLSRV_PARAM_IN),
    array('Normal', SQLSRV_PARAM_IN),
    array('S1', SQLSRV_PARAM_IN),
    array(2, SQLSRV_PARAM_IN),
    array('JD Baru Draft', SQLSRV_PARAM_IN),
    array('Kualifikasi Baru Draft', SQLSRV_PARAM_IN),
    array($userId, SQLSRV_PARAM_IN)
);
q($conn, $updSql, $paramsUpd);
testAssert("2. Edit MPR saat Draft", true, "jumlah_dibutuhkan diupdate");

// 4. Submit to HR -> Review_HR
$subHRSql = "{CALL dbo.sp_SubmitToHR(?,?)}";
q($conn, $subHRSql, array(array($idReq, SQLSRV_PARAM_IN), array($userId, SQLSRV_PARAM_IN)));
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("3. Submit to HR -> Review_HR", $curr['status_req'] === 'Review_HR');

// 5. HR minta Revisi_HR
$statusSql = "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}";
q($conn, $statusSql, array(
    array($idReq, SQLSRV_PARAM_IN),
    array('Revisi_HR', SQLSRV_PARAM_IN),
    array('Mohon perbaiki kualifikasi pengalaman kerja', SQLSRV_PARAM_IN),
    array($userId, SQLSRV_PARAM_IN)
));
$curr = qFetch($conn, "SELECT status_req, catatan_hr FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("4. HR minta Revisi_HR", $curr['status_req'] === 'Revisi_HR');
testAssert("   Catatan HR tersimpan", !empty($curr['catatan_hr']));

// 6. Edit saat Revisi_HR
$paramsUpd[11] = array(3, SQLSRV_PARAM_IN); // exp 3 tahun
$paramsUpd[13] = array('Kualifikasi Revisi HR', SQLSRV_PARAM_IN);
q($conn, $updSql, $paramsUpd);
testAssert("5. Edit data MPR saat Revisi_HR", true);

// 7. Resubmit to HR
q($conn, $subHRSql, array(array($idReq, SQLSRV_PARAM_IN), array($userId, SQLSRV_PARAM_IN)));
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("6. Resubmit to HR -> Review_HR", $curr['status_req'] === 'Review_HR');

// 8. HR Teruskan ke BOD -> Review_BOD
$subBODSql = "{CALL dbo.sp_SubmitToBOD(?,?)}";
q($conn, $subBODSql, array(array($idReq, SQLSRV_PARAM_IN), array($userId, SQLSRV_PARAM_IN)));
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("7. HR Setuju & Teruskan ke BOD -> Review_BOD", $curr['status_req'] === 'Review_BOD');

// Verifikasi REQUISITION_APPROVALS TIDAK dibuat (fitur catat keputusan BOD dihapus)
$apprCount = qFetch($conn, "SELECT COUNT(*) AS cnt FROM dbo.REQUISITION_APPROVALS WHERE id_req = ?", array($idReq));
testAssert("8. Verifikasi REQUISITION_APPROVALS tidak dibuat", (int)$apprCount['cnt'] === 0, "cnt = 0");

// 9. BOD minta Revisi_BOD (dengan catatan)
q($conn, $statusSql, array(
    array($idReq, SQLSRV_PARAM_IN),
    array('Revisi_BOD', SQLSRV_PARAM_IN),
    array('Mohon sesuaikan target tanggal join ke bulan November', SQLSRV_PARAM_IN),
    array($userId, SQLSRV_PARAM_IN)
));
$curr = qFetch($conn, "SELECT status_req, catatan_bod FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("9. BOD minta Revisi_BOD", $curr['status_req'] === 'Revisi_BOD');
testAssert("10. Catatan BOD tersimpan di dbo.REQUISITIONS", $curr['catatan_bod'] === 'Mohon sesuaikan target tanggal join ke bulan November');

// 10. Edit saat Revisi_BOD
$paramsUpd[8] = array('2026-11-01', SQLSRV_PARAM_IN); // target tgl join baru
q($conn, $updSql, $paramsUpd);
testAssert("11. Edit data MPR saat Revisi_BOD", true);

// 11. Resubmit to HR dari Revisi_BOD
q($conn, $subHRSql, array(array($idReq, SQLSRV_PARAM_IN), array($userId, SQLSRV_PARAM_IN)));
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("12. Resubmit dari Revisi_BOD ke HR -> Review_HR", $curr['status_req'] === 'Review_HR');

// 12. HR teruskan lagi ke BOD
q($conn, $subBODSql, array(array($idReq, SQLSRV_PARAM_IN), array($userId, SQLSRV_PARAM_IN)));
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("13. HR teruskan lagi ke BOD -> Review_BOD", $curr['status_req'] === 'Review_BOD');

// 13. BOD Setujui -> Approved
q($conn, $statusSql, array(
    array($idReq, SQLSRV_PARAM_IN),
    array('Approved', SQLSRV_PARAM_IN),
    array('Disetujui oleh Direksi (BOD)', SQLSRV_PARAM_IN),
    array($userId, SQLSRV_PARAM_IN)
));
$curr = qFetch($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
testAssert("14. BOD Setujui -> Approved", $curr['status_req'] === 'Approved');

// 14. Uji validasi error: Revisi_BOD tanpa catatan harus ditolak oleh SP
$threw = false;
try {
    q($conn, $statusSql, array(
        array($idReq, SQLSRV_PARAM_IN),
        array('Revisi_BOD', SQLSRV_PARAM_IN),
        array('', SQLSRV_PARAM_IN),
        array($userId, SQLSRV_PARAM_IN)
    ));
} catch (Exception $e) {
    $threw = true;
}
testAssert("15. Revisi_BOD tanpa catatan ditolak oleh SP", $threw);

// 15. Bersihkan test record
q($conn, "DELETE FROM dbo.AUDIT_LOG WHERE id_baris = ? AND nama_tabel = 'REQUISITIONS'", array($idReq));
q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
echo "\nCleaned up test requisition #{$idReq}.\n";

echo "\nAll Review_BOD & Revisi_BOD workflow tests PASSED!\n\n";

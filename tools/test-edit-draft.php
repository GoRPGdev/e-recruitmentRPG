<?php
/**
 * Test sp_UpdateRequisition -- edit data MPR saat Draft / Revisi_HR
 * Usage: php tools/test-edit-draft.php
 */
if (php_sapi_name() !== 'cli') die("CLI only.\n");

$cfg = require __DIR__ . '/koneksi.local.php';
$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'], 'UID' => $cfg['user'], 'PWD' => $cfg['password'],
    'CharacterSet' => 'UTF-8', 'ReturnDatesAsStrings' => true,
));
if (!$conn) die("[FATAL] DB connect failed.\n");

function q($conn, $sql, $p = array()) {
    $s = sqlsrv_query($conn, $sql, $p);
    if ($s === false) { $e = sqlsrv_errors(); $last = end($e); throw new Exception($last['message']); }
    return $s;
}

$ok = 0; $n = 0;
function chk($c, $m) { global $ok, $n; $n++; if ($c) { $ok++; echo "  [PASS] $m\n"; } else { echo "  [FAIL] $m\n"; } }

echo "============================================================\n";
echo "   TEST EDIT MPR DRAFT (sp_UpdateRequisition)\n";
echo "============================================================\n\n";

try {
    $r = sqlsrv_fetch_array(q($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1"), SQLSRV_FETCH_ASSOC);
    $uid = (int) $r['id_user'];

    $r = sqlsrv_fetch_array(q($conn, "SELECT TOP 1 id_posisi, id_departemen FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY id_posisi"), SQLSRV_FETCH_ASSOC);
    $pos1 = (int) $r['id_posisi'];
    $dept1 = (int) $r['id_departemen'];

    // Buat draft
    $id = 0;
    sqlsrv_query($conn, "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}", array(
        $uid, $pos1, 'HQ', NULL, 1, 'Tetap', 'Penambahan', NULL, NULL, 'Normal', NULL, 0, 0,
        NULL, NULL, 'JD awal', 'Kual awal', NULL, NULL, NULL,
        array(&$id, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    ));
    chk($id > 0, "Draft MPR dibuat (ID: $id)");

    // Test 1: Edit draft — ubah jumlah dan job_desc
    q($conn, "{CALL dbo.sp_UpdateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}", array(
        $id, $pos1, 'HQ', NULL, 3, 'Kontrak', 'Penggantian', NULL, NULL, 'Tinggi',
        'S1', 2, 'JD baru setelah edit', 'Kual baru setelah edit', $uid
    ));
    $row = sqlsrv_fetch_array(q($conn, "SELECT * FROM dbo.REQUISITIONS WHERE id_req = ?", array($id)), SQLSRV_FETCH_ASSOC);
    chk((int)$row['jumlah_dibutuhkan'] === 3, "Jumlah dibutuhkan terupdate menjadi 3");
    chk($row['job_desc'] === 'JD baru setelah edit', "Job desc terupdate");
    chk($row['kualifikasi'] === 'Kual baru setelah edit', "Kualifikasi terupdate");
    chk($row['status_karyawan'] === 'Kontrak', "Status karyawan terupdate ke Kontrak");
    chk($row['urgensi'] === 'Tinggi', "Urgensi terupdate ke Tinggi");
    chk($row['status_req'] === 'Draft', "Status tetap Draft setelah edit");

    // Test 2: Submit ke HR, HR revisi, lalu edit lagi saat Revisi_HR
    q($conn, "{CALL dbo.sp_SubmitToHR(?,?)}", array($id, $uid));
    q($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}", array($id, 'Revisi_HR', 'Perbaiki kualifikasi', $uid));

    $row2 = sqlsrv_fetch_array(q($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id)), SQLSRV_FETCH_ASSOC);
    chk($row2['status_req'] === 'Revisi_HR', "Status berubah ke Revisi_HR");

    q($conn, "{CALL dbo.sp_UpdateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}", array(
        $id, $pos1, 'HQ', NULL, 2, 'Tetap', 'Penambahan', NULL, NULL, 'Mendesak',
        'S1', 3, 'JD revisi final', 'Kual revisi final', $uid
    ));
    $row3 = sqlsrv_fetch_array(q($conn, "SELECT * FROM dbo.REQUISITIONS WHERE id_req = ?", array($id)), SQLSRV_FETCH_ASSOC);
    chk($row3['job_desc'] === 'JD revisi final', "Edit saat Revisi_HR berhasil — job_desc terupdate");
    chk($row3['status_req'] === 'Revisi_HR', "Status tetap Revisi_HR setelah edit");

    // Test 3: Setelah submit ke HR (Review_HR), edit harus ditolak
    q($conn, "{CALL dbo.sp_SubmitToHR(?,?)}", array($id, $uid));
    $err_caught = false;
    try {
        q($conn, "{CALL dbo.sp_UpdateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}", array(
            $id, $pos1, 'HQ', NULL, 2, 'Tetap', 'Test', NULL, NULL, 'Normal',
            NULL, NULL, 'x', 'x', $uid
        ));
    } catch (Exception $e) {
        $err_caught = (strpos($e->getMessage(), 'hanya dapat diedit saat berstatus Draft') !== false);
    }
    chk($err_caught, "Edit DITOLAK saat status Review_HR");

    // Cleanup
    q($conn, "DELETE FROM dbo.REQUISITION_APPROVALS WHERE id_req = ?", array($id));
    q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($id));
    echo "\n  [CLEANUP] MPR #$id dihapus.\n";

} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}

echo "\n============================================================\n";
echo "HASIL: $ok / $n pengujian berhasil.\n";
echo "============================================================\n";

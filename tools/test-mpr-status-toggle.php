<?php
/**
 * Test Fitur Kontrol Status MPR & Toggle Buka-Tutup Form Lowongan Publik
 * Usage: php tools/test-mpr-status-toggle.php
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

echo "=== TEST KONTROL STATUS MPR & TOGGLE BUKA-TUTUP FORM ===\n";

function run_query($conn, $sql, $params = array()) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === FALSE) {
        $e = sqlsrv_errors();
        $last = $e ? end($e) : NULL;
        throw new Exception($last ? $last['message'] : 'Query failed');
    }
    return $stmt;
}

try {
    // 1. Ambil 1 user HR dan 1 posisi aktif
    $stmt = run_query($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1 ORDER BY id_user ASC");
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $id_user = (int) $row['id_user'];

    $stmt = run_query($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY id_posisi ASC");
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $id_posisi = (int) $row['id_posisi'];

    // 2. Buat Requisition Uji Coba
    $id_req = 0;
    $call_sql = "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
    $params = array(
        $id_user, $id_posisi, 'HQ', NULL, 2, 'Tetap', 'Test Sourcing', NULL,
        NULL, 'Normal', NULL, 0, 0, NULL, NULL, 'Test Deskripsi', 'Test Kualifikasi',
        NULL, NULL, NULL,
        array(&$id_req, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}
    echo "[OK] Requisition uji coba dibuat (ID: $id_req)\n";

    // 3a. Draft -> Review_HR
    $call_sql = "{CALL dbo.sp_SubmitToHR(?,?)}";
    $stmt = sqlsrv_query($conn, $call_sql, array($id_req, $id_user));
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}

    // 3b. Review_HR -> Review_BOD  (sp_SubmitToBOD: 2 argumen, tidak lagi mengembalikan id_app)
    $call_sql = "{CALL dbo.sp_SubmitToBOD(?,?)}";
    $stmt = sqlsrv_query($conn, $call_sql, array($id_req, $id_user));
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}
    echo "[OK] Diajukan ke BOD -> Review_BOD (No MPR generated)\n";

    // 4. BOD Approve -> Status: Approved (via sp_UpdateRequisitionStatus, bukan lagi sp_RecordApproval)
    $call_sql = "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}";
    $params = array($id_req, 'Approved', 'Disetujui penuh oleh Direksi (test)', $id_user);
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}
    echo "[OK] BOD Approve -> Status MPR: Approved\n";

    // 5. Buat Job Posting (sp_CreatePosting: @id_req,@id_channel,@judul,@job_desc,@kualifikasi,@batch_ke,@durasi_hari,@id_posting OUT)
    $id_posting = 0;
    $call_sql = "{CALL dbo.sp_CreatePosting(?,?,?,?,?,?,?,?)}";
    $params = array($id_req, NULL, 'Posting Test Toggle', 'Deskripsi', 'Kualifikasi', 1, 14, array(&$id_posting, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT));
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}
    echo "[OK] Job Posting dibuat (ID: $id_posting, Form Aktif: 1)\n";

    // 6. Test sp_TogglePostingForm: Tutup Form
    // sig: @id_posting,@form_aktif,@durasi_hari,@oleh_user,@status_akhir OUT
    $status_akhir = -1;
    $call_sql = "{CALL dbo.sp_TogglePostingForm(?,?,?,?,?)}";
    $params = array($id_posting, 0, 14, $id_user, array(&$status_akhir, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT));
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}
    assert($status_akhir === 0, 'Form seharusnya ditutup');
    echo "[OK] Form berhasil ditutup via sp_TogglePostingForm (status: $status_akhir)\n";

    // 7. Test sp_TogglePostingForm: Buka Kembali Form
    $status_akhir = -1;
    $call_sql = "{CALL dbo.sp_TogglePostingForm(?,?,?,?,?)}";
    $params = array($id_posting, 1, 14, $id_user, array(&$status_akhir, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT));
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}
    assert($status_akhir === 1, 'Form seharusnya dibuka');
    echo "[OK] Form berhasil dibuka kembali via sp_TogglePostingForm (status: $status_akhir)\n";

    // 8. Test sp_UpdateRequisitionStatus: Ubah ke Sourcing_Ulang
    $call_sql = "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}";
    $params = array($id_req, 'Sourcing_Ulang', 'Pelamar batch 1 habis, buka batch 2', $id_user);
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}

    $stmt = run_query($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    assert($row['status_req'] === 'Sourcing_Ulang', 'Status seharusnya Sourcing_Ulang');
    echo "[OK] Status MPR berhasil diubah ke 'Sourcing_Ulang' oleh HR\n";

    // 9. Test sp_UpdateRequisitionStatus: Ubah ke Kadaluarsa -> form posting otomatis ditutup
    $call_sql = "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}";
    $params = array($id_req, 'Kadaluarsa', 'Batas SLA rekrutmen berakhir', $id_user);
    $stmt = sqlsrv_query($conn, $call_sql, $params);
    if ($stmt === FALSE) throw new Exception(print_r(sqlsrv_errors(), true));
    while (sqlsrv_next_result($stmt)) {}

    $stmt = run_query($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    assert($row['status_req'] === 'Kadaluarsa', 'Status seharusnya Kadaluarsa');

    $stmt = run_query($conn, "SELECT form_aktif FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($id_posting));
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    assert($row['form_aktif'] == 0, 'Form lowongan otomatis ditutup saat Kadaluarsa');
    echo "[OK] Status MPR menjadi 'Kadaluarsa' dan form posting otomatis ditutup (form_aktif = 0)\n";

    // 10. Test query postings_for_req persis seperti di Requisition_model.php
    $sql = "SELECT jp.id_posting, jp.id_req, jp.judul_posting, jp.url_slug, jp.form_aktif,
                   jp.form_dibuka, jp.form_ditutup, jp.jumlah_submit, jp.tanggal_posting,
                   (SELECT COUNT(*) FROM dbo.APPLICATIONS a WHERE a.id_posting = jp.id_posting) AS n_lamaran
            FROM dbo.JOB_POSTINGS jp
            WHERE jp.id_req = ? AND jp.is_aktif = 1
            ORDER BY jp.id_posting DESC";
    $stmt = run_query($conn, $sql, array($id_req));
    $test_rows = array();
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        foreach ($r as $k => $v) {
            if ($v instanceof DateTime) {
                $r[$k] = $v->format('Y-m-d H:i');
            }
        }
        $test_rows[] = $r;
    }
    assert(count($test_rows) === 1, 'Harus ada 1 posting');
    assert(isset($test_rows[0]['tanggal_posting']), 'Kolom tanggal_posting harus ada');
    echo "[OK] Query model postings_for_req() berhasil dieksekusi tanpa error kolom\n";

    // 11. Bersihkan data dummy uji coba
    run_query($conn, "DELETE FROM dbo.JOB_POSTINGS WHERE id_req = ?", array($id_req));
    run_query($conn, "DELETE FROM dbo.REQUISITION_APPROVALS WHERE id_req = ?", array($id_req));
    run_query($conn, "DELETE FROM dbo.RPT_FUNNEL_HARIAN WHERE id_req = ?", array($id_req));
    run_query($conn, "DELETE FROM dbo.AUDIT_LOG WHERE nama_tabel = 'REQUISITIONS' AND id_baris = ?", array($id_req));
    run_query($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
    echo "[OK] Data uji coba dibersihkan dengan aman.\n";

    echo "\n>>> SEMUA PENGECEKAN STATUS MPR & TOGGLE FORM SUKSES (100% PASS) <<<\n";
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

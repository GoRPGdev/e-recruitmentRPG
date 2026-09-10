<?php
/**
 * Test E2E Alur Pengajuan, Revisi HR, Penolakan HR, dan Resubmit Pemohon
 *
 * Usage: php tools/test-rejection-flow.php
 */

if (php_sapi_name() !== 'cli') {
    die("Jalankan dari CLI: php tools/test-rejection-flow.php\n");
}

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) {
    die("[FATAL] File tools/koneksi.local.php tidak ditemukan.\n");
}
$cfg = require $cfgPath;

echo "============================================================\n";
echo "   TEST E2E ALUR REQUISITION: REVISI HR & PENOLAKAN HR   \n";
echo "============================================================\n\n";

$conn = sqlsrv_connect($cfg['host'], array(
    'Database'              => $cfg['database'],
    'UID'                   => $cfg['user'],
    'PWD'                   => $cfg['password'],
    'CharacterSet'          => 'UTF-8',
    'ReturnDatesAsStrings'  => true,
));

if ($conn === false) {
    die("[FATAL] Gagal konek DB: " . print_r(sqlsrv_errors(), true));
}

function q($conn, $sql, $p = array()) {
    $stmt = sqlsrv_query($conn, $sql, $p);
    if ($stmt === false) {
        $e = sqlsrv_errors();
        throw new Exception(end($e)['message']);
    }
    return $stmt;
}

$lulus = 0;
$total = 0;

function cek($kondisi, $pesan) {
    global $lulus, $total;
    $total++;
    if ($kondisi) {
        $lulus++;
        echo "  [PASS] {$pesan}\n";
    } else {
        echo "  [FAIL] {$pesan}\n";
    }
}

try {
    // 1. Dapatkan user pemohon dan posisi valid
    $r_u = sqlsrv_fetch_array(q($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1 ORDER BY id_user"), SQLSRV_FETCH_ASSOC);
    $id_user = (int) $r_u['id_user'];

    $r_p = sqlsrv_fetch_array(q($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY id_posisi"), SQLSRV_FETCH_ASSOC);
    $id_posisi = (int) $r_p['id_posisi'];

    // 2. Buat Requisition Draft
    $id_req = 0;
    $call_create = "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}";
    $p_create = array(
        $id_user, $id_posisi, 'HQ', NULL, 1, 'Tetap', 'Penambahan Tim', NULL,
        NULL, 'Normal', NULL, 0, 0, NULL, NULL, 'Jobdesc test', 'Kualifikasi test',
        NULL, NULL, NULL,
        array(&$id_req, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt_c = sqlsrv_query($conn, $call_create, $p_create);
    cek($stmt_c !== false && $id_req > 0, "sp_CreateRequisition berhasil membuat MPR draft (ID: {$id_req})");

    // 3. Ajukan ke Review HR via sp_SubmitToHR
    q($conn, "{CALL dbo.sp_SubmitToHR(?,?)}", array($id_req, $id_user));
    $r_stat = sqlsrv_fetch_array(q($conn, "SELECT status_req, no_mpr FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req)), SQLSRV_FETCH_ASSOC);
    cek($r_stat['status_req'] === 'Review_HR', "sp_SubmitToHR mengubah status menjadi Review_HR");
    cek(!empty($r_stat['no_mpr']), "sp_SubmitToHR menghasilkan no_mpr resmi: " . $r_stat['no_mpr']);

    // 4. Test Skenario Revisi_HR
    $arahan_revisi = "Mohon tambahkan rincian beban kerja 3 bulan terakhir dan lengkapi kualifikasi sertifikasi.";
    q($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}", array($id_req, 'Revisi_HR', $arahan_revisi, $id_user));

    $r_rev = sqlsrv_fetch_array(q($conn, "SELECT status_req, catatan_hr FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req)), SQLSRV_FETCH_ASSOC);
    cek($r_rev['status_req'] === 'Revisi_HR', "Status berhasil berubah menjadi Revisi_HR");
    cek($r_rev['catatan_hr'] === $arahan_revisi, "Catatan arahan revisi HR tersimpan di kolom catatan_hr");

    // 5. Test Resubmit Pemohon dari Revisi_HR -> Review_HR
    q($conn, "{CALL dbo.sp_SubmitToHR(?,?)}", array($id_req, $id_user));
    $r_resub = sqlsrv_fetch_array(q($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req)), SQLSRV_FETCH_ASSOC);
    cek($r_resub['status_req'] === 'Review_HR', "Pemohon berhasil mengajukan kembali dari Revisi_HR ke Review_HR");

    // 6. Test Skenario Ditolak_HR beserta catatan penolakannya
    $alasan_tolak = "Budget rekrutmen departemen telah mencapai pagu maksimal tahun berjalan. Pengajuan ditolak.";
    q($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}", array($id_req, 'Ditolak_HR', $alasan_tolak, $id_user));

    $r_rej = sqlsrv_fetch_array(q($conn, "SELECT status_req, catatan_hr FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req)), SQLSRV_FETCH_ASSOC);
    cek($r_rej['status_req'] === 'Ditolak_HR', "Status berhasil berubah menjadi Ditolak_HR");
    cek($r_rej['catatan_hr'] === $alasan_tolak, "Alasan penolakan HR berhasil tersimpan di kolom catatan_hr");

    // 7. Test Query View / Model get()
    $sql_view = "
        SELECT r.*, p.nama_posisi, p.id_departemen, o.nama_outlet, d.nama AS departemen, u.nama_snapshot AS pemohon,
               bod_rej.catatan_bod, bod_rej.disetujui_oleh AS penolak_bod
        FROM dbo.REQUISITIONS r
        JOIN dbo.M_POSISI p       ON p.id_posisi = r.id_posisi
        LEFT JOIN dbo.M_OUTLET o  ON o.id_outlet = r.id_outlet
        LEFT JOIN dbo.M_DEPARTEMEN d ON d.id_departemen = p.id_departemen
        JOIN dbo.M_USERS u        ON u.id_user = r.id_user_pemohon
        OUTER APPLY (
            SELECT TOP 1 ra.catatan_bod, ra.disetujui_oleh
            FROM dbo.REQUISITION_APPROVALS ra
            WHERE ra.id_req = r.id_req AND ra.keputusan = 'Rejected'
            ORDER BY ra.putaran_ke DESC
        ) bod_rej
        WHERE r.id_req = ?
    ";
    $r_view = sqlsrv_fetch_array(q($conn, $sql_view, array($id_req)), SQLSRV_FETCH_ASSOC);
    cek($r_view['status_req'] === 'Ditolak_HR', "View query mendeteksi status Ditolak_HR");
    cek($r_view['catatan_hr'] === $alasan_tolak, "View query menampilkan catatan_hr dengan tepat sebagai alasan penolakan");

    // 8. Bersihkan data dummy
    q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
    echo "\n  [CLEANUP] Dummy MPR #{$id_req} dihapus.\n";

} catch (Exception $e) {
    echo "\n[ERROR] Exception: " . $e->getMessage() . "\n";
}

echo "\n============================================================\n";
echo "HASIL TEST: {$lulus} / {$total} pengujian berhasil.\n";
echo "============================================================\n";

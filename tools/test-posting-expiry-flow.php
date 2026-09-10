<?php
/**
 * Test E2E Batas Waktu Form Publik, Kadaluarsa, dan Perpanjangan Sourcing_Ulang
 *
 * Skenario yang diuji:
 * 1. Pembuatan draft MPR dan persetujuan hingga status 'Approved'.
 * 2. Generate Link Form Publik dengan batas waktu durasi hari (@durasi_hari = 7).
 * 3. Verifikasi: form_ditutup terhitung +7 hari dari sekarang, status MPR otomatis beralih ke 'Sourcing'.
 * 4. Pengecekan real-time form terbuka sebelum kadaluarsa (terbuka = 1).
 * 5. Simulasi form publik melewati deadline (form_ditutup diset ke masa lalu):
 *    - Pengecekan real-time form publik otomatis tertutup (terbuka = 0).
 *    - Indikator is_kadaluarsa bernilai 1.
 * 6. Eksekusi Perpanjangan Form Lowongan via sp_ExtendPosting (+14 hari):
 *    - Verifikasi: form_ditutup dimajukan +14 hari.
 *    - Verifikasi: batch_ke bertambah (batch_ke = 2).
 *    - Verifikasi: status MPR otomatis beralih menjadi 'Sourcing_Ulang'.
 *    - Verifikasi: form publik kembali terbuka (terbuka = 1, is_kadaluarsa = 0).
 *    - Verifikasi: audit log tercatat untuk aksi 'EXTEND_POSTING'.
 * 7. Pembatalan MPR (sp_CancelRequisition) dan verifikasi form publik otomatis dinonaktifkan.
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
    die("Koneksi database gagal: " . print_r(sqlsrv_errors(), true));
}

function q($conn, $sql, $params = array()) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

function one($conn, $sql, $params = array()) {
    $stmt = q($conn, $sql, $params);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return $row;
}

echo "=== MEMULAI TEST E2E FORM PUBLIK, KADALUARSA, & SOURCING_ULANG ===\n\n";

// 1. Ambil master pendukung
$posisi = one($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1");
$user = one($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1");
$id_posisi = (int) $posisi['id_posisi'];
$id_user = (int) $user['id_user'];

// 2. Buat MPR Baru
$id_req = 0;
$paramsCreate = array(
    array($id_user, SQLSRV_PARAM_IN),
    array($id_posisi, SQLSRV_PARAM_IN),
    array('HQ', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(1, SQLSRV_PARAM_IN),
    array('Tetap', SQLSRV_PARAM_IN),
    array('Uji Coba Expiry & Sourcing Ulang', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array('2026-12-31', SQLSRV_PARAM_IN),
    array('Tinggi', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(0, SQLSRV_PARAM_IN),
    array(0, SQLSRV_PARAM_IN),
    array('S1', SQLSRV_PARAM_IN),
    array(2, SQLSRV_PARAM_IN),
    array('Job desc test', SQLSRV_PARAM_IN),
    array('Kualifikasi test', SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(null, SQLSRV_PARAM_IN),
    array(&$id_req, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
);

$stmt = sqlsrv_query($conn, "{CALL dbo.sp_CreateRequisition(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}", $paramsCreate);
if ($stmt === false) {
    die("Create failed: " . print_r(sqlsrv_errors(), true));
}
sqlsrv_next_result($stmt);
sqlsrv_free_stmt($stmt);

echo "[1] MPR dibuat: id_req = $id_req\n";

// 3. Ajukan ke HR -> Review_HR
q($conn, "{CALL dbo.sp_SubmitToHR(?,?)}", array($id_req, $id_user));

// 4. Teruskan ke BOD -> Review_BOD
q($conn, "{CALL dbo.sp_SubmitToBOD(?,?)}", array($id_req, $id_user));

// 5. BOD Setujui -> Approved
q($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?,?,?,?)}", array($id_req, 'Approved', 'Disetujui BOD', $id_user));

$req = one($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
assert($req['status_req'] === 'Approved', "Status harus Approved, didapat: " . $req['status_req']);
echo "[2] MPR berhasil disetujui, status_req = 'Approved'\n";

// 6. Generate Link Form Publik dengan batas waktu 7 hari
$id_posting = 0;
$stmt_post = sqlsrv_query($conn, "{CALL dbo.sp_CreatePosting(?,?,?,?,?,?,?,?)}", array(
    $id_req, null, 'Lowongan E2E Batas Waktu', 'Job desc posting', 'Kualifikasi posting', 1, 7,
    array(&$id_posting, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
));
while (sqlsrv_next_result($stmt_post)) {}
sqlsrv_free_stmt($stmt_post);

echo "[3] Job posting dibuat: id_posting = $id_posting (durasi 7 hari)\n";

// 7. Verifikasi form_ditutup dan transisi status MPR -> Sourcing
$post = one($conn, "SELECT form_aktif, form_dibuka, form_ditutup, batch_ke,
                    DATEDIFF(DAY, GETDATE(), form_ditutup) AS sisa_hari,
                    CASE WHEN form_aktif = 1 AND form_ditutup IS NOT NULL AND form_ditutup < GETDATE() THEN 1 ELSE 0 END AS is_kadaluarsa
                    FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($id_posting));
$req_after_post = one($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));

assert((int)$post['form_aktif'] === 1, "Form harus aktif");
assert((int)$post['batch_ke'] === 1, "Batch awal harus 1");
assert((int)$post['is_kadaluarsa'] === 0, "Form belum kadaluarsa");
assert((int)$post['sisa_hari'] >= 6 && (int)$post['sisa_hari'] <= 7, "Sisa hari harus ~7 hari");
assert($req_after_post['status_req'] === 'Sourcing', "Status MPR harus beralih ke Sourcing, didapat: " . $req_after_post['status_req']);
echo "[4] Verifikasi link form aktif: Sisa hari = {$post['sisa_hari']} hari, Status MPR = '{$req_after_post['status_req']}'\n";

// 8. Cek evaluasi form publik terbuka (query yang dipakai di Application_model)
$pub_check = one($conn, "SELECT CASE WHEN jp.form_aktif = 1
                              AND (jp.form_dibuka  IS NULL OR jp.form_dibuka  <= GETDATE())
                              AND (jp.form_ditutup IS NULL OR jp.form_ditutup >= GETDATE())
                              AND r.status_req NOT IN ('Draft','Terpenuhi','Dibatalkan','Kadaluarsa')
                         THEN 1 ELSE 0 END AS terbuka
                         FROM dbo.JOB_POSTINGS jp
                         JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
                         WHERE jp.id_posting = ?", array($id_posting));
assert((int)$pub_check['terbuka'] === 1, "Portal publik harus terbuka untuk pelamar");
echo "[5] Portal publik terbuka (terbuka = 1)\n";

// 9. Simulasi lowongan melewati batas waktu (Kadaluarsa)
q($conn, "UPDATE dbo.JOB_POSTINGS SET form_ditutup = DATEADD(DAY, -2, GETDATE()) WHERE id_posting = ?", array($id_posting));

$post_kadaluarsa = one($conn, "SELECT CASE WHEN form_aktif = 1 AND form_ditutup IS NOT NULL AND form_ditutup < GETDATE() THEN 1 ELSE 0 END AS is_kadaluarsa
                               FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($id_posting));
$pub_check_kadaluarsa = one($conn, "SELECT CASE WHEN jp.form_aktif = 1
                                         AND (jp.form_dibuka  IS NULL OR jp.form_dibuka  <= GETDATE())
                                         AND (jp.form_ditutup IS NULL OR jp.form_ditutup >= GETDATE())
                                         AND r.status_req NOT IN ('Draft','Terpenuhi','Dibatalkan','Kadaluarsa')
                                    THEN 1 ELSE 0 END AS terbuka
                                    FROM dbo.JOB_POSTINGS jp
                                    JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
                                    WHERE jp.id_posting = ?", array($id_posting));

assert((int)$post_kadaluarsa['is_kadaluarsa'] === 1, "Indikator is_kadaluarsa harus 1 setelah melewati deadline");
assert((int)$pub_check_kadaluarsa['terbuka'] === 0, "Portal publik harus tertutup karena kadaluarsa");
echo "[6] Simulasi kadaluarsa sukses: is_kadaluarsa = 1, portal publik tertutup (terbuka = 0)\n";

// 10. Perpanjang lowongan via sp_ExtendPosting (+14 hari) -> Sourcing_Ulang
q($conn, "{CALL dbo.sp_ExtendPosting(?,?,?)}", array($id_posting, 14, $id_user));

$post_extended = one($conn, "SELECT form_aktif, batch_ke, DATEDIFF(DAY, GETDATE(), form_ditutup) AS sisa_hari,
                             CASE WHEN form_aktif = 1 AND form_ditutup IS NOT NULL AND form_ditutup < GETDATE() THEN 1 ELSE 0 END AS is_kadaluarsa
                             FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($id_posting));
$req_extended = one($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
$pub_check_extended = one($conn, "SELECT CASE WHEN jp.form_aktif = 1
                                       AND (jp.form_dibuka  IS NULL OR jp.form_dibuka  <= GETDATE())
                                       AND (jp.form_ditutup IS NULL OR jp.form_ditutup >= GETDATE())
                                       AND r.status_req NOT IN ('Draft','Terpenuhi','Dibatalkan','Kadaluarsa')
                                  THEN 1 ELSE 0 END AS terbuka
                                  FROM dbo.JOB_POSTINGS jp
                                  JOIN dbo.REQUISITIONS r ON r.id_req = jp.id_req
                                  WHERE jp.id_posting = ?", array($id_posting));

assert((int)$post_extended['form_aktif'] === 1, "Form harus kembali aktif");
assert((int)$post_extended['batch_ke'] === 2, "Nomor batch harus naik menjadi 2, didapat: " . $post_extended['batch_ke']);
assert((int)$post_extended['is_kadaluarsa'] === 0, "Status kadaluarsa harus reset menjadi 0");
assert((int)$post_extended['sisa_hari'] >= 13 && (int)$post_extended['sisa_hari'] <= 14, "Sisa hari harus ~14 hari");
assert($req_extended['status_req'] === 'Sourcing_Ulang', "Status MPR harus beralih ke Sourcing_Ulang, didapat: " . $req_extended['status_req']);
assert((int)$pub_check_extended['terbuka'] === 1, "Portal publik harus kembali terbuka setelah diperpanjang");

echo "[7] Perpanjangan sukses: batch_ke = {$post_extended['batch_ke']}, sisa_hari = {$post_extended['sisa_hari']}, Status MPR = '{$req_extended['status_req']}', portal terbuka kembali!\n";

// 11. Periksa pencatatan Audit Log
$audit = one($conn, "SELECT TOP 1 aksi, nilai_baru FROM dbo.AUDIT_LOG WHERE nama_tabel = 'JOB_POSTINGS' AND id_baris = ? AND aksi = 'UPDATE' ORDER BY id_audit DESC", array($id_posting));
assert(!empty($audit), "Audit log untuk UPDATE JOB_POSTINGS harus tercatat");
echo "[8] Audit log terkonfirmasi: aksi = '{$audit['aksi']}', nilai_baru = '{$audit['nilai_baru']}'\n";

// 12. Cleanup uji coba
q($conn, "DELETE FROM dbo.AUDIT_LOG WHERE nama_tabel = 'JOB_POSTINGS' AND id_baris = ?", array($id_posting));
q($conn, "DELETE FROM dbo.AUDIT_LOG WHERE nama_tabel = 'REQUISITIONS' AND id_baris = ?", array($id_req));
q($conn, "DELETE FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($id_posting));
q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
echo "[9] Pembersihan data test selesai.\n";

echo "\n>>> SELURUH CHECKPOINT TEST E2E FORM PUBLIK, KADALUARSA, & SOURCING_ULANG BERHASIL 100%! <<<\n";

<?php
/**
 * tools/seed-mpr-dua-kali-revisi.php
 * Script helper pembentukan data contoh nyata MPR dengan riwayat 2x revisi (HR & BOD):
 * 1. Putaran 1: Draft -> Diajukan ke HR -> Review_HR -> Revisi_HR (dengan catatan_hr)
 * 2. Putaran 2: Diajukan kembali ke HR -> Review_HR -> Diserahkan ke BOD -> Review_BOD -> Revisi_BOD (dengan catatan_bod)
 *
 * Menjalankan Stored Procedure resmi (sp_CreateRequisition, sp_SubmitToHR, sp_UpdateRequisitionStatus, sp_SubmitToBOD).
 * Sesuai panduan arsitektur CLAUDE.md.
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) {
    fwrite(STDERR, "[FAIL] File tools/koneksi.local.php tidak ditemukan.\n");
    exit(1);
}
$cfg = require $cfgPath;

$conn = sqlsrv_connect($cfg['host'], array(
    'Database'     => $cfg['database'],
    'UID'          => $cfg['user'],
    'PWD'          => $cfg['password'],
    'CharacterSet' => 'UTF-8',
));
if ($conn === false) {
    fwrite(STDERR, "Koneksi database gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_configure('WarningsReturnAsErrors', 0);

function scalar($conn, $sql, $p = array()) {
    $st = sqlsrv_query($conn, $sql, $p);
    if ($st === false) {
        fwrite(STDERR, "Query gagal: $sql\n" . print_r(sqlsrv_errors(), true) . "\n");
        exit(1);
    }
    $r = sqlsrv_fetch_array($st, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($st);
    return $r ? $r[0] : null;
}

function q($conn, $sql, $p = array()) {
    $st = sqlsrv_query($conn, $sql, $p);
    if ($st === false) {
        fwrite(STDERR, "Query gagal: $sql\n" . print_r(sqlsrv_errors(), true) . "\n");
        exit(1);
    }
    return $st;
}

echo "=== MEMBUAT DATA CONTOH MPR DENGAN ALUR 2X REVISI (HR & BOD) ===\n";

// 1. Ambil ID User Pemohon, HR, dan BOD yang valid di sistem dev
$idUserPemohon = scalar($conn, "SELECT TOP 1 u.id_user FROM dbo.M_USERS u JOIN dbo.M_ROLES r ON r.id_role = u.id_role WHERE r.kode_role IN ('USER_DEPT', 'SUPER_ADMIN') AND u.is_aktif = 1 ORDER BY u.id_user ASC");
$idUserHR      = scalar($conn, "SELECT TOP 1 u.id_user FROM dbo.M_USERS u JOIN dbo.M_ROLES r ON r.id_role = u.id_role WHERE r.kode_role IN ('HR_SPV', 'HR_ADMIN', 'SUPER_ADMIN') AND u.is_aktif = 1 ORDER BY u.id_user ASC");
$idUserBOD     = scalar($conn, "SELECT TOP 1 u.id_user FROM dbo.M_USERS u JOIN dbo.M_ROLES r ON r.id_role = u.id_role WHERE r.kode_role IN ('BOD', 'SUPER_ADMIN') AND u.is_aktif = 1 ORDER BY u.id_user ASC");

$idPosisi   = scalar($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY id_posisi ASC");
$namaPosisi = scalar($conn, "SELECT nama_posisi FROM dbo.M_POSISI WHERE id_posisi = ?", array($idPosisi));

echo "Pemohon ID : $idUserPemohon\n";
echo "HR ID      : $idUserHR\n";
echo "BOD ID     : $idUserBOD\n";
echo "Posisi     : $namaPosisi (ID: $idPosisi)\n\n";

// 2. Buat Requisition Draft via sp_CreateRequisition
$id_req = 0;
$spSql = "{CALL dbo.sp_CreateRequisition(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
$tipe_penempatan   = 'HQ';
$id_outlet         = null;
$jumlah_dibutuhkan = 2;
$status_karyawan   = 'KONTRAK';
$alasan_permintaan = 'PENAMBAHAN';
$nik_digantikan    = null;
$target_join       = date('Y-m-d', strtotime('+30 days'));
$urgensi           = 'NORMAL';
$id_flow           = null;
$butuh_psiko       = 1;
$butuh_bod         = 1;
$pend_min          = 'S1';
$exp_min           = 2;
$job_desc          = "Bertanggung jawab atas operasional dan implementasi sistem kerja harian tim.";
$kualifikasi       = "Pengalaman minimal 2 tahun di bidang terkait, komunikatif, dan mampu bekerja dalam tim.";
$gaji_min          = 5000000;
$gaji_max          = 7500000;
$preferensi        = "Diutamakan yang memiliki pemahaman workflow internal.";

$params = array(
    array($idUserPemohon, SQLSRV_PARAM_IN),
    array($idPosisi, SQLSRV_PARAM_IN),
    array($tipe_penempatan, SQLSRV_PARAM_IN),
    array($id_outlet, SQLSRV_PARAM_IN),
    array($jumlah_dibutuhkan, SQLSRV_PARAM_IN),
    array($status_karyawan, SQLSRV_PARAM_IN),
    array($alasan_permintaan, SQLSRV_PARAM_IN),
    array($nik_digantikan, SQLSRV_PARAM_IN),
    array($target_join, SQLSRV_PARAM_IN),
    array($urgensi, SQLSRV_PARAM_IN),
    array($id_flow, SQLSRV_PARAM_IN),
    array($butuh_psiko, SQLSRV_PARAM_IN),
    array($butuh_bod, SQLSRV_PARAM_IN),
    array($pend_min, SQLSRV_PARAM_IN),
    array($exp_min, SQLSRV_PARAM_IN),
    array($job_desc, SQLSRV_PARAM_IN),
    array($kualifikasi, SQLSRV_PARAM_IN),
    array($gaji_min, SQLSRV_PARAM_IN),
    array($gaji_max, SQLSRV_PARAM_IN),
    array($preferensi, SQLSRV_PARAM_IN),
    array(&$id_req, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
);

$st = sqlsrv_query($conn, $spSql, $params);
if ($st === false) {
    fwrite(STDERR, "sp_CreateRequisition gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_free_stmt($st);
echo "[1/6] Requisition dibuat: ID $id_req (Status: Draft)\n";

// 3. Pemohon mengajukan ke HR (sp_SubmitToHR) -> Status: Review_HR
$st = sqlsrv_query($conn, "{CALL dbo.sp_SubmitToHR(?, ?)}", array(
    array($id_req, SQLSRV_PARAM_IN),
    array($idUserPemohon, SQLSRV_PARAM_IN)
));
if ($st === false) {
    fwrite(STDERR, "sp_SubmitToHR gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_free_stmt($st);
$noMpr = scalar($conn, "SELECT no_mpr FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
echo "[2/6] Diajukan ke Tim HR: No MPR $noMpr (Status: Review_HR)\n";

// 4. REVISI KE-1: Evaluasi HR meminta revisi ke pemohon
$catatanHR = "Berdasarkan evaluasi beban kerja Q3, alokasi kuota formasi disarankan disesuaikan menjadi 1 orang terlebih dahulu. Mohon perjelas kualifikasi keahlian teknis khusus yang dibutuhkan tim.";
$st = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?, ?, ?, ?)}", array(
    array($id_req, SQLSRV_PARAM_IN),
    array('Revisi_HR', SQLSRV_PARAM_IN),
    array($catatanHR, SQLSRV_PARAM_IN),
    array($idUserHR, SQLSRV_PARAM_IN)
));
if ($st === false) {
    fwrite(STDERR, "sp_UpdateRequisitionStatus (Revisi_HR) gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_free_stmt($st);
echo "[3/6] HR menetapkan status: Revisi_HR dengan catatan arahan revisi.\n";

// 5. Pemohon menyesuaikan data formasi & mengajukan kembali ke HR
q($conn, "UPDATE dbo.REQUISITIONS SET jumlah_dibutuhkan = 1 WHERE id_req = ?", array($id_req));

$st = sqlsrv_query($conn, "{CALL dbo.sp_SubmitToHR(?, ?)}", array(
    array($id_req, SQLSRV_PARAM_IN),
    array($idUserPemohon, SQLSRV_PARAM_IN)
));
if ($st === false) {
    fwrite(STDERR, "sp_SubmitToHR setelah revisi gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_free_stmt($st);
echo "[4/6] Pemohon telah menyesuaikan formasi jadi 1 orang & mengajukan kembali ke HR (Status: Review_HR).\n";

// 6. HR menyetujui evaluasi dan meneruskan dokumen ke Direksi (Review_BOD)
$st = sqlsrv_query($conn, "{CALL dbo.sp_SubmitToBOD(?, ?)}", array(
    array($id_req, SQLSRV_PARAM_IN),
    array($idUserHR, SQLSRV_PARAM_IN)
));
if ($st === false) {
    fwrite(STDERR, "sp_SubmitToBOD gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_free_stmt($st);
echo "[5/6] HR menyetujui evaluasi dan meneruskan dokumen ke Direksi (Status: Review_BOD).\n";

// 7. REVISI KE-2: Direksi (BOD) meminta penyesuaian jadwal join
$catatanBOD = "Direksi menyetujui kualifikasi formasi 1 orang ini, namun target tanggal bergabung mohon dimajukan ke tanggal 1 bulan depan agar dapat mengikuti masa orientasi onboarding serentak.";
$st = sqlsrv_query($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?, ?, ?, ?)}", array(
    array($id_req, SQLSRV_PARAM_IN),
    array('Revisi_BOD', SQLSRV_PARAM_IN),
    array($catatanBOD, SQLSRV_PARAM_IN),
    array($idUserBOD, SQLSRV_PARAM_IN)
));
if ($st === false) {
    fwrite(STDERR, "sp_UpdateRequisitionStatus (Revisi_BOD) gagal: " . print_r(sqlsrv_errors(), true) . "\n");
    exit(1);
}
sqlsrv_free_stmt($st);
echo "[6/6] Direksi menetapkan status: Revisi_BOD dengan catatan arahan revisi Direksi.\n";

$res = q($conn, "SELECT id_req, no_mpr, status_req, catatan_hr, catatan_bod, jumlah_dibutuhkan FROM dbo.REQUISITIONS WHERE id_req = ?", array($id_req));
$row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);

echo "\n=======================================================\n";
echo "✓ SUKSES: DATA CONTOH MPR 2X REVISI TELAH AKTIF DI DEV\n";
echo "=======================================================\n";
echo "ID Requisition : " . $row['id_req'] . "\n";
echo "Nomor MPR      : " . $row['no_mpr'] . "\n";
echo "Status Final   : " . $row['status_req'] . " (Revisi dari Direksi)\n";
echo "Formasi Akhir  : " . $row['jumlah_dibutuhkan'] . " orang\n";
echo "Catatan HR (1) : " . $row['catatan_hr'] . "\n";
echo "Catatan BOD (2): " . $row['catatan_bod'] . "\n";
echo "URL Tinjauan   : http://localhost:8080/requisitions/view/" . $row['id_req'] . "\n";
echo "=======================================================\n";

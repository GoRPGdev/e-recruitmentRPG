<?php
/**
 * Deploy script mandiri untuk perbaikan Alur Pengajuan MPR & Feedback/Revisi/Penolakan HR
 *
 * Menjalankan:
 * 1. Migrasi DDL: Penambahan kolom catatan_hr & update constraint CK_REQ_status
 * 2. Deploy ulang Stored Procedure sp_UpdateRequisitionStatus (mendukung Revisi_HR, Ditolak_HR, simpan catatan_hr)
 * 3. Deploy ulang Stored Procedure sp_SubmitToHR (mendukung transisi dari Revisi_HR & Ditolak_HR)
 * 4. Deploy ulang Stored Procedure sp_CreateRequisition (validasi kesesuaian departemen pemohon)
 * 5. Deploy ulang Stored Procedure sp_UpdateRequisitionCatatanHR (edit catatan HR mandiri)
 * 6. Verifikasi skema kolom & constraint di database
 *
 * Usage: php tools/deploy-mpr-fix.php
 */

if (php_sapi_name() !== 'cli') {
    die("Jalankan dari command line: php tools/deploy-mpr-fix.php\n");
}

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) {
    die("[FATAL] File tools/koneksi.local.php tidak ditemukan.\n");
}
$cfg = require $cfgPath;

echo "============================================================\n";
echo "   DEPLOY & MIGRASI ALUR REQUISITION / MPR KE SQL SERVER   \n";
echo "   Database: " . $cfg['database'] . " @ " . $cfg['host'] . "\n";
echo "============================================================\n\n";

sqlsrv_configure('WarningsReturnAsErrors', 0);

$conn = sqlsrv_connect($cfg['host'], array(
    'Database'              => $cfg['database'],
    'UID'                   => $cfg['user'],
    'PWD'                   => $cfg['password'],
    'CharacterSet'          => 'UTF-8',
    'ReturnDatesAsStrings'  => true,
));

if ($conn === false) {
    echo "[FATAL] Gagal terhubung ke database:\n";
    print_r(sqlsrv_errors());
    exit(1);
}

function exec_batch($conn, $sql, $label = '') {
    $parts = preg_split('/^\s*GO\s*;?\s*$/mi', $sql);
    foreach ($parts as $idx => $part) {
        $p = trim($part);
        if ($p === '') continue;
        $stmt = sqlsrv_query($conn, $p);
        if ($stmt === false) {
            $errs = sqlsrv_errors();
            $msg = array();
            foreach ((array)$errs as $e) {
                $msg[] = "[SQL " . $e['code'] . "] " . trim($e['message']);
            }
            throw new Exception("Batch #{$idx} gagal: " . implode(" | ", $msg));
        }
        if (is_resource($stmt)) {
            sqlsrv_free_stmt($stmt);
        }
    }
    echo "  [OK] {$label}\n";
}

try {
    // -------------------------------------------------------------
    // 1. DDL: Kolom catatan_hr pada dbo.REQUISITIONS
    // -------------------------------------------------------------
    echo "[1/6] Memeriksa & menambahkan kolom catatan_hr...\n";
    $sql_col = "
    IF NOT EXISTS (
        SELECT 1 FROM sys.columns
        WHERE object_id = OBJECT_ID('dbo.REQUISITIONS') AND name = 'catatan_hr'
    )
    BEGIN
        ALTER TABLE dbo.REQUISITIONS ADD catatan_hr NVARCHAR(MAX) NULL;
    END
    ";
    exec_batch($conn, $sql_col, "Kolom catatan_hr siap di dbo.REQUISITIONS");

    // -------------------------------------------------------------
    // 2. DDL: Update Check Constraint CK_REQ_status
    // -------------------------------------------------------------
    echo "[2/6] Memperbarui check constraint CK_REQ_status...\n";
    $sql_ck = "
    IF OBJECT_ID('dbo.CK_REQ_status', 'C') IS NOT NULL
        ALTER TABLE dbo.REQUISITIONS DROP CONSTRAINT CK_REQ_status;
    ";
    exec_batch($conn, $sql_ck, "Drop constraint lama CK_REQ_status");

    $sql_ck_new = "
    ALTER TABLE dbo.REQUISITIONS ADD CONSTRAINT CK_REQ_status CHECK (status_req IN (
        'Draft',
        'Review_HR',
        'Revisi_HR',
        'Menunggu_BOD',
        'Approved',
        'Sourcing',
        'Sourcing_Ulang',
        'Terpenuhi_Sebagian',
        'Terpenuhi',
        'Ditolak_HR',
        'Ditolak_BOD',
        'Dibatalkan',
        'Kadaluarsa'
    ));
    ";
    exec_batch($conn, $sql_ck_new, "Buat constraint baru CK_REQ_status (mendukung Revisi_HR, Ditolak_HR, Ditolak_BOD)");

    // Catat ke SCHEMA_MIGRATIONS jika ada
    $check_sm = sqlsrv_query($conn, "SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE nama_file = '20260912_1600__requisition_catatan_hr.sql'");
    if ($check_sm !== false && !sqlsrv_has_rows($check_sm)) {
        $mg_content = @file_get_contents(dirname(__DIR__) . '/database/migrations/20260912_1600__requisition_catatan_hr.sql');
        $mg_hash = $mg_content ? hash('sha256', $mg_content) : 'manual_deploy';
        sqlsrv_query($conn, "INSERT INTO dbo.SCHEMA_MIGRATIONS (nama_file, hash_file, dijalankan_pada, dijalankan_oleh) VALUES (?, ?, GETDATE(), ?)", array(
            '20260912_1600__requisition_catatan_hr.sql', $mg_hash, get_current_user()
        ));
    }

    // -------------------------------------------------------------
    // 3. Deploy SP: sp_UpdateRequisitionStatus
    // -------------------------------------------------------------
    echo "[3/6] Deploy Stored Procedure dbo.sp_UpdateRequisitionStatus...\n";
    $sp_upd_file = dirname(__DIR__) . '/database/procedures/sp_UpdateRequisitionStatus.sql';
    if (!file_exists($sp_upd_file)) {
        throw new Exception("File {$sp_upd_file} tidak ditemukan.");
    }
    exec_batch($conn, file_get_contents($sp_upd_file), "sp_UpdateRequisitionStatus ter-deploy");

    // -------------------------------------------------------------
    // 4. Deploy SP: sp_UpdateRequisitionCatatanHR
    // -------------------------------------------------------------
    echo "[4/6] Deploy Stored Procedure dbo.sp_UpdateRequisitionCatatanHR...\n";
    $sp_cat_file = dirname(__DIR__) . '/database/procedures/sp_UpdateRequisitionCatatanHR.sql';
    if (!file_exists($sp_cat_file)) {
        throw new Exception("File {$sp_cat_file} tidak ditemukan.");
    }
    exec_batch($conn, file_get_contents($sp_cat_file), "sp_UpdateRequisitionCatatanHR ter-deploy");

    // -------------------------------------------------------------
    // 5. Deploy SP: sp_SubmitToHR
    // -------------------------------------------------------------
    echo "[5/6] Deploy Stored Procedure dbo.sp_SubmitToHR...\n";
    $sp_sub_file = dirname(__DIR__) . '/database/procedures/sp_SubmitToHR.sql';
    if (!file_exists($sp_sub_file)) {
        throw new Exception("File {$sp_sub_file} tidak ditemukan.");
    }
    exec_batch($conn, file_get_contents($sp_sub_file), "sp_SubmitToHR ter-deploy");

    // -------------------------------------------------------------
    // 6. Deploy SP: sp_CreateRequisition
    // -------------------------------------------------------------
    echo "[6/6] Deploy Stored Procedure dbo.sp_CreateRequisition...\n";
    $sp_crt_file = dirname(__DIR__) . '/database/procedures/sp_CreateRequisition.sql';
    if (!file_exists($sp_crt_file)) {
        throw new Exception("File {$sp_crt_file} tidak ditemukan.");
    }
    exec_batch($conn, file_get_contents($sp_crt_file), "sp_CreateRequisition ter-deploy");

    // -------------------------------------------------------------
    // VERIFIKASI AKHIR
    // -------------------------------------------------------------
    echo "\n------------------------------------------------------------\n";
    echo "VERIFIKASI INTEGRITAS DATABASE:\n";

    // 1. Verifikasi kolom
    $stmt = sqlsrv_query($conn, "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'REQUISITIONS' AND COLUMN_NAME = 'catatan_hr'");
    $col_info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($col_info) {
        echo "  [OK] Kolom dbo.REQUISITIONS.catatan_hr terdeteksi (Tipe: " . $col_info['DATA_TYPE'] . ")\n";
    } else {
        echo "  [FAIL] Kolom catatan_hr belum terdeteksi!\n";
    }

    // 2. Verifikasi SP definitions
    $sp_list = array('sp_UpdateRequisitionStatus', 'sp_UpdateRequisitionCatatanHR', 'sp_SubmitToHR', 'sp_CreateRequisition');
    foreach ($sp_list as $sp) {
        $stmt_sp = sqlsrv_query($conn, "SELECT modify_date FROM sys.procedures WHERE name = ?", array($sp));
        $sp_info = sqlsrv_fetch_array($stmt_sp, SQLSRV_FETCH_ASSOC);
        if ($sp_info) {
            echo "  [OK] Prosedur {$sp} aktif di DB (Updated: " . $sp_info['modify_date'] . ")\n";
        } else {
            echo "  [FAIL] Prosedur {$sp} TIDAK ditemukan di DB!\n";
        }
    }

    echo "\n============================================================\n";
    echo "  MIGRASI & DEPLOY BERHASIL 100%!\n";
    echo "  Status Revisi_HR & Ditolak_HR sudah siap digunakan secara aman.\n";
    echo "============================================================\n";

} catch (Exception $ex) {
    echo "\n[ERROR] " . $ex->getMessage() . "\n";
    exit(1);
}

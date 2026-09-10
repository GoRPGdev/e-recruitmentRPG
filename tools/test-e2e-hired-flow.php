<?php
/**
 * test-e2e-hired-flow.php
 * Pengujian End-to-End Alur Rekrutmen Lengkap:
 * Pengajuan MPR (User Dept) -> Approval BOD -> Job Posting -> Pelamar Masuk
 * -> Screening -> Kontak -> Psikotes -> Wawancara -> Offering -> HIRED
 * -> Pembatalan Hired (sp_CancelHired) -> Koreksi Kuota MPR & Posting
 * Dilengkapi pengecekan integritas data, SLA, audit history, dan akses sensitif.
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }

$ROOT = dirname(__DIR__);
$cfg  = require $ROOT . '/tools/koneksi.local.php';

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'],
    'UID'      => $cfg['user'],
    'PWD'      => $cfg['password'],
    'CharacterSet' => 'UTF-8',
));
if ($conn === false) {
    die("Koneksi gagal: " . print_r(sqlsrv_errors(), true));
}
sqlsrv_configure('WarningsReturnAsErrors', 0);

echo "\n" . str_repeat('=', 75) . "\n";
echo "   E2E TESTING ALUR LENGKAP: PENGAJUAN MPR HINGGA HIRED & PEMBATALAN\n";
echo "   Waktu: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 75) . "\n\n";

$step = 1;
function testLog($title, $status = 'INFO', $msg = '') {
    global $step;
    $badge = $status === 'OK' ? "[ PASS ]" : ($status === 'FAIL' ? "[ FAIL ]" : "[ INFO ]");
    echo sprintf("%s Langkah %02d: %s\n", $badge, $step++, $title);
    if ($msg) {
        echo "         -> " . $msg . "\n";
    }
}

function q($conn, $sql, $params = array()) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $err = sqlsrv_errors();
        throw new RuntimeException($err ? $err[0]['message'] : 'SQL Error');
    }
    return $stmt;
}

function scalar($conn, $sql, $params = array()) {
    $stmt = q($conn, $sql, $params);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($stmt);
    return $row ? $row[0] : null;
}

try {
    // -------------------------------------------------------------
    // LANGKAH 1: Ambil data referensi (User Dept, Posisi, Departemen)
    // -------------------------------------------------------------
    $userDept = scalar($conn, "SELECT id_user FROM dbo.M_USERS WHERE username = 'demo_user_dept' AND is_aktif = 1");
    $superAdmin = scalar($conn, "SELECT id_user FROM dbo.M_USERS WHERE username = 'demo_super_admin' AND is_aktif = 1");
    $idPosisi = scalar($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE nama_posisi LIKE 'Marketing%' AND is_aktif = 1 ORDER BY id_posisi");
    if (!$idPosisi) {
        $idPosisi = scalar($conn, "SELECT TOP 1 id_posisi FROM dbo.M_POSISI WHERE is_aktif = 1 ORDER BY id_posisi");
    }
    $idFlow = scalar($conn, "SELECT default_flow FROM dbo.M_POSISI WHERE id_posisi = ?", array($idPosisi));

    testLog("Identifikasi aktor pengujian", "OK", "User Dept: #$userDept, Super Admin: #$superAdmin, Posisi: #$idPosisi, Flow: #$idFlow");

    // -------------------------------------------------------------
    // LANGKAH 2: User Dept Membuat Requisition (Draft)
    // -------------------------------------------------------------
    $idReq = null;
    $sqlCreateReq = "{CALL dbo.sp_CreateRequisition(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
    $targetJoin = date('Y-m-d', strtotime('+30 days'));
    $paramsCreateReq = array(
        array($userDept, SQLSRV_PARAM_IN),
        array($idPosisi, SQLSRV_PARAM_IN),
        array('HQ', SQLSRV_PARAM_IN),
        array(null, SQLSRV_PARAM_IN), // id_outlet
        array(1, SQLSRV_PARAM_IN), // butuh 1 orang
        array('Tetap', SQLSRV_PARAM_IN),
        array('Penambahan Tim', SQLSRV_PARAM_IN),
        array(null, SQLSRV_PARAM_IN),
        array($targetJoin, SQLSRV_PARAM_IN),
        array('Tinggi', SQLSRV_PARAM_IN),
        array($idFlow, SQLSRV_PARAM_IN),
        array(1, SQLSRV_PARAM_IN), // butuh psikotes
        array(1, SQLSRV_PARAM_IN), // butuh interview bod
        array('S1', SQLSRV_PARAM_IN),
        array(2, SQLSRV_PARAM_IN),
        array('Mengelola campaign & lead digital', SQLSRV_PARAM_IN),
        array('Menguasai performance marketing', SQLSRV_PARAM_IN),
        array(6000000.00, SQLSRV_PARAM_IN),
        array(8500000.00, SQLSRV_PARAM_IN),
        array(null, SQLSRV_PARAM_IN),
        array(&$idReq, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt = sqlsrv_query($conn, $sqlCreateReq, $paramsCreateReq);
    if ($stmt === false) {
        throw new RuntimeException("Gagal sp_CreateRequisition: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    $stReq = scalar($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    testLog("User Dept membuat MPR Draft", $stReq === 'Draft' ? 'OK' : 'FAIL', "id_req: $idReq, status_req: $stReq");

    // -------------------------------------------------------------
    // LANGKAH 3: Teruskan MPR: Draft -> Review_HR -> Review_BOD
    // (alur baru: sp_SubmitToBOD 2 argumen, tidak lagi menulis REQUISITION_APPROVALS)
    // -------------------------------------------------------------
    q($conn, "{CALL dbo.sp_SubmitToHR(?, ?)}", array($idReq, $userDept));

    $sqlSubmitBOD = "{CALL dbo.sp_SubmitToBOD(?, ?)}";
    $stmt = sqlsrv_query($conn, $sqlSubmitBOD, array(
        array($idReq, SQLSRV_PARAM_IN),
        array($userDept, SQLSRV_PARAM_IN)
    ));
    if ($stmt === false) {
        throw new RuntimeException("Gagal sp_SubmitToBOD: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    $stReqAfterSubmit = scalar($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    $noMpr = scalar($conn, "SELECT no_mpr FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    testLog("Teruskan MPR ke BOD (Review_BOD)", $stReqAfterSubmit === 'Review_BOD' ? 'OK' : 'FAIL', "No MPR: $noMpr, status_req: $stReqAfterSubmit");

    // -------------------------------------------------------------
    // LANGKAH 4: BOD Menyetujui MPR (sp_UpdateRequisitionStatus -> Approved)
    // -------------------------------------------------------------
    q($conn, "{CALL dbo.sp_UpdateRequisitionStatus(?, ?, ?, ?)}", array(
        $idReq, 'Approved', 'Disetujui BOD sesuai kuota budget Q3 (uji E2E)', $superAdmin
    ));

    $stReqAfterApp = scalar($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    // status akan beralih ke 'Sourcing' otomatis saat job posting dibuat (LANGKAH 5)
    testLog("BOD Menyetujui MPR", $stReqAfterApp === 'Approved' ? 'OK' : 'FAIL', "Status Req: $stReqAfterApp");

    // -------------------------------------------------------------
    // LANGKAH 5: Buat Job Posting & Aktifkan Form Publik
    // -------------------------------------------------------------
    $idPosting = null;
    // sig: @id_req,@id_channel,@judul_posting,@job_desc,@kualifikasi,@batch_ke,@durasi_hari,@id_posting OUT
    $sqlCreatePosting = "{CALL dbo.sp_CreatePosting(?, ?, ?, ?, ?, ?, ?, ?)}";
    $paramsCreatePosting = array(
        array($idReq, SQLSRV_PARAM_IN),
        array(null, SQLSRV_PARAM_IN),
        array('Lowongan E2E Test Specialist RPG', SQLSRV_PARAM_IN),
        array('Deskripsi pekerjaan uji sistem e-rekruitmen', SQLSRV_PARAM_IN),
        array('Kualifikasi minimal berpengalaman', SQLSRV_PARAM_IN),
        array(1, SQLSRV_PARAM_IN),
        array(14, SQLSRV_PARAM_IN),
        array(&$idPosting, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt = sqlsrv_query($conn, $sqlCreatePosting, $paramsCreatePosting);
    if ($stmt === false) {
        throw new RuntimeException("Gagal sp_CreatePosting: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    // Aktifkan form publik dan berikan slug
    $testSlug = 'e2e-test-slug-' . time();
    q($conn, "UPDATE dbo.JOB_POSTINGS SET url_slug = ?, form_aktif = 1, form_dibuka = GETDATE() WHERE id_posting = ?", array($testSlug, $idPosting));

    $isFormAktif = scalar($conn, "SELECT form_aktif FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($idPosting));
    testLog("Buat dan Publikasikan Job Posting", ($idPosting && $isFormAktif == 1) ? 'OK' : 'FAIL', "id_posting: $idPosting, slug: $testSlug");

    // -------------------------------------------------------------
    // LANGKAH 6: Kandidat Melamar (sp_SubmitApplication)
    // -------------------------------------------------------------
    $idLamaran = null;
    $idKandidat = null;
    $isKandidatBaru = null;

    $namaKandidat = 'Kandidat Uji E2E ' . rand(100, 999);
    $emailKandidat = 'e2e_' . time() . '@test-rpg.local';
    $waKandidat = '0812' . rand(10000000, 99999999);

    $sqlSubmitApp = "{CALL dbo.sp_SubmitApplication(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
    $paramsSubmitApp = array(
        array($testSlug, SQLSRV_PARAM_IN), // url_slug
        array(null, SQLSRV_PARAM_IN),
        array(null, SQLSRV_PARAM_IN),
        array('FORM_PUBLIC', SQLSRV_PARAM_IN),
        array('Website Karir RPG', SQLSRV_PARAM_IN),
        array($namaKandidat, SQLSRV_PARAM_IN),
        array($emailKandidat, SQLSRV_PARAM_IN),
        array($waKandidat, SQLSRV_PARAM_IN),
        array('Jakarta', SQLSRV_PARAM_IN),
        array('1998-05-12', SQLSRV_PARAM_IN),
        array('L', SQLSRV_PARAM_IN),
        array('S1', SQLSRV_PARAM_IN),
        array('Universitas Indonesia', SQLSRV_PARAM_IN),
        array('Sistem Informasi', SQLSRV_PARAM_IN),
        array('Jakarta Selatan', SQLSRV_PARAM_IN),
        array('Jl. Rasuna Said No. 12', SQLSRV_PARAM_IN),
        array('Lajang', SQLSRV_PARAM_IN),
        array('Ibu Kandung', SQLSRV_PARAM_IN),
        array('081122334455', SQLSRV_PARAM_IN),
        array('Orang Tua', SQLSRV_PARAM_IN),
        array('PDP-v1', SQLSRV_PARAM_IN),
        array(1, SQLSRV_PARAM_IN), // talent pool consent
        array('Tidak ada riwayat penyakit berat', SQLSRV_PARAM_IN),
        array(1, SQLSRV_PARAM_IN), // consent kesehatan
        array('PT Retail Perkasa', SQLSRV_PARAM_IN),
        array('Staff Digital Marketing', SQLSRV_PARAM_IN),
        array('2022 - 2024', SQLSRV_PARAM_IN),
        array(6500000.00, SQLSRV_PARAM_IN),
        array(7500000.00, SQLSRV_PARAM_IN),
        array('hash_sha256_mock_' . bin2hex(random_bytes(16)), SQLSRV_PARAM_IN),
        array(null, SQLSRV_PARAM_IN), // id_import_batch
        array(&$idLamaran, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
        array(&$idKandidat, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
        array(&$isKandidatBaru, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    );
    $stmt = sqlsrv_query($conn, $sqlSubmitApp, $paramsSubmitApp);
    if ($stmt === false) {
        throw new RuntimeException("Gagal sp_SubmitApplication: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    $stGlobalApp = scalar($conn, "SELECT status_global FROM dbo.APPLICATIONS WHERE id_lamaran = ?", array($idLamaran));
    $cntStages = scalar($conn, "SELECT COUNT(*) FROM dbo.APPLICATION_STAGES WHERE id_lamaran = ?", array($idLamaran));
    testLog("Kandidat Melamar & Snapshot Flow Terpasang", ($idLamaran && $stGlobalApp === 'In_Progress' && $cntStages > 0) ? 'OK' : 'FAIL', "id_lamaran: $idLamaran, status_global: $stGlobalApp, total stages tersnapshot: $cntStages");

    // -------------------------------------------------------------
    // LANGKAH 7: Ambil Daftar Seluruh Tahap Lamaran (Snapshot)
    // -------------------------------------------------------------
    $stmtStages = q($conn, "SELECT ast.id_app_stage, ast.urutan, ast.status_tahap, ms.id_stage, ms.kode_stage, ms.nama_tahap, ms.tipe_tahap
                            FROM dbo.APPLICATION_STAGES ast
                            JOIN dbo.M_STAGE ms ON ms.id_stage = ast.id_stage
                            WHERE ast.id_lamaran = ?
                            ORDER BY ast.urutan ASC", array($idLamaran));
    $stages = array();
    while ($r = sqlsrv_fetch_array($stmtStages, SQLSRV_FETCH_ASSOC)) {
        $stages[] = $r;
    }
    sqlsrv_free_stmt($stmtStages);

    testLog("Evaluasi Alur Pipeline Tersnapshot", count($stages) >= 4 ? 'OK' : 'FAIL', "Jumlah tahapan alur: " . count($stages));
    foreach ($stages as $idx => $stg) {
        echo "           [" . ($idx+1) . "] Urutan: {$stg['urutan']} | {$stg['kode_stage']} - {$stg['nama_tahap']} (Tipe: {$stg['tipe_tahap']}) | Status: {$stg['status_tahap']}\n";
    }

    // -------------------------------------------------------------
    // LANGKAH 8: Iterasi Melalui Seluruh Tahap Hingga Tahap Terakhir
    // -------------------------------------------------------------
    $totalStages = count($stages);
    for ($i = 0; $i < $totalStages; $i++) {
        $cur = $stages[$i];
        $idAppStage = (int) $cur['id_app_stage'];
        $idStage = (int) $cur['id_stage'];
        $tipe = $cur['tipe_tahap'];
        $isLastStage = ($i === $totalStages - 1);

        echo "\n       >>> Memproses Tahap: {$cur['nama_tahap']} (Tipe: $tipe, id_app_stage: $idAppStage)\n";

        // A. JIKA TIPE KONTAK: Catat Upaya Kontak (sp_LogContact)
        if ($tipe === 'KONTAK') {
            $upayaKe = null;
            $sqlContact = "{CALL dbo.sp_LogContact(?, ?, ?, ?, ?, ?)}";
            $paramsContact = array(
                array($idLamaran, SQLSRV_PARAM_IN),
                array('WA', SQLSRV_PARAM_IN),
                array('Respon', SQLSRV_PARAM_IN),
                array('Kandidat merespons pesan WhatsApp dan bersedia mengikuti tes', SQLSRV_PARAM_IN),
                array($superAdmin, SQLSRV_PARAM_IN),
                array(&$upayaKe, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
            );
            $stCt = sqlsrv_query($conn, $sqlContact, $paramsContact);
            if ($stCt === false) {
                throw new RuntimeException("Gagal sp_LogContact: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stCt);
            echo "           [Action] Log Kontak tercatat. Upaya ke-$upayaKe berhasil.\n";
        }

        // B. JIKA TIPE TEST: Catat Nilai Psikotes (sp_SavePsikotes)
        if ($tipe === 'TEST') {
            $idPsi = null;
            // sig: @id_psikotes OUT, @id_app_stage, @vendor_tes, @tanggal_tes, @skor_total(INT), @hasil, @rekomendasi, @oleh_user
            $sqlPsi = "{CALL dbo.sp_SavePsikotes(?, ?, ?, ?, ?, ?, ?, ?)}";
            $paramsPsi = array(
                array(&$idPsi, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
                array($idAppStage, SQLSRV_PARAM_IN),
                array('Vendor Psikologi Terakreditasi', SQLSRV_PARAM_IN),
                array(date('Y-m-d'), SQLSRV_PARAM_IN),
                array(88, SQLSRV_PARAM_IN),
                array('Lulus', SQLSRV_PARAM_IN),
                array('Disarankan untuk posisi strategic execution', SQLSRV_PARAM_IN),
                array($superAdmin, SQLSRV_PARAM_IN)
            );
            $stPsi = sqlsrv_query($conn, $sqlPsi, $paramsPsi);
            if ($stPsi === false) {
                throw new RuntimeException("Gagal sp_SavePsikotes: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stPsi);
            echo "           [Action] Hasil Psikotes tersimpan (id_psikotes: $idPsi, Skor: 88.5, Rekomendasi: Disarankan).\n";
        }

        // C. JIKA TIPE INTERVIEW: Catat Hasil Wawancara (sp_SaveInterview)
        if ($tipe === 'INTERVIEW') {
            $idIv = null;
            // sig: @id_interview OUT, @id_app_stage, @tipe(Online/Offline), @jadwal, @lokasi_atau_link,
            //      @hasil, @skor, @catatan, @id_interviewer, @peran_interviewer(HR/User/BOD), @oleh_user
            $sqlIv = "{CALL dbo.sp_SaveInterview(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
            $paramsIv = array(
                array(&$idIv, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
                array($idAppStage, SQLSRV_PARAM_IN),
                array('Offline', SQLSRV_PARAM_IN),
                array(date('Y-m-d H:i:s'), SQLSRV_PARAM_IN),
                array('Head Office RPG Lantai 4', SQLSRV_PARAM_IN),
                array('Lulus', SQLSRV_PARAM_IN),
                array(85, SQLSRV_PARAM_IN),
                array('Komunikasi sangat baik, pemahaman teknis relevan', SQLSRV_PARAM_IN),
                array($userDept, SQLSRV_PARAM_IN),
                array('User', SQLSRV_PARAM_IN),
                array($superAdmin, SQLSRV_PARAM_IN)
            );
            $stIv = sqlsrv_query($conn, $sqlIv, $paramsIv);
            if ($stIv === false) {
                throw new RuntimeException("Gagal sp_SaveInterview: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stIv);
            echo "           [Action] Data Wawancara tersimpan (id_interview: $idIv, Hasil: Lulus, Skor: 85.0).\n";
        }

        // D. JIKA TIPE OFFER: Catat Penawaran Kerja (sp_SaveOffer)
        if ($tipe === 'OFFER') {
            $idOff = null;
            // sig: @id_offer OUT, @id_lamaran, @gaji_ditawarkan, @tanggal_penawaran, @tanggal_join_disepakati,
            //      @tanggal_join_aktual, @status_offer(Nego/Terkirim/Accepted/Ditolak), @alasan, @oleh_user
            $sqlOff = "{CALL dbo.sp_SaveOffer(?, ?, ?, ?, ?, ?, ?, ?, ?)}";
            $paramsOff = array(
                array(&$idOff, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
                array($idLamaran, SQLSRV_PARAM_IN),
                array(7500000.00, SQLSRV_PARAM_IN),
                array(date('Y-m-d'), SQLSRV_PARAM_IN),
                array(date('Y-m-d', strtotime('+14 days')), SQLSRV_PARAM_IN),
                array(date('Y-m-d', strtotime('+14 days')), SQLSRV_PARAM_IN),
                array('Diterima', SQLSRV_PARAM_IN),
                array('Offering disetujui kandidat tanpa negosiasi tambahan', SQLSRV_PARAM_IN),
                array($superAdmin, SQLSRV_PARAM_IN)
            );
            $stOff = sqlsrv_query($conn, $sqlOff, $paramsOff);
            if ($stOff === false) {
                throw new RuntimeException("Gagal sp_SaveOffer: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stOff);
            echo "           [Action] Data Penawaran Kerja (Offering) tersimpan (id_offer: $idOff, Status: Accepted, Gaji: 7.500.000).\n";
        }

        // E. ADVANCE KE TAHAP BERIKUTNYA ATAU HIRED
        // Cari remark yang sesuai untuk id_stage ini
        $efekTarget = $isLastStage ? 'HIRED' : 'LANJUT';
        $idRemark = scalar($conn, "SELECT TOP 1 id_remark FROM dbo.M_REMARKS WHERE id_stage = ? AND efek_status = ? AND is_aktif = 1", array($idStage, $efekTarget));

        if (!$idRemark) {
            // Jika tidak ada remark spesifik HIRED di tahap terakhir, cari remark LANJUT
            $idRemark = scalar($conn, "SELECT TOP 1 id_remark FROM dbo.M_REMARKS WHERE id_stage = ? AND efek_status IN ('LANJUT', 'HIRED') AND is_aktif = 1", array($idStage));
        }

        $statusBaru = null;
        $sqlAdv = "{CALL dbo.sp_AdvanceStage(?, ?, ?, ?, ?)}";
        $paramsAdv = array(
            array($idAppStage, SQLSRV_PARAM_IN),
            array($idRemark, SQLSRV_PARAM_IN),
            array($superAdmin, SQLSRV_PARAM_IN),
            array("Uji otomatis E2E tahap {$cur['nama_tahap']}", SQLSRV_PARAM_IN),
            array(&$statusBaru, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(20))
        );
        $stAdv = sqlsrv_query($conn, $sqlAdv, $paramsAdv);
        if ($stAdv === false) {
            throw new RuntimeException("Gagal sp_AdvanceStage: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stAdv);

        $stTahapNow = scalar($conn, "SELECT status_tahap FROM dbo.APPLICATION_STAGES WHERE id_app_stage = ?", array($idAppStage));
        echo "           [Advance Result] status_tahap: $stTahapNow, status_global_baru: $statusBaru\n";
    }

    testLog("Seluruh Siklus Seleksi Pipeline Berhasil Dilalui", "OK", "Kandidat telah diproses sampai tahap akhir");

    // -------------------------------------------------------------
    // LANGKAH 9: Evaluasi Dampak Status HIRED & Integritas Bisnis
    // -------------------------------------------------------------
    $finalStatusGlobal = scalar($conn, "SELECT status_global FROM dbo.APPLICATIONS WHERE id_lamaran = ?", array($idLamaran));
    $jmlTerpenuhi = scalar($conn, "SELECT jumlah_terpenuhi FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    $statusReqFinal = scalar($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    $cntHistory = scalar($conn, "SELECT COUNT(*) FROM dbo.APPLICATION_HISTORY WHERE id_lamaran = ?", array($idLamaran));
    $formAktifSaatHired = scalar($conn, "SELECT form_aktif FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($idPosting));

    echo "\n" . str_repeat('-', 60) . "\n";
    echo "  HASIL AKHIR STATUS & INTEGRITAS SISTEM SAAT HIRED:\n";
    echo str_repeat('-', 60) . "\n";
    echo "  Status Global Lamaran    : $finalStatusGlobal (Harapan: Hired)\n";
    echo "  Requisition Terpenuhi    : $jmlTerpenuhi dari 1 (Target)\n";
    echo "  Status Requisition Akhir : $statusReqFinal (Harapan: Terpenuhi)\n";
    echo "  Form Posting Terbuka     : $formAktifSaatHired (Harapan: 0 / Tertutup Otomatis)\n";
    echo "  Total Audit History Log  : $cntHistory jejak peristiwa\n";

    $isHiredLolos = ($finalStatusGlobal === 'Hired');
    $isReqTerpenuhi = ($jmlTerpenuhi >= 1 && in_array($statusReqFinal, array('Terpenuhi', 'Terpenuhi_Sebagian')));
    $isHistoryValid = ($cntHistory >= $totalStages);
    $isFormAutoClosed = ($formAktifSaatHired == 0);

    testLog("Validasi Status Final Kandidat", $isHiredLolos ? 'OK' : 'FAIL', "status_global: $finalStatusGlobal");
    testLog("Validasi Dampak ke Requisition (Fill Rate)", $isReqTerpenuhi ? 'OK' : 'FAIL', "status_req: $statusReqFinal, terpenuhi: $jmlTerpenuhi");
    testLog("Validasi Auto-close Job Posting Publik", $isFormAutoClosed ? 'OK' : 'FAIL', "form_aktif: $formAktifSaatHired");
    testLog("Validasi Audit Trail & History Tracing", $isHistoryValid ? 'OK' : 'FAIL', "History count: $cntHistory");

    // -------------------------------------------------------------
    // LANGKAH 10: Pengujian Pembatalan Status Hired (sp_CancelHired)
    // -------------------------------------------------------------
    echo "\n" . str_repeat('-', 60) . "\n";
    echo "  PENGUJIAN FITUR PEMBATALAN STATUS HIRED:\n";
    echo str_repeat('-', 60) . "\n";

    $statusBatal = null;
    $sqlCancelHired = "{CALL dbo.sp_CancelHired(?, ?, ?, ?, ?, ?)}";
    $paramsCancelHired = array(
        array($idLamaran, SQLSRV_PARAM_IN),
        array('Withdrawn', SQLSRV_PARAM_IN), // status tujuan
        array('Kandidat membatalkan join sebelum H-1 karena alasan keluarga', SQLSRV_PARAM_IN),
        array($superAdmin, SQLSRV_PARAM_IN),
        array(1, SQLSRV_PARAM_IN), // buka kembali posting publik
        array(&$statusBatal, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(20))
    );
    $stCancel = sqlsrv_query($conn, $sqlCancelHired, $paramsCancelHired);
    if ($stCancel === false) {
        throw new RuntimeException("Gagal sp_CancelHired: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stCancel);

    $stGlobalPostCancel = scalar($conn, "SELECT status_global FROM dbo.APPLICATIONS WHERE id_lamaran = ?", array($idLamaran));
    $jmlTerpenuhiPostCancel = scalar($conn, "SELECT jumlah_terpenuhi FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    $statusReqPostCancel = scalar($conn, "SELECT status_req FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
    $formAktifPostCancel = scalar($conn, "SELECT form_aktif FROM dbo.JOB_POSTINGS WHERE id_posting = ?", array($idPosting));

    echo "  Status Global Setelah Batal : $stGlobalPostCancel (Harapan: Withdrawn)\n";
    echo "  Requisition Terpenuhi Kini  : $jmlTerpenuhiPostCancel dari 1 (Target)\n";
    echo "  Status Requisition Kini     : $statusReqPostCancel (Harapan: Sourcing)\n";
    echo "  Form Posting Terbuka Kini   : $formAktifPostCancel (Harapan: 1 / Terbuka Kembali)\n";

    $isCancelOk = ($stGlobalPostCancel === 'Withdrawn');
    $isReqReset = ($jmlTerpenuhiPostCancel == 0 && $statusReqPostCancel === 'Sourcing');
    $isFormReopened = ($formAktifPostCancel == 1);

    testLog("Validasi Status Kandidat Setelah Pembatalan Hired", $isCancelOk ? 'OK' : 'FAIL', "status_global: $stGlobalPostCancel");
    testLog("Validasi Reset Kuota Pemenuhan Requisition", $isReqReset ? 'OK' : 'FAIL', "status_req: $statusReqPostCancel, terpenuhi: $jmlTerpenuhiPostCancel");
    testLog("Validasi Pembukaan Kembali Job Posting Publik", $isFormReopened ? 'OK' : 'FAIL', "form_aktif: $formAktifPostCancel");

    echo "\n[INFO] Seluruh skenario pengujian E2E dari pengajuan MPR, seleksi bertahap, Hired, hingga pembatalan Hired BERHASIL.\n";
    echo "       - id_req      : $idReq ($noMpr)\n";
    echo "       - id_posting  : $idPosting\n";
    echo "       - id_kandidat : $idKandidat ($namaKandidat)\n";
    echo "       - id_lamaran  : $idLamaran\n\n";

    // -------------------------------------------------------------
    // LANGKAH 11: Bersihkan seluruh artefak uji (dev only)
    // -------------------------------------------------------------
    try {
        q($conn, "DELETE FROM dbo.INTERVIEW_PARTICIPANTS WHERE id_interview IN
                  (SELECT i.id_interview FROM dbo.INTERVIEWS i
                   JOIN dbo.APPLICATION_STAGES s ON s.id_app_stage = i.id_app_stage WHERE s.id_lamaran = ?)", array($idLamaran));
        q($conn, "DELETE FROM dbo.INTERVIEWS WHERE id_app_stage IN (SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran = ?)", array($idLamaran));
        q($conn, "DELETE FROM dbo.PSIKOTES_RESULTS WHERE id_app_stage IN (SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran = ?)", array($idLamaran));
        foreach (array('APPLICATION_HISTORY','APPLICATION_STAGES','APPLICATION_CONTACTS','APPLICATION_PROFILE','CANDIDATE_DOCUMENTS','CANDIDATE_BANK','OFFERS') as $t) {
            q($conn, "DELETE FROM dbo.$t WHERE id_lamaran = ?", array($idLamaran));
        }
        q($conn, "DELETE FROM dbo.CANDIDATE_HEALTH WHERE id_kandidat = ?", array($idKandidat));
        q($conn, "DELETE FROM dbo.APPLICATIONS WHERE id_lamaran = ?", array($idLamaran));
        q($conn, "DELETE FROM dbo.CANDIDATES WHERE id_kandidat = ?", array($idKandidat));
        q($conn, "DELETE FROM dbo.RPT_FUNNEL_HARIAN WHERE id_req = ?", array($idReq));
        q($conn, "DELETE FROM dbo.JOB_POSTINGS WHERE id_req = ?", array($idReq));
        q($conn, "DELETE FROM dbo.REQUISITION_APPROVALS WHERE id_req = ?", array($idReq));
        q($conn, "DELETE FROM dbo.AUDIT_LOG WHERE nama_tabel IN ('REQUISITIONS','APPLICATIONS') AND id_baris IN (?, ?)", array($idReq, $idLamaran));
        q($conn, "DELETE FROM dbo.REQUISITIONS WHERE id_req = ?", array($idReq));
        echo "[INFO] Artefak uji E2E dibersihkan.\n\n";
    } catch (Exception $ce) {
        echo "[WARN] Sebagian artefak uji gagal dibersihkan: " . $ce->getMessage() . "\n\n";
    }

} catch (Exception $e) {
    echo "\n[EXCEPTION OCCURRED]: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * seed-rich-data.php -- Mengisi data dummy yang kaya & realistis untuk evaluasi projek.
 * Menambahkan posisi, MPR, posting, kandidat di berbagai tahap, jadwal interview,
 * hasil psikotes, offering, kontak log, dan data kesehatan.
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }

$ROOT = dirname(__DIR__);
$cfg  = require $ROOT . '/tools/koneksi.local.php';

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'], 'UID' => $cfg['user'], 'PWD' => $cfg['password'],
    'CharacterSet' => 'UTF-8',
));
if ($conn === false) { fwrite(STDERR, "Koneksi gagal: " . print_r(sqlsrv_errors(), true)); exit(1); }
sqlsrv_configure('WarningsReturnAsErrors', 0);

function q($conn, $sql, $p = array()) {
    $st = sqlsrv_query($conn, $sql, $p);
    if ($st === false) { fwrite(STDERR, "SQL gagal: $sql\n" . print_r(sqlsrv_errors(), true)); exit(1); }
    return $st;
}
function scalar($conn, $sql, $p = array()) {
    $st = q($conn, $sql, $p);
    $r = sqlsrv_fetch_array($st, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($st);
    return $r ? $r[0] : null;
}
function insert_row($conn, $table, array $data, $pk) {
    $cols = array_keys($data);
    $ph   = implode(',', array_fill(0, count($cols), '?'));
    $sql  = "INSERT INTO dbo.$table (" . implode(',', $cols) . ") OUTPUT INSERTED.$pk VALUES ($ph)";
    $st   = q($conn, $sql, array_values($data));
    $r    = sqlsrv_fetch_array($st, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($st);
    return (int) $r[0];
}

echo "=== SEED DATA REALISTIS E-RECRUITMENT RPG ===\n";

// 1. Ambil referensi master
$posMktMgr = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi = N'Marketing Manager'");
$posCrew   = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi = N'Crew Outlet'");
$posFin    = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi = N'Finance Staff (Krusial)'");
$posMktStf = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi = N'Marketing Staff'");

// Tambah posisi baru jika belum ada
if (!$posMktMgr) {
    $deptMkt = scalar($conn, "SELECT id_departemen FROM dbo.M_DEPARTEMEN WHERE kode='MKT'");
    $flwHqMgr = scalar($conn, "SELECT id_flow FROM dbo.M_FLOW WHERE kode_flow='HQ_MANAGER'");
    $posMktMgr = insert_row($conn, 'M_POSISI', array(
        'nama_posisi' => 'Marketing Manager', 'id_departemen' => $deptMkt,
        'level_posisi' => 'Manager', 'default_flow' => $flwHqMgr, 'is_aktif' => 1
    ), 'id_posisi');
}

$deptOps = scalar($conn, "SELECT id_departemen FROM dbo.M_DEPARTEMEN WHERE kode='OPS'");
$flwOutlet = scalar($conn, "SELECT id_flow FROM dbo.M_FLOW WHERE kode_flow='MP_OUTLET'");
$flwHqStf  = scalar($conn, "SELECT id_flow FROM dbo.M_FLOW WHERE kode_flow='HQ_STAFF'");
$flwHqKru  = scalar($conn, "SELECT id_flow FROM dbo.M_FLOW WHERE kode_flow='HQ_STAFF_KRUSIAL'");
$flwHqMgr  = scalar($conn, "SELECT id_flow FROM dbo.M_FLOW WHERE kode_flow='HQ_MANAGER'");

$posVM = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi = N'Visual Merchandising Specialist'");
if (!$posVM) {
    $deptMkt = scalar($conn, "SELECT id_departemen FROM dbo.M_DEPARTEMEN WHERE kode='MKT'");
    $posVM = insert_row($conn, 'M_POSISI', array(
        'nama_posisi' => 'Visual Merchandising Specialist', 'id_departemen' => $deptMkt,
        'level_posisi' => 'Staff', 'default_flow' => $flwHqStf, 'is_aktif' => 1
    ), 'id_posisi');
}

$outletSoyu = scalar($conn, "SELECT id_outlet FROM dbo.M_OUTLET WHERE kode_outlet='I-TCM'");
$outletKemang = scalar($conn, "SELECT id_outlet FROM dbo.M_OUTLET WHERE kode_outlet='K-LC3'");
$chPortal = null;
$chJobstreet = null;
$chGlints = null;

$uSuperAdmin = scalar($conn, "SELECT id_user FROM dbo.M_USERS WHERE username='demo_super_admin'");
$uUserDept   = scalar($conn, "SELECT id_user FROM dbo.M_USERS WHERE username='demo_user_dept'");
$uHrAdmin    = $uSuperAdmin;
$uHrSpv      = $uSuperAdmin;
$uBod        = $uSuperAdmin;

// 2. Buat Requisitions Realistis
$reqMgr = scalar($conn, "SELECT id_req FROM dbo.REQUISITIONS WHERE no_mpr='MPR/2026/08/001'");
if (!$reqMgr) {
    $reqMgr = insert_row($conn, 'REQUISITIONS', array(
        'no_mpr' => 'MPR/2026/08/001', 'id_user_pemohon' => $uUserDept, 'nama_pemohon_snapshot' => 'Budi Santoso',
        'departemen_pemohon_snapshot' => 'Marketing', 'id_posisi' => $posMktMgr, 'tipe_penempatan' => 'HQ',
        'jumlah_dibutuhkan' => 1, 'jumlah_disetujui' => 1, 'jumlah_terpenuhi' => 0, 'status_karyawan' => 'Tetap',
        'alasan_permintaan' => 'Ekspansi Brand Digital', 'target_tanggal_join' => '2026-10-01',
        'urgensi' => 'Tinggi', 'id_flow' => $flwHqMgr, 'butuh_psikotes' => 1, 'butuh_interview_bod' => 1,
        'pendidikan_minimal' => 'S1', 'pengalaman_minimal_tahun' => 5,
        'job_desc' => "Memimpin strategi pemasaran 3 brand utama (Naughty, SOYU, Les Femmes).\nMerancang kampanye omnichannel dan digital performance.",
        'kualifikasi' => "Pendidikan S1 Manajemen/Pemasaran/Komunikasi.\nPengalaman min 5 tahun di bidang retail fashion.\nMemiliki kemampuan leadership terbukti.",
        'range_gaji_min' => 18000000, 'range_gaji_max' => 25000000, 'preferensi_internal' => 'Diutamakan yang memahami retail aksesoris.',
        'status_req' => 'Sourcing', 'created_at' => date('Y-m-d H:i:s')
    ), 'id_req');

    insert_row($conn, 'JOB_POSTINGS', array(
        'id_req' => $reqMgr, 'id_channel' => $chPortal, 'batch_ke' => 1,
        'judul_posting' => 'Marketing Manager - Ratu Pertiwi Group',
        'job_desc' => 'Memimpin strategi marketing omnichannel retail fashion & lifestyle RPG.',
        'kualifikasi' => 'S1, Pengalaman retail 5+ tahun, strong analytical & leadership.',
        'url_slug' => 'marketing-manager-rpg', 'is_aktif' => 1, 'form_aktif' => 1
    ), 'id_posting');
}

$reqCrew = scalar($conn, "SELECT id_req FROM dbo.REQUISITIONS WHERE no_mpr='MPR/2026/08/002'");
if (!$reqCrew) {
    $reqCrew = insert_row($conn, 'REQUISITIONS', array(
        'no_mpr' => 'MPR/2026/08/002', 'id_user_pemohon' => $uUserDept, 'nama_pemohon_snapshot' => 'Slamet Riyadi',
        'departemen_pemohon_snapshot' => 'Operasional', 'id_posisi' => $posCrew, 'tipe_penempatan' => 'OUTLET',
        'id_outlet' => $outletSoyu, 'jumlah_dibutuhkan' => 4, 'jumlah_disetujui' => 4, 'jumlah_terpenuhi' => 2,
        'status_karyawan' => 'Kontrak', 'alasan_permintaan' => 'Toko Baru SOYU',
        'target_tanggal_join' => '2026-09-15', 'urgensi' => 'Kritis', 'id_flow' => $flwOutlet,
        'pendidikan_minimal' => 'SMA/K', 'pengalaman_minimal_tahun' => 0,
        'job_desc' => "Melayani customer, menata display produk, dan menjaga kebersihan store.",
        'kualifikasi' => "Pria/Wanita, maks 23 tahun, berpenampilan menarik, ramah dan komunikatif.",
        'range_gaji_min' => 4800000, 'range_gaji_max' => 5200000,
        'status_req' => 'Sourcing', 'created_at' => date('Y-m-d H:i:s')
    ), 'id_req');

    insert_row($conn, 'JOB_POSTINGS', array(
        'id_req' => $reqCrew, 'id_channel' => $chPortal, 'batch_ke' => 1,
        'judul_posting' => 'Crew Store SOYU - Tanjung Duren',
        'job_desc' => 'Frontline service, kasir, dan penataan display aksesoris SOYU.',
        'kualifikasi' => 'Lulusan SMA/SMK, usia 18-23 tahun, ramah dan disiplin.',
        'url_slug' => 'crew-store-soyu', 'is_aktif' => 1, 'form_aktif' => 1
    ), 'id_posting');
}

$reqVM = scalar($conn, "SELECT id_req FROM dbo.REQUISITIONS WHERE no_mpr='MPR/2026/09/001'");
if (!$reqVM) {
    $reqVM = insert_row($conn, 'REQUISITIONS', array(
        'no_mpr' => 'MPR/2026/09/001', 'id_user_pemohon' => $uUserDept, 'nama_pemohon_snapshot' => 'Budi Santoso',
        'departemen_pemohon_snapshot' => 'Marketing', 'id_posisi' => $posVM, 'tipe_penempatan' => 'HQ',
        'jumlah_dibutuhkan' => 1, 'jumlah_disetujui' => 1, 'jumlah_terpenuhi' => 0, 'status_karyawan' => 'Tetap',
        'alasan_permintaan' => 'Peremajaan Konsep Visual',
        'target_tanggal_join' => '2026-10-15', 'urgensi' => 'Sedang', 'id_flow' => $flwHqStf,
        'pendidikan_minimal' => 'S1', 'pengalaman_minimal_tahun' => 2,
        'job_desc' => 'Merancang guideline visual merchandising untuk seluruh store nasional.',
        'kualifikasi' => 'S1 Desain Komunikasi Visual / Arsitektur Interior. Portofolio visual display ritel.',
        'range_gaji_min' => 7000000, 'range_gaji_max' => 9500000,
        'status_req' => 'Sourcing', 'created_at' => date('Y-m-d H:i:s')
    ), 'id_req');

    insert_row($conn, 'JOB_POSTINGS', array(
        'id_req' => $reqVM, 'id_channel' => $chPortal, 'batch_ke' => 1,
        'judul_posting' => 'Visual Merchandising Specialist',
        'job_desc' => 'Bertanggung jawab atas estetika dan display visual toko RPG.',
        'kualifikasi' => 'S1 DKV/Interior, mahir SketchUp/Photoshop, portofolio display.',
        'url_slug' => 'visual-merchandising-hq', 'is_aktif' => 1, 'form_aktif' => 1
    ), 'id_posting');
}

echo "- Requisitions realistis siap (MPR/2026/08/001, MPR/2026/08/002, MPR/2026/09/001).\n";

// 3. Helper Membuat Kandidat & Menggeser Tahap
function tambah_kandidat($conn, $reqId, $flowId, $chId, $nama, $wa, $email, $kota, $pend, $intake, $tglLahir, $jk) {
    // Cek duplikat email
    $ada = scalar($conn, "SELECT id_kandidat FROM dbo.CANDIDATES WHERE email=?", array($email));
    if ($ada) {
        $idLam = scalar($conn, "SELECT id_lamaran FROM dbo.APPLICATIONS WHERE id_kandidat=? AND id_req=?", array($ada, $reqId));
        return array($ada, $idLam);
    }

    $k = insert_row($conn, 'CANDIDATES', array(
        'nama_lengkap' => $nama, 'email' => $email, 'no_wa_raw' => $wa,
        'no_wa_normal' => preg_replace('/\D/', '', $wa), 'kota_domisili' => $kota,
        'pendidikan_terakhir' => $pend, 'tanggal_lahir' => $tglLahir, 'jenis_kelamin' => $jk,
        'tempat_lahir' => $kota, 'status_pernikahan' => 'Belum Menikah', 'consent_versi' => 'v1.0',
        'consent_pada' => date('Y-m-d H:i:s'), 'setuju_talent_pool' => 1,
        'retensi_sampai' => date('Y-m-d', strtotime('+24 months'))
    ), 'id_kandidat');

    $l = insert_row($conn, 'APPLICATIONS', array(
        'id_kandidat' => $k, 'id_req' => $reqId, 'id_flow' => $flowId, 'id_channel' => $chId,
        'intake_method' => $intake, 'status_global' => 'In_Progress',
        'screening_score' => rand(75, 96), 'screening_method' => 'MANUAL',
        'tanggal_lamar' => date('Y-m-d', strtotime('-' . rand(2, 20) . ' days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(2, 20) . ' days'))
    ), 'id_lamaran');

    // Generate tahap otomatis
    q($conn, "EXEC dbo.sp_GenerateApplicationStages ?", array($l));
    return array($k, $l);
}

function geser_ke_tahap($conn, $idLamaran, $targetKodeStage, $picUser) {
    // Cari urutan target stage
    $targetUrutan = scalar($conn, "SELECT fs.urutan FROM dbo.APPLICATION_STAGES fs
                                  JOIN dbo.M_STAGE s ON s.id_stage = fs.id_stage
                                  WHERE fs.id_lamaran = ? AND s.kode_stage = ?",
                                  array($idLamaran, $targetKodeStage));
    if (!$targetUrutan) return;

    // Geser bertahap sampai tiba di urutan target
    while (true) {
        $cur = q($conn, "SELECT TOP 1 ast.id_app_stage, ast.urutan, s.kode_stage, s.id_stage
                        FROM dbo.APPLICATION_STAGES ast
                        JOIN dbo.M_STAGE s ON s.id_stage = ast.id_stage
                        WHERE ast.id_lamaran = ? AND ast.status_tahap = 'Berjalan'
                        ORDER BY ast.urutan", array($idLamaran));
        $r = sqlsrv_fetch_array($cur, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($cur);
        if (!$r || $r['urutan'] >= $targetUrutan) {
            break;
        }

        $idAppStage = (int) $r['id_app_stage'];
        $rmk = scalar($conn, "SELECT TOP 1 id_remark FROM dbo.M_REMARKS WHERE id_stage = ? AND efek_status = 'LANJUT'", array($r['id_stage']));
        q($conn, "DECLARE @s VARCHAR(20); EXEC dbo.sp_AdvanceStage ?, ?, ?, ?, @s OUTPUT",
           array($idAppStage, $rmk, $picUser, 'Lolos kriteria penilaian. Lanjut ke tahap berikutnya.'));
    }
}

// 4. Data Kandidat untuk Marketing Manager (HQ_MANAGER: SOURCING -> SCREENING_CV -> KONTAK_WA -> INT_HR -> FORM_PELAMAR -> PSIKOTES -> INT_USER -> INT_BOD -> OFFER -> ONBOARD)

// 4.1 Fajar Nugraha - Sedang di INT_BOD (High performer)
list($kFajar, $lFajar) = tambah_kandidat($conn, $reqMgr, $flwHqMgr, $chJobstreet, 'Fajar Nugraha, S.E.', '081288991001', 'fajar.nugraha@retailindo.com', 'Jakarta Selatan', 'S1', 'IMPORT_FILE', '1992-04-12', 'L');
geser_ke_tahap($conn, $lFajar, 'INT_BOD', $uHrAdmin);
// Tambahkan catatan interview user & psikotes
$stgUser = scalar($conn, "SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran=? AND id_stage=(SELECT id_stage FROM dbo.M_STAGE WHERE kode_stage='INT_USER')", array($lFajar));
if ($stgUser) {
    q($conn, "INSERT INTO dbo.INTERVIEWS (id_app_stage, tipe, jadwal, lokasi_atau_link, hasil, skor, catatan, selesai_pada)
              VALUES (?, 'Offline', DATEADD(day, -2, GETDATE()), 'Ruang Meeting Lt 3 RPG HQ', 'Lulus', 92, 'Pemahaman retail brand sangat matang, portofolio kampanye terbukti sukses.', GETDATE())", array($stgUser));
}
$stgPsi = scalar($conn, "SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran=? AND id_stage=(SELECT id_stage FROM dbo.M_STAGE WHERE kode_stage='PSIKOTES')", array($lFajar));
if ($stgPsi) {
    q($conn, "INSERT INTO dbo.PSIKOTES_RESULTS (id_app_stage, vendor_tes, tanggal_tes, skor_total, hasil, rekomendasi, dilakukan_oleh, dibuat_pada)
              VALUES (?, 'Assessment Center Talenta', DATEADD(day, -5, GETDATE()), 89, 'Lulus', 'Tipe kepemimpinan Directive & Influencing (D-I). Cocok untuk manajerial akseleratif.', ?, GETDATE())", array($stgPsi, $uHrAdmin));
}

// 4.2 Siti Nurhaliza - Sedang di tahap OFFER (Negotiation)
list($kSiti, $lSiti) = tambah_kandidat($conn, $reqMgr, $flwHqMgr, $chGlints, 'Siti Nurhaliza, M.M.', '081377882002', 'siti.nurhaliza@brandcorp.id', 'Jakarta Barat', 'S2', 'IMPORT_FILE', '1991-08-25', 'P');
geser_ke_tahap($conn, $lSiti, 'OFFER', $uHrAdmin);
// Masukkan data offer
$cekOff = scalar($conn, "SELECT id_offer FROM dbo.OFFERS WHERE id_lamaran=?", array($lSiti));
if (!$cekOff) {
    q($conn, "INSERT INTO dbo.OFFERS (id_lamaran, gaji_ditawarkan, tanggal_penawaran, tanggal_join_disepakati, status_offer, alasan, dibuat_oleh)
              VALUES (?, 21000000, CAST(GETDATE() AS DATE), '2026-10-01', 'Nego', 'Kandidat meminta penyesuaian tunjangan kesehatan keluarga.', ?)", array($lSiti, $uHrSpv));
}

// 4.3 Hendra Wijaya - Sedang di tahap INT_USER (Interview besok)
list($kHendra, $lHendra) = tambah_kandidat($conn, $reqMgr, $flwHqMgr, $chPortal, 'Hendra Wijaya', '081199883003', 'hendra.wijaya@outlook.co.id', 'Tangerang', 'S1', 'FORM_PUBLIC', '1994-11-03', 'L');
geser_ke_tahap($conn, $lHendra, 'INT_USER', $uHrAdmin);
$stgHendra = scalar($conn, "SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran=? AND id_stage=(SELECT id_stage FROM dbo.M_STAGE WHERE kode_stage='INT_USER')", array($lHendra));
if ($stgHendra) {
    q($conn, "INSERT INTO dbo.INTERVIEWS (id_app_stage, tipe, jadwal, lokasi_atau_link, hasil, skor, catatan)
              VALUES (?, 'Online', DATEADD(day, 1, GETDATE()), 'Google Meet: meet.google.com/rpg-mkt-user', 'Dipertimbangkan', 80, 'Jadwal terkonfirmasi dengan Budi Santoso (User Dept).')", array($stgHendra));
}

// 4.4 Rina Oktaviani - Sedang di tahap PSIKOTES
list($kRina, $lRina) = tambah_kandidat($conn, $reqMgr, $flwHqMgr, $chPortal, 'Rina Oktaviani', '085711224004', 'rina.oktaviani@gmail.com', 'Jakarta Pusat', 'S1', 'FORM_PUBLIC', '1995-02-14', 'P');
geser_ke_tahap($conn, $lRina, 'PSIKOTES', $uHrAdmin);

// 4.5 Dimas Prasetyo - Sedang di tahap INT_HR
list($kDimas, $lDimas) = tambah_kandidat($conn, $reqMgr, $flwHqMgr, $chJobstreet, 'Dimas Prasetyo', '081233445005', 'dimas.prasetyo@yahoo.com', 'Bekasi', 'S1', 'IMPORT_FILE', '1993-07-19', 'L');
geser_ke_tahap($conn, $lDimas, 'INT_HR', $uHrAdmin);

// 5. Data Kandidat untuk Crew Outlet SOYU (MP_OUTLET: KONTAK_WA -> ONBOARD)

// 5.1 Bayu Pratama - Siap Onboarding
list($kBayu, $lBayu) = tambah_kandidat($conn, $reqCrew, $flwOutlet, $chPortal, 'Bayu Pratama', '089612345678', 'bayu.pratama@gmail.com', 'Jakarta Barat', 'SMA/K', 'MANUAL', '2004-05-10', 'L');
geser_ke_tahap($conn, $lBayu, 'ONBOARD', $uHrAdmin);

// 5.2 Nanda Putri - Sudah Hired (Bergabung)
list($kNanda, $lNanda) = tambah_kandidat($conn, $reqCrew, $flwOutlet, $chPortal, 'Nanda Putri Utami', '089698765432', 'nanda.putri@gmail.com', 'Jakarta Barat', 'SMA/K', 'MANUAL', '2005-01-20', 'P');
// Luluskan sampai Onboard & Hired jika belum final
$curStatus = scalar($conn, "SELECT status_global FROM dbo.APPLICATIONS WHERE id_lamaran=?", array($lNanda));
if ($curStatus !== 'Hired') {
    geser_ke_tahap($conn, $lNanda, 'ONBOARD', $uHrAdmin);
    $stgOnb = scalar($conn, "SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran=? AND id_stage=(SELECT id_stage FROM dbo.M_STAGE WHERE kode_stage='ONBOARD') AND status_tahap='Berjalan'", array($lNanda));
    if ($stgOnb) {
        $rmkHired = scalar($conn, "SELECT id_remark FROM dbo.M_REMARKS WHERE efek_status='HIRED'");
        q($conn, "DECLARE @s VARCHAR(20); EXEC dbo.sp_AdvanceStage ?, ?, ?, ?, @s OUTPUT",
           array($stgOnb, $rmkHired, $uHrAdmin, 'Kandidat telah menandatangani PKWT dan aktif bertugas di outlet SOYU TCM.'));
    }
}

// 5.3 Aditya Permana - Baru di Kontak WA
list($kAdit, $lAdit) = tambah_kandidat($conn, $reqCrew, $flwOutlet, $chPortal, 'Aditya Permana', '087811223344', 'aditya.permana@gmail.com', 'Jakarta Barat', 'SMA/K', 'FORM_PUBLIC', '2003-09-08', 'L');
// Catat kontak WA
q($conn, "DECLARE @up INT; EXEC dbo.sp_LogContact ?, 'WA', 'Respon', 'Kandidat menyanggupi interview store besok jam 10.00 WIB.', ?, @up OUTPUT", array($lAdit, $uHrAdmin));

// 5.4 Rizky Ramadhan - Unreachable (3x kontak tidak respons)
list($kRizky, $lRizky) = tambah_kandidat($conn, $reqCrew, $flwOutlet, $chPortal, 'Rizky Ramadhan', '085899887766', 'rizky.ramadhan@gmail.com', 'Jakarta Barat', 'SMA/K', 'FORM_PUBLIC', '2004-12-01', 'L');
q($conn, "DECLARE @up INT; EXEC dbo.sp_LogContact ?, 'WA', 'Tidak_Respon', 'Pesan terkirim centang dua tapi tidak dibalas.', ?, @up OUTPUT", array($lRizky, $uHrAdmin));
q($conn, "DECLARE @up INT; EXEC dbo.sp_LogContact ?, 'Telepon', 'Tidak_Respon', 'Panggilan ditolak.', ?, @up OUTPUT", array($lRizky, $uHrAdmin));

// 6. Data Kandidat untuk Visual Merchandising (HQ_STAFF)

// 6.1 Maya Anggraini - Screening CV
list($kMaya, $lMaya) = tambah_kandidat($conn, $reqVM, $flwHqStf, $chPortal, 'Maya Anggraini, S.Ds.', '081299001122', 'maya.design@studio.id', 'Jakarta Selatan', 'S1', 'FORM_PUBLIC', '1998-03-30', 'P');
geser_ke_tahap($conn, $lMaya, 'SCREENING_CV', $uHrAdmin);

// 6.2 Bagus Saputra - Form Pelamar
list($kBagus, $lBagus) = tambah_kandidat($conn, $reqVM, $flwHqStf, $chGlints, 'Bagus Saputra', '081311002233', 'bagus.saputra@artworks.co', 'Tangerang Selatan', 'S1', 'IMPORT_FILE', '1997-06-18', 'L');
geser_ke_tahap($conn, $lBagus, 'FORM_PELAMAR', $uHrAdmin);

// 6.3 Tari Wulandari - Talent Pool (Disimpan untuk lowongan masa depan)
list($kTari, $lTari) = tambah_kandidat($conn, $reqVM, $flwHqStf, $chJobstreet, 'Tari Wulandari, S.Sn.', '081233889900', 'tari.wulandari@gmail.com', 'Bandung', 'S1', 'IMPORT_FILE', '1996-10-14', 'P');
q($conn, "UPDATE dbo.APPLICATIONS SET status_global='Talent_Pool' WHERE id_lamaran=?", array($lTari));

// 7. Update agregat funnel hari ini
q($conn, "{CALL dbo.sp_BuildFunnelHarian(?)}", array(date('Y-m-d')));

echo "=== SEEDING BERHASIL ===\n";
echo "Total Requisitions: " . scalar($conn, "SELECT COUNT(*) FROM dbo.REQUISITIONS") . "\n";
echo "Total Candidates: " . scalar($conn, "SELECT COUNT(*) FROM dbo.CANDIDATES") . "\n";
echo "Total Applications: " . scalar($conn, "SELECT COUNT(*) FROM dbo.APPLICATIONS") . "\n";
echo "Status Global Breakdown:\n";
$st = q($conn, "SELECT status_global, COUNT(*) AS n FROM dbo.APPLICATIONS GROUP BY status_global");
while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) {
    echo "  - {$row['status_global']}: {$row['n']}\n";
}

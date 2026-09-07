<?php
/**
 * seed-demo.php -- data contoh end-to-end untuk testing lokal (DEV SAJA).
 *
 * Membuat: 6 user (satu per peran), 2 requisition (1 Sourcing + posting aktif,
 * 1 Menunggu_BOD), 4 kandidat + lamaran di berbagai tahap (1 ditolak -> retensi
 * kena), dokumen contoh (CV/KTP/Rekening) + file fisik dummy, 1 data kesehatan.
 *
 *   php tools/seed-demo.php            -> isi data demo (aman diulang)
 *   php tools/seed-demo.php --reset    -> hapus semua data demo
 *
 * JANGAN jalankan di produksi. Artefak ditandai:
 *   user     username LIKE 'demo\_%'
 *   kandidat email LIKE '%@demo.local'
 *   MPR      nama_pemohon_snapshot LIKE 'DEMO %'
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }

$ROOT  = dirname(__DIR__);
$cfg   = require $ROOT . '/tools/koneksi.local.php';
$reset = in_array('--reset', $argv, true);

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
/** INSERT satu baris, kembalikan nilai PK lewat OUTPUT INSERTED. */
function insert_row($conn, $table, array $data, $pk) {
    $cols = array_keys($data);
    $ph   = implode(',', array_fill(0, count($cols), '?'));
    $sql  = "INSERT INTO dbo.$table (" . implode(',', $cols) . ") OUTPUT INSERTED.$pk VALUES ($ph)";
    $st   = q($conn, $sql, array_values($data));
    $r    = sqlsrv_fetch_array($st, SQLSRV_FETCH_NUMERIC);
    sqlsrv_free_stmt($st);
    return (int) $r[0];
}

$storage = rtrim($cfg['storage'], "/\\");

/* ----------------------------------------------------------------- reset -- */
if ($reset) {
    $L = array();
    $st = q($conn, "SELECT a.id_lamaran FROM dbo.APPLICATIONS a
                    JOIN dbo.CANDIDATES c ON c.id_kandidat=a.id_kandidat
                    WHERE c.email LIKE '%@demo.local'");
    while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_NUMERIC)) { $L[] = (int) $row[0]; }
    sqlsrv_free_stmt($st);

    foreach ($L as $l) {
        q($conn, "DELETE FROM dbo.INTERVIEW_PARTICIPANTS WHERE id_interview IN
                  (SELECT i.id_interview FROM dbo.INTERVIEWS i
                   JOIN dbo.APPLICATION_STAGES s ON s.id_app_stage=i.id_app_stage WHERE s.id_lamaran=?)", array($l));
        q($conn, "DELETE FROM dbo.INTERVIEWS WHERE id_app_stage IN
                  (SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran=?)", array($l));
        q($conn, "DELETE FROM dbo.PSIKOTES_RESULTS WHERE id_app_stage IN
                  (SELECT id_app_stage FROM dbo.APPLICATION_STAGES WHERE id_lamaran=?)", array($l));
        foreach (array('APPLICATION_HISTORY','APPLICATION_STAGES','APPLICATION_CONTACTS','APPLICATION_PROFILE',
                       'CANDIDATE_DOCUMENTS','CANDIDATE_BANK','OFFERS') as $t) {
            q($conn, "DELETE FROM dbo.$t WHERE id_lamaran = ?", array($l));
        }
    }
    q($conn, "DELETE FROM dbo.CANDIDATE_HEALTH WHERE id_kandidat IN
              (SELECT id_kandidat FROM dbo.CANDIDATES WHERE email LIKE '%@demo.local')");
    q($conn, "DELETE FROM dbo.APPLICATIONS WHERE id_kandidat IN
              (SELECT id_kandidat FROM dbo.CANDIDATES WHERE email LIKE '%@demo.local')");
    q($conn, "DELETE FROM dbo.CANDIDATES WHERE email LIKE '%@demo.local'");
    q($conn, "DELETE jp FROM dbo.JOB_POSTINGS jp JOIN dbo.REQUISITIONS r ON r.id_req=jp.id_req
              WHERE r.nama_pemohon_snapshot LIKE 'DEMO %'");
    q($conn, "DELETE FROM dbo.REQUISITION_APPROVALS WHERE id_req IN
              (SELECT id_req FROM dbo.REQUISITIONS WHERE nama_pemohon_snapshot LIKE 'DEMO %')");
    q($conn, "DELETE FROM dbo.REQUISITIONS WHERE nama_pemohon_snapshot LIKE 'DEMO %'");
    q($conn, "DELETE als FROM dbo.ACCESS_LOG_SENSITIF als JOIN dbo.M_USERS u ON u.id_user=als.id_user
              WHERE u.username LIKE 'demo\\_%' ESCAPE '\\'");
    q($conn, "DELETE FROM dbo.AUDIT_LOG WHERE oleh_user IN
              (SELECT id_user FROM dbo.M_USERS WHERE username LIKE 'demo\\_%' ESCAPE '\\')");
    q($conn, "DELETE FROM dbo.M_USERS WHERE username LIKE 'demo\\_%' ESCAPE '\\'");
    if (is_dir("$storage/lamaran")) {
        foreach (glob("$storage/lamaran/*") as $d) {
            if (is_dir($d) && file_exists("$d/.demo")) {
                array_map('unlink', glob("$d/*") ?: array());
                @unlink("$d/.demo"); @rmdir($d);
            }
        }
    }
    echo "Data demo dihapus.\n";
    exit(0);
}

/* ------------------------------------------------------------ organisasi -- */
foreach (preg_split('/^\s*GO\s*$/mi', file_get_contents($ROOT . '/database/seed/dev_organisasi.sql')) as $b) {
    if (trim($b) !== '') { q($conn, $b); }
}
echo "- organisasi (dev_organisasi.sql) OK\n";

/* ------------------------------------------------------------------ user -- */
// Sistem aktif hanya menggunakan SUPER_ADMIN dan USER_DEPT
$roles = array('SUPER_ADMIN', 'USER_DEPT');
// USER_DEPT di-scope ke Marketing (posisi demo = Marketing Staff) untuk uji G4b
$deptMkt = scalar($conn, "SELECT id_departemen FROM dbo.M_DEPARTEMEN WHERE kode = 'MKT'");
$uid = array();
foreach ($roles as $r) {
    $u = 'demo_' . strtolower($r);
    $row = array(
        'username' => $u, 'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
        'nama_snapshot' => ($r === 'SUPER_ADMIN') ? 'Super Administrator Demo' : 'Demo ' . $r,
        'is_aktif' => 1,
        'id_role' => scalar($conn, "SELECT id_role FROM dbo.M_ROLES WHERE kode_role=?", array($r)),
        'id_departemen' => ($r === 'USER_DEPT') ? $deptMkt : NULL,
    );
    $id = scalar($conn, "SELECT id_user FROM dbo.M_USERS WHERE username=?", array($u));
    if ($id) {
        q($conn, "UPDATE dbo.M_USERS SET id_departemen = ?, is_aktif = 1 WHERE id_user = ?",
          array($row['id_departemen'], (int) $id));
    } else {
        $id = insert_row($conn, 'M_USERS', $row, 'id_user');
    }
    $uid[$r] = (int) $id;
}
echo "- 2 user demo aktif (demo_super_admin, demo_user_dept)\n";

if (scalar($conn, "SELECT COUNT(*) FROM dbo.CANDIDATES WHERE email LIKE '%@demo.local'") > 0) {
    echo "- data demo sudah ada. 'php tools/seed-demo.php --reset' dulu kalau mau ulang.\n";
    tampilkan_ringkasan($conn, $roles);
    exit(0);
}

/* --------------------------------------------------------------- master -- */
$posMkt  = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi=N'Marketing Staff'");
$posCrew = scalar($conn, "SELECT id_posisi FROM dbo.M_POSISI WHERE nama_posisi=N'Crew Outlet'");
$flowMkt = scalar($conn, "SELECT default_flow FROM dbo.M_POSISI WHERE id_posisi=?", array($posMkt));
$flowCrew= scalar($conn, "SELECT default_flow FROM dbo.M_POSISI WHERE id_posisi=?", array($posCrew));
$outlet  = scalar($conn, "SELECT TOP 1 id_outlet FROM dbo.M_OUTLET ORDER BY id_outlet");
$chPortal= null;

/* ------------------------------------------------------------------- MPR -- */
$reqA = insert_row($conn, 'REQUISITIONS', array(
    'id_user_pemohon' => $uid['USER_DEPT'], 'nama_pemohon_snapshot' => 'DEMO Marketing',
    'id_posisi' => $posMkt, 'tipe_penempatan' => 'HQ', 'jumlah_dibutuhkan' => 2,
    'id_flow' => $flowMkt, 'status_req' => 'Sourcing', 'jumlah_disetujui' => 2,
), 'id_req');
insert_row($conn, 'JOB_POSTINGS', array(
    'id_req' => $reqA, 'id_channel' => $chPortal, 'batch_ke' => 1,
    'judul_posting' => 'Lowongan Marketing Staff (DEMO)', 'kualifikasi' => 'S1, pengalaman 1 tahun',
    'url_slug' => 'marketing-staff-demo', 'is_aktif' => 1, 'form_aktif' => 1,
), 'id_posting');

$reqB = insert_row($conn, 'REQUISITIONS', array(
    'id_user_pemohon' => $uid['USER_DEPT'], 'nama_pemohon_snapshot' => 'DEMO Outlet',
    'id_posisi' => $posCrew, 'tipe_penempatan' => 'OUTLET', 'id_outlet' => $outlet,
    'jumlah_dibutuhkan' => 3, 'id_flow' => $flowCrew, 'status_req' => 'Menunggu_BOD',
    'no_mpr' => 'MPR/2026/09/900',
), 'id_req');
echo "- 2 MPR: #$reqA Sourcing (+posting aktif), #$reqB Menunggu_BOD\n";

/* --------------------------------------------------- kandidat + lamaran -- */
function buat_lamaran($conn, $reqA, $flow, $ch, $nama, $wa, $email, $intake) {
    $k = insert_row($conn, 'CANDIDATES', array(
        'nama_lengkap' => $nama, 'email' => $email, 'no_wa_raw' => $wa,
        'no_wa_normal' => preg_replace('/\D/', '', $wa), 'kota_domisili' => 'Jakarta',
        'pendidikan_terakhir' => 'S1',
    ), 'id_kandidat');
    $l = insert_row($conn, 'APPLICATIONS', array(
        'id_kandidat' => $k, 'id_req' => $reqA, 'id_flow' => $flow, 'id_channel' => $ch,
        'intake_method' => $intake, 'status_global' => 'In_Progress',
    ), 'id_lamaran');
    q($conn, "EXEC dbo.sp_GenerateApplicationStages ?", array($l));
    return array($k, $l);
}
/** Proses tahap 'Berjalan' paling awal. remark NULL = lanjut apa adanya. */
function maju($conn, $l, $pic, $efek /* LANJUT|TOLAK|null */, $catatan) {
    $row = q($conn, "SELECT TOP 1 ast.id_app_stage, ast.id_stage
                     FROM dbo.APPLICATION_STAGES ast
                     WHERE ast.id_lamaran=? AND ast.status_tahap='Berjalan' ORDER BY ast.urutan", array($l));
    $as = sqlsrv_fetch_array($row, SQLSRV_FETCH_NUMERIC); sqlsrv_free_stmt($row);
    if (!$as) { return; }
    $rmk = null;
    if ($efek) {
        $rmk = scalar($conn, "SELECT TOP 1 id_remark FROM dbo.M_REMARKS WHERE id_stage=? AND efek_status=?",
                      array($as[1], $efek));
    }
    q($conn, "DECLARE @s VARCHAR(20); EXEC dbo.sp_AdvanceStage ?,?,?,?,@s OUTPUT",
       array((int) $as[0], $rmk, $pic, $catatan));
}

list($k1, $l1) = buat_lamaran($conn, $reqA, $flowMkt, $chPortal, 'Andi Pratama',   '081200000001', 'andi@demo.local',  'FORM_PUBLIC');
list($k2, $l2) = buat_lamaran($conn, $reqA, $flowMkt, $chPortal, 'Bunga Lestari',  '081200000002', 'bunga@demo.local', 'MANUAL');
list($k3, $l3) = buat_lamaran($conn, $reqA, $flowMkt, $chPortal, 'Cahyo Nugroho',  '081200000003', 'cahyo@demo.local', 'IMPORT_FILE');
list($k4, $l4) = buat_lamaran($conn, $reqA, $flowMkt, $chPortal, 'Dewi Anggraini', '081200000004', 'dewi@demo.local',  'FORM_PUBLIC');

// Andi: biarkan di tahap awal (SOURCING/SCREENING).
// Bunga: lewati sourcing + lolos screening -> masuk KONTAK_WA.
maju($conn, $l2, $uid['HR_ADMIN'], null,    'Sourcing selesai (demo)');
maju($conn, $l2, $uid['HR_ADMIN'], 'LANJUT','Lolos screening CV (demo)');
// Cahyo: lewati sourcing, lalu DITOLAK di screening -> status Rejected -> sp_SetRetensi.
maju($conn, $l3, $uid['HR_ADMIN'], null,    'Sourcing selesai (demo)');
maju($conn, $l3, $uid['HR_ADMIN'], 'TOLAK', 'Kualifikasi tidak sesuai (demo)');

// Dewi: data kesehatan + profil gaji + rekening.
insert_row($conn, 'CANDIDATE_HEALTH', array(
    'id_kandidat' => $k4, 'riwayat_penyakit' => 'Asma ringan', 'consent_khusus' => 1, 'consent_pada' => date('Y-m-d H:i:s'),
), 'id_kandidat');
insert_row($conn, 'APPLICATION_PROFILE', array(
    'id_lamaran' => $l4, 'perusahaan_terakhir' => 'PT Contoh Lama', 'jabatan_terakhir' => 'Marketing Officer',
    'gaji_terakhir' => 6500000, 'gaji_diharapkan' => 8000000, 'diisi_pada' => date('Y-m-d H:i:s'),
), 'id_lamaran');
insert_row($conn, 'CANDIDATE_BANK', array(
    'id_lamaran' => $l4, 'nama_bank' => 'BCA', 'no_rekening' => '1234567890', 'nama_pemilik' => 'Dewi Anggraini',
), 'id_bank');

/* --------------------------------------------------- dokumen + file fisik -- */
function taruh_dok($conn, $storage, $l, $namaDok, $prefix) {
    $idDok = scalar($conn, "SELECT id_dokumen FROM dbo.M_DOKUMEN WHERE nama_dokumen=?", array($namaDok));
    $dir = "$storage/lamaran/$l";
    if (!is_dir($dir)) { mkdir($dir, 0770, true); }
    if (!file_exists("$dir/.demo")) { file_put_contents("$dir/.demo", ''); }
    $body = "%PDF-1.4\n% DEMO $namaDok lamaran $l\n";
    $hash = hash('sha256', $body);
    $path = "$dir/{$prefix}_{$hash}.pdf";
    file_put_contents($path, $body);
    insert_row($conn, 'CANDIDATE_DOCUMENTS', array(
        'id_lamaran' => $l, 'id_dokumen' => $idDok, 'path_file' => $path,
        'nama_file_asli' => strtolower($prefix) . '.pdf', 'hash_sha256' => $hash,
        'ukuran_byte' => strlen($body), 'mime_type' => 'application/pdf',
    ), 'id_cand_doc');
}
taruh_dok($conn, $storage, $l1, 'CV', 'CV');
taruh_dok($conn, $storage, $l2, 'CV', 'CV');
taruh_dok($conn, $storage, $l2, 'KTP', 'KTP');       // IDENTITAS -> uji gate_sensitif
taruh_dok($conn, $storage, $l4, 'CV', 'CV');
taruh_dok($conn, $storage, $l4, 'Rekening', 'REK');  // FINANSIAL -> uji gate_sensitif
echo "- 4 kandidat: Andi (tahap awal), Bunga (lolos->kontak), Cahyo (DITOLAK), Dewi (kesehatan+gaji+rekening)\n";
echo "- 5 dokumen contoh + file fisik dummy\n";

tampilkan_ringkasan($conn, $roles);

/* ------------------------------------------------------------- ringkasan -- */
function tampilkan_ringkasan($conn, $roles) {
    $reqA = scalar($conn, "SELECT id_req FROM dbo.REQUISITIONS WHERE nama_pemohon_snapshot='DEMO Marketing'");
    $ret  = scalar($conn, "SELECT CONVERT(varchar(10), retensi_sampai, 23) FROM dbo.CANDIDATES WHERE email='cahyo@demo.local'");
    $stat = scalar($conn, "SELECT a.status_global FROM dbo.APPLICATIONS a JOIN dbo.CANDIDATES c ON c.id_kandidat=a.id_kandidat WHERE c.email='cahyo@demo.local'");
    echo "\n=== SIAP TESTING LOKAL ===\n";
    echo "1) cd web && php -S 127.0.0.1:8080 -t .\n";
    echo "2) http://127.0.0.1:8080/index.php/auth/login\n\n";
    echo "Login (password semua: demo123):\n";
    foreach ($roles as $r) { echo "   demo_" . str_pad(strtolower($r), 12) . " ($r)\n"; }
    echo "\nCoba per peran:\n";
    echo "   /index.php/dashboard            demo_hr_admin  -> metrik + funnel (1 Rejected)\n";
    echo "   /index.php/requisitions         demo_user_dept -> daftar MPR\n";
    echo "   /index.php/pipeline/index/$reqA       demo_hr_admin  -> papan pipeline\n";
    echo "   /index.php/documents            demo_hr_admin  -> 'buka' KTP = boleh+tercatat, 'buka' Rekening = 403\n";
    echo "                                  demo_hr_spv    -> dua-duanya boleh + tercatat di ACCESS_LOG_SENSITIF\n";
    echo "   /index.php/export/candidates    demo_hr_admin vs demo_hr_spv -> jumlah kolom beda\n";
    echo "   /lamar/marketing-staff-demo     (tanpa login) form lamaran publik\n";
    echo "\nCahyo Nugroho: status=$stat  retensi_sampai=" . ($ret ?: '(NULL - cek hook!)') . "  (harusnya hari ini +12 bln)\n";
    echo "Reset:  php tools/seed-demo.php --reset\n";
}

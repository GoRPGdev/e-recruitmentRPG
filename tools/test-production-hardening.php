<?php
/**
 * Test Suite: Uji Kesiapan Produksi (Production Hardening, Token Lifecycle & Background Jobs)
 * E-Recruitment RPG
 */

$baseUrl = 'http://localhost:8080';
require_once __DIR__ . '/koneksi.local.php';
$k = require __DIR__ . '/koneksi.local.php';
$conn = sqlsrv_connect($k['host'], array(
    'Database' => $k['database'],
    'UID' => $k['user'],
    'PWD' => $k['password'],
    'CharacterSet' => 'UTF-8'
));
if (!$conn) {
    die("Koneksi database gagal\n");
}

function assertTest($name, $condition, $details = '') {
    if ($condition) {
        echo "  [PASS] $name\n";
        if ($details) {
            echo "         -> $details\n";
        }
        return true;
    } else {
        echo "  [FAIL] $name\n";
        if ($details) {
            echo "         -> $details\n";
        }
        return false;
    }
}

function httpGet($url, $headers = array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    return array('code' => $httpCode, 'header' => $header, 'body' => $body);
}

echo "======================================================================\n";
echo "  E-RECRUITMENT RPG — PENGUJIAN KESIAPAN PRODUKSI (PRODUCTION READINESS)\n";
echo "======================================================================\n\n";

// ======================================================================
// BAGIAN 1: UJI KEAMANAN & PENCEGAHAN KEBOCORAN INFORMASI SISTEM
// ======================================================================
echo "--- [BAGIAN 1] UJI KEAMANAN & PENCEGAHAN KEBOCORAN INFORMASI SISTEM ---\n";

// 1.1 Test 404 Halaman Tidak Ditemukan
$res404 = httpGet("$baseUrl/index.php/halaman-acak-yang-tidak-pernah-ada-123");
$p1 = assertTest("Request rute tidak dikenal merespons HTTP 404", $res404['code'] === 404);
$p2 = assertTest("Halaman 404 TIDAK membocorkan path direktori server (C:\\laragon atau D:\\...)",
    stripos($res404['body'], 'C:\\') === false && stripos($res404['body'], 'D:\\') === false);
$p3 = assertTest("Halaman 404 TIDAK membocorkan versi PHP atau database",
    stripos($res404['body'], 'PHP Version') === false && stripos($res404['body'], 'SQL Server') === false);

// 1.2 Test Request Parameter Ilegal / Injeksi URL
$resSql = httpGet("$baseUrl/index.php/lamar/marketing-staff-demo'--%20OR%201=1");
$p4 = assertTest("Injeksi karakter ilegal pada slug diblokir sistem (HTTP 400 Bad Request)",
    $resSql['code'] === 400);
$p5 = assertTest("Respons slug ilegal TIDAK memunculkan error database SQL Server",
    stripos($resSql['body'], 'SELECT ') === false && stripos($resSql['body'], 'SQLSTATE') === false && stripos($resSql['body'], 'sqlsrv_') === false);

// 1.3 Test Header Proteksi Keamanan Cookie
$resHome = httpGet("$baseUrl/index.php/auth/login");
$p6 = assertTest("Cookie sesi ci_session memiliki flag HttpOnly (Anti-XSS)",
    preg_match('/Set-Cookie:\s*ci_session=[^\r\n]*;\s*HttpOnly/i', $resHome['header']) === 1);
$p7 = assertTest("Cookie CSRF memiliki flag HttpOnly & SameSite",
    preg_match('/Set-Cookie:\s*csrf_cookie_name=[^\r\n]*;\s*HttpOnly/i', $resHome['header']) === 1 &&
    preg_match('/Set-Cookie:\s*csrf_cookie_name=[^\r\n]*;\s*SameSite=/i', $resHome['header']) === 1);

// ======================================================================
// BAGIAN 2: UJI SIKLUS HIDUP TOKEN (TOKEN LIFECYCLE: BERKAS & ONBOARDING)
// ======================================================================
echo "\n--- [BAGIAN 2] UJI SIKLUS HIDUP TOKEN PENGISIAN FORMULIR & BERKAS ---\n";

// Ambil 1 user valid & 1 lamaran valid
$rUser = sqlsrv_query($conn, "SELECT TOP 1 id_user FROM dbo.M_USERS WHERE is_aktif = 1");
$userRow = sqlsrv_fetch_array($rUser, SQLSRV_FETCH_ASSOC);
$idUserValid = (int) $userRow['id_user'];

$rLamaran = sqlsrv_query($conn, "SELECT TOP 1 a.id_lamaran, a.id_kandidat, c.nama_lengkap FROM dbo.APPLICATIONS a JOIN dbo.CANDIDATES c ON c.id_kandidat = a.id_kandidat ORDER BY a.id_lamaran DESC");
$lamaranRow = sqlsrv_fetch_array($rLamaran, SQLSRV_FETCH_ASSOC);
$idLamaranTest = (int) $lamaranRow['id_lamaran'];

// 2.1 Buat token uji baru
$tokenUji = bin2hex(random_bytes(24));
$stmtInsertToken = sqlsrv_query($conn,
    "INSERT INTO dbo.FORM_TOKENS (id_lamaran, token, tujuan, kadaluarsa_pada, is_revoked, dibuat_oleh)
     VALUES (?, ?, 'FORM_ONBOARDING', DATEADD(DAY, 7, GETDATE()), 0, ?)",
    array($idLamaranTest, $tokenUji, $idUserValid)
);
$p8 = assertTest("Pembuatan token pengisian form onboarding baru berhasil", $stmtInsertToken !== false);

// 2.2 Akses token yang masih aktif (Valid Token)
$resValidToken = httpGet("$baseUrl/index.php/onboarding/$tokenUji");
$p9 = assertTest("Akses token valid merespons HTTP 200 dan merender formulir onboarding",
    $resValidToken['code'] === 200 && strpos($resValidToken['body'], 'Formulir Pelamar') !== false);

// 2.3 Uji Token Kadaluarsa (Expired Token)
sqlsrv_query($conn, "UPDATE dbo.FORM_TOKENS SET kadaluarsa_pada = DATEADD(DAY, -1, GETDATE()) WHERE token = ?", array($tokenUji));
$resExpired = httpGet("$baseUrl/index.php/onboarding/$tokenUji");
$p10 = assertTest("Akses token kedaluwarsa merespons halaman penolakan kadaluarsa",
    strpos($resExpired['body'], 'kedaluwarsa') !== false || strpos($resExpired['body'], 'Tautan Tidak Dapat Digunakan') !== false);

// 2.4 Uji Token yang Telah Dicabut (Revoked Token)
sqlsrv_query($conn, "UPDATE dbo.FORM_TOKENS SET kadaluarsa_pada = DATEADD(DAY, 7, GETDATE()), is_revoked = 1 WHERE token = ?", array($tokenUji));
$resRevoked = httpGet("$baseUrl/index.php/onboarding/$tokenUji");
$p11 = assertTest("Akses token yang telah dicabut HR (is_revoked = 1) berhasil ditolak",
    strpos($resRevoked['body'], 'dicabut') !== false || strpos($resRevoked['body'], 'Tautan Tidak Dapat Digunakan') !== false);

// 2.5 Uji Token yang Sudah Pernah Digunakan (Reused / One-Time Token)
sqlsrv_query($conn, "UPDATE dbo.FORM_TOKENS SET is_revoked = 0, dipakai_pada = DATEADD(HOUR, -1, GETDATE()) WHERE token = ?", array($tokenUji));
$resUsed = httpGet("$baseUrl/index.php/onboarding/$tokenUji");
$p12 = assertTest("Akses token yang sudah selesai digunakan (dipakai_pada IS NOT NULL) ditolak",
    strpos($resUsed['body'], 'sudah pernah digunakan') !== false || strpos($resUsed['body'], 'Tautan Tidak Dapat Digunakan') !== false);

// Bersihkan token uji
sqlsrv_query($conn, "DELETE FROM dbo.FORM_TOKENS WHERE token = ?", array($tokenUji));

// ======================================================================
// BAGIAN 3: UJI BACKGROUND JOBS CLI (WINDOWS TASK SCHEDULER SIMULATION)
// ======================================================================
echo "\n--- [BAGIAN 3] UJI BACKGROUND JOB CLI (WINDOWS TASK SCHEDULER) ---\n";

// 3.1 Uji Eksekusi tools/build-funnel.php
$outFunnel = array();
$retFunnel = 0;
exec('php tools/build-funnel.php 2>&1', $outFunnel, $retFunnel);
$funnelOutputStr = implode("\n", $outFunnel);
$p13 = assertTest("Skrip build-funnel.php berjalan normal via CLI tanpa error (Exit code 0)",
    $retFunnel === 0, "Output: " . trim($funnelOutputStr));

// Cek apakah tabel agregat RPT_FUNNEL_HARIAN terisi data
$qFunnel = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM dbo.RPT_FUNNEL_HARIAN WHERE tanggal = CONVERT(DATE, GETDATE())");
$rFunnel = sqlsrv_fetch_array($qFunnel, SQLSRV_FETCH_ASSOC);
$p14 = assertTest("Tabel agregat RPT_FUNNEL_HARIAN berhasil diperbarui hari ini",
    ($rFunnel['cnt'] ?? 0) > 0, "Jumlah baris data agregat hari ini: " . ($rFunnel['cnt'] ?? 0));

// 3.2 Uji Eksekusi tools/run-retensi.php (--dry-run)
$outRetensi = array();
$retRetensi = 0;
exec('php tools/run-retensi.php --dry-run 2>&1', $outRetensi, $retRetensi);
$retensiOutputStr = implode("\n", $outRetensi);
$p15 = assertTest("Skrip run-retensi.php (--dry-run) berjalan normal via CLI (Exit code 0)",
    $retRetensi === 0, "Output: " . trim($retensiOutputStr));

// ======================================================================
// RINGKASAN
// ======================================================================
echo "\n======================================================================\n";
echo "  RINGKASAN HASIL EVALUASI KESIAPAN PRODUKSI\n";
echo "======================================================================\n";
$tests = array($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12, $p13, $p14, $p15);
$lulus = count(array_filter($tests));
$total = count($tests);
echo "  Total Skenario Uji : $total\n";
echo "  Lulus (Passed)     : $lulus\n";
echo "  Gagal (Failed)     : " . ($total - $lulus) . "\n";
if ($lulus === $total) {
    echo "  >>> SEMUA PENGUJIAN KESIAPAN PRODUKSI LULUS 100% (SEMPURNA).\n";
    echo "      Sistem terbukti aman dari kebocoran info, siklus hidup token\n";
    echo "      terproteksi, dan job otomatisasi CLI siap dijadwalkan di server.\n";
} else {
    echo "  >>> TERDAPAT KELEMAHAN YANG PERLU DIPERBAIKI.\n";
}
echo "======================================================================\n";

sqlsrv_close($conn);

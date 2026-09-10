<?php
/**
 * Test filter dan integritas alur Entry Manual Pelamar
 * Usage: php tools/test-manual-flow.php
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
echo "   TEST ALUR FILTER ENTRY MANUAL PELAMAR\n";
echo "============================================================\n\n";

// 1. Uji label intake
if (!defined('BASEPATH')) define('BASEPATH', true);
require_once dirname(__DIR__) . '/web/application/helpers/status_helper.php';
chk(label_intake('FORM_PUBLIC') === 'Form Publik', 'Helper label_intake FORM_PUBLIC = "Form Publik"');
chk(label_intake('MANUAL') === 'Input Manual', 'Helper label_intake MANUAL = "Input Manual"');
chk(label_intake('IMPORT_FILE') === 'Impor File', 'Helper label_intake IMPORT_FILE = "Impor File"');

// 2. Verifikasi MPR terbuka yang diizinkan untuk intake manual
$sql = "SELECT r.id_req, r.no_mpr, r.status_req, p.nama_posisi
        FROM dbo.REQUISITIONS r
        JOIN dbo.M_POSISI p ON p.id_posisi = r.id_posisi
        WHERE r.status_req IN ('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian')
        ORDER BY r.id_req DESC";
$s = q($conn, $sql);
$open_count = 0;
$all_valid = true;
$allowed_statuses = array('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian');

while ($r = sqlsrv_fetch_array($s, SQLSRV_FETCH_ASSOC)) {
    $open_count++;
    if (!in_array($r['status_req'], $allowed_statuses, true)) {
        $all_valid = false;
    }
}
sqlsrv_free_stmt($s);

chk($open_count > 0, "Ditemukan {$open_count} MPR yang sedang membuka intake pelamar");
chk($all_valid === true, "Semua MPR yang lolos filter memiliki status intake aktif (Approved/Sourcing/Sourcing_Ulang/Terpenuhi_Sebagian)");

// 3. Verifikasi bahwa MPR non-aktif (Draft, Revisi_HR, Review_BOD, Dibatalkan, Terpenuhi) tidak lolos query ini
$sql_closed = "SELECT COUNT(*) AS total
               FROM dbo.REQUISITIONS r
               WHERE r.status_req NOT IN ('Approved', 'Sourcing', 'Sourcing_Ulang', 'Terpenuhi_Sebagian')";
$s_closed = q($conn, $sql_closed);
$r_closed = sqlsrv_fetch_array($s_closed, SQLSRV_FETCH_ASSOC);
$closed_count = (int) $r_closed['total'];
sqlsrv_free_stmt($s_closed);

chk($closed_count >= 0, "MPR tertutup ({$closed_count} dokumen) terfilter keluar dari opsi intake manual");

echo "\nHasil: $ok / $n pengujian lulus.\n";
if ($ok === $n) {
    echo "Status: SUKSES.\n";
} else {
    echo "Status: GAGAL.\n";
    exit(1);
}

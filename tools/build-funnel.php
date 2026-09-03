<?php
/**
 * Isi RPT_FUNNEL_HARIAN. Dijadwalkan harian lewat Windows Task Scheduler
 * (tidak ada SQL Server Agent di semua edisi -- ERD sec.11).
 *
 *   php tools/build-funnel.php            -> hari ini
 *   php tools/build-funnel.php 2026-09-01 -> tanggal tertentu
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }

$cfg = require __DIR__ . '/koneksi.local.php';
$tgl = isset($argv[1]) ? $argv[1] : date('Y-m-d');

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'], 'UID' => $cfg['user'], 'PWD' => $cfg['password'],
    'CharacterSet' => 'UTF-8',
));
if ($conn === false) { fwrite(STDERR, "Koneksi gagal: " . print_r(sqlsrv_errors(), true)); exit(1); }

$stmt = sqlsrv_query($conn, '{CALL dbo.sp_BuildFunnelHarian(?)}', array($tgl));
if ($stmt === false) { fwrite(STDERR, "Gagal: " . print_r(sqlsrv_errors(), true)); exit(1); }
while (sqlsrv_next_result($stmt)) { /* habiskan */ }

$r = sqlsrv_query($conn, 'SELECT COUNT(*) AS n FROM dbo.RPT_FUNNEL_HARIAN WHERE tanggal = ?', array($tgl));
$n = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC);
echo "RPT_FUNNEL_HARIAN $tgl : {$n['n']} baris.\n";

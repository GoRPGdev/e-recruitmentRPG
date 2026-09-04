<?php
/**
 * Anonimisasi kandidat yang masa retensinya habis (RENCANA sec.6 Fase 4).
 * Dijadwalkan lewat Windows Task Scheduler (tak ada SQL Server Agent di
 * semua edisi -- ERD sec.11). Panggil sp_AnonimisasiRetensi lalu hapus
 * file fisik CV/dokumen milik kandidat yang di-scrub.
 *
 *   php tools/run-retensi.php                 -> proses, batch 200
 *   php tools/run-retensi.php --dry-run       -> hanya laporkan, tak mengubah
 *   php tools/run-retensi.php --batch=500     -> ubah ukuran batch
 *
 * Exit code: 0 sukses (termasuk "tak ada yang perlu diproses"),
 *            1 koneksi/SP gagal, 2 sebagian file gagal dihapus.
 */
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }

$cfg     = require __DIR__ . '/koneksi.local.php';
$dry     = in_array('--dry-run', $argv, true);
$batch   = 200;
foreach ($argv as $a) {
    if (preg_match('/^--batch=(\d+)$/', $a, $m)) { $batch = (int) $m[1]; }
}

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'], 'UID' => $cfg['user'], 'PWD' => $cfg['password'],
    'CharacterSet' => 'UTF-8',
));
if ($conn === false) {
    fwrite(STDERR, "Koneksi gagal: " . print_r(sqlsrv_errors(), true));
    exit(1);
}

$oleh_user = null;   // job sistem -- tak ada user
$simulasi  = $dry ? 1 : 0;

$stmt = sqlsrv_query(
    $conn,
    '{CALL dbo.sp_AnonimisasiRetensi(?, ?, ?)}',
    array($batch, $oleh_user, $simulasi)
);
if ($stmt === false) {
    fwrite(STDERR, "sp_AnonimisasiRetensi gagal: " . print_r(sqlsrv_errors(), true));
    exit(1);
}

/* result set 1 -- daftar file fisik untuk dihapus */
$files = array();
do {
    while (($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) !== null && $row !== false) {
        if (isset($row['path_file'])) {
            $files[] = $row['path_file'];
        } elseif (array_key_exists('jumlah_dianonim', $row)) {
            $jumlah = (int) $row['jumlah_dianonim'];
        }
    }
} while (sqlsrv_next_result($stmt));

$stamp = date('Y-m-d H:i:s');
$jumlah = isset($jumlah) ? $jumlah : 0;

if ($dry) {
    echo "[$stamp] DRY-RUN retensi: $jumlah kandidat akan di-anonim, "
        . count($files) . " file akan dihapus.\n";
    foreach ($files as $f) { echo "  - $f\n"; }
    exit(0);
}

/* hapus file fisik -- baris DB sudah di-scrub oleh SP */
$hapus_ok = 0; $hapus_gagal = 0;
foreach ($files as $f) {
    if ( ! file_exists($f)) { $hapus_ok++; continue; }   // sudah tak ada -- anggap beres
    if (@unlink($f)) {
        $hapus_ok++;
    } else {
        $hapus_gagal++;
        fwrite(STDERR, "[$stamp] gagal hapus file: $f\n");
    }
}

echo "[$stamp] retensi selesai: $jumlah kandidat di-anonim, "
    . "file $hapus_ok dihapus, $hapus_gagal gagal.\n";

exit($hapus_gagal > 0 ? 2 : 0);

<?php
/**
 * Runner migrasi sederhana — E-Recruitment RPG
 *
 *   php tools/migrate.php status   Lihat migrasi mana yang sudah/belum jalan
 *   php tools/migrate.php up       Jalankan semua migrasi yang belum
 *   php tools/migrate.php proc     Deploy ulang semua stored procedure
 *
 * File migrasi: database/migrations/YYYYMMDD_HHMM__deskripsi.sql
 * File SP     : database/procedures/*.sql  (idempotent, boleh dijalankan berulang)
 *
 * Migrasi yang sudah tercatat di SCHEMA_MIGRATIONS tidak akan dijalankan lagi.
 * Kalau isi file berubah setelah dijalankan, runner MENOLAK dan memberi tahu —
 * buat file migrasi baru, jangan edit yang lama.
 */

if (php_sapi_name() !== 'cli') { die("Jalankan dari command line.\n"); }

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) { die("tools/koneksi.local.php belum ada. Salin dari koneksi.local.sample.php.\n"); }
$cfg = require $cfgPath;

$ROOT   = dirname(__DIR__);
$DIR_MG = $ROOT . '/database/migrations';
$DIR_SP = $ROOT . '/database/procedures';
$aksi   = isset($argv[1]) ? $argv[1] : 'status';

// SQL Server "deferred name resolution": sebuah SP boleh memanggil SP lain yang
// belum ada saat CREATE (mis. sp_AdvanceStage -> sp_SetRetensi, urutan alfabet).
// Itu warning severity 10, bukan error -- jangan hentikan deploy karenanya.
sqlsrv_configure('WarningsReturnAsErrors', 0);

$conn = sqlsrv_connect($cfg['host'], array(
    'Database' => $cfg['database'], 'UID' => $cfg['user'], 'PWD' => $cfg['password'],
    'CharacterSet' => 'UTF-8', 'ReturnDatesAsStrings' => true,
));
if ($conn === false) { die("Koneksi gagal: " . print_r(sqlsrv_errors(), true)); }

function q($sql, $p = array()) {
    global $conn;
    $r = sqlsrv_query($conn, $sql, $p);
    if ($r === false) {
        $e = sqlsrv_errors(); $m = array();
        foreach ((array)$e as $x) { $m[] = trim($x['message']); }
        throw new Exception(implode(' | ', $m));
    }
    return $r;
}

/* Pastikan tabel pencatat ada */
q("IF OBJECT_ID('dbo.SCHEMA_MIGRATIONS') IS NULL
   CREATE TABLE dbo.SCHEMA_MIGRATIONS (
     nama_file       VARCHAR(200) NOT NULL PRIMARY KEY,
     hash_file       VARCHAR(64)  NOT NULL,
     dijalankan_pada DATETIME     NOT NULL,
     dijalankan_oleh VARCHAR(100) NOT NULL
   )");

/* Baca yang sudah jalan */
$sudah = array();
$r = q("SELECT nama_file, hash_file FROM dbo.SCHEMA_MIGRATIONS");
while ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) { $sudah[$row['nama_file']] = $row['hash_file']; }

/* Daftar file migrasi */
$files = glob($DIR_MG . '/*.sql');
if ($files === false) { $files = array(); }
sort($files);

/**
 * SQL Server tidak menerima GO lewat driver — pecah manual per batch.
 */
function pecahGo($sql) {
    $parts = preg_split('/^\s*GO\s*;?\s*$/mi', $sql);
    $out = array();
    foreach ($parts as $p) { if (trim($p) !== '') { $out[] = $p; } }
    return $out;
}

if ($aksi === 'status') {
    echo "\nStatus migrasi — database: {$cfg['database']}\n" . str_repeat('-', 62) . "\n";
    if (!$files) { echo "  (belum ada file di database/migrations/)\n"; }
    foreach ($files as $f) {
        $nama = basename($f);
        $hash = hash('sha256', file_get_contents($f));
        if (!isset($sudah[$nama]))            { echo "  [ BELUM ] $nama\n"; }
        elseif ($sudah[$nama] !== $hash)      { echo "  [ BERUBAH! ] $nama  <-- file diedit setelah dijalankan\n"; }
        else                                   { echo "  [ OK    ] $nama\n"; }
    }
    foreach ($sudah as $nama => $h) {
        if (!file_exists($DIR_MG . '/' . $nama)) { echo "  [ HILANG ] $nama  <-- tercatat di DB tapi filenya tidak ada\n"; }
    }
    echo "\n";
    exit(0);
}

if ($aksi === 'up') {
    $jalan = 0;
    foreach ($files as $f) {
        $nama = basename($f);
        $isi  = file_get_contents($f);
        $hash = hash('sha256', $isi);

        if (isset($sudah[$nama])) {
            if ($sudah[$nama] !== $hash) {
                echo "\nBERHENTI: $nama sudah dijalankan tapi isinya berubah.\n";
                echo "Migrasi yang sudah dijalankan tidak boleh diedit.\n";
                echo "Kembalikan file ini, lalu buat migrasi koreksi baru.\n\n";
                exit(1);
            }
            continue;
        }

        echo "  menjalankan $nama ... ";
        try {
            foreach (pecahGo($isi) as $batch) { q($batch); }
            q("INSERT INTO dbo.SCHEMA_MIGRATIONS (nama_file, hash_file, dijalankan_pada, dijalankan_oleh)
               VALUES (?, ?, GETDATE(), ?)", array($nama, $hash, get_current_user()));
            echo "OK\n";
            $jalan++;
        } catch (Exception $e) {
            echo "GAGAL\n\n  " . $e->getMessage() . "\n\n";
            echo "Migrasi dihentikan. Perbaiki file lalu jalankan lagi.\n\n";
            exit(1);
        }
    }
    echo $jalan ? "\nSelesai. $jalan migrasi dijalankan.\n\n" : "\nTidak ada migrasi baru.\n\n";
    exit(0);
}

if ($aksi === 'proc') {
    $sps = glob($DIR_SP . '/*.sql');
    if ($sps === false) { $sps = array(); }
    sort($sps);
    if (!$sps) { echo "\n(belum ada file di database/procedures/)\n\n"; exit(0); }
    foreach ($sps as $f) {
        echo "  deploy " . basename($f) . " ... ";
        try {
            foreach (pecahGo(file_get_contents($f)) as $batch) { q($batch); }
            echo "OK\n";
        } catch (Exception $e) {
            echo "GAGAL\n\n  " . $e->getMessage() . "\n\n"; exit(1);
        }
    }
    echo "\nSemua stored procedure ter-deploy.\n\n";
    exit(0);
}

echo "Aksi tidak dikenal: $aksi\nGunakan: status | up | proc\n";
exit(1);

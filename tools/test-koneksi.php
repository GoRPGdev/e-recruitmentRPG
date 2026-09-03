<?php
/**
 * ============================================================
 *  GERBANG FASE 0 — validasi driver & koneksi
 *  E-Recruitment Ratu Pertiwi Group
 * ============================================================
 *
 *  Jalankan di MESIN PRODUKSI, bukan cuma Laragon:
 *      php tools/test-koneksi.php
 *
 *  ATURAN: selama masih ada tanda [GAGAL], JANGAN mulai menulis
 *  Stored Procedure. Perbaiki dulu lingkungannya.
 *
 *  Kombinasi yang benar:
 *      PHP 7.4 + php_sqlsrv_74 (v5.9) + ODBC Driver 17.4+
 *      -> SQL Server 2008 R2
 *  ODBC Driver 18 TIDAK mendukung SQL Server 2008 R2. Jangan dipakai.
 */

$CLI = (php_sapi_name() === 'cli');
if (!$CLI) { header('Content-Type: text/plain; charset=utf-8'); }

$LULUS = 0; $GAGAL = 0; $PERINGATAN = 0;

function judul($t) {
    echo "\n" . str_repeat('=', 62) . "\n  " . $t . "\n" . str_repeat('=', 62) . "\n";
}
function ok($t, $d = '')      { global $LULUS;      $LULUS++;      echo "  [ OK   ] $t" . ($d !== '' ? "\n           $d" : '') . "\n"; }
function gagal($t, $d = '')   { global $GAGAL;      $GAGAL++;      echo "  [GAGAL ] $t" . ($d !== '' ? "\n           $d" : '') . "\n"; }
function warn($t, $d = '')    { global $PERINGATAN; $PERINGATAN++; echo "  [ WARN ] $t" . ($d !== '' ? "\n           $d" : '') . "\n"; }
function info($t)             { echo "           $t\n"; }
function errSqlsrv() {
    $e = sqlsrv_errors();
    if (!$e) return '(tidak ada detail error)';
    $out = array();
    foreach ($e as $x) { $out[] = '[' . $x['SQLSTATE'] . '/' . $x['code'] . '] ' . trim($x['message']); }
    return implode(' | ', $out);
}

echo "\n";
echo "  E-RECRUITMENT RPG - TES KONEKSI (Gerbang Fase 0)\n";
echo "  " . date('Y-m-d H:i:s') . " di " . php_uname('n') . "\n";

/* ------------------------------------------------------------
   1. PHP
   ------------------------------------------------------------ */
judul('1. PHP');

echo "  Versi PHP  : " . PHP_VERSION . "\n";
echo "  Arsitektur : " . (PHP_INT_SIZE * 8) . "-bit\n";
echo "  Thread safe: " . (defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE ? 'TS (pakai DLL _ts)' : 'NTS (pakai DLL _nts)') . "\n";
echo "  SAPI       : " . php_sapi_name() . "\n";
echo "  php.ini    : " . (php_ini_loaded_file() ? php_ini_loaded_file() : '(tidak terdeteksi)') . "\n\n";

if (version_compare(PHP_VERSION, '7.4.0', '>=') && version_compare(PHP_VERSION, '8.0.0', '<')) {
    ok('PHP 7.4.x sesuai target');
} elseif (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    gagal('PHP ' . PHP_VERSION . ' — driver sqlsrv 5.9 hanya sampai PHP 7.4',
          'Turunkan ke PHP 7.4, atau ubah keputusan stack (bahas dulu).');
} else {
    gagal('PHP ' . PHP_VERSION . ' terlalu lama', 'Butuh PHP 7.4.x');
}

/* ------------------------------------------------------------
   2. Ekstensi sqlsrv
   ------------------------------------------------------------ */
judul('2. Ekstensi sqlsrv');

if (!extension_loaded('sqlsrv')) {
    gagal('Ekstensi sqlsrv TIDAK aktif',
          "Unduh Microsoft Drivers for PHP for SQL Server v5.9,\n" .
          "           taruh php_sqlsrv_74_*.dll di folder ext/,\n" .
          "           lalu tambahkan extension=php_sqlsrv_74_ts.dll di php.ini");
    echo "\n  >>> Tidak bisa lanjut tanpa ekstensi sqlsrv. Berhenti di sini.\n\n";
    exit(1);
}
$verSqlsrv = phpversion('sqlsrv');
if (version_compare($verSqlsrv, '5.8', '>=')) {
    ok('sqlsrv aktif, versi ' . $verSqlsrv);
} else {
    warn('sqlsrv versi ' . $verSqlsrv . ' — disarankan 5.9');
}
if (extension_loaded('pdo_sqlsrv')) {
    ok('pdo_sqlsrv aktif, versi ' . phpversion('pdo_sqlsrv'));
} else {
    warn('pdo_sqlsrv tidak aktif', 'CI3 driver sqlsrv tidak butuh ini, tapi berguna untuk skrip tools.');
}

/* ------------------------------------------------------------
   3. Konfigurasi
   ------------------------------------------------------------ */
judul('3. Konfigurasi lokal');

$cfgPath = __DIR__ . DIRECTORY_SEPARATOR . 'koneksi.local.php';
if (!file_exists($cfgPath)) {
    gagal('tools/koneksi.local.php belum ada',
          'Salin dari tools/koneksi.local.sample.php lalu isi host/database/user/password.');
    echo "\n  >>> Tidak bisa lanjut tanpa konfigurasi. Berhenti di sini.\n\n";
    exit(1);
}
$cfg = require $cfgPath;
foreach (array('host', 'database', 'user', 'password') as $k) {
    if (!isset($cfg[$k]) || $cfg[$k] === '' || $cfg[$k] === 'GANTI_DENGAN_PASSWORD') {
        gagal("Konfigurasi '$k' belum diisi di koneksi.local.php");
        exit(1);
    }
}
ok('Konfigurasi terbaca', 'host=' . $cfg['host'] . ' database=' . $cfg['database'] . ' user=' . $cfg['user']);

if (stripos($cfg['database'], 'PROD') !== false || stripos($cfg['database'], 'LIVE') !== false) {
    gagal('Nama database mengandung PROD/LIVE — ini skrip DEV, jangan diarahkan ke produksi.');
    exit(1);
}

/* ------------------------------------------------------------
   4. Koneksi
   ------------------------------------------------------------ */
judul('4. Koneksi ke SQL Server');

$conn = sqlsrv_connect($cfg['host'], array(
    'Database'             => $cfg['database'],
    'UID'                  => $cfg['user'],
    'PWD'                  => $cfg['password'],
    'CharacterSet'         => 'UTF-8',
    'ReturnDatesAsStrings' => true,
));

if ($conn === false) {
    gagal('Koneksi GAGAL', errSqlsrv());
    info('Cek: SQL Server Browser jalan? TCP/IP enabled di Configuration Manager?');
    info('Cek: nama instance benar? firewall port 1433 terbuka?');
    info('Cek: ODBC Driver 17 terpasang? (JANGAN 18)');
    echo "\n  >>> Berhenti di sini.\n\n";
    exit(1);
}
ok('Koneksi berhasil');

/* ------------------------------------------------------------
   5. Versi driver ODBC & server
   ------------------------------------------------------------ */
judul('5. Versi ODBC & SQL Server');

$ci = sqlsrv_client_info($conn);
if ($ci) {
    echo "  DriverName    : " . (isset($ci['DriverName']) ? $ci['DriverName'] : '?') . "\n";
    echo "  DriverVer     : " . (isset($ci['DriverVer']) ? $ci['DriverVer'] : '?') . "\n";
    echo "  ExtensionVer  : " . (isset($ci['ExtensionVer']) ? $ci['ExtensionVer'] : '?') . "\n\n";

    $dn = isset($ci['DriverName']) ? $ci['DriverName'] : '';
    if (stripos($dn, '18') !== false) {
        gagal('Terdeteksi ODBC Driver 18 — TIDAK mendukung SQL Server 2008 R2',
              'Uninstall 18, pasang Microsoft ODBC Driver 17 for SQL Server (17.4+).');
    } elseif (stripos($dn, '17') !== false) {
        $dv = isset($ci['DriverVer']) ? $ci['DriverVer'] : '0';
        if (version_compare($dv, '17.4', '>=')) {
            ok('ODBC Driver 17 versi ' . $dv . ' — sesuai syarat sqlsrv 5.9');
        } else {
            gagal('ODBC Driver 17 versi ' . $dv . ' terlalu lama', 'Driver PHP 5.9 mensyaratkan 17.4 atau lebih baru.');
        }
    } elseif (stripos($dn, '11') !== false || stripos($dn, '13') !== false) {
        gagal('Terdeteksi ODBC Driver lama (' . $dn . ')',
              'Driver PHP 5.9 mensyaratkan ODBC 17.4+. Ini kombinasi yang salah di dokumen v2.0.');
    } else {
        warn('Driver ODBC tidak dikenali: ' . $dn, 'Pastikan ODBC Driver 17 (17.4+).');
    }
} else {
    warn('sqlsrv_client_info() tidak mengembalikan data');
}

$si = sqlsrv_server_info($conn);
if ($si) {
    echo "  SQL Server    : " . (isset($si['SQLServerVersion']) ? $si['SQLServerVersion'] : '?') .
         "  (" . (isset($si['SQLServerName']) ? $si['SQLServerName'] : '?') . ")\n\n";
    $sv = isset($si['SQLServerVersion']) ? $si['SQLServerVersion'] : '';
    if (strpos($sv, '10.50') === 0) {
        ok('SQL Server 2008 R2 terkonfirmasi (10.50.x) — sesuai target');
    } else {
        warn('Versi SQL Server ' . $sv . ' bukan 10.50.x (2008 R2)',
             'Kalau lebih baru, batasan T-SQL di CLAUDE.md mungkin terlalu ketat. Konfirmasi dulu.');
    }
}

$r = sqlsrv_query($conn, 'SELECT @@VERSION AS v');
if ($r && ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC))) {
    ok('SELECT @@VERSION berhasil');
    foreach (explode("\n", trim($row['v'])) as $ln) { info(trim($ln)); }
} else {
    gagal('SELECT @@VERSION gagal', errSqlsrv());
}

/* ------------------------------------------------------------
   6. Parameter binding
   ------------------------------------------------------------ */
judul('6. Parameter binding');

$nama = "O'Brien & Co — ünïcode ✓";
$angka = 12345;
$r = sqlsrv_query($conn, 'SELECT ? AS teks, ? AS angka', array($nama, $angka));
if ($r && ($row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC))) {
    if ($row['teks'] === $nama && (int)$row['angka'] === $angka) {
        ok('Binding parameter aman (kutip tunggal & UTF-8 lolos utuh)');
    } else {
        gagal('Nilai kembali tidak sama', 'kirim="' . $nama . '" terima="' . $row['teks'] . '"');
    }
} else {
    gagal('Query dengan parameter gagal', errSqlsrv());
}

/* ------------------------------------------------------------
   7. Stored Procedure + transaksi
   ------------------------------------------------------------ */
judul('7. Stored Procedure, transaksi & OUTPUT parameter');

sqlsrv_query($conn, "IF OBJECT_ID('dbo.sp_TesKoneksiDummy') IS NOT NULL DROP PROCEDURE dbo.sp_TesKoneksiDummy");
sqlsrv_query($conn, "IF OBJECT_ID('dbo.TES_KONEKSI_TMP') IS NOT NULL DROP TABLE dbo.TES_KONEKSI_TMP");

$mk = sqlsrv_query($conn, "CREATE TABLE dbo.TES_KONEKSI_TMP (id INT IDENTITY PRIMARY KEY, nama VARCHAR(100), dibuat DATETIME)");
if ($mk === false) {
    gagal('Tidak bisa CREATE TABLE', errSqlsrv() . ' — user mungkin tidak punya hak DDL di database ini.');
} else {
    ok('CREATE TABLE berhasil (user punya hak DDL)');

    $sp = "
CREATE PROCEDURE dbo.sp_TesKoneksiDummy
    @nama    VARCHAR(100),
    @id_baru INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        BEGIN TRAN;
            INSERT INTO dbo.TES_KONEKSI_TMP (nama, dibuat) VALUES (@nama, GETDATE());
            SET @id_baru = SCOPE_IDENTITY();
        COMMIT TRAN;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRAN;
        DECLARE @msg VARCHAR(2000);
        SET @msg = ERROR_MESSAGE();
        RAISERROR(@msg, 16, 1);
    END CATCH
END";
    if (sqlsrv_query($conn, $sp) === false) {
        gagal('CREATE PROCEDURE gagal', errSqlsrv());
    } else {
        ok('CREATE PROCEDURE berhasil (BEGIN TRAN + TRY/CATCH + RAISERROR)');

        $idBaru = 0;
        $params = array(
            array('Kandidat Uji', SQLSRV_PARAM_IN),
            array(&$idBaru, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT),
        );
        $ex = sqlsrv_query($conn, '{CALL dbo.sp_TesKoneksiDummy(?, ?)}', $params);
        if ($ex === false) {
            gagal('EXEC stored procedure gagal', errSqlsrv());
        } else {
            while (sqlsrv_next_result($ex)) { /* habiskan result set supaya OUTPUT terisi */ }
            if ((int)$idBaru > 0) {
                ok('EXEC + OUTPUT parameter berhasil', 'id_baru = ' . $idBaru);
            } else {
                gagal('OUTPUT parameter tidak terisi', 'Ini masalah klasik sqlsrv — pastikan sqlsrv_next_result() dipanggil.');
            }
        }
    }

    /* --- 8. Pola paginasi ROW_NUMBER() --- */
    judul('8. Pola paginasi ROW_NUMBER() (wajib di 2008 R2)');

    for ($i = 1; $i <= 25; $i++) {
        sqlsrv_query($conn, 'INSERT INTO dbo.TES_KONEKSI_TMP (nama, dibuat) VALUES (?, GETDATE())', array('Baris ' . $i));
    }
    $sqlPage = "
WITH q AS (
    SELECT id, nama, ROW_NUMBER() OVER (ORDER BY id) AS rn
    FROM dbo.TES_KONEKSI_TMP
)
SELECT id, nama FROM q WHERE rn BETWEEN ? AND ? ORDER BY rn";
    $rp = sqlsrv_query($conn, $sqlPage, array(11, 20));
    if ($rp === false) {
        gagal('Paginasi ROW_NUMBER() gagal', errSqlsrv());
    } else {
        $n = 0; while (sqlsrv_fetch_array($rp, SQLSRV_FETCH_ASSOC)) { $n++; }
        if ($n === 10) { ok('Paginasi ROW_NUMBER() berhasil (10 baris halaman ke-2)'); }
        else { warn('Paginasi mengembalikan ' . $n . ' baris, diharapkan 10'); }
    }

    /* --- 9. Konfirmasi batasan 2008 R2 --- */
    judul('9. Konfirmasi batasan 2008 R2 (yang ini MEMANG harus gagal)');

    $terlarang = array(
        'OFFSET ... FETCH NEXT' => 'SELECT id FROM dbo.TES_KONEKSI_TMP ORDER BY id OFFSET 0 ROWS FETCH NEXT 5 ROWS ONLY',
        'TRY_CONVERT'           => "SELECT TRY_CONVERT(INT, '123') AS x",
        'CONCAT()'              => "SELECT CONCAT('a','b') AS x",
        'IIF()'                 => "SELECT IIF(1=1,'y','n') AS x",
        'FORMAT()'              => "SELECT FORMAT(GETDATE(),'yyyy-MM-dd') AS x",
    );
    foreach ($terlarang as $label => $sql) {
        $t = @sqlsrv_query($conn, $sql);
        if ($t === false) {
            ok($label . ' ditolak server — sesuai harapan 2008 R2');
        } else {
            warn($label . ' ternyata JALAN di server ini',
                 'Berarti server lebih baru dari 2008 R2. Konfirmasi versi sebelum melonggarkan aturan di CLAUDE.md.');
        }
    }

    sqlsrv_query($conn, "IF OBJECT_ID('dbo.sp_TesKoneksiDummy') IS NOT NULL DROP PROCEDURE dbo.sp_TesKoneksiDummy");
    sqlsrv_query($conn, "IF OBJECT_ID('dbo.TES_KONEKSI_TMP') IS NOT NULL DROP TABLE dbo.TES_KONEKSI_TMP");
    info('Objek uji sudah dibersihkan.');
}

/* ------------------------------------------------------------
   10. Folder penyimpanan file
   ------------------------------------------------------------ */
judul('10. Folder penyimpanan file kandidat');

if (empty($cfg['storage'])) {
    warn("Konfigurasi 'storage' belum diisi", 'File CV/KTP wajib disimpan di luar webroot.');
} else {
    $st = $cfg['storage'];
    if (!is_dir($st)) {
        if (@mkdir($st, 0770, true)) { ok('Folder storage dibuat: ' . $st); }
        else { gagal('Folder storage tidak ada dan tidak bisa dibuat: ' . $st); }
    } else { ok('Folder storage ada: ' . $st); }

    if (is_dir($st)) {
        $uji = rtrim($st, '\\/') . DIRECTORY_SEPARATOR . '.tes-tulis';
        if (@file_put_contents($uji, 'ok') !== false) {
            ok('Folder storage bisa ditulis');
            @unlink($uji);
        } else {
            gagal('Folder storage TIDAK bisa ditulis', 'Beri hak tulis ke user yang menjalankan Apache/IIS.');
        }
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
        if ($docRoot !== '' && stripos(realpath($st), realpath($docRoot)) === 0) {
            gagal('Folder storage ADA DI DALAM webroot', 'Pindahkan ke luar — file kandidat tidak boleh bisa diakses langsung dari URL.');
        }
    }
}

/* ------------------------------------------------------------
   Ringkasan
   ------------------------------------------------------------ */
sqlsrv_close($conn);

judul('RINGKASAN');
echo "  Lulus      : $LULUS\n";
echo "  Peringatan : $PERINGATAN\n";
echo "  Gagal      : $GAGAL\n\n";

if ($GAGAL === 0) {
    echo "  >>> GERBANG FASE 0 TERBUKA.\n";
    echo "      Lingkungan siap. Boleh mulai menulis migrasi & Stored Procedure.\n";
    if ($PERINGATAN > 0) { echo "      Tetap baca peringatan di atas sebelum lanjut.\n"; }
    echo "\n";
    exit(0);
} else {
    echo "  >>> GERBANG FASE 0 MASIH TERTUTUP.\n";
    echo "      Perbaiki semua [GAGAL] di atas. JANGAN mulai menulis Stored Procedure dulu.\n\n";
    exit(1);
}

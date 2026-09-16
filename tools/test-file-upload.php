<?php
/**
 * Test Suite: Uji Validasi & Keamanan Upload Berkas Pelamar
 * E-Recruitment RPG
 */

$baseUrl = 'http://localhost:8080';
$slug    = 'marketing-staff-demo';

require_once __DIR__ . '/koneksi.local.php';
$k = require __DIR__ . '/koneksi.local.php';
$conn = sqlsrv_connect($k['host'], array(
    'Database' => $k['database'],
    'UID' => $k['user'],
    'PWD' => $k['password'],
    'CharacterSet' => 'UTF-8'
));
if (!$conn) {
    die("Koneksi DB gagal\n");
}

function clearRateLimit($conn) {
    sqlsrv_query($conn, "DELETE FROM dbo.FORM_SUBMIT_LOG WHERE ip IN ('127.0.0.1', '::1')");
}

function getCsrfToken($baseUrl, $slug) {
    $ch = curl_init("$baseUrl/index.php/lamar/$slug");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $res = curl_exec($ch);
    curl_close($ch);

    preg_match('/csrf_cookie_name=([a-f0-9]+)/', $res, $mCsrf);
    preg_match('/name="csrf_test_name" value="([^"]+)"/', $res, $mForm);

    return array(
        'cookie' => isset($mCsrf[1]) ? $mCsrf[1] : '',
        'token'  => isset($mForm[1]) ? $mForm[1] : ''
    );
}

function uploadTest($baseUrl, $slug, $filename, $content, $mimeType, $conn) {
    clearRateLimit($conn);
    $csrf = getCsrfToken($baseUrl, $slug);

    $unique = bin2hex(random_bytes(4));

    // Pastikan jika konten berupa PDF valid, tambahkan salt unik agar tidak terbentur deduplikasi hash CV
    if (strpos($content, '%PDF') === 0 && strlen($content) < 5000) {
        $content .= "\n% Salt: " . $unique . "\n";
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'erec_');
    file_put_contents($tmpFile, $content);

    $cfile = curl_file_create($tmpFile, $mimeType, $filename);

    $postData = array(
        'csrf_test_name'      => $csrf['token'],
        'nama_lengkap'        => 'Pelamar Uji ' . $unique,
        'email'               => 'uji_' . $unique . '@example.com',
        'no_wa'               => '0812' . substr(str_pad(hexdec($unique), 8, '0', STR_PAD_LEFT), 0, 8),
        'jenis_kelamin'       => 'L',
        'consent'             => '1',
        'tempat_lahir'        => 'Jakarta',
        'kota_domisili'       => 'Jakarta Selatan',
        'pendidikan_terakhir' => 'S1',
        'nama_sekolah'        => 'Universitas Indonesia',
        'jurusan'             => 'Manajemen',
        'status_pernikahan'   => 'Belum_Menikah',
        'cv'                  => $cfile,
    );

    $ch = curl_init("$baseUrl/index.php/lamar/$slug");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_COOKIE, 'csrf_cookie_name=' . $csrf['cookie']);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    @unlink($tmpFile);

    $isSuccess = (strpos($response, 'Lamaran terkirim') !== false || strpos($response, 'sukses') !== false);

    // Ekstrak pesan flash
    $errorMsg = '';
    if (preg_match('/<div class="flash err[^>]*>(.*?)<\/div>/s', $response, $mFlash)) {
        $errorMsg = trim(strip_tags($mFlash[1]));
    } elseif (preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $response, $mErr)) {
        $errorMsg = trim(strip_tags($mErr[1]));
    }

    return array(
        'code'     => $code,
        'success'  => $isSuccess,
        'errorMsg' => $errorMsg,
        'body'     => $response
    );
}

echo "======================================================================\n";
echo "  TEST SUITE: PENGUJIAN KEAMANAN & SKENARIO EKSTREM UPLOAD BERKAS CV\n";
echo "======================================================================\n\n";

$validPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n160\n%%EOF";

$scenarios = array(
    array(
        'nama'     => '1. Upload File Normal (Valid PDF)',
        'file'     => 'Curriculum_Vitae_Valid.pdf',
        'content'  => $validPdf,
        'mime'     => 'application/pdf',
        'expected' => true,
        'errMsg'   => ''
    ),
    array(
        'nama'     => '2. File Ekstensi Terlarang (.exe binary)',
        'file'     => 'payload.exe',
        'content'  => "MZ\x90\x00\x03\x00\x00\x00This program cannot be run in DOS mode",
        'mime'     => 'application/x-msdownload',
        'expected' => false,
        'errMsg'   => 'Format'
    ),
    array(
        'nama'     => '3. File Skrip Web Executable (.php)',
        'file'     => 'webshell.php',
        'content'  => "<?php phpinfo(); ?>",
        'mime'     => 'application/x-php',
        'expected' => false,
        'errMsg'   => 'Format'
    ),
    array(
        'nama'     => '4. MIME Spoofing (Skrip PHP disamarkan jadi .pdf)',
        'file'     => 'fake_resume.pdf',
        'content'  => "<?php phpinfo(); ?>",
        'mime'     => 'application/pdf', // Client bilang PDF, finfo server deteksi text/x-php
        'expected' => false,
        'errMsg'   => 'Tipe file'
    ),
    array(
        'nama'     => '5. File Kosong (0 Byte)',
        'file'     => 'empty.pdf',
        'content'  => '',
        'mime'     => 'application/pdf',
        'expected' => false,
        'errMsg'   => 'gagal'
    ),
    array(
        'nama'     => '6. File Oversize Melebihi Batas Maksimal (> 5 MB)',
        'file'     => 'heavy_portfolio.pdf',
        'content'  => "%PDF-1.4\n" . str_repeat('A', 6 * 1024 * 1024),
        'mime'     => 'application/pdf',
        'expected' => false,
        'errMsg'   => 'maksimal'
    ),
    array(
        'nama'     => '7. Ekstensi Ganda Polyglot (.php.pdf)',
        'file'     => 'exploit.php.pdf',
        'content'  => "<?php echo 'Polyglot test'; ?>",
        'mime'     => 'application/pdf',
        'expected' => false,
        'errMsg'   => 'Tipe file'
    ),
    array(
        'nama'     => '8. Nama File Karakter Khusus & Tanda Kurung',
        'file'     => "CV & Portofolio - Muhammad Kiki (Revisi #2) [RPG-2026].pdf",
        'content'  => $validPdf,
        'mime'     => 'application/pdf',
        'expected' => true,
        'errMsg'   => ''
    ),
);

$passed = 0;
$total  = count($scenarios);

foreach ($scenarios as $s) {
    echo ">> {$s['nama']}\n";
    echo "   Berkas: {$s['file']} | Ukuran: " . strlen($s['content']) . " bytes | MIME: {$s['mime']}\n";

    $res = uploadTest($baseUrl, $slug, $s['file'], $s['content'], $s['mime'], $conn);

    if ($s['expected'] === true) {
        if ($res['success']) {
            echo "   [PASS] Berhasil terkirim dan disimpan sesuai harapan.\n\n";
            $passed++;
        } else {
            echo "   [FAIL] Seharusnya sukses tapi gagal. Respon HTTP={$res['code']}, Error='{$res['errorMsg']}'\n\n";
        }
    } else {
        if (!$res['success']) {
            echo "   [PASS] Berhasil dicegat/ditolak sistem! HTTP={$res['code']}\n";
            if ($res['errorMsg']) {
                echo "   Pesan Penolakan: '{$res['errorMsg']}'\n";
            }
            echo "\n";
            $passed++;
        } else {
            echo "   [FAIL CRITICAL] Berbahaya! File yang seharusnya dilarang malah lolos ke sistem!\n\n";
        }
    }
}

echo "======================================================================\n";
echo "  RINGKASAN EVALUASI UJI UPLOAD BERKAS\n";
echo "======================================================================\n";
echo "  Total Skenario Uji : $total\n";
echo "  Lulus (Passed)     : $passed\n";
echo "  Gagal (Failed)     : " . ($total - $passed) . "\n";
if ($passed === $total) {
    echo "  >>> SEMUA PENGUJIAN KEAMANAN UPLOAD BERKAS LOLOS 100% (SEMPURNA).\n";
} else {
    echo "  >>> TERDAPAT KELEMAHAN VALIDASI YANG PERLU DITANGANI SEBELUM DEPLOY.\n";
}
echo "======================================================================\n";

sqlsrv_close($conn);

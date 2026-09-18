<?php
/**
 * Generator Logo & Favicon Persegi Utuh (Symmetrical Square - Anti Cutoff)
 * Menjamin 100% gambar master 3200x3200 ter-render utuh tanpa terpotong lingkaran.
 */
$sourcePath = dirname(__DIR__) . '/logo.jpg.jpeg';
$targetDir = dirname(__DIR__) . '/web/assets/img';

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (!function_exists('resizeSquareImage')) {
    function resizeSquareImage($src, $srcW, $srcH, $targetSize, $outPath) {
        $dst = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetSize, $targetSize, $srcW, $srcH);
        imagepng($dst, $outPath, 8);
        imagedestroy($dst);
    }
}

if (file_exists($sourcePath)) {
    $src = imagecreatefromjpeg($sourcePath);
    if ($src !== false) {
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // 1. Logo Utama Persegi Utuh (512x512)
        resizeSquareImage($src, $srcW, $srcH, 512, $targetDir . '/logo.png');

        // 2. Logo Medium Persegi Utuh (160x160)
        resizeSquareImage($src, $srcW, $srcH, 160, $targetDir . '/logo-sm.png');

        // 3. Favicon (64x64 & 32x32)
        resizeSquareImage($src, $srcW, $srcH, 64, $targetDir . '/favicon.png');
        resizeSquareImage($src, $srcW, $srcH, 32, $targetDir . '/favicon-32x32.png');

        // 4. File favicon.ico
        $pngData = file_get_contents($targetDir . '/favicon-32x32.png');
        $icoHeader = pack('vvv', 0, 1, 1);
        $icoEntry = pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($pngData), 22);
        file_put_contents($targetDir . '/favicon.ico', $icoHeader . $icoEntry . $pngData);

        imagedestroy($src);
        touch($targetDir . '/.square_v2');
    }
}

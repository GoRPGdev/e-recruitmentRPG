<?php
/**
 * Router untuk PHP Built-in Web Server (Development Local)
 * Meniru rewrite .htaccess / web.config tanpa perlu install Apache / IIS.
 *
 * Jalankan dari root project:
 *   php -S localhost:8080 -t web router.php
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Jika file statis fisik ada (css, js, gambar, font), sajikan langsung
if ($uri !== '/' && file_exists(__DIR__ . '/web' . $uri)) {
    return false;
}

chdir(__DIR__ . '/web');
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/web/index.php';

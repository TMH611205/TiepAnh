<?php

require_once __DIR__ . '/env.php';

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', APP_ENV === 'development');

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

define('APP_NAME', 'Xe điện Tiệp Anh');
define('STORE_PHONE', '0975303993');
define('STORE_ADDRESS', 'Số nhà 08, đường Thượng Trụ, xã Can Lộc, tỉnh Hà Tĩnh');
define('STORE_FACEBOOK', 'https://www.facebook.com/minhhoang.tran.5076');
define('STORE_EMAIL', 'tiepanhelectric@gmail.com');
// TODO: chủ dự án cung cấp Zalo, giờ mở cửa, link Google Maps, logo, slogan.

// URL gốc của dự án: lấy từ APP_URL nếu có, ngược lại tự nhận theo địa chỉ truy cập
// (so vị trí thư mục dự án với DOCUMENT_ROOT) nên đổi cổng/tên miền không cần sửa mã.
$appUrl = rtrim((string)env('APP_URL', ''), '/');

if ($appUrl === '') {
    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(__DIR__ . '/..');
    $basePath = '';

    if ($documentRoot !== false && $projectRoot !== false && str_starts_with($projectRoot, $documentRoot)) {
        $basePath = str_replace(chr(92), '/', substr($projectRoot, strlen($documentRoot)));
    }

    $appUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;
}

define('BASE_URL', $appUrl . '/public');
define('ASSET_URL', $appUrl . '/assets');
define('ADMIN_URL', $appUrl . '/admin');

date_default_timezone_set('Asia/Ho_Chi_Minh');

/** URL tới file trong assets/ kèm số phiên bản (thời gian sửa file) để luôn nhận bản mới nhất. */
function asset(string $path): string
{
    $file = __DIR__ . '/../assets/' . $path;

    return ASSET_URL . '/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../models/Pricing.php';

<?php

require_once __DIR__ . '/env.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('DB_HOST', 'localhost'),
    env('DB_PORT', '3306'),
    env('DB_NAME', 'tiepanh')
);

try {
    $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    // Không để lộ host/tài khoản trong thông báo lỗi.
    error_log('Kết nối CSDL thất bại: ' . $exception->getMessage());
    http_response_code(503);
    exit('Hệ thống đang bảo trì, vui lòng quay lại sau ít phút.');
}

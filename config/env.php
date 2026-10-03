<?php

/**
 * Đọc file .env ở thư mục gốc (KEY=value, bỏ qua dòng trống và dòng bắt đầu bằng #).
 * Biến môi trường thật của máy chủ luôn được ưu tiên hơn file .env.
 */
function env(string $key, ?string $default = null): ?string
{
    static $values = null;

    if ($values === null) {
        $values = [];
        $file = __DIR__ . '/../.env';

        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);

                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim(trim($value), "\"'");
            }
        }
    }

    $fromServer = getenv($key);

    if ($fromServer !== false) {
        return $fromServer;
    }

    return $values[$key] ?? $default;
}

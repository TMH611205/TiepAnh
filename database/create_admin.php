<?php

/**
 * Tạo hoặc đặt lại mật khẩu tài khoản quản trị. Chỉ chạy bằng dòng lệnh:
 *
 *   php database/create_admin.php email@vidu.com "Họ tên" "MậtKhẩu" [admin|staff]
 *
 * Nếu email đã tồn tại thì cập nhật mật khẩu/quyền. Mật khẩu tối thiểu 10 ký tự.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

[$script, $email, $fullName, $password] = array_pad($argv, 4, null);
$role = $argv[4] ?? 'admin';

if (!$email || !$fullName || !$password) {
    fwrite(STDERR, "Cách dùng: php database/create_admin.php email \"Họ tên\" \"Mật khẩu\" [admin|staff]\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email không hợp lệ.\n");
    exit(1);
}

if (mb_strlen($password, 'UTF-8') < 10) {
    fwrite(STDERR, "Mật khẩu cần tối thiểu 10 ký tự.\n");
    exit(1);
}

if (!in_array($role, User::STAFF_ROLES, true)) {
    fwrite(STDERR, "Quyền phải là admin hoặc staff.\n");
    exit(1);
}

User::saveStaff($pdo, $email, $fullName, $password, $role);
echo "Đã lưu tài khoản {$role}: {$email}\n";

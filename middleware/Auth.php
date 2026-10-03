<?php

require_once __DIR__ . '/../models/User.php';

/**
 * Xác thực khu vực quản trị dựa trên session.
 * Mỗi request đều kiểm tra lại tài khoản trong DB để khóa tài khoản có hiệu lực ngay.
 */
class Auth
{
    private const IDLE_TIMEOUT = 7200;   // 2 giờ không thao tác thì đăng xuất
    private const MAX_FAILURES = 5;      // số lần sai tối đa trong một khung thời gian
    private const LOCK_SECONDS = 600;

    public static function current(PDO $pdo): ?array
    {
        $sessionUser = $_SESSION['admin_user'] ?? null;

        if (!is_array($sessionUser)) {
            return null;
        }

        if (time() - (int)($_SESSION['admin_last_seen'] ?? 0) > self::IDLE_TIMEOUT) {
            self::logout();
            return null;
        }

        $user = User::findById($pdo, (int)$sessionUser['id']);

        if ($user === null || !User::isStaff($user)) {
            self::logout();
            return null;
        }

        $_SESSION['admin_last_seen'] = time();

        return $user;
    }

    /** Bắt buộc đăng nhập; chuyển về trang login nếu chưa. */
    public static function require(PDO $pdo): array
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');

        $user = self::current($pdo);

        if ($user === null) {
            $next = urlencode(basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php')));
            header('Location: ' . ADMIN_URL . '/login.php?next=' . $next);
            exit;
        }

        return $user;
    }

    public static function requireAdminRole(array $user): void
    {
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            exit('Bạn không có quyền thực hiện thao tác này.');
        }
    }

    /** @return string|null thông báo lỗi, null nếu đăng nhập thành công */
    public static function attempt(PDO $pdo, string $email, string $password): ?string
    {
        $failures = $_SESSION['login_failures'] ?? ['count' => 0, 'since' => time()];

        if (time() - $failures['since'] > self::LOCK_SECONDS) {
            $failures = ['count' => 0, 'since' => time()];
        }

        if ($failures['count'] >= self::MAX_FAILURES) {
            return 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 10 phút.';
        }

        $user = User::findByEmail($pdo, $email);
        // Luôn gọi password_verify để thời gian phản hồi không lộ email có tồn tại hay không.
        $hash = $user['password_hash'] ?? '$2y$10$D1xIiNi69W/VUYKf8Q2n4usMjPre/Jx7MBiJoK74mFotA3JMUmH.y';
        $valid = password_verify($password, $hash) && $user !== null && User::isStaff($user);

        if (!$valid) {
            $failures['count']++;
            $_SESSION['login_failures'] = $failures;
            usleep(400000);

            return 'Email hoặc mật khẩu không đúng.';
        }

        unset($_SESSION['login_failures']);
        session_regenerate_id(true);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin_user'] = ['id' => (int)$user['id']];
        $_SESSION['admin_last_seen'] = time();

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }

        return null;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_user'], $_SESSION['admin_last_seen']);
        session_regenerate_id(true);
    }

    public static function verifyCsrf(): void
    {
        $token = (string)($_POST['csrf_token'] ?? '');

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Phiên làm việc đã hết hạn. Hãy quay lại, tải lại trang rồi thử lại.');
        }
    }
}

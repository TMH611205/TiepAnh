<?php

class User
{
    public const STAFF_ROLES = ['admin', 'staff'];

    public static function findByEmail(PDO $pdo, string $email): ?array
    {
        $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => mb_strtolower(trim($email), 'UTF-8')]);

        return $statement->fetch() ?: null;
    }

    public static function findById(PDO $pdo, int $id): ?array
    {
        $statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public static function isStaff(array $user): bool
    {
        return $user['status'] === 'active' && in_array($user['role'], self::STAFF_ROLES, true);
    }

    /** Tạo hoặc đặt lại mật khẩu cho tài khoản quản trị (dùng bởi database/create_admin.php). */
    public static function saveStaff(
        PDO $pdo,
        string $email,
        string $fullName,
        string $password,
        string $role = 'admin'
    ): void {
        $email = mb_strtolower(trim($email), 'UTF-8');
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if (self::findByEmail($pdo, $email) !== null) {
            $statement = $pdo->prepare('
                UPDATE users
                SET full_name = :full_name, password_hash = :hash, role = :role, status = \'active\'
                WHERE email = :email
            ');
        } else {
            $statement = $pdo->prepare('
                INSERT INTO users (full_name, email, password_hash, role, status)
                VALUES (:full_name, :email, :hash, :role, \'active\')
            ');
        }

        $statement->execute([
            'full_name' => $fullName,
            'email' => $email,
            'hash' => $hash,
            'role' => $role,
        ]);
    }
}

<?php

require_once __DIR__ . '/../middleware/admin.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

if (Auth::current($pdo) !== null) {
    redirect(ADMIN_URL . '/index.php');
}

// Chỉ cho phép quay lại một trang admin trong danh sách, tránh open redirect.
$allowedNext = ['index.php', 'order.php', 'sales.php', 'purchases.php', 'products.php', 'inventory.php', 'customers.php', 'invoices.php', 'ai-report.php'];
$next = (string)($_GET['next'] ?? $_POST['next'] ?? 'index.php');
$next = in_array($next, $allowedNext, true) ? $next : 'index.php';

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $error = Auth::attempt($pdo, $email, $password);

        if ($error === null) {
            redirect(ADMIN_URL . '/' . $next);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Đăng nhập quản trị - <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/images/logo-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>

<body class="admin-body admin-login">
    <main class="admin-login-card">
        <img src="<?= ASSET_URL ?>/images/logo-icon.png" alt="Tiệp Anh" width="56" height="56">
        <h1>Đăng nhập quản trị</h1>
        <p><?= e(APP_NAME) ?></p>

        <?php if ($error !== null) : ?>
            <div class="admin-alert error" role="alert" style="margin-bottom: 14px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= ADMIN_URL ?>/login.php" autocomplete="on">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">

            <div class="admin-field">
                <label for="email">Email</label>
                <input class="admin-input" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username" inputmode="email">
            </div>

            <div class="admin-field">
                <label for="password">Mật khẩu</label>
                <input class="admin-input" type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="admin-button">Đăng nhập</button>
        </form>
    </main>
</body>

</html>

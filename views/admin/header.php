<?php

/** @var array $admin */
/** @var string $title */
/** @var string $active */

$navigation = [
    'index' => ['Tổng quan', 'dashboard', 'index.php'],
    'order' => ['Đơn hàng', 'receipt_long', 'order.php'],
    'sales' => ['Chứng từ bán hàng', 'sell', 'sales.php'],
    'purchases' => ['Chứng từ mua hàng', 'shopping_cart_checkout', 'purchases.php'],
    'products' => ['Sản phẩm', 'two_wheeler', 'products.php'],
    'inventory' => ['Tồn kho', 'inventory_2', 'inventory.php'],
    'customers' => ['Khách hàng', 'group', 'customers.php'],
    'invoices' => ['Hóa đơn', 'description', 'invoices.php'],
    'ai' => ['Báo cáo AI', 'auto_awesome', 'ai-report.php'],
];
$flash = take_flash();

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> - Quản trị <?= e(APP_NAME) ?></title>

    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/images/logo-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        document.documentElement.classList.add('js-reveal');
        setTimeout(function () {
            if (!window.__revealReady) {
                document.documentElement.classList.remove('js-reveal');
            }
        }, 3000);
    </script>
    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>

<body class="admin-body">

    <aside class="admin-sidebar no-print" id="adminSidebar">
        <a class="admin-brand" href="<?= ADMIN_URL ?>/index.php">
            <img class="admin-logo" src="<?= ASSET_URL ?>/images/logo-icon.png" alt="" width="40" height="40">
            <span>Tiệp Anh<small>Quản trị</small></span>
        </a>

        <nav class="admin-nav" aria-label="Menu quản trị">
            <?php foreach ($navigation as $key => [$label, $icon, $file]) : ?>
                <a
                    href="<?= ADMIN_URL ?>/<?= e($file) ?>"
                    class="<?= $active === $key ? 'active' : '' ?>"
                    <?= $active === $key ? 'aria-current="page"' : '' ?>>
                    <span class="material-symbols-outlined" aria-hidden="true"><?= e($icon) ?></span>
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-foot">
            <a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener">
                <span class="material-symbols-outlined" aria-hidden="true">open_in_new</span>
                Xem website
            </a>
        </div>
    </aside>
    <div class="admin-backdrop" id="adminBackdrop"></div>

    <div class="admin-shell">
        <header class="admin-topbar no-print">
            <button type="button" class="admin-menu-button" id="adminMenuButton" aria-label="Mở menu" aria-controls="adminSidebar" aria-expanded="false">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
            <h1><?= e($title) ?></h1>

            <div class="admin-user">
                <span class="admin-user-name">
                    <?= e($admin['full_name']) ?>
                    <small><?= $admin['role'] === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?></small>
                </span>
                <form method="post" action="<?= ADMIN_URL ?>/logout.php">
                    <?= csrf_field() ?>
                    <button type="submit" class="admin-button ghost">
                        <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                        <span class="hide-mobile">Đăng xuất</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="admin-main">
            <?php if ($flash) : ?>
                <div class="admin-alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>

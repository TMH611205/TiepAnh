<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app.php';

$storePhone = (string)constant('STORE_PHONE');
$pageTitle = $pageTitle ?? 'Trang chủ';

$cartCount = $_SESSION['cart_count'] ?? 0;

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Xe điện Tiệp Anh - Xe máy điện, xe đạp điện và phụ kiện.">

    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/images/logo-icon.png">
    <meta name="theme-color" content="#006b2c">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:site_name" content="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle . ' - ' . APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="Xe điện Tiệp Anh - Xe máy điện, xe đạp điện và phụ kiện tại Can Lộc, Hà Tĩnh.">
    <meta property="og:image" content="<?= ASSET_URL ?>/images/logo-icon.png">
    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => 'Hộ kinh doanh Tiệp Anh',
            'alternateName' => APP_NAME,
            'image' => ASSET_URL . '/images/logo-icon.png',
            'telephone' => STORE_PHONE,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Số nhà 08, đường Thượng Trụ',
                'addressLocality' => 'xã Can Lộc',
                'addressRegion' => 'Hà Tĩnh',
                'addressCountry' => 'VN',
            ],
            'sameAs' => [STORE_FACEBOOK],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>
    </script>

    <title>
        <?= htmlspecialchars($pageTitle) ?> -
        <?= APP_NAME ?>
    </title>


    <!-- Google Fonts -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>


    <!-- Material Symbols -->

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Animation khi cuộn: chỉ ẩn nội dung khi JS chạy; tự hiện lại nếu reveal.js không tải được -->
    <script>
        document.documentElement.classList.add('js-reveal');
        setTimeout(function () {
            if (!window.__revealReady) {
                document.documentElement.classList.remove('js-reveal');
            }
        }, 3000);
    </script>

    <!-- Website CSS -->

    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">

</head>


<body data-base-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">

    <!-- ================= TOP NOTICE ================= -->

    <div class="top-notice">

        <span class="material-symbols-outlined">
            eco
        </span>

        <span>
            Khám phá các mẫu xe điện đang có giá ưu đãi
        </span>

        <a href="<?= BASE_URL ?>/products.php?promotion=1">
            Xem ưu đãi
        </a>

    </div>


    <!-- ================= HEADER ================= -->

    <header class="main-header">

        <div class="header-container">


            <!-- LOGO -->

            <a
                href="<?= BASE_URL ?>/index.php"
                class="brand">
                <img class="brand-logo" src="<?= ASSET_URL ?>/images/logo-horizontal.png" alt="Xe điện Tiệp Anh" width="141" height="44">
            </a>


            <!-- SEARCH -->

            <div class="header-search">

                <span class="material-symbols-outlined">
                    search
                </span>

                <input
                    type="text"
                    id="headerSearchInput"
                    placeholder="Tìm kiếm mẫu xe, phụ kiện...">

            </div>


            <!-- DESKTOP MENU -->

            <nav class="desktop-menu">

                <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>

                <a href="<?= BASE_URL ?>/about.php">Giới thiệu</a>

                <a href="<?= BASE_URL ?>/products.php">Sản phẩm</a>

                <a href="<?= BASE_URL ?>/products.php?promotion=1">Ưu đãi</a>

                <a href="<?= BASE_URL ?>/news.php">Tin tức</a>

                <a href="<?= BASE_URL ?>/contact.php">Liên hệ</a>

            </nav>


            <!-- ACTIONS -->

            <div class="header-actions">


                <!-- HOTLINE -->

                <a
                    href="tel:<?= htmlspecialchars($storePhone, ENT_QUOTES, 'UTF-8') ?>"
                    class="hotline">

                    <span class="material-symbols-outlined">
                        call
                    </span>

                    <span>
                        Hotline: <?= htmlspecialchars($storePhone, ENT_QUOTES, 'UTF-8') ?>
                    </span>

                </a>


                <!-- CART -->

                <a
                    href="<?= BASE_URL ?>/cart.php"
                    class="cart-button">

                    <span class="material-symbols-outlined">
                        shopping_cart
                    </span>

                    <span
                        class="cart-badge"
                        id="cartCountBadge">
                        <?= $cartCount ?>
                    </span>

                </a>

                <!-- MOBILE MENU -->

                <button
                    type="button"
                    class="mobile-menu-button"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        menu
                    </span>

                </button>

            </div>

        </div>

    </header>


    <!-- ================= MOBILE DRAWER ================= -->

    <div
        id="sideNavOverlay"
        class="side-nav-overlay"
        onclick="toggleSideNav()"></div>


    <aside
        id="sideNavBar"
        class="side-nav">

        <div>


            <div class="side-nav-header">

                <div class="brand">
                    <img class="brand-logo" src="<?= ASSET_URL ?>/images/logo-horizontal.png" alt="Xe điện Tiệp Anh" width="141" height="44">
                </div>


                <button
                    onclick="toggleSideNav()"
                    class="close-menu">

                    <span class="material-symbols-outlined">
                        close
                    </span>

                </button>

            </div>


            <hr>


            <nav class="mobile-navigation">

                <a
                    href="<?= BASE_URL ?>/index.php"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        home
                    </span>

                    Trang chủ

                </a>


                <a
                    href="#gioi-thieu"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        info
                    </span>

                    Giới thiệu

                </a>


                <a
                    href="#san-pham"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        two_wheeler
                    </span>

                    Sản phẩm

                </a>


                <a
                    href="<?= BASE_URL ?>/products.php?promotion=1"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        local_offer
                    </span>

                    Ưu đãi

                </a>


                <a
                    href="#tin-tuc"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        article
                    </span>

                    Tin tức

                </a>


                <a
                    href="#lien-he"
                    onclick="toggleSideNav()">

                    <span class="material-symbols-outlined">
                        contacts
                    </span>

                    Liên hệ

                </a>

            </nav>

        </div>


        <div class="mobile-menu-footer">


            <a href="tel:<?= htmlspecialchars($storePhone, ENT_QUOTES, 'UTF-8') ?>">

                <span class="material-symbols-outlined">
                    call
                </span>

                Hotline <?= htmlspecialchars($storePhone, ENT_QUOTES, 'UTF-8') ?>

            </a>


            <a href="<?= BASE_URL ?>/contact.php">

                <span class="material-symbols-outlined">
                    verified_user
                </span>

                Tư vấn bảo hành

            </a>

        </div>

    </aside>


    <main id="noi-dung">
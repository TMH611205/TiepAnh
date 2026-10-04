<?php

$pageTitle = 'Trang chủ';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$categoryStmt = $pdo->query('
    SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.status IN (\'active\', \'out_of_stock\')
    WHERE c.status = 1
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.id ASC
');
$categories = $categoryStmt->fetchAll();
$productTotal = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status IN ('active', 'out_of_stock')")->fetchColumn();

$featuredStmt = $pdo->query('
    SELECT
        p.*,
        c.name AS category_name,
        c.slug AS category_slug,
        ' . Pricing::sqlCurrent() . ' AS current_price,
        ' . Pricing::sqlOld() . ' AS old_price
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.status = \'active\'
        AND p.stock > 0
        AND p.price > 0
    ORDER BY
        ' . Pricing::sqlOnSale() . ' DESC,
        p.id DESC
    LIMIT 6
');
$featuredProducts = $featuredStmt->fetchAll();

require_once __DIR__ . '/../views/layouts/header.php';

?>

<!-- ================= HERO ================= -->

<section
    class="hero-section"
    id="trang-chu">

    <div class="container">

        <div class="row align-items-center gx-3 gy-5">


            <!-- HERO CONTENT -->

            <div class="col-lg-6">

                <div class="hero-badge">

                    <span class="material-symbols-outlined">
                        verified
                    </span>

                    Thương hiệu xe điện uy tín

                </div>


                <h1 class="hero-title">

                    Xe điện Tiệp Anh –
                    Đồng hành cùng

                    <span>
                        mọi hành trình xanh
                    </span>

                </h1>


                <p class="hero-description">

                    Khám phá các dòng xe máy điện,
                    xe đạp điện và phụ kiện chất lượng
                    với thiết kế hiện đại, tiết kiệm và
                    thân thiện với môi trường.

                </p>


                <!-- BUTTONS -->

                <div class="hero-buttons">

                    <a
                        href="#san-pham"
                        class="btn-primary-custom">

                        Khám phá sản phẩm

                        <span class="material-symbols-outlined">
                            arrow_forward
                        </span>

                    </a>


                    <a
                        href="<?= BASE_URL ?>/contact.php"
                        class="btn-outline-custom">
                        Liên hệ tư vấn
                        <span class="material-symbols-outlined" aria-hidden="true">support_agent</span>
                    </a>

                </div>


                <!-- BENEFITS -->

                <div class="hero-benefits">


                    <div class="benefit-item">

                        <span class="material-symbols-outlined">
                            verified
                        </span>

                        <div>

                            <strong>
                                Chính hãng
                            </strong>

                            <small>
                                Sản phẩm rõ nguồn gốc
                            </small>

                        </div>

                    </div>


                    <div class="benefit-item">

                        <span class="material-symbols-outlined">
                            battery_charging_full
                        </span>

                        <div>

                            <strong>
                                Tiết kiệm
                            </strong>

                            <small>
                                Chi phí vận hành thấp
                            </small>

                        </div>

                    </div>


                    <div class="benefit-item">

                        <span class="material-symbols-outlined">
                            local_shipping
                        </span>

                        <div>

                            <strong>
                                Giao tận nhà
                            </strong>

                            <small>
                                Hỗ trợ giao xe
                            </small>

                        </div>

                    </div>


                </div>

            </div>


            <!-- HERO SLIDER (bên phải) -->

            <div class="col-lg-6">

                <div class="hero-slider" id="heroSlider" aria-roledescription="carousel" aria-label="Sản phẩm và ưu đãi nổi bật">

                    <div class="hero-slider-viewport">
                        <div class="hero-slider-track">

                            <?php foreach ($featuredProducts as $i => $product): ?>
                                <?php
                                $price = (float)$product['price'];
                                $currentPrice = (float)$product['current_price'];
                                $discount = $price > 0 && $currentPrice < $price
                                    ? (int)round((($price - $currentPrice) / $price) * 100)
                                    : 0;
                                $slideImage = BASE_URL . '/../' . ltrim(str_replace(chr(92), '/', (string)($product['image'] ?? '')), '/');
                                $slideHref = BASE_URL . '/product-detail.php?id=' . (int)$product['id'];
                                ?>
                                <a class="hero-slide" href="<?= htmlspecialchars($slideHref, ENT_QUOTES, 'UTF-8') ?>" aria-roledescription="slide" aria-label="<?= $i + 1 ?> / <?= count($featuredProducts) ?>">

                                    <div class="hero-slide-media">
                                        <?php if ($discount > 0): ?>
                                            <span class="hero-slide-discount">-<?= $discount ?>%</span>
                                        <?php endif; ?>
                                        <img src="<?= htmlspecialchars($slideImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
                                    </div>

                                    <div class="hero-slide-info">
                                        <div class="hero-slide-text">
                                            <span class="hero-slide-tag<?= $discount > 0 ? ' is-sale' : '' ?>">
                                                <?= $discount > 0 ? 'Ưu đãi giảm ' . $discount . '%' : 'Sản phẩm nổi bật' ?>
                                            </span>
                                            <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <p><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>

                                        <div class="hero-slide-price">
                                            <small><?= $discount > 0 ? 'Giá ưu đãi' : 'Giá từ' ?></small>
                                            <strong><?= number_format($currentPrice, 0, ',', '.') ?>₫</strong>
                                            <?php if ($discount > 0): ?>
                                                <del><?= number_format($price, 0, ',', '.') ?>₫</del>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </a>
                            <?php endforeach; ?>

                            <?php if ($featuredProducts === []): ?>
                                <a class="hero-slide" href="<?= BASE_URL ?>/products.php">
                                    <div class="hero-slide-info">
                                        <div class="hero-slide-text">
                                            <h3>Xe điện Tiệp Anh</h3>
                                            <p>Xem tất cả sản phẩm</p>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>

                    <?php if (count($featuredProducts) > 1): ?>
                        <button type="button" class="hero-slider-arrow prev" aria-label="Slide trước">
                            <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
                        </button>
                        <button type="button" class="hero-slider-arrow next" aria-label="Slide sau">
                            <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
                        </button>
                        <div class="hero-slider-dots" role="tablist">
                            <?php foreach ($featuredProducts as $i => $product): ?>
                                <button type="button" role="tab" aria-label="Slide <?= $i + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= THƯƠNG HIỆU ================= -->

<section class="brand-story" id="thuong-hieu">

    <div class="container">

        <div class="brand-story-grid">

            <div>

                <div class="brand-story-mark">
                    <img src="<?= ASSET_URL ?>/images/logo-icon.png" alt="Logo Xe điện Tiệp Anh" width="72" height="72">
                    <span class="brand-story-eyebrow">Thương hiệu Tiệp Anh</span>
                </div>

                <h2>Xe điện <span>Tiệp Anh</span>,<br>cửa hàng của người Can Lộc</h2>

                <p>
                    Tiệp Anh kinh doanh xe máy điện, xe đạp điện, xe điện học sinh,
                    pin – ắc quy và phụ kiện. Bạn có thể đến xem trực tiếp tại
                    <?= htmlspecialchars(STORE_ADDRESS, ENT_QUOTES, 'UTF-8') ?>
                    hoặc đặt hàng và nhờ tư vấn ngay trên website.
                </p>

                <div class="brand-story-actions">
                    <a class="primary" href="tel:<?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="material-symbols-outlined" aria-hidden="true">call</span>
                        Gọi <?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a class="ghost" href="<?= BASE_URL ?>/about.php">Về Tiệp Anh</a>
                </div>

            </div>

            <div class="brand-story-facts">

                <div class="brand-fact">
                    <span class="material-symbols-outlined" aria-hidden="true">storefront</span>
                    <strong>Cửa hàng thực tế</strong>
                    <small>Thượng Trụ, Can Lộc, Hà Tĩnh</small>
                </div>

                <div class="brand-fact">
                    <span class="material-symbols-outlined" aria-hidden="true">two_wheeler</span>
                    <strong><?= $productTotal ?> sản phẩm</strong>
                    <small>Trong <?= count($categories) ?> danh mục đang kinh doanh</small>
                </div>

                <div class="brand-fact">
                    <span class="material-symbols-outlined" aria-hidden="true">verified_user</span>
                    <strong>Bảo hành rõ ràng</strong>
                    <small>Thời hạn ghi trên từng sản phẩm</small>
                </div>

                <div class="brand-fact">
                    <span class="material-symbols-outlined" aria-hidden="true">support_agent</span>
                    <strong>Tư vấn tận tình</strong>
                    <small>Hotline, Zalo, Facebook và trợ lý trên web</small>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= CATEGORY ================= -->

<section class="category-section">

    <div class="container">


        <div class="section-heading">

            <div>

                <span class="section-label">
                    Hệ sinh thái di chuyển xanh
                </span>

                <h2>
                    Danh mục sản phẩm Tiệp Anh
                </h2>

            </div>

            <p>
                Đa dạng lựa chọn xe điện và phụ kiện
                phù hợp với nhu cầu sử dụng hằng ngày.
            </p>

        </div>


        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
                <?php
                [$categoryIcon, $categoryDescription] = match ($category['slug']) {
                    'xe-may-dien' => ['two_wheeler', 'Các mẫu xe máy điện với nhiều lựa chọn theo nhu cầu di chuyển.'],
                    'xe-dap-dien' => ['pedal_bike', 'Xe đạp điện và xe trợ lực cho hành trình hằng ngày.'],
                    'xe-dien-hoc-sinh' => ['school', 'Các mẫu dành cho học sinh; giá và cấu hình được cập nhật từ catalog.'],
                    'pin-ac-quy' => ['battery_charging_full', 'Pin và ắc quy tương thích theo thông tin từng sản phẩm.'],
                    'phu-kien' => ['build', 'Phụ kiện xe điện; liên hệ khi dữ liệu giá chưa được cập nhật.'],
                    default => ['electric_scooter', $category['description'] ?? 'Sản phẩm Tiệp Anh.'],
                };
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl">
                    <a class="category-card" href="<?= BASE_URL ?>/products.php?category=<?= urlencode($category['slug']) ?>">
                        <div class="category-icon">
                            <span class="material-symbols-outlined" aria-hidden="true"><?= $categoryIcon ?></span>
                        </div>
                        <h3><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars($categoryDescription, ENT_QUOTES, 'UTF-8') ?></p>
                        <span class="category-link">
                            <?= (int)$category['product_count'] ?> sản phẩm
                            <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                        </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</section>


<!-- ================= PRODUCTS ================= -->

<section
    class="products-section"
    id="san-pham">

    <div class="container">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    Bộ sưu tập chủ lực
                </span>

                <h2>
                    Sản phẩm nổi bật
                </h2>

            </div>

            <a
                href="<?= BASE_URL ?>/products.php"
                class="view-all-link">

                Xem tất cả

                <span class="material-symbols-outlined">
                    arrow_forward
                </span>

            </a>

        </div>


        <?php if ($featuredProducts !== []): ?>
            <div class="catalog-product-grid homepage-featured-grid" id="productGrid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php
                    $price = (float)$product['price'];
                    $currentPrice = (float)$product['current_price'];
                    $discount = $price > 0 && $currentPrice < $price
                        ? (int)round((($price - $currentPrice) / $price) * 100)
                        : 0;
                    $imagePath = str_replace(chr(92), '/', (string)($product['image'] ?? ''));
                    ?>
                    <article class="catalog-product-card">
                        <div class="catalog-product-image"<?= $imagePath !== '' ? ' style="--media-image: url(\'' . htmlspecialchars(BASE_URL . '/../' . ltrim($imagePath, '/'), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>
                            <button
                                class="compare-image-toggle compare-select-button"
                                type="button"
                                data-product-id="<?= (int)$product['id'] ?>"
                                aria-pressed="false"
                                aria-label="Chọn <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> để so sánh"
                                title="Chọn để so sánh">
                                <span class="material-symbols-outlined" aria-hidden="true">add</span>
                            </button>
                            <div class="catalog-product-badges">
                                <?php if ($discount > 0): ?>
                                    <span class="catalog-badge sale-badge">-<?= $discount ?>% ưu đãi</span>
                                <?php endif; ?>
                                <span class="catalog-badge category-badge"><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if ($imagePath !== ''): ?>
                                <img src="<?= BASE_URL ?>/../<?= htmlspecialchars(ltrim($imagePath, '/'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            <?php else: ?>
                                <span class="material-symbols-outlined" aria-hidden="true">electric_scooter</span>
                            <?php endif; ?>
                        </div>
                        <div class="catalog-product-content">
                            <div class="catalog-product-meta">
                                <span><?= htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="catalog-stock in-stock">Còn <?= (int)$product['stock'] ?></span>
                            </div>
                            <h2><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="catalog-spec-grid">
                                <div><small>Động cơ</small><strong><?= htmlspecialchars($product['motor_power'] ?: 'Đang cập nhật', ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <div><small>Quãng đường</small><strong><?= $product['battery_range'] !== null ? (int)$product['battery_range'] . ' km' : 'Đang cập nhật' ?></strong></div>
                                <div><small>Tốc độ tối đa</small><strong><?= $product['max_speed'] !== null ? (int)$product['max_speed'] . ' km/h' : 'Đang cập nhật' ?></strong></div>
                            </div>
                            <div class="catalog-price-row">
                                <strong><?= number_format($currentPrice, 0, ',', '.') ?>₫</strong>
                                <?php if ($discount > 0): ?>
                                    <del><?= number_format($price, 0, ',', '.') ?>₫</del>
                                <?php endif; ?>
                            </div>
                            <div class="catalog-product-actions">
                                <a href="<?= BASE_URL ?>/product-detail.php?id=<?= (int)$product['id'] ?>">Xem chi tiết</a>
                                <button type="button" onclick="addToCart(<?= (int)$product['id'] ?>)">
                                    <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
                                    Thêm giỏ
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-products">Chưa có sản phẩm nổi bật đang sẵn hàng.</p>
        <?php endif; ?>

    </div>

</section>


<!-- ================= WHY CHOOSE US ================= -->

<section
    class="why-section"
    id="gioi-thieu">

    <div class="container">

        <div class="section-heading center">

            <div>

                <span class="section-label">
                    Cam kết dịch vụ
                </span>

                <h2>
                    Vì sao chọn Xe điện Tiệp Anh?
                </h2>

            </div>

            <p>
                Tập trung vào sản phẩm chất lượng,
                dịch vụ minh bạch và hỗ trợ khách hàng.
            </p>

        </div>


        <div class="row g-4">


            <div class="col-12 col-md-6 col-lg-3">

                <div class="why-card">

                    <div class="why-icon">

                        <span class="material-symbols-outlined">
                            verified
                        </span>

                    </div>

                    <h3>
                        Sản phẩm rõ nguồn gốc
                    </h3>

                    <p>
                        Thông tin sản phẩm,
                        giá bán và chính sách được
                        hiển thị rõ ràng.
                    </p>

                </div>

            </div>


            <div class="col-12 col-md-6 col-lg-3">

                <div class="why-card">

                    <div class="why-icon">

                        <span class="material-symbols-outlined">
                            workspace_premium
                        </span>

                    </div>

                    <h3>
                        Chính sách bảo hành
                    </h3>

                    <p>
                        Hỗ trợ kiểm tra và tra cứu
                        thông tin bảo hành của xe.
                    </p>

                </div>

            </div>


            <div class="col-12 col-md-6 col-lg-3">

                <div class="why-card">

                    <div class="why-icon">

                        <span class="material-symbols-outlined">
                            support_agent
                        </span>

                    </div>

                    <h3>
                        Hỗ trợ khách hàng
                    </h3>

                    <p>
                        Tư vấn sản phẩm và hỗ trợ
                        sau bán hàng.
                    </p>

                </div>

            </div>


            <div class="col-12 col-md-6 col-lg-3">

                <div class="why-card">

                    <div class="why-icon">

                        <span class="material-symbols-outlined">
                            receipt_long
                        </span>

                    </div>

                    <h3>
                        Hóa đơn điện tử
                    </h3>

                    <p>
                        Hỗ trợ quy trình hóa đơn điện tử
                        cho khách hàng khi mua xe.
                    </p>

                </div>

            </div>


        </div>

    </div>

</section>


<!-- ================= PROMOTION ================= -->

<section
    class="promotion-section"
    id="khuyen-mai">

    <div class="container">

        <div class="promotion-box">

            <div>

                <span class="promotion-label">
                    Ưu đãi Tiệp Anh
                </span>

                <h2>
                    Khám phá các chương trình
                    khuyến mãi mới nhất
                </h2>

                <p>
                    Theo dõi ưu đãi về xe điện,
                    phụ kiện và các chương trình
                    dành cho khách hàng.
                </p>

                <a
                    href="tel:<?= STORE_PHONE ?>"
                    class="promotion-button">

                    <span class="material-symbols-outlined">
                        call
                    </span>

                    Liên hệ tư vấn

                </a>

            </div>

        </div>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<?php

require_once __DIR__ . '/../views/layouts/footer.php';

?>
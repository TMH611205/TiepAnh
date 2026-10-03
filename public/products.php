<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';


// ===============================
// LẤY THAM SỐ TÌM KIẾM
// ===============================

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$promotion = ($_GET['promotion'] ?? '') === '1';
$pageTitle = $promotion ? 'Ưu đãi xe điện' : 'Sản phẩm';
$priceBand = trim($_GET['price'] ?? '');
$rangeBand = trim($_GET['range'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

$priceBands = [
    'under-15' => [0, 15000000],
    '15-25' => [15000000, 25000000],
    '25-35' => [25000000, 35000000],
    'over-35' => [35000000, null],
];

$rangeBands = [
    '60-80' => [60, 80],
    '80-110' => [80, 110],
    'over-110' => [110, null],
];


// ===============================
// LẤY DANH MỤC
// ===============================

$categoryStmt = $pdo->query("
    SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON p.category_id = c.id AND p.status IN ('active', 'out_of_stock')
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.id ASC
");

$categories = $categoryStmt->fetchAll();


// ===============================
// LẤY SẢN PHẨM
// ===============================

$sql = "
    SELECT
        p.*,
        p.image AS image_url,
        " . Pricing::sqlCurrent() . " AS current_price,
        " . Pricing::sqlOld() . " AS old_price,
        (p.price - " . Pricing::sqlCurrent() . ") AS discount_amount,
        c.name AS category_name,
        c.slug AS category_slug
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.id
    WHERE p.status IN ('active', 'out_of_stock')
";

$params = [];


// Tìm kiếm

if ($search !== '') {

    $sql .= "
        AND (
            p.name LIKE :search_name
            OR p.motor_power LIKE :search_motor_power
            OR p.product_code LIKE :search_code
        )
    ";

    $params['search_name'] = '%' . $search . '%';
    $params['search_motor_power'] = '%' . $search . '%';
    $params['search_code'] = '%' . $search . '%';
}


// Lọc danh mục

if ($category !== '') {

    $sql .= "
        AND c.slug = :category
    ";

    $params['category'] = $category;
}

if ($promotion) {
    $sql .= "
        AND " . Pricing::sqlOnSale() . "
    ";
}

if (isset($priceBands[$priceBand])) {
    [$minimumPrice, $maximumPrice] = $priceBands[$priceBand];
    $sql .= "
        AND (
            " . Pricing::sqlCurrent() . "
        ) >= :minimum_price
    ";
    $params['minimum_price'] = $minimumPrice;

    if ($maximumPrice !== null) {
        $sql .= " AND (
            " . Pricing::sqlCurrent() . "
        ) < :maximum_price ";
        $params['maximum_price'] = $maximumPrice;
    }
}

if (isset($rangeBands[$rangeBand])) {
    [$minimumRange, $maximumRange] = $rangeBands[$rangeBand];
    $sql .= ' AND p.battery_range >= :minimum_range';
    $params['minimum_range'] = $minimumRange;

    if ($maximumRange !== null) {
        $sql .= ' AND p.battery_range <= :maximum_range';
        $params['maximum_range'] = $maximumRange;
    }
}

$orderBy = match ($sort) {
    'price-asc' => 'current_price ASC, p.id DESC',
    'price-desc' => 'current_price DESC, p.id DESC',
    'discount' => 'discount_amount DESC, p.id DESC',
    default => 'p.id DESC',
};


$sql .= "
    ORDER BY {$orderBy}
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll();


require_once __DIR__ . '/../views/layouts/header.php';

?>


<div class="catalog-page">
    <div class="container catalog-container">
        <nav class="catalog-breadcrumb" aria-label="Đường dẫn">
            <a href="<?= BASE_URL ?>/index.php">
                <span class="material-symbols-outlined" aria-hidden="true">home</span>
                Trang chủ
            </a>
            <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
            <strong><?= $promotion ? 'Ưu đãi xe điện' : 'Danh mục sản phẩm' ?></strong>
        </nav>

        <section class="catalog-hero">
            <div class="catalog-hero-copy">
                <span class="catalog-kicker">
                    <span class="material-symbols-outlined" aria-hidden="true">electric_bolt</span>
                    <?= $promotion ? 'ƯU ĐÃI ĐANG DIỄN RA' : 'HỆ SINH THÁI DI CHUYỂN XANH' ?>
                </span>
                <h1>
                    <?= $promotion ? 'Ưu đãi xe điện' : 'Danh mục xe điện' ?>
                    <span>Tiệp Anh</span>
                </h1>
                <p>
                    <?= $promotion
                        ? 'Giá ưu đãi được cập nhật trực tiếp từ catalog. Chọn mẫu xe phù hợp và xem chi tiết chương trình.'
                        : 'Khám phá xe máy điện và xe đạp điện chính hãng, so sánh thông số và chọn mẫu xe phù hợp.' ?>
                </p>
                <a class="catalog-hero-link" href="#catalog-results">
                    <?= $promotion ? 'Xem sản phẩm ưu đãi' : 'Khám phá sản phẩm' ?>
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_downward</span>
                </a>
            </div>
            <span class="catalog-hero-art material-symbols-outlined" aria-hidden="true">
                electric_scooter
            </span>
        </section>

        <nav class="catalog-category-tabs" aria-label="Danh mục xe">
            <a class="<?= !$promotion && $category === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/products.php">
                Tất cả <span><?= array_sum(array_column($categories, 'product_count')) ?></span>
            </a>
            <a class="<?= $promotion ? 'active' : '' ?>" href="<?= BASE_URL ?>/products.php?promotion=1">
                <span class="material-symbols-outlined" aria-hidden="true">sell</span>
                Đang ưu đãi
            </a>
            <?php foreach ($categories as $item): ?>
                <a
                    class="<?= $category === $item['slug'] ? 'active' : '' ?>"
                    href="<?= BASE_URL ?>/products.php?category=<?= urlencode($item['slug']) ?><?= $promotion ? '&promotion=1' : '' ?>">
                    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                    <span><?= (int)$item['product_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form class="catalog-layout" method="get" action="<?= BASE_URL ?>/products.php">
            <?php if ($category !== ''): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>

            <aside class="catalog-filters">
                <div class="catalog-filter-heading">
                    <h2>
                        <span class="material-symbols-outlined" aria-hidden="true">filter_alt</span>
                        Bộ lọc
                    </h2>
                    <a href="<?= BASE_URL ?>/products.php<?= $category !== '' ? '?category=' . urlencode($category) : '' ?>">Xóa lọc</a>
                </div>

                <label class="catalog-filter-promo">
                    <input type="checkbox" name="promotion" value="1" <?= $promotion ? 'checked' : '' ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">sell</span>
                    <span>Chỉ xem ưu đãi</span>
                </label>

                <fieldset class="catalog-filter-group">
                    <legend>Mức giá</legend>
                    <?php foreach (
                        [
                            '' => 'Tất cả mức giá',
                            'under-15' => 'Dưới 15 triệu',
                            '15-25' => '15 - 25 triệu',
                            '25-35' => '25 - 35 triệu',
                            'over-35' => 'Trên 35 triệu',
                        ] as $value => $label
                    ): ?>
                        <label class="catalog-radio-option">
                            <input type="radio" name="price" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $priceBand === $value ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="catalog-filter-group">
                    <legend>Quãng đường / lần sạc</legend>
                    <?php foreach (
                        [
                            '' => 'Tất cả quãng đường',
                            '60-80' => '60 - 80 km',
                            '80-110' => '80 - 110 km',
                            'over-110' => 'Trên 110 km',
                        ] as $value => $label
                    ): ?>
                        <label class="catalog-radio-option">
                            <input type="radio" name="range" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $rangeBand === $value ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <button class="catalog-filter-submit" type="submit">
                    Áp dụng bộ lọc
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                </button>
            </aside>

            <section class="catalog-results" id="catalog-results">
                <div class="catalog-toolbar">
                    <div>
                        <strong><?= count($products) ?></strong>
                        <span><?= $promotion ? 'sản phẩm đang ưu đãi' : 'sản phẩm phù hợp' ?></span>
                    </div>
                    <div class="catalog-toolbar-controls">
                        <label for="catalog-sort">Sắp xếp</label>
                        <select id="catalog-sort" name="sort" onchange="this.form.submit()">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                            <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                            <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Ưu đãi cao nhất</option>
                        </select>
                    </div>
                    <label class="catalog-search">
                        <span class="material-symbols-outlined" aria-hidden="true">search</span>
                        <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm tên xe, mã sản phẩm...">
                    </label>
                </div>

                <?php if ($products === []): ?>
                    <div class="catalog-empty">
                        <span class="material-symbols-outlined" aria-hidden="true">search_off</span>
                        <h2>Không tìm thấy sản phẩm</h2>
                        <p>Thử thay đổi bộ lọc hoặc xem toàn bộ catalog.</p>
                        <a class="catalog-filter-submit" href="<?= BASE_URL ?>/products.php">Xem tất cả sản phẩm</a>
                    </div>
                <?php else: ?>
                    <div class="catalog-product-grid">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $price = (float)$product['price'];
                            $currentPrice = (float)$product['current_price'];
                            $discount = $price > 0 && $currentPrice < $price
                                ? (int)round((($price - $currentPrice) / $price) * 100)
                                : 0;
                            $imagePath = str_replace(chr(92), '/', (string)($product['image'] ?? ''));
                            $placeholderIcon = match ($product['category_slug']) {
                                'xe-dap-dien' => 'pedal_bike',
                                'pin-ac-quy' => 'battery_charging_full',
                                'phu-kien' => 'build',
                                default => 'electric_scooter',
                            };
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
                                        <span class="material-symbols-outlined" aria-hidden="true"><?= $placeholderIcon ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="catalog-product-content">
                                    <div class="catalog-product-meta">
                                        <span><?= htmlspecialchars($product['product_code'] ?? 'TIỆP ANH', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="catalog-stock <?= $product['status'] === 'active' && (int)$product['stock'] > 0 ? 'in-stock' : '' ?>">
                                            <?= $product['status'] === 'out_of_stock' ? 'Đang cập nhật' : ((int)$product['stock'] > 0 ? 'Có sẵn' : 'Hết hàng') ?>
                                        </span>
                                    </div>
                                    <h2><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <div class="catalog-spec-grid">
                                        <div><small>Động cơ</small><strong><?= htmlspecialchars($product['motor_power'] ?: 'Đang cập nhật', ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <div><small>Quãng đường</small><strong><?= $product['battery_range'] !== null ? (int)$product['battery_range'] . ' km' : 'Đang cập nhật' ?></strong></div>
                                        <div><small>Tốc độ tối đa</small><strong><?= $product['max_speed'] !== null ? (int)$product['max_speed'] . ' km/h' : 'Đang cập nhật' ?></strong></div>
                                    </div>
                                    <div class="catalog-price-row">
                                        <strong><?= $currentPrice > 0 ? number_format($currentPrice, 0, ',', '.') . '₫' : 'Liên hệ' ?></strong>
                                        <?php if ($discount > 0): ?>
                                            <del><?= number_format($price, 0, ',', '.') ?>₫</del>
                                        <?php endif; ?>
                                    </div>
                                    <div class="catalog-product-actions">
                                        <a href="<?= BASE_URL ?>/product-detail.php?id=<?= (int)$product['id'] ?>">Xem chi tiết</a>
                                        <button type="button" onclick="addToCart(<?= (int)$product['id'] ?>)" <?= $product['status'] !== 'active' || (int)$product['stock'] < 1 ? 'disabled' : '' ?>>
                                            <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
                                            Thêm giỏ
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </form>

        <section class="catalog-benefits" aria-label="Dịch vụ Tiệp Anh">
            <div><span class="material-symbols-outlined" aria-hidden="true">verified</span><strong>Cam kết chính hãng</strong><small>Hỗ trợ bảo hành theo chính sách sản phẩm</small></div>
            <div><span class="material-symbols-outlined" aria-hidden="true">local_shipping</span><strong>Hỗ trợ giao xe</strong><small>Tư vấn giao nhận theo khu vực</small></div>
            <div><span class="material-symbols-outlined" aria-hidden="true">support_agent</span><strong>Tư vấn tận tâm</strong><small>Liên hệ cửa hàng để được hỗ trợ</small></div>
        </section>
    </div>
</div>

<?php

require_once __DIR__ . '/../views/layouts/footer.php';

?>
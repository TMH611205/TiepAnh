<?php

$pageTitle = 'Chi tiết sản phẩm';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';


$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($id <= 0) {

    header(
        'Location: ' .
            BASE_URL .
            '/products.php'
    );

    exit;
}


$sql = "
    SELECT
        p.*,
        p.image AS image_url,
        " . Pricing::sqlCurrent() . " AS current_price,
        " . Pricing::sqlOld() . " AS old_price,
        c.name AS category_name,
        c.slug AS category_slug
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = :id
    LIMIT 1
";


$stmt = $pdo->prepare($sql);

$stmt->execute([
    'id' => $id
]);


$product = $stmt->fetch();


if (!$product) {

    http_response_code(404);

    $pageTitle = 'Không tìm thấy sản phẩm';

    require_once __DIR__ .
        '/../views/layouts/header.php';

?>

    <div class="product-not-found">

        <span class="material-symbols-outlined">
            inventory_2
        </span>

        <h1>
            Không tìm thấy sản phẩm
        </h1>

        <p>
            Sản phẩm bạn đang tìm kiếm
            không tồn tại hoặc đã được gỡ bỏ.
        </p>

        <a
            href="<?= BASE_URL ?>/products.php"
            class="btn-primary">
            Quay lại sản phẩm
        </a>

    </div>

<?php

    require_once __DIR__ .
        '/../views/layouts/footer.php';

    exit;
}


require_once __DIR__ .
    '/../views/layouts/header.php';

?>


<div class="product-detail-page">

    <div class="container">


        <!-- BREADCRUMB -->

        <div class="products-breadcrumb">

            <a href="<?= BASE_URL ?>/index.php">
                Trang chủ
            </a>

            <span class="material-symbols-outlined">
                chevron_right
            </span>

            <a href="<?= BASE_URL ?>/products.php">
                Sản phẩm
            </a>

            <span class="material-symbols-outlined">
                chevron_right
            </span>

            <strong>
                <?= htmlspecialchars(
                    $product['name']
                ) ?>
            </strong>

        </div>


        <!-- PRODUCT DETAIL -->

        <section class="product-detail">

            <!-- IMAGE -->

            <div class="product-detail-image">

                <div class="product-detail-image-inner media-fit"<?= !empty($product['image_url']) ? ' style="--media-image: url(\'' . htmlspecialchars(BASE_URL . '/../' . ltrim(str_replace(chr(92), '/', $product['image_url']), '/'), ENT_QUOTES, 'UTF-8') . '\')"' : '' ?>>

                    <?php if (!empty($product['image_url'])): ?>
                        <img
                            src="<?= BASE_URL ?>/../<?= htmlspecialchars(
                                                        str_replace(chr(92), '/', $product['image_url']),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                            alt="<?= htmlspecialchars(
                                        $product['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                    <?php else: ?>
                        <span class="material-symbols-outlined" aria-hidden="true">
                            <?= match ($product['category_slug']) {
                                'xe-dap-dien' => 'pedal_bike',
                                'pin-ac-quy' => 'battery_charging_full',
                                'phu-kien' => 'build',
                                default => 'electric_scooter',
                            } ?>
                        </span>
                    <?php endif; ?>

                </div>

            </div>


            <!-- INFORMATION -->

            <div class="product-detail-info">

                <span class="product-detail-category">

                    <?= htmlspecialchars(
                        $product['category_name']
                    ) ?>

                </span>


                <h1>

                    <?= htmlspecialchars(
                        $product['name']
                    ) ?>

                </h1>


                <div class="product-detail-price">

                    <strong>
                        <?= (float)$product['current_price'] > 0
                            ? number_format($product['current_price'], 0, ',', '.') . '₫'
                            : 'Liên hệ' ?>
                    </strong>


                    <?php if (!empty($product['old_price'])): ?>

                        <del>

                            <?= number_format(
                                $product['old_price'],
                                0,
                                ',',
                                '.'
                            ) ?>₫

                        </del>

                    <?php endif; ?>

                </div>


                <p class="product-detail-description">

                    <?= nl2br(htmlspecialchars(
                        $product['description'] ?: 'Xe điện Tiệp Anh chính hãng, thiết kế hiện đại, vận hành êm ái và phù hợp nhu cầu di chuyển hằng ngày.',
                        ENT_QUOTES,
                        'UTF-8'
                    )) ?>

                </p>


                <!-- SPECS -->

                <div class="detail-spec-grid">

                    <div>

                        <span class="material-symbols-outlined">
                            speed
                        </span>

                        <small>
                            Tốc độ tối đa
                        </small>

                        <strong>
                            <?= $product['max_speed'] !== null
                                ? (int)$product['max_speed'] . ' km/h'
                                : 'Đang cập nhật' ?>
                        </strong>

                    </div>


                    <div>

                        <span class="material-symbols-outlined">
                            battery_charging_full
                        </span>

                        <small>
                            Quãng đường
                        </small>

                        <strong>
                            <?= $product['battery_range'] !== null
                                ? (int)$product['battery_range'] . ' km/sạc'
                                : 'Đang cập nhật' ?>
                        </strong>

                    </div>


                    <div>

                        <span class="material-symbols-outlined">
                            electric_bolt
                        </span>

                        <small>
                            Động cơ
                        </small>

                        <strong>
                            <?= htmlspecialchars(
                                $product['motor_power'] ?? 'Đang cập nhật',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                    </div>


                    <div>

                        <span class="material-symbols-outlined">
                            verified
                        </span>

                        <small>
                            Bảo hành
                        </small>

                        <strong>
                            <?= (int)$product['warranty_months'] > 0
                                ? (int)$product['warranty_months'] . ' tháng'
                                : 'Đang cập nhật' ?>
                        </strong>

                    </div>

                </div>


                <!-- ACTION -->

                <?php if ($product['status'] === 'active' && (int)$product['stock'] > 0): ?>
                    <div class="product-detail-actions">

                        <button
                            type="button"
                            class="btn-add-cart"
                            onclick="addToCart(
                            <?= (int)$product['id'] ?>
                        )">

                            <span class="material-symbols-outlined">
                                shopping_cart
                            </span>

                            Thêm vào giỏ hàng

                        </button>


                        <a
                            href="<?= BASE_URL ?>/checkout.php?product_id=<?= (int)$product['id'] ?>"
                            class="btn-buy-now">

                            Mua ngay

                            <span class="material-symbols-outlined">
                                arrow_forward
                            </span>

                        </a>

                    </div>
                <?php else: ?>
                    <div class="product-unavailable" role="status">
                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                        <div>
                            <strong><?= $product['status'] === 'out_of_stock'
                                        ? 'Thông tin giá và tồn kho đang cập nhật'
                                        : 'Sản phẩm hiện đã hết hàng' ?></strong>
                            <small>Liên hệ cửa hàng để được xác nhận thông tin mới nhất.</small>
                        </div>
                        <a href="tel:<?= STORE_PHONE ?>">Liên hệ</a>
                    </div>
                <?php endif; ?>
                <!-- BENEFITS -->

                <div class="product-benefits">

                    <div>

                        <span class="material-symbols-outlined">
                            local_shipping
                        </span>

                        <div>

                            <strong>
                                Hỗ trợ giao xe
                            </strong>

                            <small>
                                Tư vấn giao nhận theo khu vực
                            </small>

                        </div>

                    </div>


                    <div>

                        <span class="material-symbols-outlined">
                            verified_user
                        </span>

                        <div>

                            <strong>
                                Chính sách bảo hành
                            </strong>

                            <small>
                                Hỗ trợ bảo hành chính hãng
                            </small>

                        </div>

                    </div>


                    <div>

                        <span class="material-symbols-outlined">
                            support_agent
                        </span>

                        <div>

                            <strong>
                                Hỗ trợ tư vấn
                            </strong>

                            <small>
                                Liên hệ Tiệp Anh khi cần hỗ trợ
                            </small>

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <?php require __DIR__ . '/../views/partials/product-details.php'; ?>

    </div>

</div>


<?php

require_once __DIR__ .
    '/../views/layouts/footer.php';

?>
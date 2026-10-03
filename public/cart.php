<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Product.php';

$cart = is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];

$updateCartCount = static function (array $items): int {
    $count = 0;

    foreach ($items as $quantity) {
        $count += max(0, (int)$quantity);
    }

    $_SESSION['cart_count'] = $count;

    return $count;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $isJsonRequest = str_contains(
        $_SERVER['CONTENT_TYPE'] ?? '',
        'application/json'
    );

    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Phiên làm việc hết hạn. Vui lòng tải lại trang.',
            ]);
            exit;
        }

        $productId = (int)($payload['product_id'] ?? 0);
        $productStatement = $pdo->prepare('
			SELECT id, stock, status
			FROM products
			WHERE id = :id
			LIMIT 1
		');
        $productStatement->execute(['id' => $productId]);
        $product = $productStatement->fetch();

        if (!$product || $product['status'] !== 'active') {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Sản phẩm hiện không khả dụng.',
            ]);
            exit;
        }

        $quantity = (int)($cart[$productId] ?? 0);

        if ((int)$product['stock'] <= $quantity) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Sản phẩm không còn đủ số lượng trong kho.',
            ]);
            exit;
        }

        $cart[$productId] = $quantity + 1;
        $_SESSION['cart'] = $cart;

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
            'cart_count' => $updateCartCount($cart),
        ]);
        exit;
    }

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['cart_message'] = 'Phiên làm việc hết hạn. Vui lòng thử lại.';
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }

    if (isset($_POST['remove_id'])) {
        unset($cart[(int)$_POST['remove_id']]);
        $_SESSION['cart_message'] = 'Đã xóa sản phẩm khỏi giỏ hàng.';
    } else {
        $lines = Product::findCartLines($pdo, $cart);
        $availableLines = [];

        foreach ($lines as $line) {
            $availableLines[(int)$line['id']] = $line;
        }

        foreach (($cart) as $productId => $currentQuantity) {
            $productId = (int)$productId;

            if (!isset($availableLines[$productId])) {
                continue;
            }

            $line = $availableLines[$productId];
            $requestedQuantity = max(
                1,
                (int)($_POST['quantities'][$productId] ?? $currentQuantity)
            );

            if ($line['status'] === 'active' && (int)$line['stock'] > 0) {
                $cart[$productId] = min(
                    $requestedQuantity,
                    (int)$line['stock'],
                    99
                );
            }
        }

        $_SESSION['cart_message'] = 'Giỏ hàng đã được cập nhật.';
    }

    $_SESSION['cart'] = $cart;
    $updateCartCount($cart);
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$lines = Product::findCartLines($pdo, $cart);
$knownProductIds = array_map(
    static fn(array $line): int => (int)$line['id'],
    $lines
);

foreach (array_keys($cart) as $productId) {
    if (!in_array((int)$productId, $knownProductIds, true)) {
        unset($cart[$productId]);
    }
}

$_SESSION['cart'] = $cart;
$cartCount = $updateCartCount($cart);
$subtotal = 0;
$canCheckout = $lines !== [];

foreach ($lines as $line) {
    $subtotal += (float)$line['line_total'];
    $canCheckout = $canCheckout &&
        $line['status'] === 'active' &&
        (int)$line['stock'] >= (int)$line['quantity'];
}

$message = $_SESSION['cart_message'] ?? null;
unset($_SESSION['cart_message']);
$pageTitle = 'Giỏ hàng';
require_once __DIR__ . '/../views/layouts/header.php';

?>

<div class="purchase-page">
    <div class="container purchase-container">
        <div class="purchase-heading">
            <div>
                <span class="purchase-eyebrow">TIẾP TỤC HÀNH TRÌNH</span>
                <h1>Giỏ hàng</h1>
            </div>
            <a class="text-link" href="<?= BASE_URL ?>/products.php">
                <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                Tiếp tục mua sắm
            </a>
        </div>

        <?php if ($message): ?>
            <p class="purchase-alert" role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($lines === []): ?>
            <section class="purchase-empty">
                <span class="material-symbols-outlined" aria-hidden="true">shopping_bag</span>
                <h2>Giỏ hàng đang trống</h2>
                <p>Chọn sản phẩm bạn quan tâm để bắt đầu đặt hàng.</p>
                <a class="purchase-button" href="<?= BASE_URL ?>/products.php">Khám phá sản phẩm</a>
            </section>
        <?php else: ?>
            <div class="purchase-layout">
                <form class="cart-lines" method="post" action="<?= BASE_URL ?>/cart.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <?php foreach ($lines as $line): ?>
                        <?php
                        $image = str_replace(chr(92), '/', (string)$line['image']);
                        $available = $line['status'] === 'active' &&
                            (int)$line['stock'] >= (int)$line['quantity'];
                        ?>
                        <article class="cart-line">
                            <div class="cart-product-image">
                                <?php if ($image !== ''): ?>
                                    <img src="<?= BASE_URL ?>/../<?= htmlspecialchars(ltrim($image, '/'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($line['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    <span class="material-symbols-outlined" aria-hidden="true">electric_scooter</span>
                                <?php endif; ?>
                            </div>

                            <div class="cart-product-copy">
                                <span class="purchase-eyebrow"><?= htmlspecialchars($line['product_code'] ?: 'TIỆP ANH', ENT_QUOTES, 'UTF-8') ?></span>
                                <h2><?= htmlspecialchars($line['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <strong><?= number_format($line['current_price'], 0, ',', '.') ?>₫</strong>
                                <?php if (!$available): ?>
                                    <small class="stock-warning">Sản phẩm hiện không đủ tồn kho.</small>
                                <?php else: ?>
                                    <small>Còn <?= (int)$line['stock'] ?> sản phẩm</small>
                                <?php endif; ?>
                            </div>

                            <div class="cart-line-total">
                                <label for="quantity-<?= (int)$line['id'] ?>">Số lượng</label>
                                <input
                                    id="quantity-<?= (int)$line['id'] ?>"
                                    name="quantities[<?= (int)$line['id'] ?>]"
                                    type="number"
                                    min="1"
                                    max="<?= max(1, min(99, (int)$line['stock'])) ?>"
                                    value="<?= (int)$line['quantity'] ?>"
                                    <?= (int)$line['stock'] < 1 ? 'disabled' : '' ?>>
                                <strong><?= number_format($line['line_total'], 0, ',', '.') ?>₫</strong>
                                <button class="remove-link" type="submit" name="remove_id" value="<?= (int)$line['id'] ?>" formnovalidate>Xóa</button>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <button class="secondary-button" type="submit" name="action" value="update">
                        <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
                        Cập nhật giỏ hàng
                    </button>
                </form>

                <aside class="purchase-summary">
                    <h2>Tóm tắt đơn hàng</h2>
                    <div class="summary-row">
                        <span>Tạm tính (<?= $cartCount ?> sản phẩm)</span>
                        <strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong>
                    </div>
                    <div class="summary-row">
                        <span>Giao hàng</span>
                        <strong>Miễn phí</strong>
                    </div>
                    <div class="summary-total">
                        <span>Tổng cộng</span>
                        <strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong>
                    </div>
                    <?php if ($canCheckout): ?>
                        <a class="purchase-button full-width" href="<?= BASE_URL ?>/checkout.php">Tiến hành đặt hàng</a>
                    <?php else: ?>
                        <button class="purchase-button full-width" type="button" disabled>Kiểm tra tồn kho</button>
                    <?php endif; ?>
                    <p class="summary-note">Tồn kho và giá bán sẽ được xác nhận lại khi đặt hàng.</p>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
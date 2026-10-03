<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';

$pageTitle = 'Thanh toán';
$customer = $_SESSION['checkout_customer'] ?? null;
$directProductId = (int)($_SESSION['checkout_product_id'] ?? 0);

if (!$customer) {
    header('Location: ' . BASE_URL . '/checkout.php');
    exit;
}

$checkoutCart = $directProductId > 0
    ? [$directProductId => 1]
    : ($_SESSION['cart'] ?? []);
$lines = Product::findCartLines($pdo, $checkoutCart);
$expectedProductCount = count(array_filter(
    $checkoutCart,
    static fn($quantity): bool => (int)$quantity > 0
));
$available = $lines !== [] && count($lines) === $expectedProductCount;
$subtotal = 0;

foreach ($lines as $line) {
    $subtotal += (float)$line['line_total'];
    $available = $available &&
        $line['status'] === 'active' &&
        (int)$line['stock'] >= (int)$line['quantity'];
}

$errors = [];
$selectedMethod = $_POST['payment_method'] ?? 'cod';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Phiên làm việc hết hạn. Vui lòng tải lại trang.';
    } elseif (!$available) {
        $errors[] = 'Tồn kho vừa thay đổi. Vui lòng kiểm tra lại giỏ hàng.';
    } elseif (!in_array($selectedMethod, ['cod', 'bank_transfer'], true)) {
        $errors[] = 'Vui lòng chọn phương thức thanh toán.';
    } else {
        try {
            $orderCode = Order::create(
                $pdo,
                $customer,
                $checkoutCart,
                $selectedMethod
            );

            $_SESSION['last_order_code'] = $orderCode;

            if ($directProductId === 0) {
                $_SESSION['cart'] = [];
                $_SESSION['cart_count'] = 0;
            }

            unset($_SESSION['checkout_customer'], $_SESSION['checkout_product_id']);

            header(
                'Location: ' . BASE_URL . '/order-success.php?code=' .
                    urlencode($orderCode)
            );
            exit;
        } catch (RuntimeException $error) {
            $errors[] = $error->getMessage();
        } catch (Throwable $error) {
            error_log($error->getMessage());
            $errors[] = 'Chưa thể tạo đơn hàng. Vui lòng thử lại sau.';
        }
    }
}

require_once __DIR__ . '/../views/layouts/header.php';

?>

<div class="purchase-page">
    <div class="container purchase-container">
        <div class="purchase-heading">
            <div>
                <span class="purchase-eyebrow">BƯỚC 2 / 2</span>
                <h1>Thanh toán</h1>
            </div>
            <a class="text-link" href="<?= BASE_URL ?>/checkout.php">
                <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                Sửa thông tin nhận hàng
            </a>
        </div>

        <?php foreach ($errors as $error): ?>
            <p class="purchase-alert" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endforeach; ?>

        <?php if ($available): ?>
            <form class="checkout-layout" method="post" action="<?= BASE_URL ?>/payment.php">
                <section class="checkout-form-section">
                    <h2>Chọn phương thức thanh toán</h2>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" <?= $selectedMethod === 'cod' ? 'checked' : '' ?>>
                        <span class="payment-option-icon material-symbols-outlined" aria-hidden="true">local_shipping</span>
                        <span class="payment-option-copy">
                            <strong>Thanh toán khi nhận hàng</strong>
                            <small>Thanh toán trực tiếp cho nhân viên giao xe.</small>
                        </span>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="bank_transfer" <?= $selectedMethod === 'bank_transfer' ? 'checked' : '' ?>>
                        <span class="payment-option-icon material-symbols-outlined" aria-hidden="true">account_balance</span>
                        <span class="payment-option-copy">
                            <strong>Chuyển khoản ngân hàng</strong>
                            <small>Đơn hàng sẽ chờ nhân viên liên hệ và xác nhận thanh toán.</small>
                        </span>
                    </label>

                    <div class="payment-note">
                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                        <p>Thông tin tài khoản nhận tiền sẽ được nhân viên xác nhận trực tiếp. Đơn chuyển khoản chưa được ghi nhận là đã thanh toán cho đến khi đối soát.</p>
                    </div>

                    <button class="purchase-button" type="submit" <?= !$available ? 'disabled' : '' ?>>
                        Xác nhận đặt hàng
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </button>
                </section>

                <aside class="purchase-summary">
                    <h2>Người nhận</h2>
                    <div class="recipient-summary">
                        <strong><?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($customer['address'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h2 class="summary-subheading">Đơn hàng</h2>
                    <?php foreach ($lines as $line): ?>
                        <div class="checkout-item">
                            <span><?= htmlspecialchars($line['name'], ENT_QUOTES, 'UTF-8') ?> <b>× <?= (int)$line['quantity'] ?></b></span>
                            <strong><?= number_format($line['line_total'], 0, ',', '.') ?>₫</strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row">
                        <span>Giao hàng</span>
                        <strong>Miễn phí</strong>
                    </div>
                    <div class="summary-total">
                        <span>Tổng thanh toán</span>
                        <strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong>
                    </div>
                </aside>
            </form>
        <?php else: ?>
            <section class="purchase-empty">
                <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                <h2>Sản phẩm vừa thay đổi tồn kho</h2>
                <p>Hãy quay lại giỏ hàng để kiểm tra số lượng trước khi đặt.</p>
                <a class="purchase-button" href="<?= BASE_URL ?>/cart.php">Quay lại giỏ hàng</a>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Order.php';

$orderCode = trim($_GET['code'] ?? '');
$order = $orderCode !== '' ? Order::findByCode($pdo, $orderCode) : null;

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy đơn hàng';
    require_once __DIR__ . '/../views/layouts/header.php';
?>
    <div class="purchase-page">
        <div class="container purchase-container">
            <section class="purchase-empty">
                <span class="material-symbols-outlined" aria-hidden="true">search_off</span>
                <h1>Không tìm thấy đơn hàng</h1>
                <p>Mã đơn hàng không hợp lệ hoặc không còn tồn tại.</p>
                <a class="purchase-button" href="<?= BASE_URL ?>/products.php">Quay lại mua sắm</a>
            </section>
        </div>
    </div>
<?php
    require_once __DIR__ . '/../views/layouts/footer.php';
    exit;
}

$pageTitle = 'Đặt hàng thành công';
$items = Order::findItems($pdo, (int)$order['id']);
$paymentIsComplete = $order['payment_status'] === 'paid';
$paymentMethodLabel = $order['payment_method'] === 'cod'
    ? 'Thanh toán khi nhận hàng'
    : 'Chuyển khoản ngân hàng';

require_once __DIR__ . '/../views/layouts/header.php';

?>

<div class="purchase-page success-page">
    <div class="container purchase-container">
        <section class="success-banner">
            <span class="success-icon material-symbols-outlined" aria-hidden="true">
                <?= $paymentIsComplete ? 'task_alt' : 'mark_email_read' ?>
            </span>
            <span class="purchase-eyebrow">TIỆP ANH ĐÃ NHẬN ĐƯỢC ĐƠN</span>
            <h1>Đặt hàng thành công</h1>
            <p>Đơn hàng đã được lưu và tồn kho đã cập nhật. Nhân viên sẽ liên hệ để xác nhận giao nhận.</p>
            <div class="order-code-label">MÃ ĐƠN HÀNG</div>
            <strong class="order-code-value"><?= htmlspecialchars($order['order_code'], ENT_QUOTES, 'UTF-8') ?></strong>
        </section>

        <div class="success-layout">
            <section class="success-details">
                <h2>Chi tiết đơn hàng</h2>
                <?php foreach ($items as $item): ?>
                    <div class="checkout-item">
                        <span><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?> <b>× <?= (int)$item['quantity'] ?></b></span>
                        <strong><?= number_format($item['total'], 0, ',', '.') ?>₫</strong>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span>Tổng cộng</span>
                    <strong><?= number_format($order['total_amount'], 0, ',', '.') ?>₫</strong>
                </div>
            </section>

            <aside class="purchase-summary success-payment-summary">
                <h2>Trạng thái thanh toán</h2>
                <div class="payment-status <?= $paymentIsComplete ? 'is-paid' : 'is-pending' ?>">
                    <span class="material-symbols-outlined" aria-hidden="true">
                        <?= $paymentIsComplete ? 'check_circle' : 'schedule' ?>
                    </span>
                    <strong><?= $paymentIsComplete ? 'Đã thanh toán' : 'Chờ thanh toán' ?></strong>
                </div>
                <p><?= htmlspecialchars($paymentMethodLabel, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (!$paymentIsComplete && $order['payment_method'] === 'bank_transfer'): ?>
                    <p class="summary-note">Nhân viên sẽ liên hệ cung cấp thông tin chuyển khoản và xác nhận sau khi đối soát.</p>
                <?php elseif (!$paymentIsComplete): ?>
                    <p class="summary-note">Bạn thanh toán cho nhân viên khi nhận hàng.</p>
                <?php endif; ?>
                <div class="recipient-summary">
                    <strong><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($order['customer_phone'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= htmlspecialchars($order['shipping_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </aside>
        </div>

        <div class="success-actions">
            <a class="purchase-button" href="<?= BASE_URL ?>/products.php">Tiếp tục mua sắm</a>
            <a class="secondary-button" href="<?= BASE_URL ?>/index.php">Về trang chủ</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
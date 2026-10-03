<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Product.php';

$pageTitle = 'Thông tin đặt hàng';
$errors = [];
$directProductId = 0;

if (isset($_GET['product_id'])) {
    $directProductId = (int)$_GET['product_id'];
    $_SESSION['checkout_product_id'] = $directProductId;
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $directProductId = (int)($_POST['product_id'] ?? 0);
} else {
    $directProductId = 0;
    unset($_SESSION['checkout_product_id']);
}

if ($directProductId > 0) {
    $_SESSION['checkout_product_id'] = $directProductId;
    $checkoutCart = [$directProductId => 1];
} else {
    unset($_SESSION['checkout_product_id']);
    $checkoutCart = $_SESSION['cart'] ?? [];
}

$lines = Product::findCartLines($pdo, $checkoutCart);
$validProductCount = count(array_filter(
    $checkoutCart,
    static fn($quantity): bool => (int)$quantity > 0
));
$available = $lines !== [] && count($lines) === $validProductCount;
$subtotal = 0;

foreach ($lines as $line) {
    $subtotal += (float)$line['line_total'];
    $available = $available &&
        $line['status'] === 'active' &&
        (int)$line['stock'] >= (int)$line['quantity'];
}

$customer = $_SESSION['checkout_customer'] ?? [
    'full_name' => $_SESSION['user']['full_name'] ?? '',
    'phone' => $_SESSION['user']['phone'] ?? '',
    'email' => $_SESSION['user']['email'] ?? '',
    'cccd' => '',
    'address' => '',
    'note' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Phiên làm việc hết hạn. Vui lòng tải lại trang.';
    }

    $customer = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'cccd' => trim($_POST['cccd'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'note' => trim($_POST['note'] ?? ''),
    ];

    if ($customer['full_name'] === '' || mb_strlen($customer['full_name']) > 150) {
        $errors[] = 'Vui lòng nhập họ tên hợp lệ.';
    }

    if (!preg_match('/^[0-9+().\s-]{8,20}$/', $customer['phone'])) {
        $errors[] = 'Vui lòng nhập số điện thoại hợp lệ.';
    }

    if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ để lập hóa đơn.';
    }

    if (!preg_match('/^[0-9]{12}$/', $customer['cccd'])) {
        $errors[] = 'CCCD phải gồm đúng 12 chữ số.';
    }

    if ($customer['address'] === '') {
        $errors[] = 'Vui lòng nhập địa chỉ nhận hàng.';
    }

    if (!$available) {
        $errors[] = 'Sản phẩm trong giỏ không còn đủ tồn kho. Vui lòng quay lại giỏ hàng.';
    }

    if ($errors === []) {
        $_SESSION['checkout_customer'] = $customer;
        header('Location: ' . BASE_URL . '/payment.php');
        exit;
    }
}

if (!$available) {
    $errors[] = 'Sản phẩm trong giỏ không còn đủ tồn kho. Vui lòng quay lại giỏ hàng.';
}

require_once __DIR__ . '/../views/layouts/header.php';

?>

<div class="purchase-page">
    <div class="container purchase-container">
        <div class="purchase-heading">
            <div>
                <span class="purchase-eyebrow">BƯỚC 1 / 2</span>
                <h1>Thông tin nhận hàng</h1>
            </div>
            <a class="text-link" href="<?= BASE_URL ?>/cart.php">
                <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                Quay lại giỏ hàng
            </a>
        </div>

        <?php foreach (array_unique($errors) as $error): ?>
            <p class="purchase-alert" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endforeach; ?>

        <?php if ($available): ?>
            <form class="checkout-layout" method="post" action="<?= BASE_URL ?>/checkout.php">
                <section class="checkout-form-section">
                    <h2>Thông tin người nhận</h2>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="product_id" value="<?= $directProductId ?>">

                    <label class="form-field">
                        <span>Họ và tên <b>*</b></span>
                        <input name="full_name" autocomplete="name" maxlength="150" required value="<?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <div class="form-field-grid">
                        <label class="form-field">
                            <span>Số điện thoại <b>*</b></span>
                            <input name="phone" type="tel" autocomplete="tel" minlength="8" maxlength="20" required value="<?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="form-field">
                            <span>Email <b>*</b></span>
                            <input name="email" type="email" autocomplete="email" maxlength="150" required value="<?= htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                    </div>

                    <label class="form-field">
                        <span>Số CCCD <b>*</b></span>
                        <input
                            name="cccd"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            minlength="12"
                            maxlength="12"
                            pattern="[0-9]{12}"
                            required
                            value="<?= htmlspecialchars($customer['cccd'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="form-field">
                        <span>Địa chỉ nhận hàng <b>*</b></span>
                        <textarea name="address" autocomplete="street-address" rows="3" required><?= htmlspecialchars($customer['address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>

                    <label class="form-field">
                        <span>Ghi chú đơn hàng</span>
                        <textarea name="note" rows="3" placeholder="Thời gian nhận hàng hoặc yêu cầu khác"><?= htmlspecialchars($customer['note'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>

                    <button class="purchase-button" type="submit">
                        Tiếp tục thanh toán
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </button>
                </section>

                <aside class="purchase-summary">
                    <h2>Đơn hàng của bạn</h2>
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
                        <span>Tổng cộng</span>
                        <strong><?= number_format($subtotal, 0, ',', '.') ?>₫</strong>
                    </div>
                    <p class="summary-note">Giá và tồn kho được xác nhận lại tại bước tạo đơn hàng.</p>
                </aside>
            </form>
        <?php else: ?>
            <section class="purchase-empty">
                <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                <h2>Không thể tiếp tục đặt hàng</h2>
                <p>Vui lòng kiểm tra lại sản phẩm và số lượng trong giỏ.</p>
                <a class="purchase-button" href="<?= BASE_URL ?>/cart.php">Kiểm tra giỏ hàng</a>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
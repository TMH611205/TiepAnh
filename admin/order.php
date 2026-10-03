<?php

require_once __DIR__ . '/../middleware/admin.php';

$admin = Auth::require($pdo);

$orderStatuses = order_status_labels();
$paymentStatuses = payment_status_labels();

/**
 * Cập nhật trạng thái đơn. Hủy đơn hoàn lại tồn kho (Order::create đã trừ khi đặt);
 * đơn đã hủy là trạng thái cuối để kho không bị cộng/trừ lặp.
 */
function update_order(PDO $pdo, int $orderId, string $orderStatus, string $paymentStatus): string
{
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();

        if (!$order) {
            throw new RuntimeException('Không tìm thấy đơn hàng.');
        }

        if ($order['order_status'] === 'cancelled' && $orderStatus !== 'cancelled') {
            throw new RuntimeException('Đơn đã hủy không thể mở lại. Hãy tạo đơn mới.');
        }

        if ($orderStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
            $items = $pdo->prepare('SELECT product_id, quantity FROM order_details WHERE order_id = :id');
            $items->execute(['id' => $orderId]);
            $restock = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :product_id');

            foreach ($items->fetchAll() as $item) {
                $restock->execute([
                    'quantity' => (int)$item['quantity'],
                    'product_id' => (int)$item['product_id'],
                ]);
            }
        }

        $pdo->prepare('UPDATE orders SET order_status = :order_status, payment_status = :payment_status WHERE id = :id')
            ->execute(['order_status' => $orderStatus, 'payment_status' => $paymentStatus, 'id' => $orderId]);

        $paymentRow = ['paid' => 'success', 'failed' => 'failed', 'pending' => 'pending', 'refunded' => 'failed'];
        $paidAt = $pdo->prepare('
            UPDATE payments
            SET status = :status,
                paid_at = CASE WHEN :is_paid = 1 THEN COALESCE(paid_at, NOW()) ELSE NULL END
            WHERE order_id = :id
        ');
        $paidAt->execute([
            'status' => $paymentRow[$paymentStatus],
            'is_paid' => $paymentStatus === 'paid' ? 1 : 0,
            'id' => $orderId,
        ]);

        $pdo->commit();

        return 'Đã cập nhật đơn ' . $order['order_code'] . '.';
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();

    $orderId = (int)($_POST['id'] ?? 0);
    $orderStatus = (string)($_POST['order_status'] ?? '');
    $paymentStatus = (string)($_POST['payment_status'] ?? '');

    if (!isset($orderStatuses[$orderStatus], $paymentStatuses[$paymentStatus])) {
        flash('error', 'Trạng thái không hợp lệ.');
    } else {
        try {
            flash('success', update_order($pdo, $orderId, $orderStatus, $paymentStatus));
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage());
        }
    }

    redirect(ADMIN_URL . '/order.php?id=' . $orderId);
}

$orderId = (int)($_GET['id'] ?? 0);

// ---------- Chi tiết đơn ----------
if ($orderId > 0) {
    $order = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
    $order->execute(['id' => $orderId]);
    $order = $order->fetch();

    if (!$order) {
        http_response_code(404);
        flash('error', 'Không tìm thấy đơn hàng.');
        redirect(ADMIN_URL . '/order.php');
    }

    $items = $pdo->prepare('SELECT * FROM order_details WHERE order_id = :id ORDER BY id');
    $items->execute(['id' => $orderId]);
    $items = $items->fetchAll();

    admin_page_header($admin, 'Đơn ' . $order['order_code'], 'order');
    $methods = ['cod' => 'Thanh toán khi nhận hàng', 'bank_transfer' => 'Chuyển khoản', 'online' => 'Thanh toán online'];
    $locked = $order['order_status'] === 'cancelled';
    ?>

    <p><a href="<?= ADMIN_URL ?>/order.php">&larr; Danh sách đơn hàng</a></p>

    <div class="admin-grid-2">
        <section class="admin-card">
            <h2>Thông tin khách hàng</h2>
            <dl class="admin-dl">
                <dt>Họ tên</dt><dd><?= e($order['customer_name']) ?></dd>
                <dt>Điện thoại</dt><dd><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></dd>
                <dt>Email</dt><dd><?= e($order['customer_email'] ?: '—') ?></dd>
                <dt>Địa chỉ giao hàng</dt><dd><?= e($order['shipping_address'] ?: '—') ?></dd>
                <dt>Ghi chú</dt><dd><?= e($order['note'] ?: '—') ?></dd>
                <dt>Ngày đặt</dt><dd><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></dd>
                <dt>Thanh toán</dt><dd><?= e($methods[$order['payment_method']] ?? $order['payment_method']) ?></dd>
            </dl>
        </section>

        <section class="admin-card">
            <h2>Cập nhật trạng thái</h2>
            <?php if ($locked) : ?>
                <p><?= order_status_badge($order['order_status']) ?> Đơn đã hủy, tồn kho đã được hoàn lại. Không thể thay đổi.</p>
            <?php else : ?>
                <form method="post" action="<?= ADMIN_URL ?>/order.php" class="admin-form-grid"
                    data-confirm="Lưu thay đổi trạng thái? Nếu chọn &quot;Đã hủy&quot;, hàng sẽ được hoàn lại kho và không thể mở lại.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                    <div class="admin-field full">
                        <label for="order_status">Trạng thái đơn</label>
                        <select class="admin-select" id="order_status" name="order_status">
                            <?php foreach ($orderStatuses as $value => $label) : ?>
                                <option value="<?= e($value) ?>" <?= $order['order_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field full">
                        <label for="payment_status">Trạng thái thanh toán</label>
                        <select class="admin-select" id="payment_status" name="payment_status">
                            <?php foreach ($paymentStatuses as $value => $label) : ?>
                                <option value="<?= e($value) ?>" <?= $order['payment_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full"><button type="submit" class="admin-button">Lưu thay đổi</button></div>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <section class="admin-card">
        <h2>Sản phẩm trong đơn</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Sản phẩm</th><th class="num">Đơn giá</th><th class="num">SL</th><th class="num">Thành tiền</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td class="num"><?= money($item['price']) ?></td>
                            <td class="num"><?= (int)$item['quantity'] ?></td>
                            <td class="num"><?= money($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td colspan="3" class="num">Tạm tính</td><td class="num"><?= money($order['subtotal']) ?></td></tr>
                    <tr><td colspan="3" class="num">Phí vận chuyển</td><td class="num"><?= money($order['shipping_fee']) ?></td></tr>
                    <tr><td colspan="3" class="num">Giảm giá</td><td class="num">-<?= money($order['discount']) ?></td></tr>
                    <tr><td colspan="3" class="num"><strong>Tổng cộng</strong></td><td class="num"><strong><?= money($order['total_amount']) ?></strong></td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <?php
    admin_page_footer();
    exit;
}

// ---------- Danh sách đơn ----------
$status = (string)($_GET['status'] ?? '');
$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1 = 1'];
$params = [];

if (isset($orderStatuses[$status])) {
    $where[] = 'order_status = :status';
    $params['status'] = $status;
}

if ($search !== '') {
    $where[] = '(order_code LIKE :q1 OR customer_name LIKE :q2 OR customer_phone LIKE :q3)';
    $params += ['q1' => '%' . $search . '%', 'q2' => '%' . $search . '%', 'q3' => '%' . $search . '%'];
}

$whereSql = implode(' AND ', $where);
$count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE $whereSql");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

$list = $pdo->prepare("
    SELECT id, order_code, customer_name, customer_phone, total_amount, order_status, payment_status, created_at
    FROM orders WHERE $whereSql ORDER BY id DESC " . (wants_export() ? 'LIMIT 5000' : "LIMIT $perPage OFFSET " . (($page - 1) * $perPage)));
$list->execute($params);
$orders = $list->fetchAll();

if (wants_export()) {
    $rows = [];
    foreach ($orders as $o) {
        $rows[] = [$o['order_code'], vn_datetime($o['created_at']), $o['customer_name'], (string)$o['customer_phone'],
            (float)$o['total_amount'], $orderStatuses[$o['order_status']], $paymentStatuses[$o['payment_status']]];
    }
    Xlsx::download('don-hang-' . date('Y-m-d') . '.xlsx', [[
        'name' => 'Đơn hàng',
        'headers' => ['Mã đơn', 'Ngày đặt', 'Khách hàng', 'Điện thoại', 'Tổng tiền', 'Trạng thái đơn', 'Thanh toán'],
        'rows' => $rows,
    ]]);
}

$queryBase = http_build_query(array_filter(['status' => $status, 'q' => $search]));

admin_page_header($admin, 'Đơn hàng', 'order');

?>

<section class="admin-card">
    <form method="get" class="admin-filters">
        <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Tìm mã đơn, tên, số điện thoại" aria-label="Tìm đơn hàng">
        <select class="admin-select" name="status" aria-label="Lọc theo trạng thái">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($orderStatuses as $value => $label) : ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-button">Lọc</button>
        <?= export_button() ?>
    </form>

    <?php if ($orders === []) : ?>
        <p class="admin-empty">Không có đơn hàng phù hợp.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th><th>Khách hàng</th><th class="num">Tổng tiền</th>
                        <th>Đơn hàng</th><th>Thanh toán</th><th>Ngày đặt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td><a href="<?= ADMIN_URL ?>/order.php?id=<?= (int)$order['id'] ?>"><?= e($order['order_code']) ?></a></td>
                            <td><?= e($order['customer_name']) ?><br><small><?= e($order['customer_phone']) ?></small></td>
                            <td class="num"><?= money($order['total_amount']) ?></td>
                            <td><?= order_status_badge($order['order_status']) ?></td>
                            <td><?= payment_status_badge($order['payment_status']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1) : ?>
            <nav class="admin-pagination" aria-label="Phân trang">
                <?php for ($i = 1; $i <= $pages; $i++) : ?>
                    <?php if ($i === $page) : ?>
                        <span class="current" aria-current="page"><?= $i ?></span>
                    <?php else : ?>
                        <a href="?<?= e($queryBase . ($queryBase ? '&' : '') . 'page=' . $i) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

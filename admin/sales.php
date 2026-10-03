<?php

require_once __DIR__ . '/../middleware/admin.php';

$admin = Auth::require($pdo);

$orderStatuses = order_status_labels();
$methods = ['cod' => 'Thanh toán khi nhận hàng', 'bank_transfer' => 'Chuyển khoản', 'online' => 'Thanh toán online'];

// ---------- Chi tiết / in phiếu bán hàng ----------
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId > 0) {
    $order = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
    $order->execute(['id' => $orderId]);
    $order = $order->fetch();

    if (!$order) {
        flash('error', 'Không tìm thấy chứng từ.');
        redirect(ADMIN_URL . '/sales.php');
    }

    $items = $pdo->prepare('SELECT * FROM order_details WHERE order_id = :id ORDER BY id');
    $items->execute(['id' => $orderId]);
    $items = $items->fetchAll();

    if (wants_export()) {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [$index + 1, $item['product_name'], (int)$item['quantity'], (float)$item['price'], (float)$item['total']];
        }
        $rows[] = ['', 'Phí vận chuyển', '', '', (float)$order['shipping_fee']];
        $rows[] = ['', 'Giảm giá', '', '', -(float)$order['discount']];
        $rows[] = ['', 'Tổng cộng', '', '', (float)$order['total_amount']];
        Xlsx::download('phieu-ban-hang-' . $order['order_code'] . '.xlsx', [[
            'name' => 'Phiếu bán hàng', 'headers' => ['STT', 'Sản phẩm', 'Số lượng', 'Đơn giá', 'Thành tiền'], 'rows' => $rows,
        ]]);
    }

    admin_page_header($admin, 'Phiếu bán hàng ' . $order['order_code'], 'sales');
    ?>

    <p class="no-print"><a href="<?= ADMIN_URL ?>/sales.php">&larr; Chứng từ bán hàng</a></p>

    <section class="admin-card admin-document">
        <header class="admin-document-head">
            <div>
                <strong><?= e(APP_NAME) ?></strong><br>
                <?= e(STORE_ADDRESS) ?><br>
                Điện thoại: <?= e(STORE_PHONE) ?>
            </div>
            <div class="admin-document-title">
                <h2>PHIẾU BÁN HÀNG</h2>
                Số: <strong><?= e($order['order_code']) ?></strong><br>
                Ngày: <?= e(vn_datetime($order['created_at'])) ?><br>
                <?= order_status_badge($order['order_status']) ?>
            </div>
        </header>

        <dl class="admin-dl">
            <dt>Khách hàng</dt><dd><?= e($order['customer_name']) ?></dd>
            <dt>Điện thoại</dt><dd><?= e($order['customer_phone']) ?></dd>
            <dt>Địa chỉ giao hàng</dt><dd><?= e($order['shipping_address'] ?: '—') ?></dd>
            <dt>Thanh toán</dt><dd><?= e($methods[$order['payment_method']] ?? $order['payment_method']) ?> · <?= payment_status_badge($order['payment_status']) ?></dd>
        </dl>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>STT</th><th>Sản phẩm</th><th class="num">Số lượng</th><th class="num">Đơn giá</th><th class="num">Thành tiền</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $index => $item) : ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($item['product_name']) ?></td>
                            <td class="num"><?= (int)$item['quantity'] ?></td>
                            <td class="num"><?= money($item['price']) ?></td>
                            <td class="num"><?= money($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td colspan="4" class="num">Phí vận chuyển</td><td class="num"><?= money($order['shipping_fee']) ?></td></tr>
                    <tr><td colspan="4" class="num">Giảm giá</td><td class="num">-<?= money($order['discount']) ?></td></tr>
                    <tr><td colspan="4" class="num"><strong>Tổng cộng</strong></td><td class="num"><strong><?= money($order['total_amount']) ?></strong></td></tr>
                </tbody>
            </table>
        </div>

        <div class="admin-signatures">
            <div><strong>Người lập phiếu</strong><span>(Ký, ghi rõ họ tên)</span></div>
            <div><strong>Thủ kho</strong><span>(Ký, ghi rõ họ tên)</span></div>
            <div><strong>Khách hàng</strong><span>(Ký, ghi rõ họ tên)</span></div>
        </div>
    </section>

    <div class="admin-actions no-print">
        <button type="button" class="admin-button" onclick="window.print()">
            <span class="material-symbols-outlined" aria-hidden="true">print</span> In phiếu
        </button>
        <?= export_button() ?>
        <a class="admin-button ghost" href="<?= ADMIN_URL ?>/order.php?id=<?= (int)$order['id'] ?>">Xử lý đơn hàng</a>
    </div>

    <?php
    admin_page_footer();
    exit;
}

// ---------- Danh sách ----------
$range = date_range();
$status = (string)($_GET['status'] ?? '');
$search = trim((string)($_GET['q'] ?? ''));
$where = ['o.created_at >= :from_date', 'o.created_at < :to_date'];
$params = ['from_date' => $range['from'], 'to_date' => $range['end_exclusive']];

if (isset($orderStatuses[$status])) {
    $where[] = 'o.order_status = :status';
    $params['status'] = $status;
}
if ($search !== '') {
    $where[] = '(o.order_code LIKE :q1 OR o.customer_name LIKE :q2 OR o.customer_phone LIKE :q3)';
    $params += ['q1' => '%' . $search . '%', 'q2' => '%' . $search . '%', 'q3' => '%' . $search . '%'];
}

$list = $pdo->prepare('
    SELECT o.id, o.order_code, o.customer_name, o.customer_phone, o.total_amount, o.order_status, o.payment_status, o.created_at,
        (SELECT COALESCE(SUM(quantity), 0) FROM order_details d WHERE d.order_id = o.id) AS qty
    FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY o.id DESC LIMIT 500');
$list->execute($params);
$sales = $list->fetchAll();
$revenue = array_sum(array_map(static fn(array $s): float => $s['order_status'] === 'cancelled' ? 0.0 : (float)$s['total_amount'], $sales));

if (wants_export()) {
    $rows = [];
    foreach ($sales as $s) {
        $rows[] = [$s['order_code'], vn_datetime($s['created_at']), $s['customer_name'], (string)$s['customer_phone'],
            (int)$s['qty'], (float)$s['total_amount'], order_status_labels()[$s['order_status']], payment_status_labels()[$s['payment_status']]];
    }
    Xlsx::download('chung-tu-ban-hang-' . $range['from'] . '-' . $range['to'] . '.xlsx', [[
        'name' => 'Chứng từ bán hàng',
        'headers' => ['Số phiếu', 'Ngày', 'Khách hàng', 'Điện thoại', 'Tổng số lượng', 'Tổng tiền', 'Trạng thái đơn', 'Thanh toán'],
        'rows' => $rows,
    ]]);
}

admin_page_header($admin, 'Chứng từ bán hàng', 'sales');

?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2><?= count($sales) ?> phiếu · doanh thu <?= money($revenue) ?></h2>
        <?= export_button() ?>
    </div>
    <p style="margin-top: 0; color: var(--text-muted);">Mỗi đơn hàng là một phiếu bán hàng. Doanh thu không tính đơn đã hủy.</p>

    <form method="get" class="admin-filters">
        <?= date_range_fields($range) ?>
        <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Số phiếu, tên, điện thoại" aria-label="Tìm phiếu bán hàng">
        <select class="admin-select" name="status" aria-label="Trạng thái">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($orderStatuses as $value => $text) : ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($text) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-button">Lọc</button>
    </form>

    <?php if ($sales === []) : ?>
        <p class="admin-empty">Chưa có phiếu bán hàng nào trong khoảng này.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Số phiếu</th><th>Ngày</th><th>Khách hàng</th><th class="num">SL</th><th class="num">Tổng tiền</th><th>Đơn hàng</th><th>Thanh toán</th></tr></thead>
                <tbody>
                    <?php foreach ($sales as $s) : ?>
                        <tr>
                            <td><a href="<?= ADMIN_URL ?>/sales.php?id=<?= (int)$s['id'] ?>"><?= e($s['order_code']) ?></a></td>
                            <td><?= e(vn_datetime($s['created_at'])) ?></td>
                            <td><?= e($s['customer_name']) ?><br><small><?= e($s['customer_phone']) ?></small></td>
                            <td class="num"><?= (int)$s['qty'] ?></td>
                            <td class="num"><?= money($s['total_amount']) ?></td>
                            <td><?= order_status_badge($s['order_status']) ?></td>
                            <td><?= payment_status_badge($s['payment_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

<?php

require_once __DIR__ . '/../middleware/admin.php';

$admin = Auth::require($pdo);

$search = trim((string)($_GET['q'] ?? ''));
$params = [];
$where = '1 = 1';

if ($search !== '') {
    $where = '(c.full_name LIKE :q1 OR c.phone LIKE :q2 OR c.email LIKE :q3)';
    $params = ['q1' => '%' . $search . '%', 'q2' => '%' . $search . '%', 'q3' => '%' . $search . '%'];
}

$list = $pdo->prepare("
    SELECT c.id, c.full_name, c.phone, c.email, c.address, c.created_at,
        COUNT(o.id) AS order_count,
        COALESCE(SUM(CASE WHEN o.order_status <> 'cancelled' THEN o.total_amount END), 0) AS total_spent
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
    WHERE $where
    GROUP BY c.id, c.full_name, c.phone, c.email, c.address, c.created_at
    ORDER BY c.id DESC
    LIMIT 200
");
$list->execute($params);
$customers = $list->fetchAll();

if (wants_export()) {
    $rows = [];
    foreach ($customers as $c) {
        $rows[] = [$c['full_name'], (string)$c['phone'], (string)$c['email'], (string)$c['address'],
            (int)$c['order_count'], (float)$c['total_spent'], vn_datetime($c['created_at'])];
    }
    Xlsx::download('khach-hang-' . date('Y-m-d') . '.xlsx', [[
        'name' => 'Khách hàng',
        'headers' => ['Họ tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Số đơn', 'Tổng mua', 'Ngày tạo'],
        'rows' => $rows,
    ]]);
}

admin_page_header($admin, 'Khách hàng', 'customers');

?>

<section class="admin-card">
    <form method="get" class="admin-filters">
        <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Tìm tên, số điện thoại, email" aria-label="Tìm khách hàng">
        <button type="submit" class="admin-button">Tìm</button>
        <?= export_button() ?>
    </form>

    <?php if ($customers === []) : ?>
        <p class="admin-empty">Chưa có khách hàng nào.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Khách hàng</th><th>Liên hệ</th><th>Địa chỉ</th><th class="num">Số đơn</th><th class="num">Đã mua</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer) : ?>
                        <tr>
                            <td><strong><?= e($customer['full_name']) ?></strong><br><small>Từ <?= e(date('d/m/Y', strtotime($customer['created_at']))) ?></small></td>
                            <td><a href="tel:<?= e($customer['phone']) ?>"><?= e($customer['phone']) ?></a><br><small><?= e($customer['email'] ?: '') ?></small></td>
                            <td><?= e($customer['address'] ?: '—') ?></td>
                            <td class="num"><?= (int)$customer['order_count'] ?></td>
                            <td class="num"><?= money($customer['total_spent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

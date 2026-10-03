<?php

require_once __DIR__ . '/../middleware/admin.php';

$admin = Auth::require($pdo);

$statusLabels = [
    'draft' => ['Nháp', 'muted'],
    'pending' => ['Chờ phát hành', 'warn'],
    'processing' => ['Đang xử lý', 'info'],
    'issued' => ['Đã phát hành', 'ok'],
    'sent' => ['Đã gửi khách', 'ok'],
    'failed' => ['Lỗi', 'bad'],
];

$invoices = $pdo->query('
    SELECT i.id, i.order_id, i.invoice_number, i.customer_name, i.total_amount, i.status, i.created_at, o.order_code
    FROM invoices i LEFT JOIN orders o ON o.id = i.order_id
    ORDER BY i.id DESC LIMIT 200
')->fetchAll();

if (wants_export()) {
    $rows = [];
    foreach ($invoices as $i) {
        $rows[] = [(string)($i['invoice_number'] ?: '#' . $i['id']), (string)$i['order_code'], (string)$i['customer_name'],
            (float)$i['total_amount'], $statusLabels[$i['status']][0] ?? $i['status'], vn_datetime($i['created_at'])];
    }
    Xlsx::download('hoa-don-' . date('Y-m-d') . '.xlsx', [[
        'name' => 'Hóa đơn',
        'headers' => ['Số hóa đơn', 'Đơn hàng', 'Khách hàng', 'Tổng tiền', 'Trạng thái', 'Ngày tạo'],
        'rows' => $rows,
    ]]);
}

admin_page_header($admin, 'Hóa đơn', 'invoices');

?>

<section class="admin-card">
    <div class="admin-card-head"><h2><?= count($invoices) ?> hóa đơn</h2><?= export_button() ?></div>
    <p style="margin-top: 0; color: var(--text-muted);">
        Hóa đơn nháp được tạo tự động cùng đơn hàng. Chức năng phát hành hóa đơn điện tử chưa được kết nối.
    </p>

    <?php if ($invoices === []) : ?>
        <p class="admin-empty">Chưa có hóa đơn nào.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Số hóa đơn</th><th>Đơn hàng</th><th>Khách hàng</th><th class="num">Tổng tiền</th><th>Trạng thái</th><th>Ngày tạo</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice) : ?>
                        <?php [$label, $tone] = $statusLabels[$invoice['status']] ?? [$invoice['status'], 'muted']; ?>
                        <tr>
                            <td><?= e($invoice['invoice_number'] ?: '#' . $invoice['id']) ?></td>
                            <td><a href="<?= ADMIN_URL ?>/order.php?id=<?= (int)$invoice['order_id'] ?>"><?= e($invoice['order_code'] ?: '—') ?></a></td>
                            <td><?= e($invoice['customer_name']) ?></td>
                            <td class="num"><?= money($invoice['total_amount']) ?></td>
                            <td><?= badge($label, $tone) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($invoice['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

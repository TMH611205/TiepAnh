<?php

require_once __DIR__ . '/../middleware/admin.php';
require_once __DIR__ . '/../models/Report.php';

$admin = Auth::require($pdo);

const LOW_STOCK_LIMIT = 3;

$range = date_range();
$params = ['from' => $range['from'], 'to' => $range['to']];
$days = (int)((strtotime($range['to']) - strtotime($range['from'])) / 86400) + 1;

$revenueReport = Report::run($pdo, 'revenue_by_period', $params + ['group' => $days > 92 ? 'month' : 'day']);
$topProducts = Report::run($pdo, 'top_products', $params + ['limit' => 5]);
$byCategory = Report::run($pdo, 'sales_by_category', $params);
$byStatus = Report::run($pdo, 'orders_by_status', $params);
$profit = Report::run($pdo, 'profit_estimate', $params);
$lowStockReport = Report::run($pdo, 'low_stock', ['threshold' => LOW_STOCK_LIMIT]);

$periodStats = $pdo->prepare("
    SELECT COUNT(*) AS orders,
        COALESCE(SUM(CASE WHEN order_status <> 'cancelled' THEN total_amount END), 0) AS revenue,
        COALESCE(SUM(order_status = 'cancelled'), 0) AS cancelled
    FROM orders WHERE created_at >= :from_date AND created_at < :to_date");
$periodStats->execute(['from_date' => $range['from'], 'to_date' => $range['end_exclusive']]);
$periodStats = $periodStats->fetch();

$pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$inventory = $pdo->query("SELECT COALESCE(SUM(stock), 0) AS qty, COALESCE(SUM(stock * cost_price), 0) AS value FROM products WHERE status <> 'hidden'")->fetch();
$grossProfit = (float)$profit['rows'][2][1];
$purchaseSpend = (float)$profit['rows'][3][1];

if (wants_export()) {
    $strip = static function (array $report): array {
        return ['name' => $report['title'], 'headers' => $report['headers'], 'rows' => $report['rows']];
    };
    $summaryRows = [
        ['Khoảng thời gian', $range['from'] . ' → ' . $range['to']],
        ['Số đơn hàng', (int)$periodStats['orders']],
        ['Đơn đã hủy', (int)$periodStats['cancelled']],
        ['Doanh thu', (float)$periodStats['revenue']],
        ['Lãi gộp ước tính', $grossProfit],
        ['Chi nhập hàng', $purchaseSpend],
        ['Đơn chờ xác nhận (hiện tại)', $pending],
        ['Tổng tồn kho (hiện tại)', (int)$inventory['qty']],
        ['Giá trị tồn kho theo giá vốn (hiện tại)', (float)$inventory['value']],
    ];
    Xlsx::download('thong-ke-' . $range['from'] . '-' . $range['to'] . '.xlsx', [
        ['name' => 'Tổng quan', 'headers' => ['Chỉ tiêu', 'Giá trị'], 'rows' => $summaryRows],
        $strip($revenueReport),
        $strip($topProducts),
        $strip($byCategory),
        $strip($byStatus),
        $strip($lowStockReport),
    ]);
}

// Đủ các ngày/tháng trong khoảng (kể cả không có đơn) để biểu đồ liền mạch.
$series = [];
foreach ($revenueReport['rows'] as $row) {
    if ($row[0] !== 'Tổng cộng') {
        $series[$row[0]] = ['orders' => $row[1], 'revenue' => $row[2]];
    }
}
$points = [];
if ($days <= 92) {
    for ($i = 0; $i < $days; $i++) {
        $key = date('Y-m-d', strtotime($range['from'] . " +$i days"));
        $points[] = ['label' => date('d/m', strtotime($key)), 'revenue' => $series[$key]['revenue'] ?? 0.0, 'orders' => $series[$key]['orders'] ?? 0];
    }
} else {
    foreach ($series as $key => $value) {
        $points[] = ['label' => date('m/Y', strtotime($key . '-01')), 'revenue' => $value['revenue'], 'orders' => $value['orders']];
    }
}
$maxRevenue = max(1.0, ...array_map(static fn(array $p): float => (float)$p['revenue'], $points ?: [['revenue' => 0]]));

$recentOrders = $pdo->query('
    SELECT id, order_code, customer_name, total_amount, order_status, created_at
    FROM orders ORDER BY id DESC LIMIT 6
')->fetchAll();

$quick = [7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày', 365 => '12 tháng'];

admin_page_header($admin, 'Dashboard thống kê', 'index');

?>

<section class="admin-card">
    <div class="admin-card-head">
        <div class="admin-range-links" aria-label="Chọn nhanh khoảng thời gian">
            <?php foreach ($quick as $n => $text) : ?>
                <?php $isActive = $range['to'] === date('Y-m-d') && $days === $n; ?>
                <a href="?from=<?= e(date('Y-m-d', strtotime('-' . ($n - 1) . ' days'))) ?>&amp;to=<?= e(date('Y-m-d')) ?>" class="<?= $isActive ? 'active' : '' ?>"><?= e($text) ?></a>
            <?php endforeach; ?>
        </div>
        <?= export_button() ?>
    </div>
    <form method="get" class="admin-filters" style="margin-bottom: 0;">
        <?= date_range_fields($range) ?>
        <button type="submit" class="admin-button">Xem</button>
    </form>
</section>

<section class="admin-stats">
    <div class="admin-stat">
        <span class="label">Doanh thu (<?= $days ?> ngày)</span>
        <strong><?= money($periodStats['revenue']) ?></strong>
    </div>
    <div class="admin-stat">
        <span class="label">Số đơn · đã hủy</span>
        <strong><?= (int)$periodStats['orders'] ?> · <?= (int)$periodStats['cancelled'] ?></strong>
    </div>
    <div class="admin-stat">
        <span class="label">Lãi gộp ước tính</span>
        <strong><?= money($grossProfit) ?></strong>
    </div>
    <div class="admin-stat">
        <span class="label">Chi nhập hàng</span>
        <strong><?= money($purchaseSpend) ?></strong>
    </div>
    <div class="admin-stat <?= $pending > 0 ? 'warn' : '' ?>">
        <span class="label">Đơn chờ xác nhận</span>
        <strong><?= $pending ?></strong>
    </div>
    <div class="admin-stat <?= count($lowStockReport['rows']) > 1 ? 'warn' : '' ?>">
        <span class="label">Sắp hết hàng (≤ <?= LOW_STOCK_LIMIT ?>)</span>
        <strong><?= max(0, count($lowStockReport['rows']) - 1) ?></strong>
    </div>
    <div class="admin-stat">
        <span class="label">Tồn kho (số lượng)</span>
        <strong><?= number_format((int)$inventory['qty'], 0, ',', '.') ?></strong>
    </div>
    <div class="admin-stat">
        <span class="label">Giá trị tồn (giá vốn)</span>
        <strong><?= money($inventory['value']) ?></strong>
    </div>
</section>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Doanh thu theo <?= $days > 92 ? 'tháng' : 'ngày' ?></h2>
        <span class="ai-note">Cao nhất: <?= money($maxRevenue > 1 ? $maxRevenue : 0) ?></span>
    </div>
    <?php if ($points === []) : ?>
        <p class="admin-empty">Chưa có dữ liệu trong khoảng này.</p>
    <?php else : ?>
        <div class="chart-bars" role="img" aria-label="Biểu đồ cột doanh thu theo <?= $days > 92 ? 'tháng' : 'ngày' ?>">
            <?php foreach ($points as $point) : ?>
                <div class="chart-bar" title="<?= e($point['label'] . ': ' . money($point['revenue']) . ' (' . $point['orders'] . ' đơn)') ?>">
                    <span style="height: <?= round(max(1, ((float)$point['revenue'] / $maxRevenue) * 100), 1) ?>%"></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="chart-axis">
            <span><?= e($points[0]['label']) ?></span>
            <span><?= e($points[count($points) - 1]['label']) ?></span>
        </div>
    <?php endif; ?>
</section>

<div class="admin-grid-2">
    <section class="admin-card">
        <h2>Top 5 sản phẩm bán chạy</h2>
        <?php $topRows = array_filter($topProducts['rows'], static fn(array $r): bool => $r[0] !== 'Tổng cộng'); ?>
        <?php if ($topRows === []) : ?>
            <p class="admin-empty">Chưa có đơn hàng trong khoảng này.</p>
        <?php else : ?>
            <?php $maxQty = max(1, ...array_map(static fn(array $r): int => (int)$r[1], $topRows)); ?>
            <div class="hbar-list">
                <?php foreach ($topRows as $row) : ?>
                    <div class="hbar-row">
                        <div class="hbar-label"><span><?= e($row[0]) ?></span><strong><?= (int)$row[1] ?> xe · <?= money($row[2]) ?></strong></div>
                        <div class="hbar-track"><div class="hbar-fill" style="width: <?= round(((int)$row[1] / $maxQty) * 100) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <h2>Đơn hàng theo trạng thái</h2>
        <?php $statusRows = array_filter($byStatus['rows'], static fn(array $r): bool => $r[0] !== 'Tổng cộng'); ?>
        <?php if ($statusRows === []) : ?>
            <p class="admin-empty">Chưa có đơn hàng trong khoảng này.</p>
        <?php else : ?>
            <?php $maxOrders = max(1, ...array_map(static fn(array $r): int => (int)$r[1], $statusRows)); ?>
            <div class="hbar-list">
                <?php foreach ($statusRows as $row) : ?>
                    <div class="hbar-row">
                        <div class="hbar-label"><span><?= e($row[0]) ?></span><strong><?= (int)$row[1] ?> đơn</strong></div>
                        <div class="hbar-track"><div class="hbar-fill" style="width: <?= round(((int)$row[1] / $maxOrders) * 100) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Đơn hàng mới nhất</h2>
            <a class="admin-button ghost small" href="<?= ADMIN_URL ?>/order.php">Xem tất cả</a>
        </div>
        <?php if ($recentOrders === []) : ?>
            <p class="admin-empty">Chưa có đơn hàng nào.</p>
        <?php else : ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th class="num">Tổng tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order) : ?>
                            <tr>
                                <td><a href="<?= ADMIN_URL ?>/order.php?id=<?= (int)$order['id'] ?>"><?= e($order['order_code']) ?></a></td>
                                <td><?= e($order['customer_name']) ?></td>
                                <td class="num"><?= money($order['total_amount']) ?></td>
                                <td><?= order_status_badge($order['order_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Cần nhập thêm hàng</h2>
            <a class="admin-button ghost small" href="<?= ADMIN_URL ?>/purchases.php?action=new">Lập phiếu nhập</a>
        </div>
        <?php $lowRows = array_filter($lowStockReport['rows'], static fn(array $r): bool => $r[0] !== 'Tổng cộng'); ?>
        <?php if ($lowRows === []) : ?>
            <p class="admin-empty">Tồn kho đang ổn.</p>
        <?php else : ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <tbody>
                        <?php foreach (array_slice($lowRows, 0, 8) as $row) : ?>
                            <tr>
                                <td><?= e($row[0]) ?></td>
                                <td class="num"><?= (int)$row[2] === 0 ? badge('Hết hàng', 'bad') : badge('Còn ' . (int)$row[2], 'warn') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php admin_page_footer(); ?>

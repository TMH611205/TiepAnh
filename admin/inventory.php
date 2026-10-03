<?php

require_once __DIR__ . '/../middleware/admin.php';

$admin = Auth::require($pdo);

const LOW_STOCK_LIMIT = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();

    $id = (int)($_POST['id'] ?? 0);
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]);

    if ($id <= 0 || $stock === false) {
        flash('error', 'Số lượng tồn kho không hợp lệ.');
    } else {
        // Hết hàng/đang bán tự đồng bộ theo tồn kho; sản phẩm đã ẩn giữ nguyên trạng thái.
        $pdo->prepare("
            UPDATE products
            SET stock = :stock,
                status = CASE
                    WHEN status = 'hidden' THEN status
                    WHEN :stock_check = 0 THEN 'out_of_stock'
                    ELSE 'active'
                END
            WHERE id = :id
        ")->execute(['stock' => $stock, 'stock_check' => $stock, 'id' => $id]);
        flash('success', 'Đã cập nhật tồn kho.');
    }

    redirect(ADMIN_URL . '/inventory.php' . (!empty($_POST['filter']) ? '?filter=' . urlencode((string)$_POST['filter']) : ''));
}

// ---------- Thẻ kho của một sản phẩm ----------
$cardId = (int)($_GET['card'] ?? 0);

if ($cardId > 0) {
    $product = $pdo->prepare('SELECT id, name, product_code, stock, cost_price FROM products WHERE id = :id');
    $product->execute(['id' => $cardId]);
    $product = $product->fetch();

    if (!$product) {
        flash('error', 'Không tìm thấy sản phẩm.');
        redirect(ADMIN_URL . '/inventory.php');
    }

    $movements = $pdo->prepare("
        SELECT r.created_at AS at, 'Nhập hàng' AS kind, r.receipt_code AS doc, i.quantity AS qty_in, 0 AS qty_out
        FROM purchase_receipt_items i JOIN purchase_receipts r ON r.id = i.receipt_id
        WHERE i.product_id = :p1 AND r.status = 'completed'
        UNION ALL
        SELECT o.created_at, 'Bán hàng', o.order_code, 0, d.quantity
        FROM order_details d JOIN orders o ON o.id = d.order_id
        WHERE d.product_id = :p2 AND o.order_status <> 'cancelled'
        ORDER BY at DESC, doc DESC
    ");
    $movements->execute(['p1' => $cardId, 'p2' => $cardId]);
    $movements = $movements->fetchAll();

    if (wants_export()) {
        $rows = [];
        foreach ($movements as $m) {
            $rows[] = [vn_datetime($m['at']), $m['kind'], $m['doc'], (int)$m['qty_in'], (int)$m['qty_out']];
        }
        Xlsx::download('the-kho-' . slugify($product['name']) . '.xlsx', [[
            'name' => 'Thẻ kho', 'headers' => ['Thời gian', 'Nghiệp vụ', 'Chứng từ', 'Nhập', 'Xuất'], 'rows' => $rows,
        ]]);
    }

    admin_page_header($admin, 'Thẻ kho: ' . $product['name'], 'inventory');
    ?>

    <p><a href="<?= ADMIN_URL ?>/inventory.php">&larr; Tồn kho</a></p>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Tồn hiện tại: <?= (int)$product['stock'] ?> · giá vốn <?= money($product['cost_price']) ?></h2>
            <?= export_button() ?>
        </div>
        <p class="ai-note">Thẻ kho tổng hợp từ phiếu nhập và đơn bán (không tính chứng từ đã hủy). Các lần chỉnh tay số tồn trong bảng tồn kho không được ghi lại ở đây.</p>

        <?php if ($movements === []) : ?>
            <p class="admin-empty">Chưa có phát sinh nhập/xuất.</p>
        <?php else : ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Thời gian</th><th>Nghiệp vụ</th><th>Chứng từ</th><th class="num">Nhập</th><th class="num">Xuất</th></tr></thead>
                    <tbody>
                        <?php foreach ($movements as $m) : ?>
                            <tr>
                                <td><?= e(vn_datetime($m['at'])) ?></td>
                                <td><?= e($m['kind']) ?></td>
                                <td><?= e($m['doc']) ?></td>
                                <td class="num"><?= (int)$m['qty_in'] ?: '' ?></td>
                                <td class="num"><?= (int)$m['qty_out'] ?: '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php
    admin_page_footer();
    exit;
}

// ---------- Danh sách tồn kho ----------
$filter = (string)($_GET['filter'] ?? '');
$where = "p.status <> 'hidden'";

if ($filter === 'low') {
    $where .= ' AND p.stock > 0 AND p.stock <= ' . LOW_STOCK_LIMIT;
} elseif ($filter === 'out') {
    $where .= ' AND p.stock = 0';
}

$products = $pdo->query("
    SELECT p.id, p.name, p.product_code, p.stock, p.status, p.price, p.sale_price, p.cost_price, c.name AS category_name
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE $where ORDER BY p.stock ASC, p.name ASC
")->fetchAll();

$totals = ['qty' => 0, 'cost' => 0.0, 'retail' => 0.0];
foreach ($products as &$product) {
    $product['current_price'] = Pricing::currentFromRow($product);
    $product['cost_value'] = (int)$product['stock'] * (float)$product['cost_price'];
    $product['retail_value'] = (int)$product['stock'] * $product['current_price'];
    $totals['qty'] += (int)$product['stock'];
    $totals['cost'] += $product['cost_value'];
    $totals['retail'] += $product['retail_value'];
}
unset($product);

if (wants_export()) {
    $rows = [];
    foreach ($products as $p) {
        $rows[] = [$p['name'], (string)$p['product_code'], (string)$p['category_name'], (int)$p['stock'],
            (float)$p['cost_price'], (float)$p['current_price'], $p['cost_value'], $p['retail_value']];
    }
    $rows[] = ['Tổng cộng', '', '', $totals['qty'], '', '', $totals['cost'], $totals['retail']];
    Xlsx::download('ton-kho-' . date('Y-m-d') . '.xlsx', [[
        'name' => 'Tồn kho',
        'headers' => ['Sản phẩm', 'Mã', 'Danh mục', 'Tồn', 'Giá vốn', 'Giá bán', 'Giá trị tồn (vốn)', 'Giá trị tồn (bán)'],
        'rows' => $rows,
    ]]);
}

admin_page_header($admin, 'Tồn kho', 'inventory');

?>

<section class="admin-stats">
    <div class="admin-stat"><span class="label">Tổng số lượng tồn</span><strong><?= number_format($totals['qty'], 0, ',', '.') ?></strong></div>
    <div class="admin-stat"><span class="label">Giá trị tồn (giá vốn)</span><strong><?= money($totals['cost']) ?></strong></div>
    <div class="admin-stat"><span class="label">Giá trị tồn (giá bán)</span><strong><?= money($totals['retail']) ?></strong></div>
    <div class="admin-stat"><span class="label">Lãi tiềm năng</span><strong><?= money($totals['retail'] - $totals['cost']) ?></strong></div>
</section>

<section class="admin-card">
    <div class="admin-card-head">
        <form method="get" class="admin-filters" style="margin: 0;">
            <select class="admin-select" name="filter" aria-label="Lọc tồn kho" onchange="this.form.submit()">
                <option value="">Tất cả sản phẩm đang kinh doanh</option>
                <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>Sắp hết (1–<?= LOW_STOCK_LIMIT ?>)</option>
                <option value="out" <?= $filter === 'out' ? 'selected' : '' ?>>Đã hết hàng</option>
            </select>
            <noscript><button type="submit" class="admin-button">Lọc</button></noscript>
        </form>
        <div class="admin-actions">
            <?= export_button() ?>
            <a class="admin-button" href="<?= ADMIN_URL ?>/purchases.php?action=new">Lập phiếu nhập</a>
        </div>
    </div>
    <p class="ai-note">Giá vốn được cập nhật theo bình quân gia quyền mỗi khi nhập hàng. Sản phẩm chưa có phiếu nhập sẽ có giá vốn 0.</p>

    <?php if ($products === []) : ?>
        <p class="admin-empty">Không có sản phẩm phù hợp.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Sản phẩm</th><th>Tình trạng</th><th class="num">Giá vốn</th><th class="num">Giá bán</th><th class="num">Giá trị tồn (vốn)</th><th>Đặt lại tồn</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td>
                                <a href="<?= ADMIN_URL ?>/inventory.php?card=<?= (int)$product['id'] ?>"><strong><?= e($product['name']) ?></strong></a><br>
                                <small><?= e($product['product_code'] ?: '—') ?> · <?= e($product['category_name'] ?: '—') ?></small>
                            </td>
                            <td>
                                <?php if ((int)$product['stock'] === 0) : ?>
                                    <?= badge('Hết hàng', 'bad') ?>
                                <?php elseif ((int)$product['stock'] <= LOW_STOCK_LIMIT) : ?>
                                    <?= badge('Còn ' . (int)$product['stock'], 'warn') ?>
                                <?php else : ?>
                                    <?= badge('Còn ' . (int)$product['stock'], 'ok') ?>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= (float)$product['cost_price'] > 0 ? money($product['cost_price']) : '—' ?></td>
                            <td class="num"><?= (float)$product['price'] > 0 ? money($product['current_price']) : 'Liên hệ' ?></td>
                            <td class="num"><?= money($product['cost_value']) ?></td>
                            <td>
                                <form class="admin-inline-form" method="post" action="<?= ADMIN_URL ?>/inventory.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                    <input class="admin-input" type="number" min="0" max="100000" name="stock" value="<?= (int)$product['stock'] ?>" aria-label="Tồn kho của <?= e($product['name']) ?>">
                                    <button type="submit" class="admin-button small">Lưu</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

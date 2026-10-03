<?php

require_once __DIR__ . '/../middleware/admin.php';
require_once __DIR__ . '/../models/Purchase.php';

$admin = Auth::require($pdo);

$statusLabels = ['completed' => ['Đã nhập kho', 'ok'], 'cancelled' => ['Đã hủy', 'bad']];

function parse_money(mixed $value): float
{
    return max(0.0, (float)str_replace(['.', ',', ' ', '₫'], '', (string)$value));
}

// ---------- Tạo / hủy phiếu ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $postAction = (string)($_POST['action'] ?? '');

    if ($postAction === 'cancel') {
        $id = (int)($_POST['id'] ?? 0);

        try {
            flash('success', Purchase::cancel($pdo, $id));
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage());
        }

        redirect(ADMIN_URL . '/purchases.php?id=' . $id);
    }

    $header = [
        'supplier_name' => trim((string)($_POST['supplier_name'] ?? '')),
        'supplier_phone' => trim((string)($_POST['supplier_phone'] ?? '')) ?: null,
        'note' => trim((string)($_POST['note'] ?? '')) ?: null,
    ];
    $items = [];
    $productIds = (array)($_POST['product_id'] ?? []);
    $quantities = (array)($_POST['quantity'] ?? []);
    $costs = (array)($_POST['unit_cost'] ?? []);
    $errors = [];

    foreach ($productIds as $index => $productId) {
        $productId = (int)$productId;
        $quantity = (int)($quantities[$index] ?? 0);

        if ($productId <= 0 && $quantity <= 1 && parse_money($costs[$index] ?? 0) === 0.0) {
            continue; // dòng trống (chưa chọn sản phẩm, giữ nguyên giá trị mặc định)
        }

        if ($productId <= 0 || $quantity < 1 || $quantity > 100000) {
            $errors[] = 'Dòng ' . ($index + 1) . ': chọn sản phẩm và nhập số lượng từ 1 đến 100.000.';
            continue;
        }

        $items[] = ['product_id' => $productId, 'quantity' => $quantity, 'unit_cost' => parse_money($costs[$index] ?? 0)];
    }

    if ($header['supplier_name'] === '') {
        $errors[] = 'Vui lòng nhập tên nhà cung cấp.';
    }
    if ($header['supplier_phone'] !== null && !preg_match('/^(0|\+84)\d{9}$/', $header['supplier_phone'])) {
        $errors[] = 'Số điện thoại nhà cung cấp không hợp lệ (ví dụ 0975303993).';
    }
    if ($items === [] && $errors === []) {
        $errors[] = 'Phiếu nhập cần ít nhất một sản phẩm.';
    }

    if ($errors === []) {
        try {
            $receiptId = Purchase::create($pdo, $header, $items, (int)$admin['id']);
            flash('success', 'Đã lập phiếu nhập và cộng tồn kho.');
            redirect(ADMIN_URL . '/purchases.php?id=' . $receiptId);
        } catch (RuntimeException $error) {
            $errors[] = $error->getMessage();
        }
    }

    $_SESSION['purchase_form'] = ['errors' => $errors, 'header' => $header, 'items' => $items];
    redirect(ADMIN_URL . '/purchases.php?action=new');
}

$action = (string)($_GET['action'] ?? 'list');
$receiptId = (int)($_GET['id'] ?? 0);

// ---------- Chi tiết / in chứng từ ----------
if ($receiptId > 0) {
    $receipt = $pdo->prepare('
        SELECT r.*, u.full_name AS creator FROM purchase_receipts r
        LEFT JOIN users u ON u.id = r.created_by WHERE r.id = :id');
    $receipt->execute(['id' => $receiptId]);
    $receipt = $receipt->fetch();

    if (!$receipt) {
        flash('error', 'Không tìm thấy phiếu nhập.');
        redirect(ADMIN_URL . '/purchases.php');
    }

    $items = $pdo->prepare('SELECT * FROM purchase_receipt_items WHERE receipt_id = :id ORDER BY id');
    $items->execute(['id' => $receiptId]);
    $items = $items->fetchAll();

    if (wants_export()) {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [$index + 1, $item['product_name'], (int)$item['quantity'], (float)$item['unit_cost'], (float)$item['total']];
        }
        $rows[] = ['', 'Tổng cộng', '', '', (float)$receipt['total_amount']];
        Xlsx::download('phieu-nhap-' . $receipt['receipt_code'] . '.xlsx', [[
            'name' => 'Phiếu nhập', 'headers' => ['STT', 'Sản phẩm', 'Số lượng', 'Đơn giá nhập', 'Thành tiền'], 'rows' => $rows,
        ]]);
    }

    [$label, $tone] = $statusLabels[$receipt['status']];
    admin_page_header($admin, 'Phiếu nhập ' . $receipt['receipt_code'], 'purchases');
    ?>

    <p class="no-print"><a href="<?= ADMIN_URL ?>/purchases.php">&larr; Danh sách phiếu nhập</a></p>

    <section class="admin-card admin-document">
        <header class="admin-document-head">
            <div>
                <strong><?= e(APP_NAME) ?></strong><br>
                <?= e(STORE_ADDRESS) ?><br>
                Điện thoại: <?= e(STORE_PHONE) ?>
            </div>
            <div class="admin-document-title">
                <h2>PHIẾU NHẬP KHO</h2>
                Số: <strong><?= e($receipt['receipt_code']) ?></strong><br>
                Ngày: <?= e(vn_datetime($receipt['created_at'])) ?><br>
                <?= badge($label, $tone) ?>
            </div>
        </header>

        <dl class="admin-dl">
            <dt>Nhà cung cấp</dt><dd><?= e($receipt['supplier_name']) ?></dd>
            <dt>Điện thoại</dt><dd><?= e($receipt['supplier_phone'] ?: '—') ?></dd>
            <dt>Ghi chú</dt><dd><?= e($receipt['note'] ?: '—') ?></dd>
            <dt>Người lập phiếu</dt><dd><?= e($receipt['creator'] ?: '—') ?></dd>
        </dl>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>STT</th><th>Sản phẩm</th><th class="num">Số lượng</th><th class="num">Đơn giá nhập</th><th class="num">Thành tiền</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $index => $item) : ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($item['product_name']) ?></td>
                            <td class="num"><?= (int)$item['quantity'] ?></td>
                            <td class="num"><?= money($item['unit_cost']) ?></td>
                            <td class="num"><?= money($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td colspan="4" class="num"><strong>Tổng cộng</strong></td><td class="num"><strong><?= money($receipt['total_amount']) ?></strong></td></tr>
                </tbody>
            </table>
        </div>

        <div class="admin-signatures">
            <div><strong>Người lập phiếu</strong><span>(Ký, ghi rõ họ tên)</span></div>
            <div><strong>Người giao hàng</strong><span>(Ký, ghi rõ họ tên)</span></div>
            <div><strong>Thủ kho</strong><span>(Ký, ghi rõ họ tên)</span></div>
        </div>
    </section>

    <div class="admin-actions no-print">
        <button type="button" class="admin-button" onclick="window.print()">
            <span class="material-symbols-outlined" aria-hidden="true">print</span> In phiếu
        </button>
        <?= export_button() ?>
        <?php if ($receipt['status'] === 'completed') : ?>
            <form method="post" action="<?= ADMIN_URL ?>/purchases.php" data-confirm="Hủy phiếu nhập này? Tồn kho sẽ bị trừ lại số lượng đã nhập.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="id" value="<?= (int)$receipt['id'] ?>">
                <button type="submit" class="admin-button danger">Hủy phiếu</button>
            </form>
        <?php endif; ?>
    </div>

    <?php
    admin_page_footer();
    exit;
}

// ---------- Form lập phiếu ----------
if ($action === 'new') {
    $products = $pdo->query("SELECT id, name, product_code, cost_price FROM products WHERE status <> 'hidden' ORDER BY name")->fetchAll();
    $old = $_SESSION['purchase_form'] ?? null;
    unset($_SESSION['purchase_form']);
    $errors = $old['errors'] ?? [];
    $header = $old['header'] ?? ['supplier_name' => '', 'supplier_phone' => '', 'note' => ''];
    $lines = $old['items'] ?? [];
    $lines = $lines === [] ? [['product_id' => 0, 'quantity' => 1, 'unit_cost' => 0]] : $lines;

    admin_page_header($admin, 'Lập phiếu nhập hàng', 'purchases');
    ?>

    <p><a href="<?= ADMIN_URL ?>/purchases.php">&larr; Danh sách phiếu nhập</a></p>

    <section class="admin-card">
        <?php foreach ($errors as $message) : ?>
            <div class="admin-alert error" role="alert" style="margin-bottom: 10px;"><?= e($message) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= ADMIN_URL ?>/purchases.php" id="purchaseForm">
            <?= csrf_field() ?>
            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="supplier_name">Nhà cung cấp *</label>
                    <input class="admin-input" id="supplier_name" name="supplier_name" value="<?= e($header['supplier_name']) ?>" required maxlength="150">
                </div>
                <div class="admin-field">
                    <label for="supplier_phone">Điện thoại nhà cung cấp</label>
                    <input class="admin-input" id="supplier_phone" name="supplier_phone" value="<?= e($header['supplier_phone']) ?>" inputmode="tel" maxlength="20">
                </div>
                <div class="admin-field full">
                    <label for="note">Ghi chú</label>
                    <input class="admin-input" id="note" name="note" value="<?= e($header['note']) ?>">
                </div>
            </div>

            <h2 style="font-size: 17px; margin: 18px 0 10px;">Sản phẩm nhập</h2>
            <div id="purchaseLines" class="purchase-lines">
                <?php foreach ($lines as $line) : ?>
                    <div class="purchase-line">
                        <select class="admin-select" name="product_id[]" aria-label="Sản phẩm">
                            <option value="0">— Chọn sản phẩm —</option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?= (int)$product['id'] ?>" data-cost="<?= (int)$product['cost_price'] ?>" <?= (int)$line['product_id'] === (int)$product['id'] ? 'selected' : '' ?>>
                                    <?= e($product['name']) ?><?= $product['product_code'] ? ' (' . e($product['product_code']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input class="admin-input" type="number" min="1" max="100000" name="quantity[]" value="<?= (int)$line['quantity'] ?>" aria-label="Số lượng" inputmode="numeric">
                        <input class="admin-input" name="unit_cost[]" value="<?= (int)$line['unit_cost'] ?>" aria-label="Đơn giá nhập (₫)" inputmode="numeric">
                        <button type="button" class="admin-button danger small" data-remove-line aria-label="Xóa dòng">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="admin-button ghost" id="addLine">+ Thêm dòng</button></p>
            <p class="purchase-total">Tạm tính: <strong id="purchaseTotal">0 ₫</strong></p>

            <div class="admin-actions">
                <button type="submit" class="admin-button">Lưu phiếu và nhập kho</button>
                <a class="admin-button ghost" href="<?= ADMIN_URL ?>/purchases.php">Hủy</a>
            </div>
        </form>
    </section>

    <script>
        (function () {
            var wrap = document.getElementById('purchaseLines');
            var total = document.getElementById('purchaseTotal');

            function format(number) {
                return number.toLocaleString('vi-VN') + ' ₫';
            }

            function recalc() {
                var sum = 0;
                wrap.querySelectorAll('.purchase-line').forEach(function (line) {
                    var qty = parseInt(line.querySelector('[name="quantity[]"]').value, 10) || 0;
                    var cost = parseInt(line.querySelector('[name="unit_cost[]"]').value.replace(/\D/g, ''), 10) || 0;
                    sum += qty * cost;
                });
                total.textContent = format(sum);
            }

            wrap.addEventListener('input', recalc);
            wrap.addEventListener('change', function (event) {
                // Chọn sản phẩm: gợi ý đơn giá bằng giá vốn hiện tại nếu ô giá đang là 0.
                if (event.target.matches('select')) {
                    var costInput = event.target.closest('.purchase-line').querySelector('[name="unit_cost[]"]');
                    var suggested = event.target.selectedOptions[0].dataset.cost;
                    if ((parseInt(costInput.value, 10) || 0) === 0 && suggested) {
                        costInput.value = suggested;
                    }
                }
                recalc();
            });
            wrap.addEventListener('click', function (event) {
                var button = event.target.closest('[data-remove-line]');
                if (button && wrap.children.length > 1) {
                    button.closest('.purchase-line').remove();
                    recalc();
                }
            });
            document.getElementById('addLine').addEventListener('click', function () {
                var clone = wrap.firstElementChild.cloneNode(true);
                clone.querySelector('select').value = '0';
                clone.querySelector('[name="quantity[]"]').value = 1;
                clone.querySelector('[name="unit_cost[]"]').value = 0;
                wrap.appendChild(clone);
            });
            recalc();
        })();
    </script>

    <?php
    admin_page_footer();
    exit;
}

// ---------- Danh sách ----------
$range = date_range();
$status = (string)($_GET['status'] ?? '');
$search = trim((string)($_GET['q'] ?? ''));
$where = ['r.created_at >= :from_date', 'r.created_at < :to_date'];
$params = ['from_date' => $range['from'], 'to_date' => $range['end_exclusive']];

if (isset($statusLabels[$status])) {
    $where[] = 'r.status = :status';
    $params['status'] = $status;
}
if ($search !== '') {
    $where[] = '(r.receipt_code LIKE :q1 OR r.supplier_name LIKE :q2)';
    $params += ['q1' => '%' . $search . '%', 'q2' => '%' . $search . '%'];
}

$list = $pdo->prepare('
    SELECT r.id, r.receipt_code, r.supplier_name, r.supplier_phone, r.total_amount, r.status, r.created_at,
        (SELECT COALESCE(SUM(quantity), 0) FROM purchase_receipt_items i WHERE i.receipt_id = r.id) AS qty
    FROM purchase_receipts r WHERE ' . implode(' AND ', $where) . ' ORDER BY r.id DESC LIMIT 500');
$list->execute($params);
$receipts = $list->fetchAll();
$totalSpent = array_sum(array_map(static fn(array $r): float => $r['status'] === 'completed' ? (float)$r['total_amount'] : 0.0, $receipts));

if (wants_export()) {
    $rows = [];
    foreach ($receipts as $r) {
        $rows[] = [$r['receipt_code'], vn_datetime($r['created_at']), $r['supplier_name'], (string)$r['supplier_phone'],
            (int)$r['qty'], (float)$r['total_amount'], $statusLabels[$r['status']][0]];
    }
    Xlsx::download('chung-tu-mua-hang-' . $range['from'] . '-' . $range['to'] . '.xlsx', [[
        'name' => 'Chứng từ mua hàng',
        'headers' => ['Số phiếu', 'Ngày', 'Nhà cung cấp', 'Điện thoại', 'Tổng số lượng', 'Tổng tiền', 'Trạng thái'],
        'rows' => $rows,
    ]]);
}

admin_page_header($admin, 'Chứng từ mua hàng', 'purchases');

?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2><?= count($receipts) ?> phiếu nhập · <?= money($totalSpent) ?></h2>
        <div class="admin-actions">
            <?= export_button() ?>
            <a class="admin-button" href="<?= ADMIN_URL ?>/purchases.php?action=new">
                <span class="material-symbols-outlined" aria-hidden="true">add</span> Lập phiếu nhập
            </a>
        </div>
    </div>

    <form method="get" class="admin-filters">
        <?= date_range_fields($range) ?>
        <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Số phiếu, nhà cung cấp" aria-label="Tìm phiếu nhập">
        <select class="admin-select" name="status" aria-label="Trạng thái">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($statusLabels as $value => [$text]) : ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($text) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-button">Lọc</button>
    </form>

    <?php if ($receipts === []) : ?>
        <p class="admin-empty">Chưa có phiếu nhập nào trong khoảng này.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Số phiếu</th><th>Ngày</th><th>Nhà cung cấp</th><th class="num">SL</th><th class="num">Tổng tiền</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    <?php foreach ($receipts as $r) : ?>
                        <tr>
                            <td><a href="<?= ADMIN_URL ?>/purchases.php?id=<?= (int)$r['id'] ?>"><?= e($r['receipt_code']) ?></a></td>
                            <td><?= e(vn_datetime($r['created_at'])) ?></td>
                            <td><?= e($r['supplier_name']) ?></td>
                            <td class="num"><?= (int)$r['qty'] ?></td>
                            <td class="num"><?= money($r['total_amount']) ?></td>
                            <td><?= badge($statusLabels[$r['status']][0], $statusLabels[$r['status']][1]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

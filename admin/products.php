<?php

require_once __DIR__ . '/../middleware/admin.php';

$admin = Auth::require($pdo);

$statusLabels = ['active' => 'Đang bán', 'out_of_stock' => 'Hết hàng', 'hidden' => 'Đã ẩn'];
$statusTones = ['active' => 'ok', 'out_of_stock' => 'warn', 'hidden' => 'muted'];
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$categoryIds = array_map('intval', array_column($categories, 'id'));

const MAX_IMAGE_BYTES = 3 * 1024 * 1024;

/** Lưu ảnh tải lên vào uploads/products; trả về đường dẫn tương đối hoặc ném lỗi tiếng Việt. */
function store_product_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_IMAGE_BYTES) {
        throw new RuntimeException('Ảnh tải lên lỗi hoặc lớn hơn 3MB.');
    }

    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.');
    }

    $directory = __DIR__ . '/../uploads/products';
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $name = 'sp-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];

    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
        throw new RuntimeException('Không lưu được ảnh, vui lòng thử lại.');
    }

    return 'uploads/products/' . $name;
}

function unique_slug(PDO $pdo, string $name, int $ignoreId): string
{
    $base = slugify($name);
    $slug = $base;
    $suffix = 2;
    $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = :slug AND id <> :id');

    while (true) {
        $check->execute(['slug' => $slug, 'id' => $ignoreId]);
        if ((int)$check->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $suffix++;
    }
}

function optional_int(mixed $value): ?int
{
    $value = trim((string)$value);

    return $value === '' ? null : max(0, (int)$value);
}

$action = (string)($_GET['action'] ?? 'list');

// ---------- Lưu (thêm / sửa) hoặc ẩn ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $postAction = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($postAction === 'hide') {
        $pdo->prepare("UPDATE products SET status = 'hidden' WHERE id = :id")->execute(['id' => $id]);
        flash('success', 'Đã ẩn sản phẩm khỏi website. Đơn cũ không bị ảnh hưởng.');
        redirect(ADMIN_URL . '/products.php');
    }

    $input = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'product_code' => trim((string)($_POST['product_code'] ?? '')),
        'price' => max(0, (float)str_replace(['.', ','], '', (string)($_POST['price'] ?? '0'))),
        'sale_price' => trim((string)($_POST['sale_price'] ?? '')) === ''
            ? null
            : max(0, (float)str_replace(['.', ','], '', (string)$_POST['sale_price'])),
        'stock' => max(0, (int)($_POST['stock'] ?? 0)),
        'status' => (string)($_POST['status'] ?? 'active'),
        'description' => trim((string)($_POST['description'] ?? '')),
        'specifications' => trim((string)($_POST['specifications'] ?? '')),
        'max_speed' => optional_int($_POST['max_speed'] ?? ''),
        'battery_range' => optional_int($_POST['battery_range'] ?? ''),
        'motor_power' => trim((string)($_POST['motor_power'] ?? '')),
        'warranty_months' => max(0, (int)($_POST['warranty_months'] ?? 12)),
    ];

    $errors = [];

    if ($input['name'] === '' || mb_strlen($input['name'], 'UTF-8') > 255) {
        $errors[] = 'Tên sản phẩm bắt buộc và tối đa 255 ký tự.';
    }
    if (!in_array($input['category_id'], $categoryIds, true)) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }
    if (!isset($statusLabels[$input['status']])) {
        $errors[] = 'Trạng thái không hợp lệ.';
    }
    if ($input['sale_price'] !== null && $input['sale_price'] >= $input['price']) {
        $errors[] = 'Giá khuyến mãi phải nhỏ hơn giá gốc (hoặc để trống).';
    }
    if ($input['product_code'] !== '') {
        $duplicate = $pdo->prepare('SELECT COUNT(*) FROM products WHERE product_code = :code AND id <> :id');
        $duplicate->execute(['code' => $input['product_code'], 'id' => $id]);
        if ((int)$duplicate->fetchColumn() > 0) {
            $errors[] = 'Mã sản phẩm đã được dùng cho xe khác.';
        }
    }

    $image = null;

    if ($errors === []) {
        try {
            $image = store_product_image($_FILES['image'] ?? []);
        } catch (RuntimeException $error) {
            $errors[] = $error->getMessage();
        }
    }

    if ($errors !== []) {
        $_SESSION['product_form'] = ['errors' => $errors, 'input' => $input, 'id' => $id];
        redirect(ADMIN_URL . '/products.php?action=' . ($id > 0 ? 'edit&id=' . $id : 'new'));
    }

    $input['product_code'] = $input['product_code'] === '' ? null : $input['product_code'];
    $input['slug'] = unique_slug($pdo, $input['name'], $id);

    if ($id > 0) {
        $columns = 'category_id=:category_id, name=:name, slug=:slug, product_code=:product_code, price=:price,
            sale_price=:sale_price, stock=:stock, status=:status, description=:description,
            specifications=:specifications, max_speed=:max_speed, battery_range=:battery_range,
            motor_power=:motor_power, warranty_months=:warranty_months';
        $params = $input + ['id' => $id];

        if ($image !== null) {
            $columns .= ', image=:image';
            $params['image'] = $image;
        }

        $pdo->prepare("UPDATE products SET $columns WHERE id=:id")->execute($params);
        flash('success', 'Đã cập nhật sản phẩm.');
    } else {
        $input['image'] = $image;
        $pdo->prepare('
            INSERT INTO products (category_id, name, slug, product_code, price, sale_price, stock, status,
                description, specifications, max_speed, battery_range, motor_power, warranty_months, image)
            VALUES (:category_id, :name, :slug, :product_code, :price, :sale_price, :stock, :status,
                :description, :specifications, :max_speed, :battery_range, :motor_power, :warranty_months, :image)
        ')->execute($input);
        flash('success', 'Đã thêm sản phẩm mới.');
    }

    redirect(ADMIN_URL . '/products.php');
}

// ---------- Form thêm / sửa ----------
if ($action === 'new' || $action === 'edit') {
    $id = $action === 'edit' ? (int)($_GET['id'] ?? 0) : 0;
    $product = [
        'name' => '', 'category_id' => '', 'product_code' => '', 'price' => 0, 'sale_price' => null,
        'stock' => 0, 'status' => 'active', 'description' => '', 'specifications' => '',
        'max_speed' => null, 'battery_range' => null, 'motor_power' => '', 'warranty_months' => 12, 'image' => null,
    ];

    if ($id > 0) {
        $row = $pdo->prepare('SELECT * FROM products WHERE id = :id');
        $row->execute(['id' => $id]);
        $row = $row->fetch();

        if (!$row) {
            flash('error', 'Không tìm thấy sản phẩm.');
            redirect(ADMIN_URL . '/products.php');
        }

        $product = $row;
    }

    $errors = [];
    $old = $_SESSION['product_form'] ?? null;
    unset($_SESSION['product_form']);

    if ($old && (int)$old['id'] === $id) {
        $errors = $old['errors'];
        $product = $old['input'] + $product;
    }

    admin_page_header($admin, $id > 0 ? 'Sửa sản phẩm' : 'Thêm sản phẩm', 'products');
    ?>

    <p><a href="<?= ADMIN_URL ?>/products.php">&larr; Danh sách sản phẩm</a></p>

    <section class="admin-card">
        <?php foreach ($errors as $message) : ?>
            <div class="admin-alert error" role="alert" style="margin-bottom: 10px;"><?= e($message) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= ADMIN_URL ?>/products.php" enctype="multipart/form-data" class="admin-form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <div class="admin-field full">
                <label for="name">Tên sản phẩm *</label>
                <input class="admin-input" id="name" name="name" value="<?= e($product['name']) ?>" required maxlength="255">
            </div>

            <div class="admin-field">
                <label for="category_id">Danh mục *</label>
                <select class="admin-select" id="category_id" name="category_id" required>
                    <option value="">— Chọn danh mục —</option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-field">
                <label for="product_code">Mã sản phẩm</label>
                <input class="admin-input" id="product_code" name="product_code" value="<?= e($product['product_code']) ?>" maxlength="100">
            </div>

            <div class="admin-field">
                <label for="price">Giá gốc (₫) *</label>
                <input class="admin-input" id="price" name="price" inputmode="numeric" value="<?= e((int)$product['price']) ?>" required>
                <small>Nhập 0 nếu muốn hiển thị &quot;Liên hệ&quot;.</small>
            </div>

            <div class="admin-field">
                <label for="sale_price">Giá khuyến mãi (₫)</label>
                <input class="admin-input" id="sale_price" name="sale_price" inputmode="numeric" value="<?= $product['sale_price'] !== null ? e((int)$product['sale_price']) : '' ?>">
                <small>Để trống nếu không giảm giá.</small>
            </div>

            <div class="admin-field">
                <label for="stock">Tồn kho</label>
                <input class="admin-input" type="number" min="0" id="stock" name="stock" value="<?= (int)$product['stock'] ?>">
            </div>

            <div class="admin-field">
                <label for="status">Trạng thái</label>
                <select class="admin-select" id="status" name="status">
                    <?php foreach ($statusLabels as $value => $label) : ?>
                        <option value="<?= e($value) ?>" <?= $product['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-field">
                <label for="max_speed">Tốc độ tối đa (km/h)</label>
                <input class="admin-input" type="number" min="0" id="max_speed" name="max_speed" value="<?= e($product['max_speed']) ?>">
            </div>

            <div class="admin-field">
                <label for="battery_range">Quãng đường / lần sạc (km)</label>
                <input class="admin-input" type="number" min="0" id="battery_range" name="battery_range" value="<?= e($product['battery_range']) ?>">
            </div>

            <div class="admin-field">
                <label for="motor_power">Công suất động cơ</label>
                <input class="admin-input" id="motor_power" name="motor_power" value="<?= e($product['motor_power']) ?>" maxlength="100">
            </div>

            <div class="admin-field">
                <label for="warranty_months">Bảo hành (tháng)</label>
                <input class="admin-input" type="number" min="0" id="warranty_months" name="warranty_months" value="<?= (int)$product['warranty_months'] ?>">
            </div>

            <div class="admin-field full">
                <label for="image">Ảnh sản phẩm</label>
                <?php if (!empty($product['image'])) : ?>
                    <p><img class="admin-thumb" style="width: 120px; height: 90px;" src="<?= e(image_url($product['image'])) ?>" alt="Ảnh hiện tại của <?= e($product['name']) ?>"></p>
                <?php endif; ?>
                <input class="admin-input" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG hoặc WebP, tối đa 3MB. Để trống để giữ ảnh hiện tại.</small>
            </div>

            <div class="admin-field full">
                <label for="description">Mô tả</label>
                <textarea class="admin-textarea" id="description" name="description"><?= e($product['description']) ?></textarea>
            </div>

            <div class="admin-field full">
                <label for="specifications">Thông số kỹ thuật</label>
                <textarea class="admin-textarea" id="specifications" name="specifications"><?= e($product['specifications']) ?></textarea>
            </div>

            <div class="full admin-actions">
                <button type="submit" class="admin-button">Lưu sản phẩm</button>
                <a class="admin-button ghost" href="<?= ADMIN_URL ?>/products.php">Hủy</a>
            </div>
        </form>
    </section>

    <?php
    admin_page_footer();
    exit;
}

// ---------- Danh sách ----------
$search = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(p.name LIKE :q1 OR p.product_code LIKE :q2)';
    $params += ['q1' => '%' . $search . '%', 'q2' => '%' . $search . '%'];
}
if (isset($statusLabels[$status])) {
    $where[] = 'p.status = :status';
    $params['status'] = $status;
}

$list = $pdo->prepare('
    SELECT p.id, p.name, p.product_code, p.price, p.sale_price, p.stock, p.status, p.image, c.name AS category_name
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC');
$list->execute($params);
$products = $list->fetchAll();

if (wants_export()) {
    $rows = [];
    foreach ($products as $p) {
        $rows[] = [$p['name'], (string)$p['product_code'], (string)$p['category_name'], (float)$p['price'],
            $p['sale_price'] === null ? '' : (float)$p['sale_price'], (int)$p['stock'], $statusLabels[$p['status']] ?? $p['status']];
    }
    Xlsx::download('san-pham-' . date('Y-m-d') . '.xlsx', [[
        'name' => 'Sản phẩm',
        'headers' => ['Sản phẩm', 'Mã', 'Danh mục', 'Giá gốc', 'Giá khuyến mãi', 'Tồn kho', 'Trạng thái'],
        'rows' => $rows,
    ]]);
}

admin_page_header($admin, 'Sản phẩm', 'products');

?>

<section class="admin-card">
    <div class="admin-card-head">
        <h2><?= count($products) ?> sản phẩm</h2>
        <div class="admin-actions">
            <?= export_button() ?>
            <a class="admin-button" href="<?= ADMIN_URL ?>/products.php?action=new">
                <span class="material-symbols-outlined" aria-hidden="true">add</span> Thêm sản phẩm
            </a>
        </div>
    </div>

    <form method="get" class="admin-filters">
        <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Tìm tên hoặc mã sản phẩm" aria-label="Tìm sản phẩm">
        <select class="admin-select" name="status" aria-label="Lọc theo trạng thái">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($statusLabels as $value => $label) : ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-button">Lọc</button>
    </form>

    <?php if ($products === []) : ?>
        <p class="admin-empty">Không có sản phẩm phù hợp.</p>
    <?php else : ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Ảnh</th><th>Sản phẩm</th><th>Danh mục</th><th class="num">Giá</th><th class="num">Tồn</th><th>Trạng thái</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td><img class="admin-thumb" src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy"></td>
                            <td><strong><?= e($product['name']) ?></strong><br><small><?= e($product['product_code'] ?: '—') ?></small></td>
                            <td><?= e($product['category_name'] ?: '—') ?></td>
                            <td class="num">
                                <?php $current = Pricing::currentFromRow($product); ?>
                                <?= (float)$product['price'] > 0 ? money($current) : 'Liên hệ' ?>
                                <?php if (Pricing::isOnSale((float)$product['price'], $product['sale_price'] === null ? null : (float)$product['sale_price'])) : ?>
                                    <br><small><s><?= money($product['price']) ?></s></small>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= (int)$product['stock'] ?></td>
                            <td><?= badge($statusLabels[$product['status']] ?? $product['status'], $statusTones[$product['status']] ?? 'muted') ?></td>
                            <td>
                                <div class="admin-actions">
                                    <a class="admin-button ghost small" href="<?= ADMIN_URL ?>/products.php?action=edit&amp;id=<?= (int)$product['id'] ?>">Sửa</a>
                                    <?php if ($product['status'] !== 'hidden') : ?>
                                        <form method="post" action="<?= ADMIN_URL ?>/products.php" data-confirm="Ẩn sản phẩm này khỏi website?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hide">
                                            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                            <button type="submit" class="admin-button danger small">Ẩn</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_page_footer(); ?>

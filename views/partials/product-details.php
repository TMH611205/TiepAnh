<?php

/**
 * Phần nội dung chi tiết dưới khối ảnh + thông số nổi bật của trang sản phẩm.
 * Dữ liệu: cột products.details (JSON), xem database/seed_brand_products.php.
 * Mục thiếu dữ liệu hiển thị "[CẦN BỔ SUNG]" (không tự bịa số liệu).
 *
 * @var array $product dòng sản phẩm đã có trong product-detail.php
 */

$details = json_decode((string)($product['details'] ?? ''), true);

if (!is_array($details)) {
    return; // sản phẩm chưa có nội dung chi tiết thì không hiển thị gì
}

$missing = '<span class="pd-missing">[CẦN BỔ SUNG]</span>';
$h = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
// Hiển thị một giá trị; rỗng thì báo cần bổ sung. Thay {phone}/{address} bằng thông tin cửa hàng.
$show = static function (mixed $value) use ($h, $missing): string {
    if ($value === null || $value === '' || $value === []) {
        return $missing;
    }

    $text = strtr((string)$value, ['{phone}' => STORE_PHONE, '{address}' => STORE_ADDRESS]);

    return str_replace('[CẦN BỔ SUNG]', $missing, $h($text));
};

$brand = (string)($details['brand'] ?? '');
$variants = (array)($details['variants'] ?? []);
$battery = (array)($details['battery'] ?? []);
$specGroups = (array)($details['spec_groups'] ?? []);
$features = (array)($details['features'] ?? []);
$warranty = (array)($details['warranty'] ?? []);
$faq = (array)($details['faq'] ?? []);
$sources = (array)($details['sources'] ?? []);

?>

<div class="pd-more">

    <nav class="pd-nav" aria-label="Các mục thông tin sản phẩm">
        <a href="#pd-variants">Phiên bản &amp; giá</a>
        <a href="#pd-battery">Pin &amp; sạc</a>
        <a href="#pd-specs">Thông số</a>
        <a href="#pd-features">Tính năng</a>
        <a href="#pd-warranty">Bảo hành</a>
        <a href="#pd-faq">Hỏi đáp</a>
    </nav>

    <!-- 1. PHIÊN BẢN, GIÁ, MÀU -->
    <section class="pd-section" id="pd-variants" aria-labelledby="pd-variants-title">
        <h2 id="pd-variants-title">Phiên bản, giá và màu sắc</h2>
        <?php if ($brand !== '') : ?>
            <p class="pd-sub">Hãng <?= $h($brand) ?></p>
        <?php endif; ?>

        <?php if ($variants === []) : ?>
            <p><?= $missing ?></p>
        <?php else : ?>
            <div class="pd-table-wrap">
                <table class="pd-table pd-table-stack">
                    <thead>
                        <tr><th scope="col">Phiên bản</th><th scope="col">Giá bán</th><th scope="col">Màu sắc</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $variant) : ?>
                            <?php $price = $variant['price'] ?? null; ?>
                            <tr>
                                <th scope="row" data-label="Phiên bản"><?= $h($variant['name'] ?? '') ?></th>
                                <td data-label="Giá bán">
                                    <?php if (is_numeric($price) && (float)$price > 0) : ?>
                                        <?= $h(number_format((float)$price, 0, ',', '.')) ?> ₫
                                    <?php elseif (!empty($variant['price_text'])) : ?>
                                        <?= $h($variant['price_text']) ?>
                                    <?php else : ?>
                                        <?= $missing ?>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Màu sắc">
                                    <?php if (!empty($variant['colors'])) : ?>
                                        <?= $h(implode(', ', (array)$variant['colors'])) ?>
                                    <?php else : ?>
                                        <?= $missing ?>
                                    <?php endif; ?>
                                    <?php if (!empty($variant['note'])) : ?>
                                        <small class="pd-note"><?= $h($variant['note']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="pd-note">Giá bán và màu còn hàng tại Tiệp Anh: gọi <a href="tel:<?= $h(STORE_PHONE) ?>"><?= $h(STORE_PHONE) ?></a>.</p>
        <?php endif; ?>
    </section>

    <!-- 2. PIN VÀ SẠC -->
    <section class="pd-section" id="pd-battery" aria-labelledby="pd-battery-title">
        <h2 id="pd-battery-title">Pin và sạc</h2>
        <dl class="pd-stats">
            <div><dt><span class="material-symbols-outlined" aria-hidden="true">battery_android_full</span>Loại pin / ắc quy</dt><dd><?= $show($battery['type'] ?? null) ?></dd></div>
            <div><dt><span class="material-symbols-outlined" aria-hidden="true">bolt</span>Điện áp, dung lượng</dt><dd><?= $show($battery['voltage_capacity'] ?? null) ?></dd></div>
            <div><dt><span class="material-symbols-outlined" aria-hidden="true">route</span>Quãng đường</dt><dd><?= $show($battery['range'] ?? null) ?></dd></div>
            <div><dt><span class="material-symbols-outlined" aria-hidden="true">schedule</span>Thời gian sạc</dt><dd><?= $show($battery['charge_time'] ?? null) ?></dd></div>
            <div><dt><span class="material-symbols-outlined" aria-hidden="true">verified</span>Bảo hành pin</dt><dd><?= $show($battery['warranty'] ?? null) ?></dd></div>
        </dl>
    </section>

    <!-- 3. THÔNG SỐ ĐẦY ĐỦ (accordion, mặc định thu gọn) -->
    <section class="pd-section" id="pd-specs" aria-labelledby="pd-specs-title">
        <div class="pd-head">
            <h2 id="pd-specs-title">Thông số đầy đủ</h2>
            <?php if ($specGroups !== []) : ?>
                <button type="button" class="pd-toggle-all" data-pd-toggle="#pd-specs">Mở tất cả</button>
            <?php endif; ?>
        </div>
        <?php if ($specGroups === []) : ?>
            <p><?= $missing ?></p>
        <?php else : ?>
            <div class="pd-accordion">
                <?php foreach ($specGroups as $group) : ?>
                    <details>
                        <summary><?= $h($group['title'] ?? '') ?></summary>
                        <dl class="pd-spec-list">
                            <?php foreach ((array)($group['rows'] ?? []) as $row) : ?>
                                <div><dt><?= $h($row[0] ?? '') ?></dt><dd><?= $show($row[1] ?? null) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 4. TÍNH NĂNG NỔI BẬT -->
    <section class="pd-section" id="pd-features" aria-labelledby="pd-features-title">
        <h2 id="pd-features-title">Tính năng nổi bật</h2>
        <?php if ($features === []) : ?>
            <p><?= $missing ?> <span class="pd-note">Nguồn chưa nêu danh sách tính năng cho mẫu này.</span></p>
        <?php else : ?>
            <?php if (!empty($details['features_note'])) : ?>
                <p class="pd-note"><?= $h($details['features_note']) ?></p>
            <?php endif; ?>
            <ul class="pd-features">
                <?php foreach (array_slice($features, 0, 6) as $feature) : ?>
                    <li class="pd-feature">
                        <?php if (!empty($feature['image'])) : ?>
                            <img src="<?= BASE_URL ?>/../<?= $h(ltrim(str_replace(chr(92), '/', (string)$feature['image']), '/')) ?>" alt="<?= $h($feature['title'] ?? '') ?>" loading="lazy">
                        <?php else : ?>
                            <span class="pd-feature-icon"><span class="material-symbols-outlined" aria-hidden="true"><?= $h($feature['icon'] ?? 'check_circle') ?></span></span>
                        <?php endif; ?>
                        <h3><?= $h($feature['title'] ?? '') ?></h3>
                        <p><?= $h($feature['text'] ?? '') ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- 5. BẢO HÀNH, HẬU MÃI -->
    <section class="pd-section" id="pd-warranty" aria-labelledby="pd-warranty-title">
        <h2 id="pd-warranty-title">Bảo hành và hậu mãi</h2>
        <?php if (!empty($warranty['summary'])) : ?>
            <p class="pd-lead"><?= $show($warranty['summary']) ?></p>
        <?php endif; ?>
        <dl class="pd-spec-list pd-card">
            <?php foreach ((array)($warranty['items'] ?? []) as $item) : ?>
                <div><dt><?= $h($item[0] ?? '') ?></dt><dd><?= $show($item[1] ?? null) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <ul class="pd-bullets">
            <?php foreach ((array)($warranty['aftersales'] ?? []) as $line) : ?>
                <li><?= $show($line) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- 6. HỎI ĐÁP -->
    <section class="pd-section" id="pd-faq" aria-labelledby="pd-faq-title">
        <h2 id="pd-faq-title">Câu hỏi thường gặp</h2>
        <?php if ($faq === []) : ?>
            <p><?= $missing ?></p>
        <?php else : ?>
            <div class="pd-accordion">
                <?php foreach (array_slice($faq, 0, 5) as $item) : ?>
                    <details>
                        <summary><?= $h($item['q'] ?? '') ?></summary>
                        <p><?= $show($item['a'] ?? null) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($sources !== []) : ?>
        <p class="pd-source">
            Thông số tham khảo từ trang
            <?php foreach ($sources as $index => $source) : ?>
                <?= $index > 0 ? ', ' : '' ?><a href="<?= $h($source['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer"><?= $h($source['label'] ?? 'nguồn') ?></a>
            <?php endforeach; ?>
            (cập nhật <?= $h($details['fetched_on'] ?? '') ?>). Thông số có thể thay đổi theo lô xe, vui lòng đối chiếu với xe thực tế tại cửa hàng.
        </p>
    <?php endif; ?>

</div>

<script>
    (function () {
        // Nút "Mở tất cả / Thu gọn tất cả" cho accordion thông số.
        document.querySelectorAll('[data-pd-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var items = document.querySelectorAll(button.dataset.pdToggle + ' details');
                var open = Array.prototype.some.call(items, function (item) { return !item.open; });
                items.forEach(function (item) { item.open = open; });
                button.textContent = open ? 'Thu gọn tất cả' : 'Mở tất cả';
            });
        });
    })();
</script>

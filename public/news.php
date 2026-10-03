<?php

$pageTitle = 'Tin tức xe điện';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Text.php';

$articles = [
    [
        'slug' => 'chon-xe-dien-theo-nhu-cau',
        'category' => 'Kinh nghiệm chọn xe',
        'date' => '27/09/2026',
        'title' => 'Chọn xe điện theo quãng đường di chuyển mỗi ngày',
        'description' => 'Bắt đầu từ nhu cầu đi lại, chỗ sạc và khả năng bảo dưỡng để thu hẹp lựa chọn phù hợp.',
        'image' => 'uploads/products/xe2.jpg',
        'content' => [
            'Hãy ước lượng quãng đường thường đi trong ngày và nơi có thể sạc xe. Đây là hai dữ kiện thực tế giúp bạn so sánh các mẫu đang có trong catalog.',
            'Khi xem xe, đối chiếu giá, quãng đường, công suất và tốc độ tối đa theo thông tin của từng mẫu. Nếu một trường chưa được công bố, hãy hỏi cửa hàng thay vì suy đoán từ tên gọi hoặc hình dáng.',
            'Bạn có thể gửi tên hai mẫu cho trợ lý trên website để xem nhanh các điểm khác nhau dựa trên dữ liệu hiện có.'
        ],
    ],
    [
        'slug' => 'bao-quan-pin-xe-dien',
        'category' => 'Pin & ắc quy',
        'date' => '25/09/2026',
        'title' => 'Lưu ý khi sử dụng pin và ắc quy xe điện',
        'description' => 'Tham khảo hướng dẫn đi kèm bộ pin và sử dụng bộ sạc phù hợp với xe.',
        'image' => 'uploads/products/xe4.jpg',
        'content' => [
            'Sử dụng đúng bộ sạc được cung cấp hoặc được cửa hàng xác nhận tương thích với mẫu xe. Không thay đổi đầu nối hay thông số nguồn nếu chưa được hướng dẫn.',
            'Đặt xe và bộ sạc ở nơi khô ráo, thông thoáng. Nếu pin nóng bất thường, có mùi lạ hoặc vỏ bị biến dạng, ngừng sạc và liên hệ nơi bán để được kiểm tra.',
            'Chu kỳ và cách bảo quản cụ thể tùy loại pin, vì vậy ưu tiên sách hướng dẫn và thông tin chính thức của đúng sản phẩm.'
        ],
    ],
    [
        'slug' => 'kiem-tra-xe-truoc-moi-chuyen-di',
        'category' => 'An toàn & bảo dưỡng',
        'date' => '22/09/2026',
        'title' => 'Các bước kiểm tra xe điện trước mỗi chuyến đi',
        'description' => 'Quan sát lốp, phanh, đèn và mức pin trước khi khởi hành.',
        'image' => 'uploads/products/xe5.jpg',
        'content' => [
            'Trước khi đi, quan sát lốp và kiểm tra phanh trước/sau ở tốc độ thấp. Đảm bảo đèn, còi và các tín hiệu hoạt động bình thường.',
            'Kiểm tra mức pin theo quãng đường dự kiến. Nếu phát hiện tiếng động, rung lắc hoặc cảnh báo bất thường, nên dừng xe tại nơi an toàn và liên hệ kỹ thuật viên.',
            'Lịch bảo dưỡng và áp suất lốp phụ thuộc từng mẫu. Hãy tham khảo sách hướng dẫn của xe thay vì dùng một con số chung cho mọi sản phẩm.'
        ],
    ],
    [
        'slug' => 'xe-dap-tro-luc-va-xe-may-dien',
        'category' => 'So sánh dòng xe',
        'date' => '18/09/2026',
        'title' => 'Xe đạp trợ lực và xe máy điện khác nhau thế nào?',
        'description' => 'So sánh mục đích sử dụng và kiểm tra thông số được công bố cho từng mẫu.',
        'image' => 'uploads/products/xe6.jpg',
        'content' => [
            'Hai nhóm xe hướng tới những nhu cầu di chuyển khác nhau. Hãy cân nhắc quãng đường, tốc độ cần thiết, địa hình và nơi gửi xe trước khi chọn.',
            'Catalog Tiệp Anh hiển thị thông số theo từng sản phẩm. Dùng bộ lọc danh mục và quãng đường để xem các mẫu có dữ liệu phù hợp; thông số trống được ghi rõ là đang cập nhật.',
            'Quy định sử dụng có thể phụ thuộc loại xe và cấu hình cụ thể. Hãy xác nhận với cửa hàng khi cần tư vấn cho trường hợp của bạn.'
        ],
    ],
    [
        'slug' => 'sac-xe-dien-an-toan',
        'category' => 'Sạc điện',
        'date' => '15/09/2026',
        'title' => 'Sạc xe điện tại nhà: những điều nên lưu ý',
        'description' => 'Dùng bộ sạc tương thích, chọn vị trí thông thoáng và theo dõi quá trình sạc.',
        'image' => 'uploads/products/xe3.jpg',
        'content' => [
            'Sạc tại nơi khô ráo, thông thoáng, tránh che phủ bộ sạc. Dùng bộ sạc đúng theo hướng dẫn của nhà sản xuất dành cho chiếc xe.',
            'Không tiếp tục sử dụng dây hoặc đầu cắm bị hỏng. Khi có dấu hiệu quá nhiệt hay mùi bất thường, ngắt nguồn nếu có thể thực hiện an toàn và liên hệ kỹ thuật.',
            'Thời gian sạc khác nhau theo mẫu xe, dung lượng pin và bộ sạc. Website chỉ hiển thị thông tin đã có trong hồ sơ sản phẩm.'
        ],
    ],
    [
        'slug' => 'chon-xe-dien-cho-hoc-sinh',
        'category' => 'Xe điện học sinh',
        'date' => '10/09/2026',
        'title' => 'Chọn xe điện cho học sinh: ưu tiên an toàn và phù hợp',
        'description' => 'Kiểm tra kích thước xe, phanh, đèn và thông tin sử dụng trước khi quyết định.',
        'image' => 'uploads/products/xe1.jpg',
        'content' => [
            'Khi chọn xe cho học sinh, nên để người sử dụng thử tư thế ngồi, tầm với tay phanh và khả năng chống chân. Kiểm tra đèn, còi và phanh trước khi nhận xe.',
            'Đối chiếu cấu hình, giá và tình trạng hàng theo đúng mẫu cụ thể. Nếu dữ liệu ở catalog đang cập nhật, liên hệ cửa hàng để nhận thông tin đã xác nhận.',
            'Người mua nên kiểm tra quy định áp dụng cho loại xe và độ tuổi sử dụng trước khi tham gia giao thông.'
        ],
    ],
];

// ---- Hàm hỗ trợ hiển thị ----
$h = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$articleUrl = static fn(array $article): string => BASE_URL . '/news.php?article=' . urlencode($article['slug']);
$mediaUrl = static fn(array $article): string => BASE_URL . '/../' . ltrim($article['image'], '/');
$readMinutes = static function (array $article): int {
    $words = str_word_count(Text::plain($article['title'] . ' ' . $article['description'] . ' ' . implode(' ', $article['content'])));

    return max(1, (int)ceil($words / 180));
};
$isoDate = static function (string $date): string {
    $parsed = DateTime::createFromFormat('d/m/Y', $date);

    return $parsed ? $parsed->format('Y-m-d') : '';
};
$topicSlug = static fn(string $category): string => Text::slug($category, 'khac');

$renderCard = static function (array $article) use ($h, $articleUrl, $mediaUrl, $readMinutes, $isoDate): void {
    ?>
    <article class="np-card">
        <a class="np-media media-fit" href="<?= $h($articleUrl($article)) ?>" style="--media-image: url('<?= $h($mediaUrl($article)) ?>')" tabindex="-1" aria-hidden="true">
            <img src="<?= $h($mediaUrl($article)) ?>" alt="" loading="lazy">
            <span class="np-chip"><?= $h($article['category']) ?></span>
        </a>
        <div class="np-card-body">
            <div class="np-meta">
                <time datetime="<?= $h($isoDate($article['date'])) ?>"><?= $h($article['date']) ?></time>
                <span aria-hidden="true">·</span>
                <span><?= $readMinutes($article) ?> phút đọc</span>
            </div>
            <h2><a href="<?= $h($articleUrl($article)) ?>"><?= $h($article['title']) ?></a></h2>
            <p><?= $h($article['description']) ?></p>
            <a class="np-more" href="<?= $h($articleUrl($article)) ?>" aria-label="Đọc bài: <?= $h($article['title']) ?>">
                Đọc tiếp
                <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
            </a>
        </div>
    </article>
    <?php
};

$selectedSlug = trim($_GET['article'] ?? '');
$selectedArticle = null;

if ($selectedSlug !== '') {
    foreach ($articles as $item) {
        if ($item['slug'] === $selectedSlug) {
            $selectedArticle = $item;
            break;
        }
    }

    if ($selectedArticle === null) {
        http_response_code(404);
        $pageTitle = 'Không tìm thấy bài viết';
    } else {
        $pageTitle = $selectedArticle['title'];
    }
}

// Lọc theo chủ đề (?topic=slug)
$topics = [];
foreach ($articles as $item) {
    $topics[$topicSlug($item['category'])] = $item['category'];
}
$topic = (string)($_GET['topic'] ?? '');
$activeTopic = isset($topics[$topic]) ? $topic : '';
$listed = $activeTopic === ''
    ? array_slice($articles, 1)
    : array_values(array_filter($articles, static fn(array $a): bool => $topicSlug($a['category']) === $activeTopic));

require_once __DIR__ . '/../views/layouts/header.php';

?>

<div class="np-page">
    <div class="container np-container">
        <nav class="np-breadcrumb" aria-label="Đường dẫn">
            <a href="<?= BASE_URL ?>/index.php">Trang chủ</a>
            <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
            <?php if ($selectedArticle !== null): ?>
                <a href="<?= BASE_URL ?>/news.php">Tin tức</a>
                <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
                <strong><?= $h($selectedArticle['category']) ?></strong>
            <?php else: ?>
                <strong>Tin tức &amp; cẩm nang</strong>
            <?php endif; ?>
        </nav>

        <?php if ($selectedSlug !== '' && $selectedArticle === null): ?>

            <section class="np-empty">
                <span class="material-symbols-outlined" aria-hidden="true">article</span>
                <h1>Không tìm thấy bài viết</h1>
                <p>Bài viết có thể đã được gỡ hoặc đường dẫn chưa đúng.</p>
                <a class="np-button" href="<?= BASE_URL ?>/news.php">Xem tất cả bài viết</a>
            </section>

        <?php elseif ($selectedArticle !== null): ?>

            <article class="np-article">
                <header class="np-article-head">
                    <a class="np-chip np-chip-solid" href="<?= BASE_URL ?>/news.php?topic=<?= $h($topicSlug($selectedArticle['category'])) ?>"><?= $h($selectedArticle['category']) ?></a>
                    <h1><?= $h($selectedArticle['title']) ?></h1>
                    <p class="np-lead"><?= $h($selectedArticle['description']) ?></p>
                    <div class="np-meta">
                        <span class="material-symbols-outlined" aria-hidden="true">calendar_today</span>
                        <time datetime="<?= $h($isoDate($selectedArticle['date'])) ?>"><?= $h($selectedArticle['date']) ?></time>
                        <span aria-hidden="true">·</span>
                        <span><?= $readMinutes($selectedArticle) ?> phút đọc</span>
                        <span aria-hidden="true">·</span>
                        <span><?= $h(APP_NAME) ?></span>
                    </div>
                </header>

                <figure class="np-cover media-fit" style="--media-image: url('<?= $h($mediaUrl($selectedArticle)) ?>')">
                    <img src="<?= $h($mediaUrl($selectedArticle)) ?>" alt="<?= $h($selectedArticle['title']) ?>">
                </figure>

                <div class="np-body">
                    <?php foreach ($selectedArticle['content'] as $paragraph): ?>
                        <p><?= $h($paragraph) ?></p>
                    <?php endforeach; ?>
                </div>

                <aside class="np-cta" aria-label="Liên hệ tư vấn">
                    <div>
                        <strong>Cần tư vấn chọn xe phù hợp?</strong>
                        <span>Gọi <?= $h(STORE_PHONE) ?> hoặc xem các mẫu đang kinh doanh.</span>
                    </div>
                    <div class="np-cta-actions">
                        <a class="np-button" href="tel:<?= $h(STORE_PHONE) ?>">
                            <span class="material-symbols-outlined" aria-hidden="true">call</span> Gọi ngay
                        </a>
                        <a class="np-button np-button-ghost" href="<?= BASE_URL ?>/products.php">Xem sản phẩm</a>
                    </div>
                </aside>
            </article>

            <?php $related = array_values(array_filter($articles, static fn(array $a): bool => $a['slug'] !== $selectedArticle['slug'])); ?>
            <section class="np-related" aria-labelledby="related-title">
                <div class="np-section-head">
                    <h2 id="related-title">Bài viết khác</h2>
                    <a href="<?= BASE_URL ?>/news.php">Xem tất cả</a>
                </div>
                <div class="np-grid">
                    <?php foreach (array_slice($related, 0, 3) as $item) {
                        $renderCard($item);
                    } ?>
                </div>
            </section>

        <?php else: ?>

            <header class="np-hero">
                <span class="np-eyebrow">Tin tức &amp; cẩm nang</span>
                <h1>Kiến thức cho mỗi hành trình điện</h1>
                <p>Kinh nghiệm chọn xe, sử dụng pin và bảo dưỡng từ catalog và hướng dẫn thực tế.</p>
            </header>

            <nav class="np-topics" aria-label="Lọc theo chủ đề">
                <a href="<?= BASE_URL ?>/news.php" class="<?= $activeTopic === '' ? 'active' : '' ?>" <?= $activeTopic === '' ? 'aria-current="true"' : '' ?>>Tất cả</a>
                <?php foreach ($topics as $slug => $label): ?>
                    <a href="<?= BASE_URL ?>/news.php?topic=<?= $h($slug) ?>" class="<?= $activeTopic === $slug ? 'active' : '' ?>" <?= $activeTopic === $slug ? 'aria-current="true"' : '' ?>><?= $h($label) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if ($activeTopic === ''): ?>
                <?php $featured = $articles[0]; ?>
                <section class="np-featured" aria-label="Bài viết nổi bật">
                    <a class="np-featured-media media-fit" href="<?= $h($articleUrl($featured)) ?>" style="--media-image: url('<?= $h($mediaUrl($featured)) ?>')" tabindex="-1" aria-hidden="true">
                        <img src="<?= $h($mediaUrl($featured)) ?>" alt="">
                    </a>
                    <div class="np-featured-body">
                        <span class="np-chip np-chip-solid"><?= $h($featured['category']) ?></span>
                        <h2><a href="<?= $h($articleUrl($featured)) ?>"><?= $h($featured['title']) ?></a></h2>
                        <p><?= $h($featured['description']) ?></p>
                        <div class="np-meta">
                            <time datetime="<?= $h($isoDate($featured['date'])) ?>"><?= $h($featured['date']) ?></time>
                            <span aria-hidden="true">·</span>
                            <span><?= $readMinutes($featured) ?> phút đọc</span>
                        </div>
                        <a class="np-button" href="<?= $h($articleUrl($featured)) ?>">
                            Đọc bài viết
                            <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                        </a>
                    </div>
                </section>
            <?php endif; ?>

            <section class="np-list" aria-labelledby="list-title">
                <div class="np-section-head">
                    <h2 id="list-title"><?= $activeTopic === '' ? 'Bài viết mới' : $h($topics[$activeTopic]) ?></h2>
                    <span><?= count($listed) ?> bài viết</span>
                </div>
                <div class="np-grid">
                    <?php foreach ($listed as $item) {
                        $renderCard($item);
                    } ?>
                </div>
            </section>

            <aside class="np-cta" aria-label="Liên hệ tư vấn">
                <div>
                    <strong>Chưa tìm thấy điều bạn cần?</strong>
                    <span>Tiệp Anh tư vấn trực tiếp tại cửa hàng và qua điện thoại <?= $h(STORE_PHONE) ?>.</span>
                </div>
                <div class="np-cta-actions">
                    <a class="np-button" href="tel:<?= $h(STORE_PHONE) ?>">
                        <span class="material-symbols-outlined" aria-hidden="true">call</span> Gọi ngay
                    </a>
                    <a class="np-button np-button-ghost" href="<?= BASE_URL ?>/contact.php">Liên hệ</a>
                </div>
            </aside>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

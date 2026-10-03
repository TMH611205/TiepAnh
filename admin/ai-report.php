<?php

require_once __DIR__ . '/../middleware/admin.php';
require_once __DIR__ . '/../models/AiReport.php';

$admin = Auth::require($pdo);

$catalog = Report::catalog();

// ---------- Nhận câu hỏi: AI chọn báo cáo rồi chuyển sang trang kết quả (GET, tải lại/ xuất Excel được) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();

    $question = trim((string)($_POST['question'] ?? ''));

    if ($question === '' || mb_strlen($question, 'UTF-8') > 300) {
        flash('error', 'Hãy nhập câu hỏi (tối đa 300 ký tự).');
        redirect(ADMIN_URL . '/ai-report.php');
    }

    $spec = AiReport::resolve($question);
    $_SESSION['ai_note'] = ['question' => $question, 'note' => $spec['note'], 'source' => $spec['source']];

    if ($spec['report'] === '') {
        redirect(ADMIN_URL . '/ai-report.php');
    }

    redirect(ADMIN_URL . '/ai-report.php?' . http_build_query(['report' => $spec['report']] + $spec['params']));
}

// Ghi chú của lần hỏi vừa rồi chỉ hiển thị một lần.
$info = $_SESSION['ai_note'] ?? null;
unset($_SESSION['ai_note']);
$reportKey = (string)($_GET['report'] ?? '');
$report = null;

if (isset($catalog[$reportKey])) {
    $report = Report::run($pdo, $reportKey, $_GET);

    if (wants_export()) {
        Xlsx::download('bao-cao-' . $reportKey . '-' . date('Y-m-d') . '.xlsx', [[
            'name' => $report['title'],
            'headers' => $report['headers'],
            'rows' => $report['rows'],
        ]]);
    }
}

$suggestions = [
    'Doanh thu 7 ngày qua theo từng ngày',
    'Top 5 xe bán chạy tháng này',
    'Doanh thu theo danh mục năm nay',
    'Xe nào sắp hết hàng?',
    'Ước tính lợi nhuận tháng trước',
    'Khách hàng mua nhiều nhất 90 ngày qua',
    'Chi phí nhập hàng theo nhà cung cấp tháng này',
    'Giá trị tồn kho hiện tại',
];

admin_page_header($admin, 'Báo cáo AI', 'ai');

?>

<section class="admin-card">
    <h2>Hỏi nhanh bằng tiếng Việt</h2>
    <p class="ai-note" style="margin-top: 0;">
        Gõ điều bạn muốn xem, hệ thống chọn báo cáo phù hợp và lập bảng thống kê, có thể xuất Excel.
        <?php if (AiReport::isAiConfigured()) : ?>
            <?= badge('AI Claude đang bật', 'ok') ?>
            Chỉ câu hỏi của bạn được gửi tới AI, không gửi dữ liệu khách hàng hay đơn hàng.
        <?php else : ?>
            <?= badge('Chế độ từ khóa', 'muted') ?>
            Chưa cấu hình <code>ANTHROPIC_API_KEY</code> trong <code>.env</code> nên hệ thống nhận diện câu hỏi bằng từ khóa.
        <?php endif; ?>
    </p>

    <form method="post" action="<?= ADMIN_URL ?>/ai-report.php" class="ai-prompt" id="aiForm">
        <?= csrf_field() ?>
        <label for="question" class="hide-visually" style="position:absolute;left:-9999px;">Câu hỏi thống kê</label>
        <textarea class="admin-textarea" id="question" name="question" rows="2" maxlength="300" required
            placeholder="Ví dụ: Top 5 xe bán chạy tháng này"><?= e($info['question'] ?? '') ?></textarea>
        <div class="admin-actions">
            <button type="submit" class="admin-button" id="aiSubmit">
                <span class="material-symbols-outlined" aria-hidden="true">auto_awesome</span> Lập bảng thống kê
            </button>
        </div>
        <div class="ai-suggestions" aria-label="Câu hỏi gợi ý">
            <?php foreach ($suggestions as $text) : ?>
                <button type="button" data-suggestion="<?= e($text) ?>"><?= e($text) ?></button>
            <?php endforeach; ?>
        </div>
    </form>
</section>

<?php if ($info !== null && $report === null) : ?>
    <div class="admin-alert error" role="status"><?= e($info['note'] ?: 'Không tìm thấy báo cáo phù hợp với câu hỏi.') ?></div>
<?php endif; ?>

<?php if ($report !== null) : ?>
    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <h2><?= e($report['title']) ?></h2>
                <span class="ai-note"><?= e($report['subtitle']) ?></span>
            </div>
            <?= export_button() ?>
        </div>

        <?php if ($info !== null && $info['note'] !== '') : ?>
            <p class="ai-note">
                <?= $info['source'] === 'ai' ? 'AI: ' : 'Hệ thống: ' ?><?= e($info['note']) ?>
            </p>
        <?php endif; ?>

        <?php if ($report['rows'] === []) : ?>
            <p class="admin-empty">Không có dữ liệu trong khoảng thời gian này.</p>
        <?php else : ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <?php foreach ($report['headers'] as $index => $header) : ?>
                                <?php $numeric = is_int($report['rows'][0][$index] ?? null) || is_float($report['rows'][0][$index] ?? null); ?>
                                <th class="<?= $numeric ? 'num' : '' ?>"><?= e($header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['rows'] as $row) : ?>
                            <tr>
                                <?php foreach ($row as $index => $cell) : ?>
                                    <?php $isNumber = is_int($cell) || is_float($cell); ?>
                                    <td class="<?= $isNumber ? 'num' : '' ?>">
                                        <?php if ($isNumber) : ?>
                                            <?= number_format($cell, 0, ',', '.') ?>
                                        <?php else : ?>
                                            <?= $row[0] === 'Tổng cộng' ? '<strong>' . e($cell) . '</strong>' : e($cell) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="ai-note">Số tiền tính bằng ₫. Doanh thu không tính đơn đã hủy.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<script>
    (function () {
        var form = document.getElementById('aiForm');
        var textarea = document.getElementById('question');
        var submit = document.getElementById('aiSubmit');

        document.querySelectorAll('[data-suggestion]').forEach(function (button) {
            button.addEventListener('click', function () {
                textarea.value = button.dataset.suggestion;
                form.requestSubmit();
            });
        });

        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.textContent = 'Đang phân tích...';
        });
    })();
</script>

<?php admin_page_footer(); ?>

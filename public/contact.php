<?php

$pageTitle = 'Liên hệ';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Mailer.php';

$contactReceiver = (string)env('CONTACT_RECEIVER_EMAIL', 'minhhoang06112005@gmail.com');
$contactOld = ['full_name' => '', 'phone' => '', 'email' => '', 'message' => ''];
$contactError = '';
$contactSuccess = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $contactOld = [
        'full_name' => trim((string)($_POST['full_name'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'message' => trim((string)($_POST['message'] ?? '')),
    ];

    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $contactError = 'Phiên làm việc hết hạn. Vui lòng thử lại.';
    } elseif (trim((string)($_POST['website'] ?? '')) !== '') {
        // Ô bẫy bot: người thật không điền. Báo thành công giả để bot không dò thêm.
        $contactSuccess = 'Đã gửi yêu cầu. Tiệp Anh sẽ liên hệ lại với bạn sớm nhất.';
        $contactOld = array_fill_keys(array_keys($contactOld), '');
    } elseif ($contactOld['full_name'] === '' || $contactOld['message'] === '') {
        $contactError = 'Vui lòng nhập họ tên và nội dung.';
    } elseif (!preg_match('/^[0-9 +().-]{8,20}$/', $contactOld['phone'])) {
        $contactError = 'Số điện thoại không hợp lệ.';
    } elseif ($contactOld['email'] !== '' && !filter_var($contactOld['email'], FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Email không hợp lệ.';
    } elseif (mb_strlen($contactOld['message']) > 3000 || mb_strlen($contactOld['full_name']) > 100) {
        $contactError = 'Nội dung quá dài.';
    } elseif (($_SESSION['contact_last_sent'] ?? 0) > time() - 30) {
        $contactError = 'Bạn vừa gửi yêu cầu. Vui lòng đợi ít giây rồi thử lại.';
    } else {
        $body = "Yêu cầu liên hệ mới từ website Tiệp Anh\n\n"
            . 'Họ tên: ' . $contactOld['full_name'] . "\n"
            . 'Điện thoại: ' . $contactOld['phone'] . "\n"
            . 'Email: ' . ($contactOld['email'] !== '' ? $contactOld['email'] : '(không có)') . "\n"
            . 'Thời gian: ' . date('d/m/Y H:i:s') . "\n\n"
            . "Nội dung:\n" . $contactOld['message'] . "\n";

        // Luôn lưu bản sao để không mất yêu cầu nếu gửi mail lỗi.
        $logDir = __DIR__ . '/../storage/logs';
        if (is_dir($logDir)) {
            @file_put_contents(
                $logDir . '/contact-messages.log',
                str_repeat('=', 40) . "\n" . $body . "\n",
                FILE_APPEND | LOCK_EX
            );
        }

        $mailError = Mailer::send(
            $contactReceiver,
            'Liên hệ mới từ ' . $contactOld['full_name'],
            $body,
            $contactOld['email'] !== '' ? $contactOld['email'] : null
        );

        if ($mailError === null) {
            $_SESSION['contact_last_sent'] = time();
            $contactSuccess = 'Đã gửi yêu cầu. Tiệp Anh sẽ liên hệ lại với bạn sớm nhất.';
            $contactOld = array_fill_keys(array_keys($contactOld), '');
        } else {
            error_log('Contact mail failed: ' . $mailError);
            $contactError = 'Chưa gửi được email lúc này. Vui lòng gọi ' . STORE_PHONE . ' để được hỗ trợ ngay.';
        }
    }
}

require_once __DIR__ . '/../views/layouts/header.php';

?>

<section class="contact-hero">

    <div class="page-container">

        <span class="page-eyebrow">
            LIÊN HỆ TIỆP ANH
        </span>

        <h1>
            Chúng tôi luôn<br>
            <span>sẵn sàng hỗ trợ bạn</span>
        </h1>

        <p>
            Bạn cần tư vấn sản phẩm, chính sách bảo hành,
            đơn hàng hoặc các dịch vụ khác?
        </p>

    </div>

</section>

<section class="info-section">

    <div class="page-container">

        <div class="contact-grid">

            <div>

                <span class="section-eyebrow">
                    THÔNG TIN LIÊN HỆ
                </span>

                <h2>
                    Hãy kết nối với Tiệp Anh
                </h2>

                <p class="contact-description">
                    Đội ngũ Tiệp Anh sẵn sàng hỗ trợ bạn trong quá trình
                    tìm hiểu và sử dụng sản phẩm.
                </p>

                <div class="contact-list">

                    <div class="contact-item">

                        <div class="contact-icon">
                            <span class="material-symbols-outlined">
                                call
                            </span>
                        </div>

                        <div>
                            <span>Hotline</span>
                            <strong><?= STORE_PHONE ?></strong>
                        </div>

                    </div>

                    <div class="contact-item">

                        <div class="contact-icon">
                            <span class="material-symbols-outlined">
                                mail
                            </span>
                        </div>

                        <div>
                            <span>Email</span>
                            <strong><a href="mailto:<?= htmlspecialchars(STORE_EMAIL, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(STORE_EMAIL, ENT_QUOTES, 'UTF-8') ?></a></strong>
                        </div>

                    </div>

                    <div class="contact-item">

                        <div class="contact-icon">
                            <span class="material-symbols-outlined">
                                location_on
                            </span>
                        </div>

                        <div>
                            <span>Địa chỉ</span>
                            <strong>
                                <?= htmlspecialchars(STORE_ADDRESS, ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                    </div>

                    <div class="contact-item">

                        <div class="contact-icon">
                            <span class="material-symbols-outlined">
                                schedule
                            </span>
                        </div>

                        <div>
                            <span>Hỗ trợ qua điện thoại</span>
                            <strong>
                                <a href="tel:<?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?></a>
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

            <div class="contact-form-card">

                <h3>
                    Gửi yêu cầu cho chúng tôi
                </h3>

                <p>
                    Điền thông tin bên dưới để Tiệp Anh liên hệ lại với bạn.
                </p>

                <form
                    method="POST"
                    action="<?= BASE_URL ?>/contact.php#contact-form"
                    id="contact-form">

                    <?php if ($contactSuccess !== ''): ?>
                        <div class="contact-alert success" role="status"><?= htmlspecialchars($contactSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php elseif ($contactError !== ''): ?>
                        <div class="contact-alert error" role="alert"><?= htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

                    <div class="form-group">
                        <label>Họ và tên *</label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars($contactOld['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="100"
                            placeholder="Nguyễn Văn A"
                            required>
                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label>Số điện thoại *</label>

                            <input
                                type="tel"
                                name="phone"
                                value="<?= htmlspecialchars($contactOld['phone'], ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="0975 303 993"
                                required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>

                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($contactOld['email'], ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="example@gmail.com">
                        </div>

                    </div>

                    <div class="form-group">
                        <label>Nội dung *</label>

                        <textarea
                            name="message"
                            rows="5"
                            maxlength="3000"
                            placeholder="Bạn cần Tiệp Anh hỗ trợ vấn đề gì?"
                            required><?= htmlspecialchars($contactOld['message'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <button
                        type="submit"
                        class="auth-submit">
                        Gửi yêu cầu

                        <span class="material-symbols-outlined">
                            send
                        </span>
                    </button>

                </form>

                <small class="contact-note">
                    * Cần hỗ trợ ngay? Vui lòng gọi <a href="tel:<?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?></a>.
                </small>

            </div>

        </div>

    </div>

</section>

<section class="contact-map">

    <div class="page-container">

        <?php $mapUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode(STORE_ADDRESS); ?>
        <div class="contact-map-card">
            <a
                class="contact-map-preview"
                href="<?= htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8') ?>"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Mở địa chỉ cửa hàng Tiệp Anh trên Google Maps">
                <iframe
                    src="https://maps.google.com/maps?q=<?= urlencode(STORE_ADDRESS) ?>&output=embed"
                    title="Bản đồ cửa hàng Tiệp Anh, <?= htmlspecialchars(STORE_ADDRESS, ENT_QUOTES, 'UTF-8') ?>"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    tabindex="-1"></iframe>
                <span class="map-open-badge">
                    <span class="material-symbols-outlined" aria-hidden="true">open_in_new</span>
                    Mở Google Maps
                </span>
            </a>
            <div class="contact-map-caption">
                <div>
                    <span class="section-eyebrow">CỬA HÀNG TIỆP ANH</span>
                    <h3><?= htmlspecialchars(STORE_ADDRESS, ENT_QUOTES, 'UTF-8') ?></h3>
                    <p>Hotline: <a href="tel:<?= STORE_PHONE ?>"><?= STORE_PHONE ?></a></p>
                </div>
                <a class="purchase-button" href="<?= htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                    Chỉ đường
                    <span class="material-symbols-outlined" aria-hidden="true">directions</span>
                </a>
            </div>
        </div>

    </div>

</section>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
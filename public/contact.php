<?php

$pageTitle = 'Liên hệ';

require_once __DIR__ . '/../config/app.php';

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
                    action="#contact-form"
                    id="contact-form">

                    <div class="form-group">
                        <label>Họ và tên *</label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Nguyễn Văn A"
                            required>
                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label>Số điện thoại *</label>

                            <input
                                type="tel"
                                name="phone"
                                placeholder="0975 303 993"
                                required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>

                            <input
                                type="email"
                                name="email"
                                placeholder="example@gmail.com">
                        </div>

                    </div>

                    <div class="form-group">
                        <label>Nội dung *</label>

                        <textarea
                            name="message"
                            rows="5"
                            placeholder="Bạn cần Tiệp Anh hỗ trợ vấn đề gì?"
                            required></textarea>
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
                    * Biểu mẫu đang được hoàn thiện. Để được hỗ trợ ngay,
                    vui lòng gọi <a href="tel:<?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(STORE_PHONE, ENT_QUOTES, 'UTF-8') ?></a>.
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
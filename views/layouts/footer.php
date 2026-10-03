</main>

<footer class="site-footer">

    <div class="container">

        <div class="footer-main">

            <!-- BRAND -->
            <div class="footer-brand">

                <div class="footer-logo">

                    <div class="footer-logo-icon"><img class="brand-logo" src="<?= ASSET_URL ?>/images/logo-icon.png" alt="" width="52" height="52"></div>

                    <div>
                        <strong>Xe điện Tiệp Anh</strong>
                        <span>THẾ HỆ XE ĐIỆN THÔNG MINH</span>
                    </div>

                </div>

                <p class="footer-description">
                    Tiệp Anh cung cấp các dòng xe điện hiện đại,
                    tiết kiệm năng lượng và phù hợp với nhu cầu
                    di chuyển hằng ngày.
                </p>

                <div class="footer-social">

                    <a href="<?= htmlspecialchars(STORE_FACEBOOK, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook Tiệp Anh">
                        <img class="footer-social-icon" src="https://cdn.simpleicons.org/facebook/FFFFFF" alt="">
                    </a>

                </div>

            </div>


            <!-- SẢN PHẨM -->
            <div class="footer-column">

                <h3>Sản phẩm</h3>

                <a href="<?= BASE_URL ?>/products.php">
                    Tất cả sản phẩm
                </a>

                <a href="<?= BASE_URL ?>/products.php?category=xe-may-dien">
                    Xe máy điện
                </a>

                <a href="<?= BASE_URL ?>/products.php?category=xe-dien-hoc-sinh">
                    Xe điện học sinh
                </a>

                <a href="<?= BASE_URL ?>/products.php?category=xe-dap-dien">
                    Xe đạp điện
                </a>

                <a href="<?= BASE_URL ?>/products.php?category=pin-ac-quy">
                    Pin & ắc quy
                </a>

                <a href="<?= BASE_URL ?>/products.php?category=phu-kien">
                    Phụ kiện
                </a>

                <a href="<?= BASE_URL ?>/products.php?promotion=1">
                    Ưu đãi
                </a>

            </div>


            <!-- HỖ TRỢ -->
            <div class="footer-column">

                <h3>Hỗ trợ & cẩm nang</h3>

                <a href="<?= BASE_URL ?>/contact.php">
                    Tư vấn bảo hành
                </a>

                <a href="<?= BASE_URL ?>/contact.php">
                    Liên hệ
                </a>

                <a href="<?= BASE_URL ?>/news.php">
                    Tin tức & hướng dẫn
                </a>

                <a href="<?= BASE_URL ?>/cart.php">
                    Giỏ hàng
                </a>

                <a href="<?= BASE_URL ?>/products.php?promotion=1">
                    Ưu đãi hiện có
                </a>

            </div>


            <!-- LIÊN HỆ -->
            <div class="footer-column footer-contact">

                <h3>Liên hệ</h3>

                <div class="footer-contact-item">

                    <span class="material-symbols-outlined">
                        phone
                    </span>

                    <div>

                        <small>Hotline</small>

                        <strong>
                            <?= STORE_PHONE ?>
                        </strong>

                    </div>

                </div>


                <div class="footer-contact-item">

                    <span class="material-symbols-outlined">
                        mail
                    </span>

                    <div>

                        <small>Email</small>

                        <strong>
                            <?= htmlspecialchars(STORE_EMAIL, ENT_QUOTES, 'UTF-8') ?>
                        </strong>

                    </div>

                </div>


                <div class="footer-contact-item">

                    <span class="material-symbols-outlined">
                        location_on
                    </span>

                    <div>

                        <small>Địa chỉ</small>

                        <strong>
                            <?= htmlspecialchars(STORE_ADDRESS, ENT_QUOTES, 'UTF-8') ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- FOOTER BOTTOM -->

        <div class="footer-bottom">

            <p>
                © <?= date('Y') ?>
                Xe điện Tiệp Anh.
                Tất cả quyền được bảo lưu.
            </p>

            <div class="footer-bottom-links">

                <a href="<?= BASE_URL ?>/about.php">
                    Về Tiệp Anh
                </a>

                <a href="<?= BASE_URL ?>/contact.php">
                    Liên hệ
                </a>

            </div>

        </div>

    </div>

</footer>

<div class="floating-contact-tools" aria-label="Liên hệ nhanh">
    <a
        class="floating-contact-button zalo-contact"
        href="https://zalo.me/<?= STORE_PHONE ?>"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Nhắn tin qua Zalo"
        title="Zalo">
        <img src="https://cdn.simpleicons.org/zalo/FFFFFF" alt="">
    </a>
    <a
        class="floating-contact-button facebook-contact"
        href="<?= htmlspecialchars(STORE_FACEBOOK, ENT_QUOTES, 'UTF-8') ?>"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Facebook Tiệp Anh"
        title="Facebook">
        <img src="https://cdn.simpleicons.org/facebook/FFFFFF" alt="">
    </a>
    <button
        class="floating-contact-button assistant-launcher"
        id="assistantLauncher"
        type="button"
        aria-label="Mở trợ lý tư vấn xe điện"
        aria-expanded="false"
        aria-controls="assistantPanel"
        title="Tư vấn xe điện">
        <span class="material-symbols-outlined" aria-hidden="true">smart_toy</span>
    </button>
</div>

<section class="assistant-panel" id="assistantPanel" aria-label="Trợ lý tư vấn xe điện" hidden>
    <header class="assistant-panel-header">
        <div>
            <span class="assistant-status-dot" aria-hidden="true"></span>
            <strong>Trợ lý xe điện Tiệp Anh</strong>
        </div>
        <button id="assistantClose" type="button" aria-label="Đóng trợ lý" title="Đóng">
            <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
    </header>
    <div class="assistant-quick-intents" aria-label="Nhu cầu nhanh">
        <button type="button" class="assistant-quick-intent" data-intent="school">Đi học</button>
        <button type="button" class="assistant-quick-intent" data-intent="distance">Đi xa</button>
        <button type="button" class="assistant-quick-intent" data-intent="speed">Tốc độ</button>
        <button type="button" class="assistant-quick-intent" data-intent="budget">Tiết kiệm</button>
    </div>
    <div class="assistant-messages" id="assistantMessages" role="log" aria-live="polite">
        <p class="assistant-message assistant-message-bot">Bạn muốn tìm mẫu xe hoặc so sánh thông số nào trong catalog?</p>
    </div>
    <form class="assistant-form" id="assistantForm">
        <label class="visually-hidden" for="assistantInput">Tin nhắn tư vấn</label>
        <input id="assistantInput" name="message" maxlength="600" autocomplete="off" placeholder="Hỏi về mẫu xe, giá, quãng đường..." required>
        <button type="submit" aria-label="Gửi tin nhắn" title="Gửi">
            <span class="material-symbols-outlined" aria-hidden="true">send</span>
        </button>
    </form>
</section>

<div class="comparison-tray" id="comparisonTray" hidden>
    <span><strong id="comparisonCount">0</strong> mẫu đã chọn</span>
    <button type="button" id="compareSelectedProducts" disabled>
        <span class="material-symbols-outlined" aria-hidden="true">compare_arrows</span>
        So sánh mẫu
    </button>
    <button class="comparison-tray-clear" type="button" id="clearSelectedProducts">Xóa chọn</button>
</div>

<dialog class="comparison-dialog" id="productComparisonDialog" aria-labelledby="comparisonTitle">
    <div class="comparison-dialog-header">
        <div>
            <span class="purchase-eyebrow">ĐỐI CHIẾU THEO CATALOG</span>
            <h2 id="comparisonTitle">So sánh mẫu xe</h2>
        </div>
        <button type="button" id="comparisonClose" aria-label="Đóng so sánh" title="Đóng">
            <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
    </div>
    <div class="comparison-dialog-body" id="comparisonResult" aria-live="polite"></div>
</dialog>

<!-- BOOTSTRAP -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<!-- MAIN JS -->

<script src="<?= asset('js/main.js') ?>"></script>
<script src="<?= asset('js/reveal.js') ?>"></script>

</body>

</html>
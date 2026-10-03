<?php

$pageTitle = 'Giới thiệu';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../views/layouts/header.php';
?>

<section class="info-hero">
    <div class="page-container">

        <span class="page-eyebrow">
            VỀ TIỆP ANH
        </span>

        <h1>
            Đồng hành cùng<br>
            <span>mọi hành trình xanh</span>
        </h1>

        <p>
            Tiệp Anh hướng tới việc cung cấp các sản phẩm xe điện
            chất lượng, an toàn và phù hợp với nhu cầu di chuyển
            hằng ngày của người Việt.
        </p>

    </div>
</section>

<section class="info-section">
    <div class="page-container">

        <div class="two-column">

            <div>
                <span class="section-eyebrow">
                    CÂU CHUYỆN TIỆP ANH
                </span>

                <h2>
                    Xe điện thông minh cho cuộc sống hiện đại
                </h2>

                <p>
                    Tiệp Anh tập trung cung cấp các dòng xe điện,
                    pin - ắc quy và phụ kiện phục vụ nhu cầu đi lại
                    hằng ngày.
                </p>

                <p>
                    Chúng tôi hướng tới trải nghiệm mua hàng minh bạch,
                    tư vấn rõ ràng và hỗ trợ khách hàng trong suốt
                    quá trình sử dụng sản phẩm.
                </p>

                <p>
                    Mỗi sản phẩm được lựa chọn dựa trên các tiêu chí
                    về thiết kế, hiệu suất, độ an toàn và khả năng
                    đáp ứng nhu cầu sử dụng thực tế.
                </p>
            </div>

            <div class="about-highlight">

                <div class="about-item">
                    <span class="material-symbols-outlined">
                        verified
                    </span>

                    <div>
                        <strong>Sản phẩm rõ ràng</strong>
                        <p>Thông tin và thông số sản phẩm minh bạch.</p>
                    </div>
                </div>

                <div class="about-item">
                    <span class="material-symbols-outlined">
                        support_agent
                    </span>

                    <div>
                        <strong>Tư vấn tận tâm</strong>
                        <p>Hỗ trợ khách hàng trước và sau khi mua.</p>
                    </div>
                </div>

                <div class="about-item">
                    <span class="material-symbols-outlined">
                        security
                    </span>

                    <div>
                        <strong>Bảo hành</strong>
                        <p>Hỗ trợ bảo hành và sửa chữa theo chính sách.</p>
                    </div>
                </div>

                <div class="about-item">
                    <span class="material-symbols-outlined">
                        eco
                    </span>

                    <div>
                        <strong>Di chuyển xanh</strong>
                        <p>Hướng tới phương tiện sạch và tiết kiệm.</p>
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>

<section class="info-section light">
    <div class="page-container">

        <div class="section-heading center">
            <span class="section-eyebrow">
                GIÁ TRỊ CỐT LÕI
            </span>

            <h2>
                Những điều Tiệp Anh hướng tới
            </h2>
        </div>

        <div class="value-grid">

            <div class="value-card">
                <span class="material-symbols-outlined">
                    handshake
                </span>

                <h3>Uy tín</h3>

                <p>
                    Xây dựng quan hệ lâu dài với khách hàng
                    bằng sự minh bạch và trách nhiệm.
                </p>
            </div>

            <div class="value-card">
                <span class="material-symbols-outlined">
                    lightbulb
                </span>

                <h3>Công nghệ</h3>

                <p>
                    Tiếp cận những công nghệ mới trong lĩnh vực
                    phương tiện điện.
                </p>
            </div>

            <div class="value-card">
                <span class="material-symbols-outlined">
                    favorite
                </span>

                <h3>Khách hàng</h3>

                <p>
                    Lấy trải nghiệm và nhu cầu thực tế của khách hàng
                    làm trọng tâm.
                </p>
            </div>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
-- Nội dung chi tiết sản phẩm (phiên bản/màu, pin & sạc, thông số đầy đủ, tính năng, bảo hành, FAQ, nguồn dữ liệu).
-- Lưu dạng JSON trong một cột để mỗi sản phẩm có bộ nội dung riêng. Chạy lại nhiều lần an toàn.
-- Cấu trúc JSON: xem database/seed_brand_products.php và views/partials/product-details.php.

SET NAMES utf8mb4;

ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
  CHECK (`details` IS NULL OR json_valid(`details`)) AFTER `specifications`;

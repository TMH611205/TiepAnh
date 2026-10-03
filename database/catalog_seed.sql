INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`, `status`) VALUES
(5, 'Xe điện học sinh', 'xe-dien-hoc-sinh', 'Các mẫu xe điện dành cho học sinh', 1);

INSERT INTO `products` (
  `category_id`, `name`, `slug`, `product_code`, `price`, `sale_price`,
  `image`, `description`, `specifications`, `max_speed`, `battery_range`,
  `motor_power`, `warranty_months`, `stock`, `status`
) VALUES
(1, 'Tiệp Anh Apex Grand Tourer', 'tiep-anh-apex-grand-tourer', 'TA-APEX', 35000000.00, 32800000.00, 'uploads\\products\\xe2.jpg', 'Xe máy điện touring cao cấp với động cơ 2000W và quãng đường tham khảo 110 km mỗi lần sạc.', 'Phanh ABS hai kênh; bảo hành pin 36 tháng.', NULL, 110, '2000W', 36, 4, 'active'),
(1, 'Tiệp Anh E-Sport Pro 2024', 'tiep-anh-e-sport-pro-2024', 'TA-ESPORT', 27500000.00, 24990000.00, 'uploads\\products\\xe3.jpg', 'Xe máy điện thể thao với động cơ 1500W, tốc độ tối đa 65 km/h và quãng đường tham khảo 95 km.', 'Giao xe tận nơi theo khu vực; bảo hành 36 tháng.', 65, 95, '1500W', 36, 7, 'active'),
(1, 'Tiệp Anh Aura Urban Scooter', 'tiep-anh-aura-urban-scooter', 'TA-AURA', 17500000.00, NULL, 'uploads\\products\\xe4.jpg', 'Xe điện đô thị nhỏ gọn, động cơ 1000W, pin tháo rời và quãng đường tham khảo 75 km.', 'Pin tháo rời; trọng lượng tham khảo 48 kg.', NULL, 75, '1000W', 24, 6, 'active'),
(2, 'Tiệp Anh City E-Bike Vintage', 'tiep-anh-city-e-bike-vintage', 'TA-CITY-E', 12500000.00, 11200000.00, 'uploads\\products\\xe6.jpg', 'Xe đạp điện trợ lực khung hợp kim nhôm với quãng đường tham khảo 60 km.', 'Khối lượng tham khảo 18.5 kg; hộp số Shimano 7 cấp.', NULL, 60, NULL, 24, 4, 'active'),
(1, 'Tiệp Anh CyberBlade Storm', 'tiep-anh-cyberblade-storm', 'TA-CYBER', 38500000.00, NULL, 'uploads\\products\\xe7.jpg', 'Xe máy điện hiệu năng cao với động cơ 3000W và quãng đường tham khảo 140 km mỗi lần sạc.', 'Hỗ trợ sạc nhanh theo cấu hình sản phẩm.', NULL, 140, '3000W', 36, 2, 'active')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `slug` = VALUES(`slug`),
  `price` = VALUES(`price`),
  `sale_price` = VALUES(`sale_price`),
  `image` = VALUES(`image`),
  `description` = VALUES(`description`),
  `specifications` = VALUES(`specifications`),
  `max_speed` = VALUES(`max_speed`),
  `battery_range` = VALUES(`battery_range`),
  `motor_power` = VALUES(`motor_power`),
  `warranty_months` = VALUES(`warranty_months`),
  `stock` = VALUES(`stock`),
  `status` = VALUES(`status`);

INSERT IGNORE INTO `products` (
  `category_id`, `name`, `slug`, `product_code`, `price`, `sale_price`,
  `image`, `description`, `specifications`, `max_speed`, `battery_range`,
  `motor_power`, `warranty_months`, `stock`, `status`
) VALUES
(5, 'Xe điện học sinh Tiệp Anh - Mẫu A', 'xe-dien-hoc-sinh-tiep-anh-mau-a', 'TA-STUDENT-A', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock'),
(5, 'Xe đạp điện học sinh Tiệp Anh - Mẫu B', 'xe-dap-dien-hoc-sinh-tiep-anh-mau-b', 'TA-STUDENT-B', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock'),
(3, 'Pin lithium xe điện', 'pin-lithium-xe-dien-lien-he', 'TA-BATTERY-LI', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock'),
(3, 'Ắc quy xe điện', 'ac-quy-xe-dien-lien-he', 'TA-BATTERY-AC', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock'),
(4, 'Bộ sạc xe điện', 'bo-sac-xe-dien-lien-he', 'TA-ACCESSORY-CHARGER', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock'),
(4, 'Mũ bảo hiểm xe điện', 'mu-bao-hiem-xe-dien-lien-he', 'TA-ACCESSORY-HELMET', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock'),
(4, 'Khóa chống trộm xe điện', 'khoa-chong-trom-xe-dien-lien-he', 'TA-ACCESSORY-LOCK', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock');
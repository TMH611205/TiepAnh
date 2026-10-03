-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 27, 2026 lúc 10:23 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `tiepanh`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `status`, `created_at`) VALUES
(1, 'Xe máy điện', 'xe-may-dien', 'Các dòng xe máy điện', 1, '2026-09-27 03:43:53'),
(2, 'Xe đạp điện', 'xe-dap-dien', 'Các dòng xe đạp điện', 1, '2026-09-27 03:43:53'),
(3, 'Pin & ắc quy', 'pin-ac-quy', 'Pin và ắc quy xe điện', 1, '2026-09-27 03:43:53'),
(4, 'Phụ kiện', 'phu-kien', 'Phụ kiện xe điện', 1, '2026-09-27 03:43:53'),
(5, 'Xe điện học sinh', 'xe-dien-hoc-sinh', 'Các mẫu xe điện dành cho học sinh', 1, '2026-09-27 10:30:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `cccd_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_code` varchar(50) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_series` varchar(100) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_cccd` varchar(20) DEFAULT NULL,
  `tax_code` varchar(50) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `vnpt_invoice_id` varchar(255) DEFAULT NULL,
  `vnpt_lookup_code` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','processing','issued','sent','failed') DEFAULT 'draft',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_cccd` varchar(20) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `shipping_fee` decimal(15,2) DEFAULT 0.00,
  `discount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `payment_method` enum('cod','bank_transfer','online') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','shipping','completed','cancelled') DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `serial_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `transaction_code` varchar(150) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `product_code` varchar(100) DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `max_speed` int(10) UNSIGNED DEFAULT NULL,
  `battery_range` int(10) UNSIGNED DEFAULT NULL,
  `motor_power` varchar(100) DEFAULT NULL,
  `warranty_months` int(11) DEFAULT 12,
  `stock` int(11) DEFAULT 0,
  `status` enum('active','hidden','out_of_stock') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `product_code`, `price`, `sale_price`, `image`, `description`, `specifications`, `max_speed`, `battery_range`, `motor_power`, `warranty_months`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Xe máy điện Tiệp Anh A1', 'xe-may-dien-tiep-anh-a1', 'TA-A1', 18500000.00, 17900000.00, 'uploads\\products\\xe1.jpg', 'Xe máy điện dành cho nhu cầu đi lại hằng ngày.', NULL, NULL, NULL, NULL, 24, 5, 'active', '2026-09-27 03:43:53', '2026-09-27 06:53:13'),
(2, 1, 'Xe máy điện Tiệp Anh A2', 'xe-may-dien-tiep-anh-a2', 'TA-A2', 21500000.00, 20900000.00, 'uploads\\products\\xe5.jpg', 'Thiết kế hiện đại, vận hành ổn định.', NULL, NULL, NULL, NULL, 24, 3, 'active', '2026-09-27 03:43:53', '2026-09-27 08:15:05');

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `product_code`, `price`, `sale_price`, `image`, `description`, `specifications`, `max_speed`, `battery_range`, `motor_power`, `warranty_months`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(3, 1, 'Tiệp Anh Apex Grand Tourer', 'tiep-anh-apex-grand-tourer', 'TA-APEX', 35000000.00, 32800000.00, 'uploads\\products\\xe2.jpg', 'Xe máy điện touring cao cấp với động cơ 2000W và quãng đường tham khảo 110 km mỗi lần sạc.', 'Phanh ABS hai kênh; bảo hành pin 36 tháng.', NULL, 110, '2000W', 36, 4, 'active', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(4, 1, 'Tiệp Anh E-Sport Pro 2024', 'tiep-anh-e-sport-pro-2024', 'TA-ESPORT', 27500000.00, 24990000.00, 'uploads\\products\\xe3.jpg', 'Xe máy điện thể thao với động cơ 1500W, tốc độ tối đa 65 km/h và quãng đường tham khảo 95 km.', 'Giao xe tận nơi theo khu vực; bảo hành 36 tháng.', 65, 95, '1500W', 36, 7, 'active', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(5, 1, 'Tiệp Anh Aura Urban Scooter', 'tiep-anh-aura-urban-scooter', 'TA-AURA', 17500000.00, NULL, 'uploads\\products\\xe4.jpg', 'Xe điện đô thị nhỏ gọn, động cơ 1000W, pin tháo rời và quãng đường tham khảo 75 km.', 'Pin tháo rời; trọng lượng tham khảo 48 kg.', NULL, 75, '1000W', 24, 6, 'active', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(6, 2, 'Tiệp Anh City E-Bike Vintage', 'tiep-anh-city-e-bike-vintage', 'TA-CITY-E', 12500000.00, 11200000.00, 'uploads\\products\\xe6.jpg', 'Xe đạp điện trợ lực khung hợp kim nhôm với quãng đường tham khảo 60 km.', 'Khối lượng tham khảo 18.5 kg; hộp số Shimano 7 cấp.', NULL, 60, NULL, 24, 4, 'active', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(7, 1, 'Tiệp Anh CyberBlade Storm', 'tiep-anh-cyberblade-storm', 'TA-CYBER', 38500000.00, NULL, 'uploads\\products\\xe7.jpg', 'Xe máy điện hiệu năng cao với động cơ 3000W và quãng đường tham khảo 140 km mỗi lần sạc.', 'Hỗ trợ sạc nhanh theo cấu hình sản phẩm.', NULL, 140, '3000W', 36, 2, 'active', '2026-09-27 10:30:00', '2026-09-27 10:30:00');

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `product_code`, `price`, `sale_price`, `image`, `description`, `specifications`, `max_speed`, `battery_range`, `motor_power`, `warranty_months`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(8, 5, 'Xe điện học sinh Tiệp Anh - Mẫu A', 'xe-dien-hoc-sinh-tiep-anh-mau-a', 'TA-STUDENT-A', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(9, 5, 'Xe đạp điện học sinh Tiệp Anh - Mẫu B', 'xe-dap-dien-hoc-sinh-tiep-anh-mau-b', 'TA-STUDENT-B', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(10, 3, 'Pin lithium xe điện', 'pin-lithium-xe-dien-lien-he', 'TA-BATTERY-LI', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(11, 3, 'Ắc quy xe điện', 'ac-quy-xe-dien-lien-he', 'TA-BATTERY-AC', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(12, 4, 'Bộ sạc xe điện', 'bo-sac-xe-dien-lien-he', 'TA-ACCESSORY-CHARGER', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(13, 4, 'Mũ bảo hiểm xe điện', 'mu-bao-hiem-xe-dien-lien-he', 'TA-ACCESSORY-HELMET', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00'),
(14, 4, 'Khóa chống trộm xe điện', 'khoa-chong-trom-xe-dien-lien-he', 'TA-ACCESSORY-LOCK', 0.00, NULL, NULL, 'Thông tin giá, hình ảnh và cấu hình đang được showroom cập nhật.', NULL, NULL, NULL, NULL, NULL, 0, 'out_of_stock', '2026-09-27 10:30:00', '2026-09-27 10:30:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_serials`
--

CREATE TABLE `product_serials` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `serial_number` varchar(150) NOT NULL,
  `vin` varchar(150) DEFAULT NULL,
  `motor_number` varchar(150) DEFAULT NULL,
  `battery_number` varchar(150) DEFAULT NULL,
  `status` enum('available','reserved','sold','warranty') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','admin','staff') DEFAULT 'customer',
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warranties`
--

CREATE TABLE `warranties` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `serial_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `warranty_start` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warranty_repairs`
--

CREATE TABLE `warranty_repairs` (
  `id` int(11) NOT NULL,
  `warranty_id` int(11) NOT NULL,
  `issue_description` text DEFAULT NULL,
  `repair_description` text DEFAULT NULL,
  `received_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `status` enum('received','checking','repairing','completed','returned') DEFAULT 'received',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `serial_id` (`serial_id`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Chỉ mục cho bảng `product_serials`
--
ALTER TABLE `product_serials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `warranties`
--
ALTER TABLE `warranties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `serial_id` (`serial_id`);

--
-- Chỉ mục cho bảng `warranty_repairs`
--
ALTER TABLE `warranty_repairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warranty_id` (`warranty_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `product_serials`
--
ALTER TABLE `product_serials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `warranties`
--
ALTER TABLE `warranties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `warranty_repairs`
--
ALTER TABLE `warranty_repairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_details_ibfk_3` FOREIGN KEY (`serial_id`) REFERENCES `product_serials` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `product_serials`
--
ALTER TABLE `product_serials`
  ADD CONSTRAINT `product_serials_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `warranties`
--
ALTER TABLE `warranties`
  ADD CONSTRAINT `warranties_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranties_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranties_ibfk_3` FOREIGN KEY (`serial_id`) REFERENCES `product_serials` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `warranty_repairs`
--
ALTER TABLE `warranty_repairs`
  ADD CONSTRAINT `warranty_repairs_ibfk_1` FOREIGN KEY (`warranty_id`) REFERENCES `warranties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

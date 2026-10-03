-- Mô-đun nhập hàng (chứng từ mua hàng) + giá vốn. Import sau tiepanh.sql. Chạy lại nhiều lần an toàn.

SET NAMES utf8mb4;

ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00 AFTER `sale_price`;

CREATE TABLE IF NOT EXISTS `purchase_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(50) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `supplier_phone` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_code` (`receipt_code`),
  KEY `idx_purchase_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_item_receipt` (`receipt_id`),
  KEY `idx_item_product` (`product_id`),
  CONSTRAINT `fk_item_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `purchase_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

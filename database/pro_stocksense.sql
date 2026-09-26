-- =====================================================================
-- StockSense Pro - Complete Relational Database Schema & Sample Data
-- Database Name: pro_stocksense
-- Designed for Odoo Hackathon Virtual Round
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `pro_stocksense` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pro_stocksense`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `stock_moves`;
DROP TABLE IF EXISTS `stock_adjustments`;
DROP TABLE IF EXISTS `internal_transfers`;
DROP TABLE IF EXISTS `delivery_items`;
DROP TABLE IF EXISTS `delivery_orders`;
DROP TABLE IF EXISTS `receipt_items`;
DROP TABLE IF EXISTS `receipts`;
DROP TABLE IF EXISTS `stock_levels`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `warehouses`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Table: users
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('manager', 'staff') NOT NULL DEFAULT 'staff',
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `otp_code` VARCHAR(10) DEFAULT NULL,
  `otp_expiry` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Table: categories
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Table: warehouses
-- ---------------------------------------------------------------------
CREATE TABLE `warehouses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `address` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Table: locations (Specific storage racks / zones per warehouse)
-- ---------------------------------------------------------------------
CREATE TABLE `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `warehouse_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `barcode` VARCHAR(50) DEFAULT NULL,
  `location_type` ENUM('internal', 'vendor', 'customer', 'adjustment') DEFAULT 'internal',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Table: products
-- ---------------------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(100) NOT NULL UNIQUE,
  `barcode` VARCHAR(100) DEFAULT NULL,
  `unit_of_measure` VARCHAR(50) DEFAULT 'Units',
  `cost_price` DECIMAL(10,2) DEFAULT 0.00,
  `selling_price` DECIMAL(10,2) DEFAULT 0.00,
  `reorder_min_level` INT DEFAULT 10,
  `reorder_max_level` INT DEFAULT 100,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Table: stock_levels (Real-time stock balance per product per location)
-- ---------------------------------------------------------------------
CREATE TABLE `stock_levels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) DEFAULT 0.00,
  `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_prod_loc` (`product_id`, `location_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Table: receipts (Incoming goods from Vendors)
-- ---------------------------------------------------------------------
CREATE TABLE `receipts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_name` VARCHAR(150) NOT NULL,
  `destination_location_id` INT NOT NULL,
  `status` ENUM('draft', 'waiting', 'ready', 'done', 'canceled') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `validated_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `validated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`destination_location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`validated_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Table: receipt_items
-- ---------------------------------------------------------------------
CREATE TABLE `receipt_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity_expected` DECIMAL(10,2) NOT NULL,
  `quantity_received` DECIMAL(10,2) DEFAULT 0.00,
  `unit_cost` DECIMAL(10,2) DEFAULT 0.00,
  FOREIGN KEY (`receipt_id`) REFERENCES `receipts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. Table: delivery_orders (Outgoing goods to Customers)
-- ---------------------------------------------------------------------
CREATE TABLE `delivery_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(150) NOT NULL,
  `source_location_id` INT NOT NULL,
  `status` ENUM('draft', 'waiting', 'ready', 'done', 'canceled') DEFAULT 'draft',
  `shipping_address` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `validated_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `validated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`source_location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`validated_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. Table: delivery_items
-- ---------------------------------------------------------------------
CREATE TABLE `delivery_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `delivery_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity_demanded` DECIMAL(10,2) NOT NULL,
  `quantity_picked` DECIMAL(10,2) DEFAULT 0.00,
  `quantity_packed` DECIMAL(10,2) DEFAULT 0.00,
  `unit_price` DECIMAL(10,2) DEFAULT 0.00,
  FOREIGN KEY (`delivery_id`) REFERENCES `delivery_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. Table: internal_transfers (Move stock inside company)
-- ---------------------------------------------------------------------
CREATE TABLE `internal_transfers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `source_location_id` INT NOT NULL,
  `destination_location_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `status` ENUM('draft', 'ready', 'done', 'canceled') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `validated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`source_location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`destination_location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. Table: stock_adjustments (Reconcile physical count vs recorded stock)
-- ---------------------------------------------------------------------
CREATE TABLE `stock_adjustments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `product_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `recorded_quantity` DECIMAL(10,2) NOT NULL,
  `counted_quantity` DECIMAL(10,2) NOT NULL,
  `difference_quantity` DECIMAL(10,2) NOT NULL,
  `reason` ENUM('damage', 'discrepancy', 'expiry', 'theft', 'other') DEFAULT 'discrepancy',
  `status` ENUM('draft', 'done') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `validated_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13. Table: stock_moves (Immutable Double-Entry Ledger / Move History)
-- ---------------------------------------------------------------------
CREATE TABLE `stock_moves` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `move_type` ENUM('receipt', 'delivery', 'internal', 'adjustment') NOT NULL,
  `reference_doc` VARCHAR(50) NOT NULL,
  `product_id` INT NOT NULL,
  `source_location_id` INT DEFAULT NULL,
  `destination_location_id` INT DEFAULT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `user_id` INT NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED SAMPLE DATA (Realistic data for immediate hackathon demo)
-- =====================================================================

-- Users (Default passwords: manager123 and staff123)
-- Using PHP standard bcrypt hashes:
-- manager123 -> $2y$10$n4qGz9N.W91d4R9VqN91Ye4cR4W9Z4qY2X2vV3wU4tT5sS6rR7qP2 (we will also seed via setup.php)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `phone`, `created_at`) VALUES
(1, 'Arjun Mehta', 'manager@stocksense.com', '$2y$10$ZV3L4YuvO5sNLuHX3UvDAud75y028kmnhoJxXuN.9x8Nq/JTu9KF2', 'manager', '+91 98200 12345', NOW()),
(2, 'Rohan Sharma', 'staff@stocksense.com', '$2y$10$WpP/c/8t9z3rzP.GroHVV.AycHEcbSjdp/y9D5W7PrGOlJkfWczZS', 'staff', '+91 98765 43210', NOW());

-- Categories
INSERT INTO `categories` (`id`, `name`, `code`, `description`) VALUES
(1, 'Raw Materials & Metals', 'CAT-RAW', 'Steel rods, aluminum sheets, copper piping, raw ingots'),
(2, 'Furniture & Workspace', 'CAT-FURN', 'Office chairs, standing desks, modular shelving, drawers'),
(3, 'Mechanical & Hardware', 'CAT-MECH', 'Ball bearings, high-tensile bolts, hydraulic pumps, gears'),
(4, 'Packaging & Shipping', 'CAT-PACK', 'Corrugated boxes, heavy-duty tape, pallets, bubble wrap');

-- Warehouses
INSERT INTO `warehouses` (`id`, `name`, `code`, `address`, `is_active`) VALUES
(1, 'Main Central Warehouse', 'WH-MAIN', 'Plot 4A, Bhiwandi Logistics Park, Thane, Maharashtra', 1),
(2, 'Secondary Production Depot', 'WH-PROD', 'Zone 12, Chakan Industrial Area, Pune, Maharashtra', 1);

-- Locations (Specific zones per problem statement)
INSERT INTO `locations` (`id`, `warehouse_id`, `name`, `barcode`, `location_type`) VALUES
(1, 1, 'Main Store', 'LOC-MS-01', 'internal'),
(2, 1, 'Rack A (Raw Metals)', 'LOC-RA-01', 'internal'),
(3, 1, 'Rack B (Finished Goods)', 'LOC-RB-01', 'internal'),
(4, 2, 'Production Floor', 'LOC-PF-01', 'internal'),
(5, 2, 'Assembly Bay 2', 'LOC-AB-02', 'internal');

-- Products (Including items from problem statement: Steel Rods, Ergonomic Chair, Industrial Bearings)
INSERT INTO `products` (`id`, `category_id`, `name`, `sku`, `barcode`, `unit_of_measure`, `cost_price`, `selling_price`, `reorder_min_level`, `reorder_max_level`, `description`) VALUES
(1, 1, 'Steel Rods 25mm Heavy Duty', 'STL-ROD-25', '890123456001', 'kg', 45.00, 68.00, 50, 300, 'Structural steel rods for heavy industrial framework'),
(2, 2, 'Ergonomic Mesh Office Chair', 'FUR-CHR-01', '890123456002', 'Units', 85.00, 149.00, 15, 80, 'High-grade lumbar support executive task chair'),
(3, 3, 'Industrial High-Speed Ball Bearings', 'BRG-IND-88', '890123456003', 'Units', 12.50, 24.00, 40, 250, 'Precision hardened chrome steel bearings (ISO certified)'),
(4, 1, 'Aluminum Alloy Ingot 99.7%', 'ALU-ING-99', '890123456004', 'kg', 28.00, 42.00, 100, 500, 'Pure primary foundry aluminum ingot'),
(5, 2, 'Heavy-Duty Steel Storage Rack', 'FUR-RCK-04', '890123456005', 'Units', 120.00, 210.00, 5, 25, '3-tier industrial shelving with 1200kg capacity'),
(6, 4, 'Double-Wall Corrugated Carton (L)', 'BOX-DW-01', '890123456006', 'Boxes', 2.80, 5.50, 200, 1000, 'Heavy duty double wall corrugated shipping box');

-- Initial Stock Levels
INSERT INTO `stock_levels` (`product_id`, `location_id`, `quantity`, `last_updated`) VALUES
(1, 1, 150.00, NOW()), -- Steel Rods in Main Store: 150 kg
(1, 4, 50.00, NOW()),  -- Steel Rods in Production Floor: 50 kg
(2, 3, 28.00, NOW()),  -- Ergonomic Chair in Rack B: 28 Units
(3, 1, 120.00, NOW()), -- Ball Bearings in Main Store: 120 Units
(3, 4, 8.00, NOW()),   -- Low stock in Production: 8 Units (Min is 40!) -> Triggers Low Stock Alert
(4, 1, 240.00, NOW()), -- Aluminum in Main Store: 240 kg
(5, 3, 4.00, NOW()),   -- Steel Storage Rack: 4 Units (Min is 5!) -> Triggers Low Stock Alert
(6, 1, 450.00, NOW()); -- Shipping boxes: 450 Boxes

-- Receipts (Incoming Goods)
INSERT INTO `receipts` (`id`, `reference_no`, `supplier_name`, `destination_location_id`, `status`, `notes`, `created_by`, `validated_by`, `created_at`, `validated_at`) VALUES
(1, 'REC-2026-0001', 'Bharat Steel & Alloys Co.', 1, 'done', 'Scheduled quarterly raw steel restock', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'REC-2026-0002', 'Godrej Ergo Furnishings Pvt Ltd', 3, 'ready', 'Waiting for unboxing and shelving verification', 1, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(3, 'REC-2026-0003', 'Konkan Bearings & Precision Pvt Ltd', 1, 'waiting', 'In transit via freight carrier', 2, NULL, NOW(), NULL);

INSERT INTO `receipt_items` (`receipt_id`, `product_id`, `quantity_expected`, `quantity_received`, `unit_cost`) VALUES
(1, 1, 100.00, 100.00, 45.00),
(2, 2, 20.00, 20.00, 85.00),
(3, 3, 150.00, 0.00, 12.50);

-- Delivery Orders (Outgoing Goods)
INSERT INTO `delivery_orders` (`id`, `reference_no`, `customer_name`, `source_location_id`, `status`, `shipping_address`, `created_by`, `validated_by`, `created_at`, `validated_at`) VALUES
(1, 'DEL-2026-0001', 'Bharat Modern Workspaces Pvt Ltd', 3, 'done', '452 Innovation Tower, Whitefield, Bengaluru, Karnataka', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'DEL-2026-0002', 'Shivani Precision Manufacturing Pvt Ltd', 1, 'ready', '788 Industrial Estate, Aurangabad, Maharashtra', 2, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(3, 'DEL-2026-0003', 'Metro Construction Enterprises Ltd', 1, 'waiting', '12 Dockyard Road, Mazgaon, Mumbai, Maharashtra', 1, NULL, NOW(), NULL);

INSERT INTO `delivery_items` (`delivery_id`, `product_id`, `quantity_demanded`, `quantity_picked`, `quantity_packed`, `unit_price`) VALUES
(1, 2, 10.00, 10.00, 10.00, 149.00),
(2, 3, 25.00, 25.00, 25.00, 24.00),
(3, 1, 20.00, 0.00, 0.00, 68.00);

-- Internal Transfers
INSERT INTO `internal_transfers` (`id`, `reference_no`, `source_location_id`, `destination_location_id`, `product_id`, `quantity`, `status`, `notes`, `created_by`, `created_at`, `validated_at`) VALUES
(1, 'INT-2026-0001', 1, 4, 1, 50.00, 'done', 'Transfer steel rods from Main Store to Production Rack for frame fabrication', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'INT-2026-0002', 1, 2, 4, 80.00, 'ready', 'Relocate aluminum from general storage to Rack A dedicated bay', 2, DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL);

-- Stock Adjustments
INSERT INTO `stock_adjustments` (`id`, `reference_no`, `product_id`, `location_id`, `recorded_quantity`, `counted_quantity`, `difference_quantity`, `reason`, `status`, `notes`, `created_by`, `created_at`, `validated_at`) VALUES
(1, 'ADJ-2026-0001', 1, 4, 53.00, 50.00, -3.00, 'damage', 'done', '3 kg steel rods bent during forklift movement. Scrapped.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Stock Ledger (Move History - Comprehensive audit trail)
INSERT INTO `stock_moves` (`id`, `move_type`, `reference_doc`, `product_id`, `source_location_id`, `destination_location_id`, `quantity`, `user_id`, `notes`, `created_at`) VALUES
(1, 'receipt', 'REC-2026-0001', 1, NULL, 1, 100.00, 1, 'Initial receipt of 100kg steel rods into Main Store', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'internal', 'INT-2026-0001', 1, 1, 4, 50.00, 1, 'Internal transfer from Main Store to Production Floor', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'delivery', 'DEL-2026-0001', 2, 3, NULL, -10.00, 1, 'Dispatched 10 ergonomic chairs to Bharat Modern Workspaces', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'adjustment', 'ADJ-2026-0001', 1, 4, NULL, -3.00, 1, 'Physical count write-off: 3 kg damaged steel rods', DATE_SUB(NOW(), INTERVAL 1 DAY));
-- =======================================================
-- WAREHOUSE MANAGEMENT SYSTEM
-- Database Schema & Initial Seed Data
-- Database: warehouse_management
-- Character Set: utf8mb4, Engine: InnoDB
-- =======================================================

CREATE DATABASE IF NOT EXISTS `warehouse_management` 
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `warehouse_management`;

-- -------------------------------------------------------
-- 1. Table structure for table `users`
-- -------------------------------------------------------
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table structure for table `items`
-- -------------------------------------------------------
CREATE TABLE `items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_code` VARCHAR(50) NOT NULL,
    `item_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `location` VARCHAR(50) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
    `unit` VARCHAR(20) NOT NULL,
    `reorder_level` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_items_item_code` (`item_code`),
    KEY `idx_items_category` (`category`),
    KEY `idx_items_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Table structure for table `stock_movements`
-- -------------------------------------------------------
CREATE TABLE `stock_movements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `movement_type` ENUM('IN', 'OUT') NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `movement_date` DATE NOT NULL,
    `reference_note` VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sm_item_id` (`item_id`),
    KEY `idx_sm_movement_date` (`movement_date`),
    KEY `idx_sm_created_by` (`created_by`),
    CONSTRAINT `fk_sm_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. Sample Seed Data
-- -------------------------------------------------------

-- Insert Default Admin (Password: admin123) and Default User (Password: user123)
INSERT INTO `users` (`id`, `full_name`, `username`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin', '$2y$12$XzsD1eFA7Ha3XQa8fq2K4OcjaBDEKg.bXJD1TpbcR9GjVoenHV5PC', 'admin', 1, NOW(), NOW()),
(2, 'Warehouse Demo Staff', 'demo', '$2y$12$VzUgYe6tZT/nkIvgMdULPOXd4elcs.TnHSLyYZ9ETBSL4pIL9YQEa', 'user', 1, NOW(), NOW());

-- Insert Required 5 Sample Items
INSERT INTO `items` (`id`, `item_code`, `item_name`, `category`, `location`, `quantity`, `unit`, `reorder_level`, `created_at`, `updated_at`) VALUES
(1, 'ITM-001', 'Industrial Gloves', 'Safety', 'A1-01', 120, 'pairs', 30, NOW(), NOW()),
(2, 'ITM-002', 'Packing Tape', 'Packaging', 'B2-04', 85, 'rolls', 20, NOW(), NOW()),
(3, 'ITM-003', 'Cardboard Box M', 'Packaging', 'B1-02', 40, 'pcs', 50, NOW(), NOW()),
(4, 'ITM-004', 'LED Work Light', 'Electrical', 'C3-07', 15, 'pcs', 10, NOW(), NOW()),
(5, 'ITM-005', 'Hydraulic Oil 5L', 'Maintenance', 'D1-03', 22, 'bottles', 15, NOW(), NOW());

-- Insert Sample Stock Movement Records
INSERT INTO `stock_movements` (`id`, `item_id`, `movement_type`, `quantity`, `movement_date`, `reference_note`, `created_by`, `created_at`) VALUES
-- ITM-001 history (100 IN, 30 IN, 10 OUT => 120)
(1, 1, 'IN', 100, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Initial Purchase Order PO-1001', 1, NOW()),
(2, 1, 'IN', 30, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Stock Restock PO-1025', 1, NOW()),
(3, 1, 'OUT', 10, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Dispatch to Assembly Line Req-501', 1, NOW()),

-- ITM-002 history (100 IN, 15 OUT => 85)
(4, 2, 'IN', 100, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Supplier Delivery PO-1005', 1, NOW()),
(5, 2, 'OUT', 15, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Packing Station Allocation Req-502', 1, NOW()),

-- ITM-003 history (50 IN, 10 OUT => 40 - Below Reorder Level of 50!)
(6, 3, 'IN', 50, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Bulk Shipment PO-1002', 1, NOW()),
(7, 3, 'OUT', 10, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Order Fulfillment Batch #12', 1, NOW()),

-- ITM-004 history (20 IN, 5 OUT => 15)
(8, 4, 'IN', 20, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Maintenance Equipment PO-1010', 1, NOW()),
(9, 4, 'OUT', 5, CURDATE(), 'Shop Floor Replacement Req-508', 1, NOW()),

-- ITM-005 history (25 IN, 3 OUT => 22)
(10, 5, 'IN', 25, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Lubricant Delivery PO-1008', 1, NOW()),
(11, 5, 'OUT', 3, CURDATE(), 'Forklift Service Job Req-509', 1, NOW());

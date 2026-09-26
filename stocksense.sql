-- ============================================================
-- StockSense - Inventory Management System
-- Database: stocksense
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS stocksense CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stocksense;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

INSERT INTO roles (role_id, role_name, description) VALUES
(1, 'inventory_manager', 'Manages incoming & outgoing stock, approvals, master data'),
(2, 'warehouse_staff', 'Performs transfers, picking, shelving and counting');

-- ============================================================
-- 2. UNITS OF MEASURE
-- ============================================================
CREATE TABLE units_of_measure (
    uom_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    symbol VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

INSERT INTO units_of_measure (uom_id, name, symbol) VALUES
(1, 'Piece', 'pcs'),
(2, 'Kilogram', 'kg'),
(3, 'Litre', 'L'),
(4, 'Box', 'box'),
(5, 'Meter', 'm'),
(6, 'Dozen', 'dz'),
(7, 'Ton', 'ton'),
(8, 'Gram', 'g');

-- ============================================================
-- 3. CATEGORIES
-- ============================================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (category_id, name, description) VALUES
(1, 'Raw Materials', 'Steel, cement, wood and other raw inputs'),
(2, 'Electronics', 'Electronic components and finished electronic goods'),
(3, 'Furniture', 'Office and home furniture items'),
(4, 'Packaging', 'Boxes, tapes, wrapping material'),
(5, 'Textiles', 'Fabric, yarn and garments'),
(6, 'Hardware Tools', 'Hand tools and hardware fittings'),
(7, 'Stationery', 'Office and stationery supplies'),
(8, 'Food Grains', 'Rice, wheat, pulses and grains');

-- ============================================================
-- 4. WAREHOUSES
-- ============================================================
CREATE TABLE warehouses (
    warehouse_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO warehouses (warehouse_id, name, address, city, is_active) VALUES
(1, 'Ahmedabad Main Warehouse', 'Plot 12, Vatva GIDC Industrial Estate', 'Ahmedabad', 1),
(2, 'Surat Textile Hub', 'Ring Road Industrial Area, Udhna', 'Surat', 1),
(3, 'Pune Production Unit', 'Chakan MIDC Phase II', 'Pune', 1);

-- ============================================================
-- 5. LOCATIONS (racks/zones within a warehouse)
-- ============================================================
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(30) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_loc_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE CASCADE,
    UNIQUE KEY uq_location_code (warehouse_id, code)
) ENGINE=InnoDB;

INSERT INTO locations (location_id, warehouse_id, name, code, is_active) VALUES
(1, 1, 'Main Store', 'AMD-MAIN', 1),
(2, 1, 'Rack A', 'AMD-RACKA', 1),
(3, 1, 'Rack B', 'AMD-RACKB', 1),
(4, 1, 'Production Floor', 'AMD-PROD', 1),
(5, 2, 'Main Store', 'SRT-MAIN', 1),
(6, 2, 'Dispatch Bay', 'SRT-DISP', 1),
(7, 3, 'Main Store', 'PUN-MAIN', 1),
(8, 3, 'Production Rack', 'PUN-PRACK', 1);

-- ============================================================
-- 6. SUPPLIERS
-- ============================================================
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO suppliers (supplier_id, name, contact_person, email, phone, address, is_active) VALUES
(1, 'Reliance Steel Traders', 'Rajesh Mehta', 'rajesh.mehta@reliancesteel.in', '9825012345', 'Naroda Industrial Area, Ahmedabad', 1),
(2, 'Bajaj Electronics Supply Co.', 'Sunita Bajaj', 'sunita@bajajelectro.in', '9898076543', 'Ring Road, Surat', 1),
(3, 'Patel Furniture Works', 'Kiran Patel', 'kiran.patel@patelfurniture.in', '9974512398', 'GIDC Vatva, Ahmedabad', 1),
(4, 'Sharma Packaging Industries', 'Vikram Sharma', 'vikram@sharmapack.in', '9765432190', 'MIDC Chakan, Pune', 1),
(5, 'Gupta Textiles Pvt Ltd', 'Anita Gupta', 'anita.gupta@guptatextiles.in', '9887654321', 'Udhna Industrial Estate, Surat', 1);

-- ============================================================
-- 7. USERS
-- ============================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role_id INT NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    otp_code VARCHAR(10) DEFAULT NULL,
    otp_expires_at DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- Demo credentials (password for both = Demo@123)
-- Hash generated with PHP password_hash(), bcrypt
-- Password for ALL demo users = Demo@123
INSERT INTO users (user_id, full_name, email, password_hash, phone, role_id, is_active) VALUES
(1, 'Aarav Shah', 'manager@stocksense.in', '$2y$10$.bM9yt4ntVulm00WQILp2OZr8trhfT6kqtzgtgSiUK2e6pIg2kKu6', '9825011111', 1, 1),
(2, 'Priya Nair', 'staff@stocksense.in', '$2y$10$.bM9yt4ntVulm00WQILp2OZr8trhfT6kqtzgtgSiUK2e6pIg2kKu6', '9825022222', 2, 1),
(3, 'Rohan Deshmukh', 'rohan.deshmukh@stocksense.in', '$2y$10$.bM9yt4ntVulm00WQILp2OZr8trhfT6kqtzgtgSiUK2e6pIg2kKu6', '9922033333', 2, 1),
(4, 'Sneha Iyer', 'sneha.iyer@stocksense.in', '$2y$10$.bM9yt4ntVulm00WQILp2OZr8trhfT6kqtzgtgSiUK2e6pIg2kKu6', '9845044444', 1, 1);

-- ============================================================
-- 8. PRODUCTS
-- ============================================================
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    uom_id INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reorder_point INT NOT NULL DEFAULT 10,
    reorder_qty INT NOT NULL DEFAULT 50,
    product_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(category_id),
    CONSTRAINT fk_product_uom FOREIGN KEY (uom_id) REFERENCES units_of_measure(uom_id),
    CONSTRAINT fk_product_creator FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

INSERT INTO products (product_id, name, sku, category_id, uom_id, unit_price, reorder_point, reorder_qty, created_by) VALUES
(1, 'Steel Rods 12mm', 'RM-STL-001', 1, 2, 62.50, 200, 500, 1),
(2, 'Cement Bags OPC 53', 'RM-CEM-002', 1, 4, 385.00, 50, 200, 1),
(3, 'Plywood Sheets 18mm', 'RM-PLY-003', 1, 1, 1450.00, 20, 60, 1),
(4, 'LED Bulb 9W', 'EL-LED-004', 2, 1, 95.00, 100, 300, 1),
(5, 'Copper Wire Roll 90m', 'EL-CWR-005', 2, 1, 1250.00, 15, 40, 1),
(6, 'Ceiling Fan 48 inch', 'EL-FAN-006', 2, 1, 1899.00, 10, 30, 1),
(7, 'Office Chair Ergonomic', 'FR-CHR-007', 3, 1, 4499.00, 5, 20, 1),
(8, 'Wooden Study Table', 'FR-TBL-008', 3, 1, 6250.00, 5, 15, 1),
(9, 'Corrugated Box Medium', 'PK-BOX-009', 4, 1, 18.00, 500, 1000, 1),
(10, 'Bubble Wrap Roll 50m', 'PK-BWR-010', 4, 5, 320.00, 30, 80, 1),
(11, 'Cotton Fabric Roll', 'TX-CFR-011', 5, 5, 145.00, 100, 250, 1),
(12, 'Denim Fabric Roll', 'TX-DFR-012', 5, 5, 210.00, 80, 200, 1),
(13, 'Hammer 1kg', 'HW-HAM-013', 6, 1, 210.00, 20, 50, 1),
(14, 'Screwdriver Set 6pc', 'HW-SDS-014', 6, 4, 350.00, 25, 60, 1),
(15, 'A4 Paper Ream', 'ST-PPR-015', 7, 4, 285.00, 40, 100, 1),
(16, 'Gel Pen Box (50pc)', 'ST-PEN-016', 7, 4, 175.00, 30, 80, 1),
(17, 'Basmati Rice 25kg Bag', 'FG-RIC-017', 8, 2, 1850.00, 40, 100, 1),
(18, 'Wheat Flour 25kg Bag', 'FG-WHT-018', 8, 2, 950.00, 40, 100, 1),
(19, 'Toor Dal 25kg Bag', 'FG-DAL-019', 8, 2, 2650.00, 20, 60, 1),
(20, 'Steel Almirah 2-Door', 'FR-ALM-020', 3, 1, 8750.00, 3, 10, 1);

-- ============================================================
-- 9. PRODUCT STOCK (current qty per product per location)
-- ============================================================
CREATE TABLE product_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_product_location (product_id, location_id),
    CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_ps_location FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO product_stock (product_id, location_id, quantity) VALUES
(1, 1, 350), (1, 2, 120),
(2, 1, 40),
(3, 1, 55),
(4, 1, 420), (4, 5, 60),
(5, 1, 22),
(6, 1, 18),
(7, 1, 12),
(8, 1, 8),
(9, 1, 1200), (9, 5, 300),
(10, 1, 45),
(11, 5, 210), (11, 6, 50),
(12, 5, 95),
(13, 1, 60),
(14, 1, 70),
(15, 1, 150),
(16, 1, 90),
(17, 1, 80), (17, 7, 40),
(18, 1, 65), (18, 7, 30),
(19, 1, 15),
(20, 1, 4);

-- ============================================================
-- 10. STOCK OPERATIONS (Receipts / Delivery / Internal / Adjustment)
-- ============================================================
CREATE TABLE stock_operations (
    operation_id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(30) NOT NULL UNIQUE,
    operation_type ENUM('receipt','delivery','internal','adjustment') NOT NULL,
    status ENUM('draft','waiting','ready','done','canceled') NOT NULL DEFAULT 'draft',
    supplier_id INT DEFAULT NULL,
    source_location_id INT DEFAULT NULL,
    destination_location_id INT DEFAULT NULL,
    scheduled_date DATE DEFAULT NULL,
    validated_at DATETIME DEFAULT NULL,
    created_by INT NOT NULL,
    validated_by INT DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_op_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    CONSTRAINT fk_op_source FOREIGN KEY (source_location_id) REFERENCES locations(location_id),
    CONSTRAINT fk_op_dest FOREIGN KEY (destination_location_id) REFERENCES locations(location_id),
    CONSTRAINT fk_op_creator FOREIGN KEY (created_by) REFERENCES users(user_id),
    CONSTRAINT fk_op_validator FOREIGN KEY (validated_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ============================================================
-- 11. STOCK OPERATION LINES
-- ============================================================
CREATE TABLE stock_operation_lines (
    line_id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    product_id INT NOT NULL,
    expected_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    done_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    uom_id INT NOT NULL,
    CONSTRAINT fk_line_operation FOREIGN KEY (operation_id) REFERENCES stock_operations(operation_id) ON DELETE CASCADE,
    CONSTRAINT fk_line_product FOREIGN KEY (product_id) REFERENCES products(product_id),
    CONSTRAINT fk_line_uom FOREIGN KEY (uom_id) REFERENCES units_of_measure(uom_id)
) ENGINE=InnoDB;

-- ============================================================
-- 12. STOCK MOVES (immutable ledger)
-- ============================================================
CREATE TABLE stock_moves (
    move_id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    product_id INT NOT NULL,
    from_location_id INT DEFAULT NULL,
    to_location_id INT DEFAULT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    move_type ENUM('in','out','internal','adjustment') NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_move_operation FOREIGN KEY (operation_id) REFERENCES stock_operations(operation_id) ON DELETE CASCADE,
    CONSTRAINT fk_move_product FOREIGN KEY (product_id) REFERENCES products(product_id),
    CONSTRAINT fk_move_from FOREIGN KEY (from_location_id) REFERENCES locations(location_id),
    CONSTRAINT fk_move_to FOREIGN KEY (to_location_id) REFERENCES locations(location_id),
    CONSTRAINT fk_move_creator FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ============================================================
-- 13. NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO notifications (user_id, message, type, is_read) VALUES
(1, 'Steel Almirah 2-Door stock is below reorder point', 'low_stock', 0),
(1, 'Office Chair Ergonomic stock is below reorder point', 'low_stock', 0),
(1, 'Receipt RCPT-1003 is waiting for approval', 'pending_receipt', 0),
(2, 'Delivery DEL-1002 assigned to you for picking', 'assignment', 0);

-- ============================================================
-- 14. ACTIVITY LOGS
-- ============================================================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SAMPLE OPERATIONS + LINES + MOVES (realistic demo history)
-- ============================================================

-- Receipt 1: Steel Rods from Reliance Steel Traders - DONE
INSERT INTO stock_operations (reference_no, operation_type, status, supplier_id, destination_location_id, scheduled_date, validated_at, created_by, validated_by, notes)
VALUES ('RCPT-1001', 'receipt', 'done', 1, 1, '2026-09-10', '2026-09-10 11:20:00', 2, 1, 'Monthly steel rod restock');
SET @op1 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op1, 1, 500, 500, 2);
INSERT INTO stock_moves (operation_id, product_id, to_location_id, quantity, move_type, created_by, created_at)
VALUES (@op1, 1, 1, 500, 'in', 1, '2026-09-10 11:20:00');

-- Receipt 2: Electronics from Bajaj Electronics - WAITING (pending approval)
INSERT INTO stock_operations (reference_no, operation_type, status, supplier_id, destination_location_id, scheduled_date, created_by, notes)
VALUES ('RCPT-1002', 'receipt', 'waiting', 2, 1, '2026-09-24', 3, 'LED bulbs bulk order');
SET @op2 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op2, 4, 300, 0, 1);

-- Receipt 3: Furniture from Patel Furniture - DRAFT
INSERT INTO stock_operations (reference_no, operation_type, status, supplier_id, destination_location_id, scheduled_date, created_by, notes)
VALUES ('RCPT-1003', 'receipt', 'draft', 3, 1, '2026-09-28', 2, 'Office chairs new batch');
SET @op3 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op3, 7, 20, 0, 1);

-- Delivery 1: Chairs to customer - DONE
INSERT INTO stock_operations (reference_no, operation_type, status, source_location_id, scheduled_date, validated_at, created_by, validated_by, notes)
VALUES ('DEL-1001', 'delivery', 'done', 1, '2026-09-15', '2026-09-15 15:40:00', 2, 1, 'Sales order SO-2201 - 10 chairs');
SET @op4 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op4, 7, 10, 10, 1);
INSERT INTO stock_moves (operation_id, product_id, from_location_id, quantity, move_type, created_by, created_at)
VALUES (@op4, 7, 1, 10, 'out', 1, '2026-09-15 15:40:00');

-- Delivery 2: Fabric to customer - READY (picked & packed, awaiting validation)
INSERT INTO stock_operations (reference_no, operation_type, status, source_location_id, scheduled_date, created_by, notes)
VALUES ('DEL-1002', 'delivery', 'ready', 5, '2026-09-25', 3, 'Sales order SO-2214 - cotton fabric');
SET @op5 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op5, 11, 40, 40, 5);

-- Delivery 3: Rice bags - WAITING
INSERT INTO stock_operations (reference_no, operation_type, status, source_location_id, scheduled_date, created_by, notes)
VALUES ('DEL-1003', 'delivery', 'waiting', 1, '2026-09-27', 2, 'Sales order SO-2220 - rice bags');
SET @op6 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op6, 17, 20, 0, 2);

-- Internal transfer 1: Steel rods Main Store -> Rack A - DONE
INSERT INTO stock_operations (reference_no, operation_type, status, source_location_id, destination_location_id, scheduled_date, validated_at, created_by, validated_by, notes)
VALUES ('INT-1001', 'internal', 'done', 1, 2, '2026-09-12', '2026-09-12 09:15:00', 3, 1, 'Reorganizing rack space');
SET @op7 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op7, 1, 120, 120, 2);
INSERT INTO stock_moves (operation_id, product_id, from_location_id, to_location_id, quantity, move_type, created_by, created_at)
VALUES (@op7, 1, 1, 2, 120, 'internal', 3, '2026-09-12 09:15:00');

-- Internal transfer 2: LED bulbs Ahmedabad -> Surat - READY
INSERT INTO stock_operations (reference_no, operation_type, status, source_location_id, destination_location_id, scheduled_date, created_by, notes)
VALUES ('INT-1002', 'internal', 'ready', 1, 5, '2026-09-26', 2, 'Balancing stock across warehouses');
SET @op8 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op8, 4, 60, 60, 1);

-- Adjustment 1: Damaged steel - DONE
INSERT INTO stock_operations (reference_no, operation_type, status, destination_location_id, scheduled_date, validated_at, created_by, validated_by, notes)
VALUES ('ADJ-1001', 'adjustment', 'done', 1, '2026-09-18', '2026-09-18 14:00:00', 3, 1, '3kg steel rods damaged during handling');
SET @op9 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op9, 1, 353, 350, 2);
INSERT INTO stock_moves (operation_id, product_id, from_location_id, quantity, move_type, created_by, created_at)
VALUES (@op9, 1, 1, 3, 'adjustment', 3, '2026-09-18 14:00:00');

-- Adjustment 2: Cement bags recount - DRAFT
INSERT INTO stock_operations (reference_no, operation_type, status, destination_location_id, scheduled_date, created_by, notes)
VALUES ('ADJ-1002', 'adjustment', 'draft', 1, '2026-09-26', 2, 'Monthly physical stock count - cement');
SET @op10 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op10, 2, 40, 38, 4);

-- Canceled example
INSERT INTO stock_operations (reference_no, operation_type, status, supplier_id, destination_location_id, scheduled_date, created_by, notes)
VALUES ('RCPT-1004', 'receipt', 'canceled', 4, 5, '2026-09-08', 2, 'Order canceled - supplier out of stock');
SET @op11 = LAST_INSERT_ID();
INSERT INTO stock_operation_lines (operation_id, product_id, expected_qty, done_qty, uom_id) VALUES (@op11, 9, 500, 0, 1);

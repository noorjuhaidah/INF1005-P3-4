-- =============================================================
-- lazydrip.sql
-- Full database schema for the LazyDrip café web app.
-- Stack: MySQL 8 / MariaDB 10.6+
--
-- Run this once on a clean database:
--   mysql -u root -p lazydrip < lazydrip.sql
--
-- Column names are verified against the PHP source files:
--   users            → auth/process_login.php, process_register.php
--   orders           → admin/orders.php, cart/checkout.php
--   menu_items       → admin/product_create.php, products.php
--   categories       → admin/product_create.php
--   reviews          → reviews.php
--   points_transactions → auth/process_register.php
--   contact_messages → contact.php
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Drop tables in safe order (child → parent)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS points_transactions;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- USERS
-- PK: user_id  (confirmed: process_login.php, process_register.php,
--               admin/orders.php, customer/dashboard.php)
-- =============================================================
CREATE TABLE users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    phone         VARCHAR(30)   DEFAULT NULL,
    role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    points        INT UNSIGNED  NOT NULL DEFAULT 0,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- LOGIN ATTEMPTS
-- Brute-force protection for authentication.
-- Tracks failed login attempts by email + IP combination.
-- =============================================================
CREATE TABLE login_attempts (
    email        VARCHAR(255) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    attempts     INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt DATETIME     NOT NULL,
    locked_until DATETIME     DEFAULT NULL,
    PRIMARY KEY (email, ip_address),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- CATEGORIES
-- PK: category_id  (confirmed: admin/product_create.php, products.php)
-- =============================================================
CREATE TABLE categories (
    category_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(80)  NOT NULL UNIQUE,
    sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- MENU ITEMS
-- PK: item_id  (confirmed: admin/products.php, product_create.php)
-- =============================================================
CREATE TABLE menu_items (
    item_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    item_name     VARCHAR(120) NOT NULL,
    description   TEXT         DEFAULT NULL,
    price         DECIMAL(8,2) NOT NULL,
    image_path    VARCHAR(255) DEFAULT NULL,
    is_available  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_category
        FOREIGN KEY (category_id) REFERENCES categories (category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- ORDERS
-- PK: order_id  (confirmed: admin/orders.php, cart/checkout.php)
-- =============================================================
CREATE TABLE orders (
    order_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status        ENUM('submitted','preparing','ready_for_pickup','completed','cancelled')
                  NOT NULL DEFAULT 'submitted',
    points_used   INT UNSIGNED  NOT NULL DEFAULT 0,   -- points redeemed on this order
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- ORDER ITEMS
-- Records individual line-items for each order.
-- Prices are snapshotted at order time so menu changes do not
-- alter historical totals.
-- =============================================================
CREATE TABLE order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL,
    item_id       INT UNSIGNED NOT NULL,
    item_name     VARCHAR(120) NOT NULL,   -- snapshot
    unit_price    DECIMAL(8,2) NOT NULL,   -- snapshot
    quantity      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    subtotal      DECIMAL(10,2) NOT NULL,  -- unit_price * quantity
    CONSTRAINT fk_oi_order
        FOREIGN KEY (order_id) REFERENCES orders (order_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_oi_item
        FOREIGN KEY (item_id) REFERENCES menu_items (item_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- REVIEWS
-- PK: id  — NOTE: reviews.php originally joined on u.id but
-- the users table uses user_id. The updated reviews.php in this
-- scope corrects the join to u.user_id. The reviews table keeps
-- its own PK as `id` since no PHP file references it by name.
-- =============================================================
CREATE TABLE reviews (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    review_text   TEXT         NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- POINTS TRANSACTIONS
-- Audit log of all point movements.
-- txn_type: 'bonus' | 'earn' | 'redeem'
-- points_delta: positive = earned/bonus, negative = redeemed
-- =============================================================
CREATE TABLE points_transactions (
    txn_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    order_id     INT UNSIGNED DEFAULT NULL,   -- NULL for bonus transactions
    txn_type     ENUM('bonus','earn','redeem') NOT NULL,
    points_delta INT          NOT NULL,       -- signed: + earn, - redeem
    note         VARCHAR(255) DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pt_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pt_order
        FOREIGN KEY (order_id) REFERENCES orders (order_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- CONTACT MESSAGES
-- Stores submissions from contact.php.
-- user_id is NULL when the submitter is not logged in.
-- =============================================================
CREATE TABLE contact_messages (
    message_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED DEFAULT NULL,
    name         VARCHAR(120) NOT NULL,
    email        VARCHAR(255) NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    message      TEXT         NOT NULL,
    is_read      TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cm_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- SEED DATA
-- =============================================================

-- --- Admin account -------------------------------------------
-- Seeded with a bcrypt password hash. Replace credentials after first login.
INSERT INTO users (full_name, email, password_hash, role, points, is_active)
VALUES (
    'LazyDrip Admin',
    'admin@lazydrip.sg',
    '$2y$12$eK7b0JsNZh5q4hH4C9RGOOVpuJJg7kv1qw4xYCCxC4HvvqSK6nBUy',
    'admin',
    0,
    1
);

-- --- Demo customer -------------------------------------------
-- Seeded with a bcrypt password hash for local testing only.
INSERT INTO users (full_name, email, password_hash, phone, role, points, is_active)
VALUES (
    'Jane Tan',
    'jane@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uP9E/bNE.',
    '+65 9123 4567',
    'customer',
    10,
    1
);

-- Sign-up bonus transaction for demo customer
INSERT INTO points_transactions (user_id, order_id, txn_type, points_delta, note)
VALUES (2, NULL, 'bonus', 10, 'Welcome bonus for creating an account');

-- --- Categories ----------------------------------------------
INSERT INTO categories (category_name, sort_order) VALUES
    ('Coffee',      1),
    ('Tea',         2),
    ('Cold Drinks',  3),
    ('Food',        4);

-- --- Menu items ----------------------------------------------
INSERT INTO menu_items (category_id, item_name, description, price, is_available) VALUES
-- Coffee
(1, 'Espresso',
    'A short, intense shot of our house blend — clean finish, no bitterness.',
    3.50, 1),
(1, 'Flat White',
    'Velvety microfoam poured over a double ristretto. The everyday workhorse.',
    5.50, 1),
(1, 'Iced Latte',
    'Double espresso over ice, topped with cold milk. Refreshing and smooth.',
    6.00, 1),
(1, 'Pour Over',
    'Single-origin beans, slow-brewed to order. Ask our staff for today\'s origin.',
    7.00, 1),
-- Tea
(2, 'Hojicha Latte',
    'Japanese roasted green tea with steamed oat milk. Earthy and warming.',
    6.00, 1),
(2, 'Chamomile',
    'Loose-leaf chamomile steep served in a glass pot. Calming and floral.',
    4.50, 1),
-- Cold drinks
(3, 'Sparkling Yuzu Lemonade',
    'House-pressed yuzu and lemon, topped with sparkling water. Bright and citrusy.',
    5.50, 1),
(3, 'Cold Brew',
    '18-hour cold-steeped coffee concentrate over ice. Smooth and low-acid.',
    6.50, 1),
-- Food
(4, 'Butter Croissant',
    'Laminated daily in-house. Best warm.',
    3.50, 1),
(4, 'Avocado Toast',
    'Sourdough, smashed avo, chilli flakes, lemon. Simple done right.',
    9.00, 1);


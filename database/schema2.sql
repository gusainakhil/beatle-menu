-- =====================================================================
-- Beatle Analytics Smart Menu System
-- Database Schema (MariaDB/MySQL)
-- Converted from the PostgreSQL SRS schema.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS report_snapshot;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS notification;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS order_item;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS qr_code;
DROP TABLE IF EXISTS table_room;
DROP TABLE IF EXISTS menu_item;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS app_user;
DROP TABLE IF EXISTS tax_config;
DROP TABLE IF EXISTS business_settings;
DROP TABLE IF EXISTS platform_setting;
DROP TABLE IF EXISTS business;

-- Legacy schema.sql tables. Dropping these makes it safe to migrate a local
-- database that previously used the older integer-id schema.
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS waiters;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS service_units;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS businesses;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- BUSINESS
-- ---------------------------------------------------------------------
CREATE TABLE business (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_type           ENUM('restaurant', 'hotel', 'villa', 'cafe', 'bar', 'resort', 'cloud_kitchen') NOT NULL,
    business_name           VARCHAR(255) NOT NULL,
    gst_number              VARCHAR(50),
    registration_number     VARCHAR(100),
    owner_name              VARCHAR(150) NOT NULL,
    phone_number            VARCHAR(20) NOT NULL,
    whatsapp_number         VARCHAR(20),
    email                   VARCHAR(255) NOT NULL,
    website                 VARCHAR(255),
    logo_url                TEXT,
    cover_image_url         TEXT,
    tagline                 VARCHAR(255),
    description             TEXT,
    opening_time            TIME,
    closing_time            TIME,
    weekly_off              ENUM('none', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') DEFAULT 'none',
    address                 TEXT,
    city                    VARCHAR(100),
    state                   VARCHAR(100),
    country                 VARCHAR(100),
    pin_code                VARCHAR(20),
    latitude                DECIMAL(10, 7) NOT NULL,
    longitude               DECIMAL(10, 7) NOT NULL,
    currency                VARCHAR(10) NOT NULL DEFAULT 'INR',
    language                VARCHAR(10) NOT NULL DEFAULT 'en',
    timezone                VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
    gps_radius_meters       INT NOT NULL DEFAULT 100,
    status                  ENUM('active', 'inactive', 'suspended', 'pending_verification') NOT NULL DEFAULT 'pending_verification',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_business_email (email),
    KEY idx_business_status (status),
    KEY idx_business_type (business_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- BUSINESS SETTINGS (1:1 with business)
-- ---------------------------------------------------------------------
CREATE TABLE business_settings (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL UNIQUE,
    tax_percentage          DECIMAL(5, 2) NOT NULL DEFAULT 0,
    service_charge_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0,
    number_of_tables        INT NOT NULL DEFAULT 0,
    number_of_rooms         INT NOT NULL DEFAULT 0,
    notification_prefs      JSON NOT NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_business_settings_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TAX CONFIG
-- ---------------------------------------------------------------------
CREATE TABLE tax_config (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    tax_name                VARCHAR(100) NOT NULL,
    percentage              DECIMAL(5, 2) NOT NULL,
    is_active               TINYINT(1) NOT NULL DEFAULT 1,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tax_config_business (business_id),
    CONSTRAINT fk_tax_config_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- USERS (Admin / Waiter / Super Admin)
-- ---------------------------------------------------------------------
CREATE TABLE app_user (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36),
    role                    ENUM('super_admin', 'admin', 'waiter') NOT NULL,
    name                    VARCHAR(150) NOT NULL,
    employee_id             VARCHAR(50),
    username                VARCHAR(100),
    password_hash           TEXT,
    google_id               VARCHAR(255),
    phone                   VARCHAR(20),
    email                   VARCHAR(255),
    address                 TEXT,
    photo_url               TEXT,
    joining_date            DATE,
    status                  ENUM('active', 'inactive', 'deactivated') NOT NULL DEFAULT 'active',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_business (business_id),
    KEY idx_user_role (role),
    UNIQUE KEY idx_user_business_username (business_id, username),
    UNIQUE KEY idx_user_email (email),
    CONSTRAINT fk_app_user_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT chk_admin_has_business CHECK (
        (role = 'super_admin' AND business_id IS NULL) OR
        (role IN ('admin', 'waiter') AND business_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CATEGORIES
-- ---------------------------------------------------------------------
CREATE TABLE category (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    name                    VARCHAR(150) NOT NULL,
    display_order           INT NOT NULL DEFAULT 0,
    is_active               TINYINT(1) NOT NULL DEFAULT 1,
    is_hidden               TINYINT(1) NOT NULL DEFAULT 0,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_category_business (business_id),
    KEY idx_category_business_order (business_id, display_order),
    CONSTRAINT fk_category_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- MENU ITEMS
-- ---------------------------------------------------------------------
CREATE TABLE menu_item (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    category_id             CHAR(36) NOT NULL,
    name                    VARCHAR(200) NOT NULL,
    description             TEXT,
    price                   DECIMAL(10, 2) NOT NULL,
    discount_price          DECIMAL(10, 2),
    dietary_type            ENUM('veg', 'nonveg', 'egg', 'jain') NOT NULL DEFAULT 'veg',
    spicy_level             ENUM('none', 'mild', 'medium', 'hot', 'extra_hot') NOT NULL DEFAULT 'none',
    prep_time_minutes       INT,
    image_url               TEXT,
    gallery_urls            JSON NOT NULL,
    is_recommended          TINYINT(1) NOT NULL DEFAULT 0,
    is_best_seller          TINYINT(1) NOT NULL DEFAULT 0,
    is_todays_special       TINYINT(1) NOT NULL DEFAULT 0,
    is_available            TINYINT(1) NOT NULL DEFAULT 1,
    display_order           INT NOT NULL DEFAULT 0,
    sku                     VARCHAR(100),
    barcode                 VARCHAR(100),
    is_active               TINYINT(1) NOT NULL DEFAULT 1,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_menu_item_business (business_id),
    KEY idx_menu_item_category (category_id),
    KEY idx_menu_item_business_available (business_id, is_available, is_active),
    UNIQUE KEY idx_menu_item_business_sku (business_id, sku),
    CONSTRAINT fk_menu_item_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_item_category
        FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE RESTRICT,
    CONSTRAINT chk_price_positive CHECK (price >= 0),
    CONSTRAINT chk_discount_price CHECK (discount_price IS NULL OR discount_price <= price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLES / ROOMS
-- ---------------------------------------------------------------------
CREATE TABLE table_room (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    type                    ENUM('table', 'room') NOT NULL,
    number_label            VARCHAR(50) NOT NULL,
    status                  ENUM('available', 'occupied', 'disabled') NOT NULL DEFAULT 'available',
    active_order_id         CHAR(36),
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_table_room_business (business_id),
    KEY idx_table_room_business_status (business_id, status),
    UNIQUE KEY idx_table_room_business_label (business_id, type, number_label),
    CONSTRAINT fk_table_room_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- QR CODES
-- ---------------------------------------------------------------------
CREATE TABLE qr_code (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    table_room_id           CHAR(36) NOT NULL,
    encrypted_token         VARCHAR(512) NOT NULL,
    qr_image_url            TEXT,
    is_active               TINYINT(1) NOT NULL DEFAULT 1,
    revoked_at              TIMESTAMP NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_qr_code_business (business_id),
    KEY idx_qr_code_table_room (table_room_id),
    UNIQUE KEY idx_qr_code_token (encrypted_token),
    CONSTRAINT fk_qr_code_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT fk_qr_code_table_room
        FOREIGN KEY (table_room_id) REFERENCES table_room(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ATTENDANCE
-- ---------------------------------------------------------------------
CREATE TABLE attendance (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id                 CHAR(36) NOT NULL,
    business_id             CHAR(36) NOT NULL,
    attendance_date         DATE NOT NULL,
    check_in_time           TIMESTAMP NULL,
    check_out_time          TIMESTAMP NULL,
    check_in_lat            DECIMAL(10, 7),
    check_in_long           DECIMAL(10, 7),
    check_out_lat           DECIMAL(10, 7),
    check_out_long          DECIMAL(10, 7),
    status                  ENUM('present', 'absent', 'late', 'half_day') NOT NULL DEFAULT 'absent',
    working_hours           DECIMAL(5, 2),
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_attendance_user (user_id),
    KEY idx_attendance_business_date (business_id, attendance_date),
    UNIQUE KEY idx_attendance_user_date (user_id, attendance_date),
    CONSTRAINT fk_attendance_user
        FOREIGN KEY (user_id) REFERENCES app_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ORDERS
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    table_room_id           CHAR(36) NOT NULL,
    assigned_waiter_id      CHAR(36),
    status                  ENUM('pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    subtotal                DECIMAL(12, 2) NOT NULL DEFAULT 0,
    tax_amount              DECIMAL(12, 2) NOT NULL DEFAULT 0,
    service_charge_amount   DECIMAL(12, 2) NOT NULL DEFAULT 0,
    total_amount            DECIMAL(12, 2) NOT NULL DEFAULT 0,
    is_paid                 TINYINT(1) NOT NULL DEFAULT 0,
    paid_at                 TIMESTAMP NULL,
    paid_confirmed_by       CHAR(36),
    customer_instructions   TEXT,
    cancel_reason           TEXT,
    cancelled_by            CHAR(36),
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_order_business (business_id),
    KEY idx_order_business_status (business_id, status),
    KEY idx_order_table_room_status (table_room_id, status),
    KEY idx_order_waiter (assigned_waiter_id),
    KEY idx_order_created_at (created_at),
    CONSTRAINT fk_orders_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_table_room
        FOREIGN KEY (table_room_id) REFERENCES table_room(id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_waiter
        FOREIGN KEY (assigned_waiter_id) REFERENCES app_user(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_paid_confirmed_by
        FOREIGN KEY (paid_confirmed_by) REFERENCES app_user(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES app_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE table_room
    ADD CONSTRAINT fk_table_room_active_order
    FOREIGN KEY (active_order_id) REFERENCES orders(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- ORDER ITEMS
-- ---------------------------------------------------------------------
CREATE TABLE order_item (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    order_id                CHAR(36) NOT NULL,
    menu_item_id            CHAR(36) NOT NULL,
    quantity                INT NOT NULL DEFAULT 1,
    unit_price              DECIMAL(10, 2) NOT NULL,
    special_instructions    TEXT,
    item_status             ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_order_item_order (order_id),
    KEY idx_order_item_menu_item (menu_item_id),
    CONSTRAINT fk_order_item_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_menu_item
        FOREIGN KEY (menu_item_id) REFERENCES menu_item(id) ON DELETE RESTRICT,
    CONSTRAINT chk_quantity_positive CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- FEEDBACK (1:1 with order, only after payment)
-- ---------------------------------------------------------------------
CREATE TABLE feedback (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    order_id                CHAR(36) NOT NULL UNIQUE,
    business_id             CHAR(36) NOT NULL,
    overall_rating          SMALLINT NOT NULL,
    food_rating             SMALLINT,
    service_rating          SMALLINT,
    staff_rating            SMALLINT,
    cleanliness_rating      SMALLINT,
    comment                 TEXT,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_feedback_business (business_id),
    CONSTRAINT fk_feedback_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT chk_ratings_range CHECK (
        overall_rating BETWEEN 1 AND 5 AND
        (food_rating IS NULL OR food_rating BETWEEN 1 AND 5) AND
        (service_rating IS NULL OR service_rating BETWEEN 1 AND 5) AND
        (staff_rating IS NULL OR staff_rating BETWEEN 1 AND 5) AND
        (cleanliness_rating IS NULL OR cleanliness_rating BETWEEN 1 AND 5)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- NOTIFICATIONS
-- ---------------------------------------------------------------------
CREATE TABLE notification (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    recipient_user_id       CHAR(36),
    order_id                CHAR(36),
    type                    ENUM(
        'new_order', 'order_accepted', 'order_preparing', 'order_ready',
        'order_served', 'waiter_assigned', 'feedback_received',
        'attendance_reminder', 'business_closed', 'payment_confirmed'
    ) NOT NULL,
    title                   VARCHAR(200) NOT NULL,
    body                    TEXT,
    is_read                 TINYINT(1) NOT NULL DEFAULT 0,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_business (business_id),
    KEY idx_notification_recipient (recipient_user_id, is_read),
    CONSTRAINT fk_notification_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES app_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ACTIVITY LOGS (audit trail)
-- ---------------------------------------------------------------------
CREATE TABLE activity_log (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36),
    actor_user_id           CHAR(36),
    action                  VARCHAR(150) NOT NULL,
    entity_type             VARCHAR(100),
    entity_id               CHAR(36),
    before_state            JSON,
    after_state             JSON,
    ip_address              VARCHAR(45),
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_activity_log_business (business_id),
    KEY idx_activity_log_actor (actor_user_id),
    KEY idx_activity_log_entity (entity_type, entity_id),
    KEY idx_activity_log_created_at (created_at),
    CONSTRAINT fk_activity_log_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_log_actor
        FOREIGN KEY (actor_user_id) REFERENCES app_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REPORT SNAPSHOTS
-- ---------------------------------------------------------------------
CREATE TABLE report_snapshot (
    id                      CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    business_id             CHAR(36) NOT NULL,
    report_type             VARCHAR(50) NOT NULL,
    period_start            DATE NOT NULL,
    period_end              DATE NOT NULL,
    data                    JSON NOT NULL,
    generated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report_snapshot_business_type (business_id, report_type, period_start),
    CONSTRAINT fk_report_snapshot_business
        FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PLATFORM SETTINGS
-- ---------------------------------------------------------------------
CREATE TABLE platform_setting (
    setting_key             VARCHAR(150) PRIMARY KEY,
    setting_value           JSON NOT NULL,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- END OF SCHEMA
-- =====================================================================

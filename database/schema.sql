-- ระบบเซ็นเอกสารออนไลน์ - โครงสร้างฐานข้อมูล
-- นำเข้าไฟล์นี้ผ่าน phpMyAdmin หรือคำสั่ง: mysql -u user -p dbname < database/schema.sql
-- ตารางทั้งหมดขึ้นต้นด้วย signflow_ เพื่อไม่ให้ชนกับตารางของระบบอื่นในฐานข้อมูลเดียวกัน

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS signflow_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS signflow_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_number VARCHAR(100) NOT NULL DEFAULT '',
    title VARCHAR(500) NOT NULL,
    description TEXT NULL,
    original_filename VARCHAR(255) NOT NULL,
    working_file_path VARCHAR(500) NOT NULL,
    page_count INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('draft', 'in_progress', 'ready_for_review', 'saved', 'cancelled') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    drive_file_id VARCHAR(255) NULL,
    drive_web_view_link VARCHAR(1000) NULL,
    drive_name VARCHAR(255) NULL,
    drive_mime_type VARCHAR(150) NULL,
    drive_size BIGINT UNSIGNED NULL,
    saved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_signflow_documents_created_by FOREIGN KEY (created_by) REFERENCES signflow_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS signflow_signers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    order_no INT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    position_title VARCHAR(255) NOT NULL,
    token CHAR(48) NOT NULL UNIQUE,
    status ENUM('pending', 'signed') NOT NULL DEFAULT 'pending',
    sig_page INT UNSIGNED NOT NULL DEFAULT 1,
    sig_x_pct DECIMAL(6,3) NOT NULL DEFAULT 10.000,
    sig_y_pct DECIMAL(6,3) NOT NULL DEFAULT 80.000,
    sig_w_pct DECIMAL(6,3) NOT NULL DEFAULT 22.000,
    signed_at DATETIME NULL,
    signed_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_signflow_signers_document FOREIGN KEY (document_id) REFERENCES signflow_documents(id) ON DELETE CASCADE,
    INDEX idx_signflow_signers_document (document_id, order_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS signflow_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS signflow_activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    message VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_signflow_activity_document FOREIGN KEY (document_id) REFERENCES signflow_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- หมายเหตุ: ยังไม่ต้องสร้างบัญชีผู้ใช้ที่นี่
-- หลังนำเข้าฐานข้อมูลแล้ว ให้เปิดหน้า public/setup.php เพื่อสร้างบัญชีเจ้าหน้าที่คนแรกด้วยตนเอง
-- (หน้านี้จะใช้งานได้เฉพาะตอนที่ยังไม่มีผู้ใช้ในระบบเท่านั้น เพื่อความปลอดภัย)

-- Ruth Jackson site — MySQL/MariaDB schema.
-- Run once in phpMyAdmin (select your database first, then Import this file or paste into SQL).
-- MariaDB 10.6+, utf8mb4.

CREATE TABLE IF NOT EXISTS users (
  id             CHAR(36) NOT NULL PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  token       CHAR(64) NOT NULL PRIMARY KEY,
  user_id     CHAR(36) NOT NULL,
  expires_at  DATETIME NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id                 CHAR(36) NOT NULL PRIMARY KEY,
  merchant_reference VARCHAR(80) NOT NULL,
  order_tracking_id  VARCHAR(80) NULL,
  user_id            CHAR(36) NULL,
  email              VARCHAR(190) NULL,
  phone              VARCHAR(40) NULL,
  program_id         VARCHAR(80) NOT NULL,
  affiliate_code     VARCHAR(20) NULL,
  amount             DECIMAL(10,2) NOT NULL,
  commission         DECIMAL(10,2) NULL,
  commission_status  ENUM('none','pending','requested','paid') NOT NULL DEFAULT 'none',
  currency           VARCHAR(8) NOT NULL DEFAULT 'USD',
  status             ENUM('PENDING','COMPLETED','FAILED','REVERSED','INVALID') NOT NULL DEFAULT 'PENDING',
  confirmation_code  VARCHAR(80) NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_ref (merchant_reference),
  KEY idx_tracking (order_tracking_id),
  KEY idx_user (user_id),
  KEY idx_status (status),
  KEY idx_affiliate (affiliate_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_clicks (
  id         CHAR(36) NOT NULL PRIMARY KEY,
  code       VARCHAR(20) NOT NULL,
  ip         VARCHAR(45) NULL,
  ref_path   VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_code_time (code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_payouts (
  id           CHAR(36) NOT NULL PRIMARY KEY,
  code         VARCHAR(20) NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  status       ENUM('requested','paid','rejected') NOT NULL DEFAULT 'requested',
  note         VARCHAR(255) NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at      DATETIME NULL,
  KEY idx_code (code),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
  id          CHAR(36) NOT NULL PRIMARY KEY,
  user_id     CHAR(36) NOT NULL,
  program_id  VARCHAR(80) NOT NULL,
  order_id    CHAR(36) NULL,
  status      ENUM('active','pending','revoked') NOT NULL DEFAULT 'active',
  progress    TINYINT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_program (user_id, program_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id          CHAR(36) NOT NULL PRIMARY KEY,
  user_id     CHAR(36) NOT NULL,
  sender      ENUM('customer','ruth') NOT NULL,
  body        TEXT NOT NULL,
  read_flag   TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_time (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  ip          VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

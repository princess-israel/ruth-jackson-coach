-- Affiliate tracking + payouts migration.
-- Run ONCE in phpMyAdmin (select the site database, then paste into the SQL tab and Go).
-- Safe to run on the existing live database; it only adds columns/tables.

-- 1) Credit each order to the affiliate who referred it, and track the commission.
ALTER TABLE orders
  ADD COLUMN affiliate_code    VARCHAR(20) NULL AFTER program_id,
  ADD COLUMN commission        DECIMAL(10,2) NULL AFTER amount,
  ADD COLUMN commission_status ENUM('none','pending','requested','paid') NOT NULL DEFAULT 'none' AFTER commission,
  ADD KEY idx_affiliate (affiliate_code);

-- 2) Every click on a referral link (?ref=CODE).
CREATE TABLE IF NOT EXISTS affiliate_clicks (
  id         CHAR(36) NOT NULL PRIMARY KEY,
  code       VARCHAR(20) NOT NULL,
  ip         VARCHAR(45) NULL,
  ref_path   VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_code_time (code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Payout requests (affiliate requests, admin pays via M-Pesa and marks paid).
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

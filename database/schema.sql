-- =====================================================================
-- StockSense IMS  |  Database schema  |  Modules M0 + M1 (Auth & Access)
-- Import this file in phpMyAdmin or:  mysql -u root -p < database/schema.sql
-- Every later module appends its own tables to this file.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS stocksense_ims
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stocksense_ims;

-- ---------------------------------------------------------------------
-- users: one row per account. is_verified = 1 only after e-mail OTP.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name       VARCHAR(60)  NOT NULL,
  email           VARCHAR(120) NOT NULL,
  phone           VARCHAR(15)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('inventory_manager','warehouse_staff') NOT NULL DEFAULT 'warehouse_staff',
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  is_verified     TINYINT(1)   NOT NULL DEFAULT 0,
  email_verified_at DATETIME   NULL,
  last_login_at   DATETIME     NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- otp_verifications: hashed 6-digit OTP, valid 120 seconds, 5 attempts.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS otp_verifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  purpose     VARCHAR(30)  NOT NULL DEFAULT 'register',
  otp_hash    CHAR(64)     NOT NULL,
  attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at  DATETIME     NOT NULL,
  consumed_at DATETIME     NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_otp_user (user_id, consumed_at, expires_at),
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- password_resets: SHA-256 of the e-mailed token; valid 8 h; one-time.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64)     NOT NULL,
  expires_at  DATETIME     NOT NULL,
  used_at     DATETIME     NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reset_token (token_hash),
  KEY idx_reset_user (user_id),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- rate_limits: sliding-window counters (login, OTP sends, forgot-password...)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  action      VARCHAR(40)  NOT NULL,
  identifier  VARCHAR(190) NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rate (action, identifier, created_at)
) ENGINE=InnoDB;

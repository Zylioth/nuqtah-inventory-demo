-- ============================================================================
--  Nuqtah IT Inventory Management System - Schema
--  Portfolio demo build
-- ============================================================================
--  Derived from a phpMyAdmin export of the live `nuqtah_inv` database
--  (MariaDB 10.4.20), then reconciled against the application source.
--
--  Corrections applied to the original export:
--    * `users.password_reset_code` and `users.password_reset_expires` were
--      missing from the export. Both are required by actions/forgot_password.php
--      and actions/reset_password.php, and are added here.
--
--  Structure only. Demo data lives in seed.sql.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Uncomment if you are importing into a server where the database does not
-- yet exist. Most managed hosts pre-create it and pin the name, so this is
-- left off by default.
-- CREATE DATABASE IF NOT EXISTS `nuqtah_inv`
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `nuqtah_inv`;

DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `borrowing_requests`;
DROP TABLE IF EXISTS `asset_tags`;
DROP TABLE IF EXISTS `assets`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------------------------
-- users
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id`               int(11) NOT NULL AUTO_INCREMENT,
  `full_name`             varchar(100) NOT NULL,
  `username`              varchar(50)  NOT NULL,
  `email`                 varchar(100) NOT NULL,
  `password`              varchar(255) NOT NULL,
  `role`                  enum('Admin','Staff','Student') NOT NULL,
  `account_status`        enum('Pending','Active','Suspended') DEFAULT 'Pending',
  `created_at`            timestamp NOT NULL DEFAULT current_timestamp(),
  `activation_token`      varchar(64) DEFAULT NULL,
  `password_reset_code`   varchar(64) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- assets
-- ----------------------------------------------------------------------------
-- `status` is stored, not derived, but it is expected to agree with
-- `current_stock` (see the Low Stock / Out of Stock thresholds in
-- admin/manage_assets.php and admin/index.php).
CREATE TABLE `assets` (
  `asset_id`      int(11) NOT NULL AUTO_INCREMENT,
  `asset_name`    varchar(100) NOT NULL,
  `category`      varchar(50)  NOT NULL,
  `total_stock`   int(11) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `status`        enum('Available','Low Stock','Out of Stock') DEFAULT 'Available',
  `asset_image`   varchar(255) NOT NULL DEFAULT 'default_asset.png',
  PRIMARY KEY (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- asset_tags
-- ----------------------------------------------------------------------------
-- One row per physical, barcoded unit. `unique_tag` is NOT constrained UNIQUE
-- in the original database; duplicate tags are prevented only by the
-- SELECT COUNT(*) guard in admin/actions/add_asset_tag.php.
--
-- Recommended hardening (not applied here, so the demo matches the shipped
-- behaviour):
--   ALTER TABLE `asset_tags` ADD UNIQUE KEY `unique_tag` (`unique_tag`);
CREATE TABLE `asset_tags` (
  `tag_id`     int(11) NOT NULL AUTO_INCREMENT,
  `asset_id`   int(11) DEFAULT NULL,
  `unique_tag` varchar(50) NOT NULL,
  `status`     enum('Available','On Loan','Issued','Maintenance','Damaged') NOT NULL DEFAULT 'Available',
  PRIMARY KEY (`tag_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `asset_tags_ibfk_1` FOREIGN KEY (`asset_id`)
    REFERENCES `assets` (`asset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- borrowing_requests
-- ----------------------------------------------------------------------------
-- `assigned_tag` is a legacy free-text column kept for historical rows;
-- current code paths use `tag_id` -> asset_tags.unique_tag instead.
CREATE TABLE `borrowing_requests` (
  `request_id`         int(11) NOT NULL AUTO_INCREMENT,
  `user_id`            int(11) NOT NULL,
  `asset_id`           int(11) NOT NULL,
  `tag_id`             int(11) DEFAULT NULL,
  `assigned_tag`       varchar(100) DEFAULT NULL,
  `quantity`           int(11) DEFAULT 1,
  `request_date`       timestamp NOT NULL DEFAULT current_timestamp(),
  `issued_date`        datetime DEFAULT NULL,
  `return_date`        date DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `status`             enum('Pending','Approved','On Loan','Returned','Rejected','Issued')
                         NOT NULL DEFAULT 'Pending',
  `admin_note`         text DEFAULT NULL,
  `condition_note`     text DEFAULT NULL,
  `return_note`        text DEFAULT NULL,
  `is_read`            tinyint(1) DEFAULT 0,
  PRIMARY KEY (`request_id`),
  KEY `fk_user` (`user_id`),
  KEY `fk_asset` (`asset_id`),
  KEY `fk_assigned_tag` (`tag_id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asset` FOREIGN KEY (`asset_id`)
    REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assigned_tag` FOREIGN KEY (`tag_id`)
    REFERENCES `asset_tags` (`tag_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- admin_logs
-- ----------------------------------------------------------------------------
CREATE TABLE `admin_logs` (
  `log_id`       int(11) NOT NULL AUTO_INCREMENT,
  `admin_id`     int(11) NOT NULL,
  `action_taken` varchar(255) NOT NULL,
  `timestamp`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `fk_admin` (`admin_id`),
  CONSTRAINT `fk_admin` FOREIGN KEY (`admin_id`)
    REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

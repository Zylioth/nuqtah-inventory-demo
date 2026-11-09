-- ============================================================================
--  Nuqtah IT Inventory Management System - Demo Seed Data
-- ============================================================================
--  Run after schema.sql. Safe to re-run: all tables are truncated first.
--
--  Demo credentials (every account shares one password):
--      password: demo1234
--
--      admin    / demo1234   -> Admin   (full back office)
--      aisyah   / demo1234   -> Student (has requests in several states)
--      hafiz    / demo1234   -> Student
--      nuriman  / demo1234   -> Staff
--      faridah  / demo1234   -> Staff
--      azman    / demo1234   -> Student, account_status = Pending
--      rosnah   / demo1234   -> Staff,   account_status = Suspended
--
--  All dates are relative to CURDATE()/NOW() so the demo does not age:
--  the overdue loan is always overdue, the pending requests are always new.
--
--  Every value of every ENUM is represented at least once, so no admin screen
--  renders empty on first load.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `admin_logs`;
TRUNCATE TABLE `borrowing_requests`;
TRUNCATE TABLE `asset_tags`;
TRUNCATE TABLE `assets`;
TRUNCATE TABLE `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- users
-- ----------------------------------------------------------------------------
-- Hash below is password_hash('demo1234', PASSWORD_DEFAULT), bcrypt cost 10.
INSERT INTO `users`
  (`user_id`, `full_name`, `username`, `email`, `password`, `role`, `account_status`, `created_at`, `activation_token`, `password_reset_code`, `password_reset_expires`)
VALUES
  (1, 'Demo Admin',    'admin',    'admin@nuqtah.demo',    '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Admin',   'Active',    DATE_SUB(NOW(), INTERVAL 120 DAY), NULL, NULL, NULL),
  (2, 'Aisyah Rahman', 'aisyah',   'aisyah@nuqtah.demo',   '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Student', 'Active',    DATE_SUB(NOW(), INTERVAL  90 DAY), NULL, NULL, NULL),
  (3, 'Hafiz Zulkifli','hafiz',    'hafiz@nuqtah.demo',    '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Student', 'Active',    DATE_SUB(NOW(), INTERVAL  88 DAY), NULL, NULL, NULL),
  (4, 'Nur Iman',      'nuriman',  'nuriman@nuqtah.demo',  '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Staff',   'Active',    DATE_SUB(NOW(), INTERVAL  75 DAY), NULL, NULL, NULL),
  (5, 'Faridah Yusof', 'faridah',  'faridah@nuqtah.demo',  '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Staff',   'Active',    DATE_SUB(NOW(), INTERVAL  60 DAY), NULL, NULL, NULL),
  (6, 'Azman Kadir',   'azman',    'azman@nuqtah.demo',    '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Student', 'Pending',   DATE_SUB(NOW(), INTERVAL   2 DAY), 'demo-pending-activation-token', NULL, NULL),
  (7, 'Rosnah Idris',  'rosnah',   'rosnah@nuqtah.demo',   '$2y$10$eCuAAZrduoLRwHEvSRr1ReIwlfKgVppu31bmdNPaf1NB7UzriWiIu', 'Staff',   'Suspended', DATE_SUB(NOW(), INTERVAL  45 DAY), NULL, NULL, NULL);

-- ----------------------------------------------------------------------------
-- assets
-- ----------------------------------------------------------------------------
-- Covers all three `status` values. current_stock drives the dashboard
-- Low Stock (1..5) and Out of Stock (0) counters.
--
-- NOTE: the dashboard derives "low stock" from current_stock alone and ignores
-- this `status` column, so the two must be kept in agreement or the dashboard
-- contradicts the badges in the inventory table. Every asset with
-- 0 < current_stock <= 5 is therefore seeded as 'Low Stock'. That threshold is
-- absolute, not proportional — see "What I'd do differently" in the README.
INSERT INTO `assets`
  (`asset_id`, `asset_name`, `category`, `total_stock`, `current_stock`, `status`, `asset_image`)
VALUES
  ( 1, 'Dell Latitude 5420',          'Laptops',    12,  9, 'Available',    '1775443676_Dell_Latitude_5420.jpg'),
  ( 2, 'MacBook Air M2 (Silver)',     'Laptops',     3,  0, 'Out of Stock', '1775443786_MacBook_Air_M2__Silver_.jfif'),
  ( 3, 'Epson EB-X06 Projector',      'Projectors',  4,  2, 'Low Stock',    '1777426160_Epson_EB_X06_Projector.jpg'),
  ( 4, 'HP EliteBook 840',            'Laptops',     8,  7, 'Available',    '1777426207_HP_EliteBook_840.jpg'),
  ( 5, 'Logitech MX Master 3S',       'Accessories',10,  8, 'Available',    '1777425839_Logitech_MX_Master_3S.jfif'),
  ( 6, 'Anker Type C Cable',          'Accessories',20, 16, 'Available',    '1776927749_Anker_Type_C_Cable.png'),
  ( 7, 'HDMI to USB-C Hub',           'Accessories',12,  3, 'Low Stock',    'default_asset.png'),
  ( 8, 'A4 Double A Paper',           'Consumables',50, 45, 'Available',    '1777426040_A4_Double_A_Paper.jpg'),
  ( 9, 'Epson 003 Black Ink',         'Consumables',10,  8, 'Available',    '1775527928_Epson_003_Black.webp'),
  (10, 'Insta360 X5',                 'Others',      8,  6, 'Available',    '1777425960_Insta360_X5.jpg');

-- ----------------------------------------------------------------------------
-- asset_tags
-- ----------------------------------------------------------------------------
-- Covers all five tag states: Available, On Loan, Issued, Maintenance, Damaged.
INSERT INTO `asset_tags` (`tag_id`, `asset_id`, `unique_tag`, `status`) VALUES
  -- Dell Latitude 5420 (asset 1, 6 units)
  ( 1,  1, 'ITQSHHB-LAP-001', 'Available'),
  ( 2,  1, 'ITQSHHB-LAP-002', 'On Loan'),
  ( 3,  1, 'ITQSHHB-LAP-003', 'Available'),
  ( 4,  1, 'ITQSHHB-LAP-004', 'Maintenance'),
  ( 5,  1, 'ITQSHHB-LAP-005', 'Available'),
  ( 6,  1, 'ITQSHHB-LAP-006', 'Damaged'),
  -- MacBook Air M2 (asset 2, 3 units)
  ( 7,  2, 'ITQSHHB-LAP-101', 'On Loan'),
  ( 8,  2, 'ITQSHHB-LAP-102', 'On Loan'),
  ( 9,  2, 'ITQSHHB-LAP-103', 'Issued'),
  -- Epson EB-X06 Projector (asset 3, 4 units)
  (10,  3, 'ITQSHHB-PROJ-001','Available'),
  (11,  3, 'ITQSHHB-PROJ-002','Available'),
  (12,  3, 'ITQSHHB-PROJ-003','On Loan'),
  (13,  3, 'ITQSHHB-PROJ-004','Available'),
  -- HP EliteBook 840 (asset 4, 8 units)
  (14,  4, 'ITQSHHB-LAP-201', 'Available'),
  (15,  4, 'ITQSHHB-LAP-202', 'Available'),
  (16,  4, 'ITQSHHB-LAP-203', 'On Loan'),
  (17,  4, 'ITQSHHB-LAP-204', 'Available'),
  -- Logitech MX Master 3S (asset 5, 5 units)
  (18,  5, 'ITQSHHB-ACC-001', 'Available'),
  (19,  5, 'ITQSHHB-ACC-002', 'Available'),
  (20,  5, 'ITQSHHB-ACC-003', 'Available'),
  -- Anker Type C Cable (asset 6, 20 units)
  (21,  6, 'ITQSHHB-CAB-001', 'Available'),
  (22,  6, 'ITQSHHB-CAB-002', 'Available'),
  (23,  6, 'ITQSHHB-CAB-003', 'Issued'),
  -- HDMI to USB-C Hub (asset 7, 12 units)
  (24,  7, 'ITQSHHB-HUB-001', 'Available'),
  (25,  7, 'ITQSHHB-HUB-002', 'Available'),
  -- A4 Double A Paper (asset 8, 50 units)
  (26,  8, 'ITQ-PAPER-001',   'Available'),
  -- Epson 003 Black Ink (asset 9, 10 units)
  (27,  9, 'ITQ-INK-001',     'Issued'),
  (28,  9, 'ITQ-INK-002',     'Available'),
  -- Insta360 X5 (asset 10, 2 units)
  (29, 10, 'ITQSHHB-OTH-001', 'Available'),
  (30, 10, 'ITQSHHB-OTH-002', 'Available');

-- ----------------------------------------------------------------------------
-- borrowing_requests
-- ----------------------------------------------------------------------------
-- All six status values present: Pending, Approved, On Loan, Returned,
-- Rejected, Issued. Request 3 is deliberately overdue (On Loan, past
-- return_date) to exercise the overdue counter on the admin dashboard.
-- Requests 1, 2, 5 and 8 are unread (is_read = 0) for notification badges.
INSERT INTO `borrowing_requests`
  (`request_id`, `user_id`, `asset_id`, `tag_id`, `assigned_tag`, `quantity`,
   `request_date`, `issued_date`, `return_date`, `actual_return_date`,
   `status`, `admin_note`, `condition_note`, `return_note`, `is_read`)
VALUES
  -- Pending (awaiting admin decision)
  ( 1, 2,  3, NULL, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL,
    'Pending', NULL, NULL, NULL, 0),
  ( 2, 3,  5, NULL, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL,
    'Pending', NULL, NULL, NULL, 0),

  -- On Loan - request 3 is OVERDUE by 2 days
  ( 3, 2,  1,    2, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL,
    'On Loan', NULL, 'unit in good condition, charger included', NULL, 1),
  ( 4, 3,  3,   12, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL,
    'On Loan', NULL, 'projector bag included', NULL, 1),

  -- Issued (collected but not yet confirmed as On Loan)
  ( 5, 4,  2,    7, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL,
    'Issued', NULL, NULL, NULL, 0),

  -- Returned (closed, with condition / return notes)
  ( 6, 2,  1,    1, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(CURDATE(), INTERVAL 17 DAY), DATE_SUB(NOW(), INTERVAL 17 DAY),
    'Returned', NULL, 'minor scuff on lid', '', 1),
  ( 7, 4,  7, NULL, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(CURDATE(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY),
    'Returned', NULL, '', 'one port is loose - flagged for inspection', 1),

  -- Approved (approved, awaiting physical handover/tag assignment)
  ( 8, 5,  9, NULL, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL,
    'Approved', NULL, NULL, NULL, 0),
  ( 9, 5, 10, NULL, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL,
    'Approved', NULL, NULL, NULL, 1),

  -- Rejected (with admin reason)
  (10, 5,  8, NULL, NULL, 1,
    DATE_SUB(NOW(), INTERVAL 4 DAY), NULL, NULL, NULL,
    'Rejected', 'Bulk consumables must be requested through the department head.', NULL, NULL, 1);

-- ----------------------------------------------------------------------------
-- admin_logs
-- ----------------------------------------------------------------------------
-- Mirrors the seeded request lifecycle so the audit log reads coherently.
INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action_taken`, `timestamp`) VALUES
  ( 1, 1, 'Admin Demo Admin ADDED a new asset: Dell Latitude 5420 (ID: #1) with initial stock of 6.', DATE_SUB(NOW(), INTERVAL 120 DAY)),
  ( 2, 1, 'Admin Demo Admin ADDED a new asset: Epson EB-X06 Projector (ID: #3) with initial stock of 4.', DATE_SUB(NOW(), INTERVAL 110 DAY)),
  ( 3, 1, 'Admin Demo Admin APPROVED request #6 (Dell Latitude 5420) for Aisyah Rahman.',        DATE_SUB(NOW(), INTERVAL  20 DAY)),
  ( 4, 1, 'Admin Demo Admin HANDED OVER Dell Latitude 5420 (Tag: ITQSHHB-LAP-001) to Aisyah Rahman. Status: On Loan.', DATE_SUB(NOW(), INTERVAL 19 DAY)),
  ( 5, 1, 'Admin Demo Admin processed RETURN for Request #6 (Dell Latitude 5420). Condition: Available.', DATE_SUB(NOW(), INTERVAL 17 DAY)),
  ( 6, 1, 'Admin Demo Admin REJECTED request #10 (A4 Double A Paper) for Faridah Yusof.',         DATE_SUB(NOW(), INTERVAL   4 DAY)),
  ( 7, 1, 'Admin Demo Admin APPROVED request #8 (Epson 003 Black Ink) for Faridah Yusof.',       DATE_SUB(NOW(), INTERVAL   1 DAY)),
  ( 8, 1, 'Admin Demo Admin UPDATED asset: HDMI to USB-C Hub (ID: #7). New Stock: 3/12.',        DATE_SUB(NOW(), INTERVAL   1 DAY)),
  ( 9, 1, 'Admin Demo Admin HANDED OVER MacBook Air M2 (Tag: ITQSHHB-LAP-101) to Nur Iman. Status: On Loan.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (10, 1, 'Added serial tag [ITQSHHB-OTH-002] to asset: Insta360 X5',                            DATE_SUB(NOW(), INTERVAL  12 HOUR));

-- Migration: Payment methods, notifications, and review analytics support
-- Date: 2026-06-05

-- 1) Ensure the payment_method table exists for dynamic checkout.
CREATE TABLE IF NOT EXISTS `payment_method` (
  `method_id` varchar(50) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Ensure a notifications table exists to log email alerts.
CREATE TABLE IF NOT EXISTS `notification` (
  `notification_id` varchar(50) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `recipient_role` varchar(30) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `email_to` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_user` (`user_id`),
  KEY `idx_notification_role` (`recipient_role`),
  KEY `idx_notification_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) Add transaction-friendly fields to the payment table if they are missing.
ALTER TABLE `payment`
  ADD COLUMN IF NOT EXISTS `transaction_reference` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `gateway_status` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_date` datetime DEFAULT NULL;

-- 4) Add notification-friendly indexes for faster lookup.
ALTER TABLE `payment`
  ADD INDEX IF NOT EXISTS `idx_payment_order` (`order_id`);

ALTER TABLE `notification`
  ADD INDEX IF NOT EXISTS `idx_notification_created_at` (`created_at`);

-- 5) Default payment method seed data for environments that have no dynamic methods yet.
INSERT INTO `payment_method` (`method_id`, `method_name`, `provider_name`, `active`) VALUES
  ('telebirr', 'Telebirr', 'Telebirr', 1),
  ('cbe', 'CBE Birr', 'CBE Birr', 1),
  ('chapa', 'Chapa', 'Chapa', 1),
  ('paypal', 'PayPal', 'PayPal', 1),
  ('stripe', 'Stripe', 'Stripe', 1),
  ('bank_transfer', 'Bank Transfer', 'Bank Transfer', 1)
ON DUPLICATE KEY UPDATE `method_name` = VALUES(`method_name`), `provider_name` = VALUES(`provider_name`), `active` = VALUES(`active`);

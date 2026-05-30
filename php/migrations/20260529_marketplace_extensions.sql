-- Migration: marketplace extensions (Refunds, Reviews, Delivery, Property Stakeholders)
-- Date: 2026-05-29
-- This migration extends existing marketplace tables without replacing current functionality.

-- 1) Extend payment table to link payments to orders.
ALTER TABLE `payment`
  ADD COLUMN IF NOT EXISTS `order_id` varchar(50) DEFAULT NULL;

ALTER TABLE `payment`
  ADD CONSTRAINT IF NOT EXISTS `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL;

-- 2) Extend orders table for delivery tracking and shipment state.
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `tracking_number` varchar(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `delivery_provider` varchar(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `delivery_status` varchar(30) DEFAULT 'Pending';

-- 3) Extend return_request with the requesting user and explicit type.
ALTER TABLE `return_request`
  ADD COLUMN IF NOT EXISTS `user_id` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `request_type` varchar(20) DEFAULT 'Return';

ALTER TABLE `return_request`
  ADD CONSTRAINT IF NOT EXISTS `fk_return_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- 4) Add order link to return records for easier refund tracking.
ALTER TABLE `return_record`
  ADD COLUMN IF NOT EXISTS `order_id` varchar(50) DEFAULT NULL;

ALTER TABLE `return_record`
  ADD CONSTRAINT IF NOT EXISTS `fk_return_record_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL;

-- 5) Extend service_contract to support contract completion state.
ALTER TABLE `service_contract`
  ADD COLUMN IF NOT EXISTS `status` varchar(20) NOT NULL DEFAULT 'Active';

-- 6) Extend review table for listing-based reviews and contract/order linking.
ALTER TABLE `review`
  ADD COLUMN IF NOT EXISTS `listing_type` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `listing_id` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `related_order_id` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `related_contract_id` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `title` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `approved` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `review`
  MODIFY COLUMN `recipient_id` varchar(50) DEFAULT NULL;

ALTER TABLE `review`
  ADD CONSTRAINT IF NOT EXISTS `fk_review_order` FOREIGN KEY (`related_order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL;

ALTER TABLE `review`
  ADD CONSTRAINT IF NOT EXISTS `fk_review_contract` FOREIGN KEY (`related_contract_id`) REFERENCES `service_contract` (`contract_id`) ON DELETE SET NULL;

-- 7) Add delivery status history tracking.
CREATE TABLE IF NOT EXISTS `delivery_status_history` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `location` varchar(128) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  KEY `fk_delivery_history_order` (`order_id`),
  CONSTRAINT `fk_delivery_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8) Add property stakeholder support.
CREATE TABLE IF NOT EXISTS `property_stakeholders` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `property_id` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `ownership_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  KEY `fk_property_stakeholder_property` (`property_id`),
  KEY `fk_property_stakeholder_user` (`user_id`),
  CONSTRAINT `fk_property_stakeholder_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_property_stakeholder_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9) Ownership enforcement triggers for property stakeholders.
DROP TRIGGER IF EXISTS `trg_property_ownership_sum`;
DELIMITER $$
CREATE TRIGGER `trg_property_ownership_sum`
BEFORE INSERT ON `property_stakeholders`
FOR EACH ROW
BEGIN
  DECLARE total decimal(7,2);
  SELECT IFNULL(SUM(ownership_percentage),0) INTO total
    FROM property_stakeholders
    WHERE property_id = NEW.property_id;
  IF total + NEW.ownership_percentage > 100 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Total ownership percentage for this property cannot exceed 100%';
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_property_ownership_sum_upd`;
DELIMITER $$
CREATE TRIGGER `trg_property_ownership_sum_upd`
BEFORE UPDATE ON `property_stakeholders`
FOR EACH ROW
BEGIN
  DECLARE total decimal(7,2);
  SELECT IFNULL(SUM(ownership_percentage),0) - OLD.ownership_percentage INTO total
    FROM property_stakeholders
    WHERE property_id = NEW.property_id;
  IF total + NEW.ownership_percentage > 100 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Total ownership percentage for this property cannot exceed 100%';
  END IF;
END$$
DELIMITER ;

-- 10) Helpful indexes for the new review and order links.
ALTER TABLE `orders`
  ADD INDEX IF NOT EXISTS `idx_orders_delivery_status` (`delivery_status`);

ALTER TABLE `payment`
  ADD INDEX IF NOT EXISTS `idx_payment_order` (`order_id`);

ALTER TABLE `review`
  ADD INDEX IF NOT EXISTS `idx_review_listing` (`listing_type`, `listing_id`);

ALTER TABLE `review`
  ADD INDEX IF NOT EXISTS `idx_review_order` (`related_order_id`);

ALTER TABLE `return_request`
  ADD INDEX IF NOT EXISTS `idx_return_request_order_item` (`order_item_id`);

-- End of migration

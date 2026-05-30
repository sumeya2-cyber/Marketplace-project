-- Fix for guest orders: Allow NULL user_id in orders table for guest checkouts
-- This migration removes the foreign key constraint that prevents guest orders

-- 1) Drop the foreign key constraint on orders.user_id
ALTER TABLE `orders`
  DROP FOREIGN KEY IF EXISTS `fk_orders_user`;

-- 2) Modify user_id to allow NULL for guest orders
ALTER TABLE `orders`
  MODIFY COLUMN `user_id` varchar(50) DEFAULT NULL;

-- 3) Re-add the foreign key constraint, but now it allows NULL values
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- Similarly, fix the order_item table if it has a foreign key constraint on user_id
ALTER TABLE `order_item`
  DROP FOREIGN KEY IF EXISTS `fk_order_item_user`;

-- 4) Also ensure order_item.order_id references orders correctly
ALTER TABLE `order_item`
  DROP FOREIGN KEY IF EXISTS `fk_order_item_order`;

ALTER TABLE `order_item`
  ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

-- Ensure return_request.user_id can be NULL for guest refunds
ALTER TABLE `return_request`
  MODIFY COLUMN `user_id` varchar(50) DEFAULT NULL;

-- Ensure review.user_id can be NULL for guest reviews
ALTER TABLE `review`
  MODIFY COLUMN `user_id` varchar(50) DEFAULT NULL;

-- End of migration

-- Run this SQL in phpMyAdmin to create the new tables

-- 1. Contact Inquiries table
CREATE TABLE IF NOT EXISTS `contact_inquiries` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(200) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add stock columns to products if missing
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `stock_xs` INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `stock_s`  INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `stock_m`  INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `stock_l`  INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `stock_xl` INT NOT NULL DEFAULT 0;

-- 3. Add image3 and image4 columns to products if missing
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `image3` VARCHAR(255) NULL AFTER `image2`,
  ADD COLUMN IF NOT EXISTS `image4` VARCHAR(255) NULL AFTER `image3`;

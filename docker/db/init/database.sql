SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

/* TODO: check out if charset can be utf-8 */

CREATE DATABASE IF NOT EXISTS `swimming` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `swimming`;

CREATE TABLE IF NOT EXISTS `cin` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row identification',
  `member_hash` text NOT NULL COMMENT 'Hashed sum of stable personal data',
  `cin` bigint(11) NOT NULL COMMENT 'Customer Identification number for norwegian swimming federation',
  `created_at` TIMESTAMP, 
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_hash` (`member_hash`),
  UNIQUE KEY `cin` (`cin`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `gender` text NOT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(31) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `zip` int(4) DEFAULT NULL,
  `license` text DEFAULT NULL COMMENT 'lisenced under club',
  `have_volunteered` tinyint(1) DEFAULT NULL COMMENT 'If true then this member has attended voulentary work',
  `cin_id` int(11) DEFAULT NULL, 
  `approved_at` date DEFAULT NULL COMMENT 'if date is set, then member is approved',
  `license_forwarded_at` date DEFAULT NULL COMMENT 'date when the license has been forwarded to NSF',
  `created_at` TIMESTAMP,  -- registration date
  `updated_at` TIMESTAMP, 
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `email` (`email`),
  FOREIGN KEY (`cin_id`) REFERENCES cin(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `ref_count` int(11) NOT NULL,
  `created_at` TIMESTAMP, 
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_hash` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` int(11) NOT NULL,
  `price_member` int(11) DEFAULT NULL,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `max_orders_per_customer_per_year` int(11) DEFAULT NULL,
  `require_phone` tinyint(1) NOT NULL DEFAULT '0',
  `require_email` tinyint(1) NOT NULL DEFAULT '0',
  `require_comment` tinyint(1) NOT NULL DEFAULT '0',
  `require_active_membership` tinyint(1) NOT NULL DEFAULT '0',
  `amount_available` int(11) DEFAULT NULL,
  `image_id` int(11) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` TIMESTAMP, 
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_hash` (`product_hash`),
  FOREIGN KEY (`image_id`) REFERENCES images(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(31),
  `intent_id` text ,
  `order_status` enum('PLACED','FINALIZED','DELIVERED','FAILED','REFUNDED') DEFAULT NULL,
  `comment` text default NULL,
  `created_at` TIMESTAMP, 
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES products(`id`),
  FOREIGN KEY (`phone`) REFERENCES members(`phone`),
  FOREIGN KEY (`email`) REFERENCES members(`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--INSERT INTO `images`(
--  `filename`,
--  `ref_count`
--)VALUES (
--  '31e61c8253b54cdde3b9.jpg',
--  1
--);
--
--INSERT INTO `products`(
--  `product_hash`,
--  `name`,
--  `description`,
--  `price`,
--  `image_id`,
--  `visible`
--) VALUES
--(
--  '31e61c8253b54cdde3b9',
--  '{\"no\":\"NSF Lisens\",\"en\":\"NSF License\"}',
--  '{\"no\":\"Lisensen gir deg adgang til nasjonsale stevner og obligatorisk treningsforsikring. Lisensen har en gyldighet i ett kalender år fra januar til desember.\",\"en\":\"Norwegian Swimming license is required for practices and national competitions. License is valid max one year from January until December.\"}',
--  76500,
--  1,
--  0
--);
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password_hash` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password_updated_at` date DEFAULT NULL,
  `created_at` TIMESTAMP, 
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- login credentials on /admin
INSERT INTO `users` (`username`, `password_hash`, `name`, `password_updated_at`) VALUES
('admin', '$2y$10$6DvTLWHY38TLZCfcKdQs5utG1EX39QcAmeVdA3FY6JEm6SVeGWyym', 'Administrator', NOW());

-- login credentials to database
GRANT ALL PRIVILEGES ON *.* TO `admin`@`%` IDENTIFIED BY PASSWORD '*C26EC909F368489BFCE885F5ED67303BB3822B41' WITH GRANT OPTION;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

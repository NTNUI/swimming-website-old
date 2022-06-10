SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `svommer_member` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `svommer_member`;

CREATE TABLE IF NOT EXISTS `member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` text,
  `surname` text,
  `gender` text,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `email` text,
  `address` text,
  `zip` int(4) DEFAULT NULL,
  `licensee` text COMMENT 'lisenced under club',
  `registration_date` date DEFAULT NULL COMMENT 'date when the form was filled out',
  `approved_date` date DEFAULT NULL COMMENT 'if date is set, then member is approved',
  `have_volunteered` tinyint(1) DEFAULT NULL COMMENT 'If true then this member has attended voulentary work',
  `license_forwarded` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'true if the license has been marked as forwarded to NSF',
  `CIN` bigint(20) DEFAULT NULL COMMENT 'Customer identification number for NSF license payments',
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `CIN` (`CIN`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `member_CIN` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row identification',
  `hash` text NOT NULL COMMENT 'Hashed sum of stable personal data',
  `NSF_CIN` bigint(11) NOT NULL COMMENT 'Customer Identification number for norwegian swimming federation',
  `last_used` date NOT NULL COMMENT 'Last time this row was an active member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  UNIQUE KEY `NSF_CIN` (`NSF_CIN`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `member_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `male_count` int(11) NOT NULL,
  `female_count` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE DATABASE IF NOT EXISTS `svommer_web` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `svommer_web`;

CREATE TABLE IF NOT EXISTS `friday_beer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- TODO: add timestamp
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `products_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `phone` text,
  `source_id` text COMMENT 'intent_id',
  `charge_id` text COMMENT 'deprecated',
  `order_status` enum('PLACED','FINALIZED','DELIVERED','FAILED','REFUNDED') DEFAULT NULL,
  `comment` text,
  `timestamp` date NOT NULL COMMENT 'Timestamp of last order status change' DEFAULT NOW(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`products_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(20) NOT NULL COMMENT 'product_hash',
  `name` text NOT NULL,
  `description` text,
  `price` int(11) NOT NULL,
  `price_member` int(11) DEFAULT NULL,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `max_orders_per_customer_per_year` int(11) DEFAULT NULL,
  `require_phone` tinyint(1) NOT NULL DEFAULT '0',
  `require_email` tinyint(1) NOT NULL DEFAULT '0',
  `require_comment` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Show and require comment from user',
  `require_active_membership` tinyint(1) NOT NULL DEFAULT '0',
  `amount_available` int(11) DEFAULT NULL,
  `image` text,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `group_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_id` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `products` (
  `hash`,
  `name`,
  `description`,
  `price`,
  `available_from`,
  `available_until`,
  `max_orders_per_customer_per_year`,
  `require_phone`,
  `require_email`,
  `require_comment`,
  `require_active_membership`,
  `amount_available`,
  `image`,
  `visible`,
  `archived`,
  `group_id`
) VALUES
(
  '31e61c8253b54cdde3b9',
  '{\"no\":\"NSF Lisens\",\"en\":\"NSF License\"}',
  '{\"no\":\"Lisensen gir deg adgang til nasjonsale stevner og obligatorisk treningsforsikring. Lisensen har en gyldighet i ett kalender år fra januar til desember.\",\"en\":\"Norwegian Swimming license is required for practices and national competitions. License is valid max one year from January until December.\"}',
  76500,
  NULL,
  NULL,
  1,
  1,
  1,
  0,
  0,
  NULL,
  '31e61c8253b54cdde3b9.jpg',
  0,
  0,
  1
);

CREATE TABLE IF NOT EXISTS `product_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `product_groups` (`name`) VALUES
('default');

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `roles` (`name`) VALUES
('admin'),
('cashier'),
('membership'),
('pr'),
('default');

CREATE TABLE IF NOT EXISTS `role_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('ALLOW','DENY') NOT NULL DEFAULT 'DENY',
  `role` int(11) NOT NULL,
  `page` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `role_access` (`type`, `role`, `page`) VALUES
-- admin
('ALLOW', 1, '*'),
-- cachier
('ALLOW', 2, 'admin/autopay'),
('ALLOW', 2, 'admin/changepass'),
('ALLOW', 2, 'admin/friday_beer'),
('ALLOW', 2, 'admin/isMember'),
('ALLOW', 2, 'admin/kid'),
('ALLOW', 2, 'admin/logout'),
('ALLOW', 2, 'admin/member_register'),
('ALLOW', 2, 'admin/store'),
('ALLOW', 2, 'api/isMember'),
('ALLOW', 2, 'api/kid'),
('ALLOW', 2, 'api/member_register'),
('ALLOW', 2, 'api/store'),
-- member
('ALLOW', 3, 'admin/changepass'),
('ALLOW', 3, 'admin/friday_beer'),
('ALLOW', 3, 'admin/kid'),
('ALLOW', 3, 'admin/logout'),
('ALLOW', 3, 'api/kid'),
-- pr
('ALLOW', 4, 'admin/changepass'),
('ALLOW', 4, 'admin/friday_beer'),
('ALLOW', 4, 'admin/logout'),
('ALLOW', 4, 'admin/translations'),
('ALLOW', 4, 'api/translations'),
-- default
('ALLOW', 5, 'admin/changepass'),
('ALLOW', 5, 'admin/friday_beer'),
('ALLOW', 5, 'admin/logout'),
('DENY', 5, '*');

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL DEFAULT '',
  `passwd` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `last_password` date DEFAULT NULL,
  `role` int(11) NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- login credentials on /admin
INSERT INTO `users` (`username`, `passwd`, `name`, `last_password`, `role`) VALUES
('admin', '$2y$10$6DvTLWHY38TLZCfcKdQs5utG1EX39QcAmeVdA3FY6JEm6SVeGWyym', 'Administrator', NOW(), 1);

-- login credentials to database
GRANT ALL PRIVILEGES ON *.* TO `admin`@`%` IDENTIFIED BY PASSWORD '*C26EC909F368489BFCE885F5ED67303BB3822B41' WITH GRANT OPTION;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

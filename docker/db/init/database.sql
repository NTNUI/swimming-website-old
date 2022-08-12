SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `member` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `member`;

/*
"id",
"name",
"gender",
"birthDate",
"phone",
"email",
"address",
"zip",
"licensee",
"registrationDate",
"approvedDate",
"haveVolunteered", 
"licenseForwarded",
"cinId",
*/
CREATE TABLE IF NOT EXISTS `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `gender` text NOT NULL,
  `birthDate` date DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `email` text NOT NULL,
  `address` text NOT NULL,
  `zip` int(4) DEFAULT NULL,
  `licensee` text DEFAULT NULL COMMENT 'lisenced under club',
  `registrationDate` date DEFAULT NULL COMMENT 'date when the form was filled out',
  `approvedDate` date DEFAULT NULL COMMENT 'if date is set, then member is approved',
  `haveVolunteered` tinyint(1) DEFAULT NULL COMMENT 'If true then this member has attended voulentary work',
  `licenseForwarded` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'true if the license has been marked as forwarded to NSF',
  `cinId` int(11) DEFAULT NULL, 
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `cinId` (`cinId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `cin` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row identification',
  `memberHash` text NOT NULL COMMENT 'Hashed sum of stable personal data',
  `cin` bigint(11) NOT NULL COMMENT 'Customer Identification number for norwegian swimming federation',
  `lastUsed` date NOT NULL COMMENT 'Last time this row was active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `memberHash` (`memberHash`),
  UNIQUE KEY `cin` (`cin`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE DATABASE IF NOT EXISTS `web` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `web`;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productId` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `phone` text,
  `intentId` text ,
  `orderStatus` enum('PLACED','FINALIZED','DELIVERED','FAILED','REFUNDED') DEFAULT NULL,
  `comment` text default NULL,
  `timestamp` date NOT NULL COMMENT 'Timestamp of last order status change' DEFAULT NOW(),
  PRIMARY KEY (`id`),
  KEY `productId` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productHash` varchar(20) NOT NULL,
  `name` text NOT NULL,
  `description` text,
  `price` int(11) NOT NULL,
  `priceMember` int(11) DEFAULT NULL,
  `availableFrom` datetime DEFAULT NULL,
  `availableUntil` datetime DEFAULT NULL,
  `maxOrdersPerCustomerPerYear` int(11) DEFAULT NULL,
  `requirePhone` tinyint(1) NOT NULL DEFAULT '0',
  `requireEmail` tinyint(1) NOT NULL DEFAULT '0',
  `requireComment` tinyint(1) NOT NULL DEFAULT '0',
  `requireActiveMembership` tinyint(1) NOT NULL DEFAULT '0',
  `amountAvailable` int(11) DEFAULT NULL,
  `image` text,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_id` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `products` (
  `productHash`,
  `name`,
  `description`,
  `price`,
  `availableFrom`,
  `availableUntil`,
  `maxOrdersPerCustomerPerYear`,
  `requirePhone`,
  `requireEmail`,
  `requireComment`,
  `requireActiveMembership`,
  `amountAvailable`,
  `image`,
  `visible`,
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
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL DEFAULT '',
  `passwd` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `lastPassword` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- login credentials on /admin
INSERT INTO `users` (`username`, `passwd`, `name`, `lastPassword`)VALUES
('admin', '$2y$10$6DvTLWHY38TLZCfcKdQs5utG1EX39QcAmeVdA3FY6JEm6SVeGWyym', 'Administrator', NOW());

-- login credentials to database
GRANT ALL PRIVILEGES ON *.* TO `admin`@`%` IDENTIFIED BY PASSWORD '*C26EC909F368489BFCE885F5ED67303BB3822B41' WITH GRANT OPTION;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: mariadb
-- Generation Time: Dec 10, 2021 at 10:11 PM
-- Server version: 10.6.4-MariaDB-1:10.6.4+maria~focal-log
-- PHP Version: 7.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `svommer_member`
--
CREATE DATABASE IF NOT EXISTS `svommer_member` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `svommer_member`;

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `id` int(11) NOT NULL,
  `first_name` text DEFAULT NULL,
  `surname` text DEFAULT NULL,
  `gender` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` text NOT NULL,
  `email` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `zip` int(4) DEFAULT NULL,
  `licensee` text DEFAULT NULL COMMENT 'lisenced under club',
  `registration_date` date DEFAULT NULL COMMENT 'date when the form was filled out',
  `approved_date` date DEFAULT NULL COMMENT 'if date is set, then member is approved',
  `have_volunteered` tinyint(1) DEFAULT NULL COMMENT 'If true then this member has attended voulentary work',
  `license_forwarded` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true if the license has been marked as forwarded to NSF',
  `CIN` bigint(20) DEFAULT NULL COMMENT 'Customer identification number for NSF license payments'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `member_CIN`
--

CREATE TABLE `member_CIN` (
  `id` int(11) NOT NULL COMMENT 'Row identification',
  `hash` text NOT NULL COMMENT 'Hashed sum of stable personal data',
  `NSF_CIN` bigint(11) NOT NULL COMMENT 'Customer Identification number for norwegian swimming federation',
  `last_used` date NOT NULL COMMENT 'Last time this row was an active member'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `member_statistics`
--

CREATE TABLE `member_statistics` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `male_count` int(11) NOT NULL,
  `female_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`) USING HASH;

--
-- Indexes for table `member_CIN`
--
ALTER TABLE `member_CIN`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_statistics`
--
ALTER TABLE `member_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_CIN`
--
ALTER TABLE `member_CIN`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row identification';

--
-- AUTO_INCREMENT for table `member_statistics`
--
ALTER TABLE `member_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Database: `svommer_web`
--
CREATE DATABASE IF NOT EXISTS `svommer_web` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `svommer_web`;

-- --------------------------------------------------------

--
-- Table structure for table `access_log`
--

CREATE TABLE `access_log` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'time_stamp',
  `page` text NOT NULL,
  `user` text NOT NULL,
  `action` text NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `friday_beer`
--

CREATE TABLE `friday_beer` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `products_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `phone` text DEFAULT NULL,
  `source_id` text DEFAULT NULL COMMENT 'intent_id',
  `charge_id` text DEFAULT NULL COMMENT 'deprecated',
  `order_status` enum('PLACED','FINALIZED','DELIVERED','FAILED','REFUNDED') DEFAULT NULL,
  `comment` text DEFAULT '\'\''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `hash` varchar(20) NOT NULL COMMENT 'store_item_hash',
  `name` text NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL,
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `require_phone` tinyint(1) NOT NULL DEFAULT 0,
  `amount_available` int(11) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `hash`, `name`, `description`, `price`, `available_from`, `available_until`, `require_phone`, `amount_available`, `image`, `visible`, `archived`, `group_id`) VALUES
(1, '31e61c8253b54cdde3b9', '{\"no\":\"NSF Lisens\",\"en\":\"NSF License\"}', '{\"no\":\"Lisensen gir deg adgang til nasjonsale stevner og obligatorisk treningsforsikring. Lisensen har en gyldighet i ett kalender år fra januar til desember.\",\"en\":\"Norwegian Swimming license is required for practices and national competitions. License is valid max one year from January until December.\"}', 765, NULL, NULL, 0, NULL, '31e61c8253b54cdde3b9.jpg', 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_groups`
--

CREATE TABLE `product_groups` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `product_groups`
--

INSERT INTO `product_groups` (`id`, `name`) VALUES
(1, 'default');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'maintainer'),
(3, 'accountant'),
(4, 'membership_manager'),
(5, 'no_group');

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

CREATE TABLE `role_access` (
  `id` int(11) NOT NULL,
  `type` enum('ALLOW','DENY') NOT NULL DEFAULT 'DENY',
  `role` int(11) NOT NULL,
  `page` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `role_access`
--

INSERT INTO `role_access` (`id`, `type`, `role`, `page`) VALUES
(1, 'ALLOW', 1, '*'),
(2, 'DENY', 5, '*');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL DEFAULT '',
  `passwd` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `last_password` date DEFAULT NULL,
  `role` int(11) NOT NULL DEFAULT 5
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `passwd`, `name`, `last_password`, `role`) VALUES
(1, 'admin', '$2y$10$6DvTLWHY38TLZCfcKdQs5utG1EX39QcAmeVdA3FY6JEm6SVeGWyym', 'Administrator', '2021-12-10', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_log`
--
ALTER TABLE `access_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `friday_beer`
--
ALTER TABLE `friday_beer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`products_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_id` (`hash`);

--
-- Indexes for table `product_groups`
--
ALTER TABLE `product_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_access`
--
ALTER TABLE `role_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role` (`role`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD UNIQUE KEY `role_2` (`role`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_log`
--
ALTER TABLE `access_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friday_beer`
--
ALTER TABLE `friday_beer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_groups`
--
ALTER TABLE `product_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `role_access`
--
ALTER TABLE `role_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=420;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

GRANT ALL PRIVILEGES ON *.* TO `svommer_web`@`%` IDENTIFIED BY PASSWORD '*C26EC909F368489BFCE885F5ED67303BB3822B41' WITH GRANT OPTION;
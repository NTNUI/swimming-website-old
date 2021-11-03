-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: mariadb
-- Generation Time: Nov 03, 2021 at 05:37 PM
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
-- Database: `svommer_medlem`
--
DROP DATABASE IF EXISTS `svommer_medlem`;
CREATE DATABASE IF NOT EXISTS `svommer_medlem` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `svommer_medlem`;

-- --------------------------------------------------------

--
-- Table structure for table `medlem`
--

DROP TABLE IF EXISTS `medlem`;
CREATE TABLE `medlem` (
  `id` int(11) NOT NULL,
  `kjonn` text DEFAULT NULL COMMENT 'gender',
  `fornavn` text NOT NULL COMMENT 'first_name',
  `fodselsdato` date DEFAULT NULL COMMENT 'birth_date',
  `etternavn` text DEFAULT NULL COMMENT 'surname',
  `phoneNumber` text NOT NULL,
  `adresse` text DEFAULT NULL COMMENT 'address',
  `postnr` int(4) DEFAULT NULL COMMENT 'zip',
  `epost` text DEFAULT NULL COMMENT 'email',
  `kortnr` int(9) DEFAULT NULL COMMENT 'deprecating',
  `kommentar` text DEFAULT NULL COMMENT 'user_comment',
  `regdato` date DEFAULT NULL COMMENT 'enrollment_date',
  `kontrolldato` date DEFAULT NULL COMMENT 'approved_date',
  `gammelKlubb` text DEFAULT NULL COMMENT 'licenser',
  `ekstra` text DEFAULT NULL COMMENT 'unused',
  `harUtførtFrivilligArbeid` tinyint(1) DEFAULT NULL COMMENT 'have_volunteered',
  `triatlon` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'is_triathlon',
  `KID` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `medlem`
--

INSERT INTO `medlem` (`id`, `kjonn`, `fornavn`, `fodselsdato`, `etternavn`, `phoneNumber`, `adresse`, `postnr`, `epost`, `kortnr`, `kommentar`, `regdato`, `kontrolldato`, `gammelKlubb`, `ekstra`, `harUtførtFrivilligArbeid`, `triatlon`, `KID`) VALUES
(1, 'Male', 'Ola', '1999-01-23', 'Normann', '12345678', 'Spuistraat 304, Amsterdam, NL', 1234, 'admin@4chan.org', 123456, 'Commodo occaecat occaecat commodo consequat consequat sint cupidatat cupidatat reprehenderit. Ullamco eu in qui amet amet mollit pariatur est commodo fugiat voluptate velit. Occaecat excepteur amet aliquip ut id consectetur est consequat mollit. Nostrud elit aliquip enim fugiat mollit non. Sit eu aliquip in id eiusmod qui Lorem est proident ad. Est dolor ad dolor ad cillum consequat. Sunt incididunt sunt cillum aute laborum Lorem ad exercitation anim fugiat duis.', '2021-11-03', NULL, '', NULL, NULL, 0, NULL),
(2, 'Male', 'Kari', '2002-02-14', 'Normann', '987654321', 'The White House 1600 Pennsylvania Avenue, N.W. Washington, DC 20500', 9876, 'contact@thewhitehouse.gov', 918273, '', '2021-11-03', NULL, 'Triatlon', NULL, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medlem_statistikk`
--

DROP TABLE IF EXISTS `medlem_statistikk`;
CREATE TABLE `medlem_statistikk` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `male_count` int(11) NOT NULL,
  `female_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `member_kid`
--

DROP TABLE IF EXISTS `member_kid`;
CREATE TABLE `member_kid` (
  `id` int(255) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `active_since` date NOT NULL,
  `active_until` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `medlem`
--
ALTER TABLE `medlem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medlem_statistikk`
--
ALTER TABLE `medlem_statistikk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_kid`
--
ALTER TABLE `member_kid`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `medlem`
--
ALTER TABLE `medlem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medlem_statistikk`
--
ALTER TABLE `medlem_statistikk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `member_kid`
--
ALTER TABLE `member_kid`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- Database: `svommer_web`
--
DROP DATABASE IF EXISTS `svommer_web`;
CREATE DATABASE IF NOT EXISTS `svommer_web` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `svommer_web`;

-- --------------------------------------------------------

--
-- Table structure for table `access_log`
--

DROP TABLE IF EXISTS `access_log`;
CREATE TABLE `access_log` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'time_stamp',
  `page` text NOT NULL,
  `user` text NOT NULL,
  `action` text NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `access_log`
--

INSERT INTO `access_log` (`id`, `timestamp`, `page`, `user`, `action`, `value`) VALUES
(1, '2021-11-03 17:21:33', 'admin/store', 'admin', 'created item', 'dbc95034394d12473ef0');

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

DROP TABLE IF EXISTS `alumni`;
CREATE TABLE `alumni` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `year` year(4) NOT NULL,
  `email` text NOT NULL,
  `phone` int(11) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `alumni`
--

INSERT INTO `alumni` (`id`, `name`, `year`, `email`, `phone`, `description`) VALUES
(1, 'Test Testson', 2019, 'test@example.com', 0, '- Lagde svømmegruppa'),
(2, 'Geir Testson', 2020, 'mycoolemail@domain.org', 12345678, '- Gjorde alt\n- Reddet verden\n- Vant kilomedley alene\n- Svømte 800m en gang\n');

-- --------------------------------------------------------

--
-- Table structure for table `forside`
--

DROP TABLE IF EXISTS `forside`;
CREATE TABLE `forside` (
  `nokkel` int(11) NOT NULL COMMENT 'id',
  `av` text NOT NULL COMMENT 'author',
  `tid` datetime DEFAULT NULL COMMENT 'time_stamp',
  `overskrift` text NOT NULL COMMENT 'title',
  `innhold` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT 'content'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `forside`
--

INSERT INTO `forside` (`nokkel`, `av`, `tid`, `overskrift`, `innhold`) VALUES
(1, 'Administrator', '2021-11-03 17:16:43', 'Test announcement', 'Magna dolor minim occaecat non id pariatur enim veniam. Fugiat dolor est laborum voluptate nostrud officia laboris nostrud Lorem occaecat. Lorem cupidatat aliquip cupidatat ullamco ea exercitation qui proident. Ipsum sunt anim in laborum ea elit id esse deserunt sit et.'),
(2, 'Administrator', '2021-11-03 17:17:20', 'Another test announcement', 'Sit id occaecat ut elit sint do nulla eu anim esse culpa ipsum velit velit. Exercitation culpa tempor tempor cupidatat ad sunt mollit elit voluptate ex eu mollit. Elit velit do fugiat aliqua. Aute aliquip amet eiusmod amet officia consequat.\r\n\r\nEnim labore aliqua do sit dolore labore proident sunt. Et labore ullamco enim ea deserunt magna duis est. Mollit quis fugiat ea eiusmod adipisicing pariatur proident laborum aute incididunt. Aliquip enim eu fugiat ut consequat magna reprehenderit ullamco ad nisi. Duis nulla aliqua do reprehenderit proident reprehenderit amet tempor. Amet irure enim ad voluptate. Elit esse ad minim esse Lorem magna irure.\r\n\r\nSunt ex tempor et adipisicing duis commodo laborum nisi aliquip veniam. Aliquip proident et laboris minim mollit sit sit dolore aliquip aute. Est ipsum voluptate ut ex fugiat qui ipsum.\r\n\r\nSit cillum sint non esse nostrud. Non aute proident sit deserunt nisi laborum enim sint nostrud ad aliqua. Sunt esse quis tempor laboris deserunt laboris exercitation fugiat. Mollit minim consectetur dolore occaecat et exercitation laboris. Occaecat exercitation commodo ex anim adipisicing non est.\r\n\r\nCulpa est ex culpa irure incididunt veniam eiusmod. Tempor sint cupidatat dolor esse sit. Aliqua ullamco do ipsum cillum eiusmod ut elit labore. Mollit qui aute quis aute minim culpa. Mollit aute cillum ullamco veniam cillum commodo excepteur. Exercitation aute reprehenderit nostrud amet Lorem duis duis deserunt esse in nisi.\r\n\r\nReprehenderit voluptate cupidatat nulla elit sit eu veniam deserunt et commodo velit. Irure incididunt sunt minim incididunt quis magna est cillum culpa id ut aliqua. Irure quis aliquip est pariatur aliquip proident anim ea deserunt amet. Consequat eu deserunt officia magna culpa consectetur labore aliquip irure culpa. Ea qui eiusmod deserunt et quis ad proident magna consectetur duis velit dolor ea. Laborum dolor in aliquip non officia dolor excepteur pariatur excepteur voluptate sunt ad quis cillum.');

-- --------------------------------------------------------

--
-- Table structure for table `friday_beer`
--

DROP TABLE IF EXISTS `friday_beer`;
CREATE TABLE `friday_beer` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'default'),
(2, 'styret'),
(5, 'kasserer'),
(6, 'superadmin');

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

DROP TABLE IF EXISTS `role_access`;
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
(6, 'ALLOW', 6, '*');

-- --------------------------------------------------------

--
-- Table structure for table `store_groups`
--

DROP TABLE IF EXISTS `store_groups`;
CREATE TABLE `store_groups` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `store_groups`
--

INSERT INTO `store_groups` (`id`, `name`) VALUES
(1, 'default');

-- --------------------------------------------------------

--
-- Table structure for table `store_items`
--

DROP TABLE IF EXISTS `store_items`;
CREATE TABLE `store_items` (
  `id` int(11) NOT NULL,
  `api_id` varchar(20) NOT NULL,
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
-- Dumping data for table `store_items`
--

INSERT INTO `store_items` (`id`, `api_id`, `name`, `description`, `price`, `available_from`, `available_until`, `require_phone`, `amount_available`, `image`, `visible`, `archived`, `group_id`) VALUES
(1, 'dbc95034394d12473ef0', '{\"no\":\"Den beste butkkvaren\",\"en\":\"The perfect store item\"}', '{\"no\":\"Bedre alternativer finnes det ikke. <a href=\\\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\\\" target=\\\"_blank\\\">Bevis.\",\"en\":\"There are no better options. <a href=\\\"https:\\/\\/www.youtube.com\\/watch?v=dQw4w9WgXcQ\\\" target=\\\"_blank\\\">Proof.\"}', 42000, NULL, NULL, 0, 69, 'dbc95034394d12473ef0c08c7abce18e.jpeg', 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `store_orders`
--

DROP TABLE IF EXISTS `store_orders`;
CREATE TABLE `store_orders` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL COMMENT 'store_items_id',
  `name` text NOT NULL,
  `email` text NOT NULL,
  `phone` text DEFAULT NULL,
  `source_id` text DEFAULT NULL,
  `charge_id` text DEFAULT NULL,
  `order_status` enum('PLACED','FINALIZED','DELIVERED','FAILED','REFUNDED') DEFAULT NULL,
  `kommentar` text NOT NULL COMMENT 'comment'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL DEFAULT '',
  `passwd` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `last_password` date DEFAULT NULL,
  `role` int(11) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `passwd`, `name`, `last_password`, `role`) VALUES
(407, 'admin', '$2y$10$6DvTLWHY38TLZCfcKdQs5utG1EX39QcAmeVdA3FY6JEm6SVeGWyym', 'Administrator', '2021-11-03', 6);

-- --------------------------------------------------------

--
-- Table structure for table `user_access`
--

DROP TABLE IF EXISTS `user_access`;
CREATE TABLE `user_access` (
  `id` int(11) NOT NULL,
  `type` enum('ALLOW','DENY') NOT NULL DEFAULT 'DENY',
  `user` int(11) NOT NULL,
  `page` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_log`
--
ALTER TABLE `access_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forside`
--
ALTER TABLE `forside`
  ADD PRIMARY KEY (`nokkel`);

--
-- Indexes for table `friday_beer`
--
ALTER TABLE `friday_beer`
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
-- Indexes for table `store_groups`
--
ALTER TABLE `store_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_items`
--
ALTER TABLE `store_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_id` (`api_id`);

--
-- Indexes for table `store_orders`
--
ALTER TABLE `store_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role` (`role`);

--
-- Indexes for table `user_access`
--
ALTER TABLE `user_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_log`
--
ALTER TABLE `access_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `alumni`
--
ALTER TABLE `alumni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `forside`
--
ALTER TABLE `forside`
  MODIFY `nokkel` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `friday_beer`
--
ALTER TABLE `friday_beer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `role_access`
--
ALTER TABLE `role_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `store_groups`
--
ALTER TABLE `store_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `store_items`
--
ALTER TABLE `store_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_orders`
--
ALTER TABLE `store_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=418;

--
-- AUTO_INCREMENT for table `user_access`
--
ALTER TABLE `user_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

GRANT ALL PRIVILEGES ON *.* TO `svommer_web`@`%` IDENTIFIED BY PASSWORD '*C26EC909F368489BFCE885F5ED67303BB3822B41' WITH GRANT OPTION;
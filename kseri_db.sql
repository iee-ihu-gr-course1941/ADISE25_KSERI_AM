-- Clean SQL dump for XAMPP MySQL 8
-- UTF8MB4, safe for import

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

SET NAMES utf8mb4;

-- Create and use database
CREATE DATABASE IF NOT EXISTS `kseri_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `kseri_db`;

-- --------------------------------------------------------
-- Table structure for table `board`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `board`;

CREATE TABLE `board` (
  `card_id` int(11) NOT NULL,
  `pos` enum('deck','hand_A','hand_B','table','pile_A','pile_B') NOT NULL DEFAULT 'deck',
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `board`;

INSERT INTO `board` (`card_id`, `pos`, `weight`) VALUES
(1, 'hand_A', NULL),
(2, 'hand_A', NULL),
(3, 'hand_A', NULL),
(4, 'deck', NULL),
(5, 'deck', NULL),
(6, 'deck', NULL),
(7, 'hand_A', NULL),
(8, 'deck', NULL),
(9, 'deck', NULL),
(10, 'deck', NULL),
(11, 'deck', NULL),
(12, 'table', 1),
(13, 'deck', NULL),
(14, 'hand_B', NULL),
(15, 'hand_B', NULL),
(16, 'deck', NULL),
(17, 'deck', NULL),
(18, 'deck', NULL),
(19, 'table', 2),
(20, 'deck', NULL),
(21, 'hand_A', NULL),
(22, 'deck', NULL),
(23, 'hand_B', NULL),
(24, 'deck', NULL),
(25, 'deck', NULL),
(26, 'deck', NULL),
(27, 'deck', NULL),
(28, 'deck', NULL),
(29, 'deck', NULL),
(30, 'deck', NULL),
(31, 'deck', NULL),
(32, 'deck', NULL),
(33, 'deck', NULL),
(34, 'deck', NULL),
(35, 'hand_B', NULL),
(36, 'deck', NULL),
(37, 'deck', NULL),
(38, 'deck', NULL),
(39, 'deck', NULL),
(40, 'deck', NULL),
(41, 'deck', NULL),
(42, 'hand_B', NULL),
(43, 'table', 3),
(44, 'deck', NULL),
(45, 'deck', NULL),
(46, 'hand_A', NULL),
(47, 'hand_B', NULL),
(48, 'deck', NULL),
(49, 'deck', NULL),
(50, 'deck', NULL),
(51, 'deck', NULL),
(52, 'table', 4);

-- --------------------------------------------------------
-- Table structure for table `deck`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `deck`;

CREATE TABLE `deck` (
  `card_id` int(11) NOT NULL AUTO_INCREMENT,
  `card_rank` enum('A','2','3','4','5','6','7','8','9','10','J','Q','K') NOT NULL,
  `card_suit` enum('Club','Diamond','Heart','Spade') NOT NULL,
  PRIMARY KEY (`card_id`),
  UNIQUE KEY `idx_card_unique` (`card_rank`,`card_suit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `deck`;

INSERT INTO `deck` (`card_id`, `card_rank`, `card_suit`) VALUES
(1, 'A', 'Club'),
(14, 'A', 'Diamond'),
(27, 'A', 'Heart'),
(40, 'A', 'Spade'),
(2, '2', 'Club'),
(15, '2', 'Diamond'),
(28, '2', 'Heart'),
(41, '2', 'Spade'),
(3, '3', 'Club'),
(16, '3', 'Diamond'),
(29, '3', 'Heart'),
(42, '3', 'Spade'),
(4, '4', 'Club'),
(17, '4', 'Diamond'),
(30, '4', 'Heart'),
(43, '4', 'Spade'),
(5, '5', 'Club'),
(18, '5', 'Diamond'),
(31, '5', 'Heart'),
(44, '5', 'Spade'),
(6, '6', 'Club'),
(19, '6', 'Diamond'),
(32, '6', 'Heart'),
(45, '6', 'Spade'),
(7, '7', 'Club'),
(20, '7', 'Diamond'),
(33, '7', 'Heart'),
(46, '7', 'Spade'),
(8, '8', 'Club'),
(21, '8', 'Diamond'),
(34, '8', 'Heart'),
(47, '8', 'Spade'),
(9, '9', 'Club'),
(22, '9', 'Diamond'),
(35, '9', 'Heart'),
(48, '9', 'Spade'),
(10, '10', 'Club'),
(23, '10', 'Diamond'),
(36, '10', 'Heart'),
(49, '10', 'Spade'),
(11, 'J', 'Club'),
(24, 'J', 'Diamond'),
(37, 'J', 'Heart'),
(50, 'J', 'Spade'),
(12, 'Q', 'Club'),
(25, 'Q', 'Diamond'),
(38, 'Q', 'Heart'),
(51, 'Q', 'Spade'),
(13, 'K', 'Club'),
(26, 'K', 'Diamond'),
(39, 'K', 'Heart'),
(52, 'K', 'Spade');

-- --------------------------------------------------------
-- Table structure for table `game_status`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `game_status`;

CREATE TABLE `game_status` (
  `status` enum('not active','initialized','started','ended','aborted') NOT NULL DEFAULT 'not active',
  `p_turn` enum('A','B') DEFAULT NULL,
  `result` enum('A','B','D') DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `game_status`;

INSERT INTO `game_status` (`status`, `p_turn`, `result`, `last_change`) VALUES
('started', 'B', NULL, '2026-01-07 09:31:37');

-- --------------------------------------------------------
-- Table structure for table `players`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `players`;

CREATE TABLE `players` (
  `username` varchar(50) DEFAULT NULL,
  `player` enum('A','B') NOT NULL,
  `token` varchar(100) DEFAULT NULL,
  `last_action` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `score` int(11) DEFAULT 0,
  `kseres` int(11) DEFAULT 0,
  PRIMARY KEY (`player`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `players`;

-- If you want initial players, uncomment and edit
-- INSERT INTO `players` (`username`, `player`, `token`, `last_action`, `score`, `kseres`) VALUES
-- ('Μαρία', 'A', 'e03dcaefe11702923f1769802d57ab9d', '2026-01-07 09:31:01', 0, 0),
-- ('Μάριος', 'B', '949e9618b4757fbe0a024a4bfc4ba6ee', '2026-01-07 09:31:05', 0, 0);

COMMIT;
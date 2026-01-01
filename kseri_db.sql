-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 01, 2026 at 06:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kseri_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `clean_game` ()   BEGIN
    -- επαναφορά board
    UPDATE `board` SET `pos`='deck', `weight`=NULL;
    
    -- επαναφορά παικτών
    UPDATE `players` SET `username`=NULL, `token`=NULL;
  
    -- επαναφορά κατάστασης παιχνιδιού
    UPDATE `game_status` SET `status`='not active', `p_turn`=NULL, `result`=NULL;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `move_card` (IN `card_to_play` INT, IN `player_id` ENUM('A','B'))   BEGIN
    -- μετακίνηση κάρτας από το χέρι στο τραπέζι
    UPDATE board 
    SET pos='table', 
        weight=(SELECT IFNULL(MAX(weight),0)+1 FROM (SELECT weight FROM board) as x) 
    WHERE card_id = card_to_play;

    -- αλλαγή παίκτη που έχει σειρά
    UPDATE game_status SET p_turn = IF(player_id='A','B','A');
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `board`
--

CREATE TABLE `board` (
  `card_id` int(11) NOT NULL,
  `pos` enum('deck','hand_A','hand_B','table','pile_A','pile_B') NOT NULL DEFAULT 'deck',
  `weight` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `board`
--

INSERT INTO `board` (`card_id`, `pos`, `weight`) VALUES
(1, 'pile_B', 0),
(2, 'deck', NULL),
(3, 'deck', NULL),
(4, 'deck', NULL),
(5, 'pile_B', 0),
(6, 'pile_B', 0),
(7, 'pile_B', 0),
(8, 'pile_B', 0),
(9, 'deck', NULL),
(10, 'pile_B', 0),
(11, 'deck', NULL),
(12, 'deck', NULL),
(13, 'deck', NULL),
(14, 'deck', NULL),
(15, 'pile_B', 0),
(16, 'deck', NULL),
(17, 'deck', NULL),
(18, 'pile_B', 0),
(19, 'pile_B', 0),
(20, 'pile_B', 0),
(21, 'deck', NULL),
(22, 'deck', NULL),
(23, 'deck', NULL),
(24, 'deck', NULL),
(25, 'table', 1),
(26, 'deck', NULL),
(27, 'deck', NULL),
(28, 'deck', NULL),
(29, 'deck', NULL),
(30, 'deck', NULL),
(31, 'pile_B', 0),
(32, 'deck', NULL),
(33, 'deck', NULL),
(34, 'deck', NULL),
(35, 'deck', NULL),
(36, 'deck', NULL),
(37, 'pile_B', 0),
(38, 'deck', NULL),
(39, 'deck', NULL),
(40, 'deck', NULL),
(41, 'deck', NULL),
(42, 'deck', NULL),
(43, 'deck', NULL),
(44, 'pile_B', 0),
(45, 'deck', NULL),
(46, 'deck', NULL),
(47, 'deck', NULL),
(48, 'deck', NULL),
(49, 'pile_B', 0),
(50, 'deck', NULL),
(51, 'deck', NULL),
(52, 'table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `deck`
--

CREATE TABLE `deck` (
  `card_id` int(11) NOT NULL,
  `card_rank` enum('A','2','3','4','5','6','7','8','9','10','J','Q','K') NOT NULL,
  `card_suit` enum('Club','Diamond','Heart','Spade') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deck`
--

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

--
-- Table structure for table `game_status`
--

CREATE TABLE `game_status` (
  `status` enum('not active','initialized','started','ended','aborted') NOT NULL DEFAULT 'not active',
  `p_turn` enum('A','B') DEFAULT NULL,
  `result` enum('A','B','D') DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_status`
--

INSERT INTO `game_status` (`status`, `p_turn`, `result`, `last_change`) VALUES
('not active', 'A', NULL, '2026-01-01 17:09:48');

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `username` varchar(50) DEFAULT NULL,
  `player` enum('A','B') NOT NULL,
  `token` varchar(100) DEFAULT NULL,
  `last_action` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`username`, `player`, `token`, `last_action`) VALUES
(NULL, 'A', NULL, '2026-01-01 17:09:46'),
(NULL, 'B', NULL, '2026-01-01 17:09:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `board`
--
ALTER TABLE `board`
  ADD PRIMARY KEY (`card_id`);

--
-- Indexes for table `deck`
--
ALTER TABLE `deck`
  ADD PRIMARY KEY (`card_id`),
  ADD UNIQUE KEY `idx_card_unique` (`card_rank`,`card_suit`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deck`
--
ALTER TABLE `deck`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

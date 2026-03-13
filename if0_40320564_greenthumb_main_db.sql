-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql200.infinityfree.com
-- Generation Time: Mar 13, 2026 at 06:49 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40320564_greenthumb_main_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `reward` int(11) NOT NULL,
  `unlocked_at` datetime NOT NULL,
  `player_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flower_collection`
--

CREATE TABLE `flower_collection` (
  `collection_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `plant_id` int(11) DEFAULT NULL,
  `flower_key` varchar(128) NOT NULL,
  `flower_label` varchar(128) NOT NULL,
  `collected_count` int(11) NOT NULL DEFAULT 0,
  `collected_at` datetime NOT NULL,
  `bonus_unlocked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flower_ranks`
--

CREATE TABLE `flower_ranks` (
  `rank_id` int(11) NOT NULL,
  `rank_name` varchar(50) NOT NULL,
  `bonus_multiplier` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `plant_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `plant_name` varchar(100) NOT NULL,
  `rarity` enum('Common','Rare','Epic','Legendary') NOT NULL,
  `current_stage` tinyint(4) NOT NULL DEFAULT 1,
  `current_taps` tinyint(4) NOT NULL,
  `current_flower` varchar(64) DEFAULT NULL,
  `growth_level` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `discovered_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `player_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tap_power` int(11) NOT NULL,
  `luck` int(11) NOT NULL,
  `coins` int(11) NOT NULL,
  `create_at` datetime NOT NULL,
  `last_login` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_progress`
--

CREATE TABLE `player_progress` (
  `player_id` int(11) NOT NULL,
  `current_stage` tinyint(4) NOT NULL DEFAULT 1,
  `current_taps` tinyint(4) NOT NULL DEFAULT 0,
  `current_flower` varchar(64) DEFAULT NULL,
  `current_flower_rarity` varchar(16) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pots`
--

CREATE TABLE `pots` (
  `pot_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `pot_name` varchar(100) NOT NULL,
  `luck_boost` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `purchased_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weather_events`
--

CREATE TABLE `weather_events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(50) NOT NULL,
  `effect_type` varchar(50) NOT NULL,
  `multiplier` float NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `triggered_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`),
  ADD KEY `fk_achievements_player` (`player_id`);

--
-- Indexes for table `flower_collection`
--
ALTER TABLE `flower_collection`
  ADD PRIMARY KEY (`collection_id`),
  ADD UNIQUE KEY `idx_player_flower` (`player_id`,`flower_key`),
  ADD UNIQUE KEY `player_flower_unique` (`player_id`,`plant_id`),
  ADD KEY `fk_flowercollection_plant` (`plant_id`);

--
-- Indexes for table `flower_ranks`
--
ALTER TABLE `flower_ranks`
  ADD PRIMARY KEY (`rank_id`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`plant_id`),
  ADD KEY `fk_plants_player` (`player_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`);

--
-- Indexes for table `player_progress`
--
ALTER TABLE `player_progress`
  ADD PRIMARY KEY (`player_id`);

--
-- Indexes for table `pots`
--
ALTER TABLE `pots`
  ADD PRIMARY KEY (`pot_id`),
  ADD KEY `fk_pots_player` (`player_id`);

--
-- Indexes for table `weather_events`
--
ALTER TABLE `weather_events`
  ADD PRIMARY KEY (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flower_collection`
--
ALTER TABLE `flower_collection`
  MODIFY `collection_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flower_ranks`
--
ALTER TABLE `flower_ranks`
  MODIFY `rank_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `plant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pots`
--
ALTER TABLE `pots`
  MODIFY `pot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weather_events`
--
ALTER TABLE `weather_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `fk_achievements_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `flower_collection`
--
ALTER TABLE `flower_collection`
  ADD CONSTRAINT `fk_flowercollection_plant` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_flowercollection_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `plants`
--
ALTER TABLE `plants`
  ADD CONSTRAINT `fk_plants_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pots`
--
ALTER TABLE `pots`
  ADD CONSTRAINT `fk_pots_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

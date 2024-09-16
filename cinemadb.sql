-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2024 at 04:07 AM
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
-- Database: `cinemadb`
--

-- --------------------------------------------------------

--
-- Table structure for table `crew`
--

CREATE TABLE `crew` (
  `crew_id` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `biography` longtext DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `deathday` date DEFAULT NULL,
  `imdbcrew` varchar(255) DEFAULT NULL,
  `birthplace` varchar(255) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crew_movie`
--

CREATE TABLE `crew_movie` (
  `crew_id` varchar(20) NOT NULL,
  `imdbid` varchar(20) DEFAULT NULL,
  `tmdbid` varchar(20) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `point` int(11) DEFAULT NULL,
  `personnel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `genre_id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `imdbid` varchar(20) NOT NULL,
  `tmdbid` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `year` varchar(255) NOT NULL,
  `plot` longtext DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `runtime` int(255) DEFAULT NULL,
  `director` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `imdbrating` decimal(3,1) NOT NULL,
  `numvote` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `movies`
--
DELIMITER $$
CREATE TRIGGER `delete_crew_movie` AFTER DELETE ON `movies` FOR EACH ROW BEGIN
    DELETE FROM crew_movie WHERE imdbid = OLD.imdbid;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `delete_movie_geners` AFTER DELETE ON `movies` FOR EACH ROW BEGIN
    DELETE FROM movie_genres WHERE imdbid = OLD.imdbid;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `movie_genres`
--

CREATE TABLE `movie_genres` (
  `SN` bigint(255) NOT NULL,
  `imdbid` varchar(255) DEFAULT NULL,
  `tmdbid` varchar(255) DEFAULT NULL,
  `genre_id` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movie_id` varchar(255) DEFAULT NULL,
  `crew_id` int(11) DEFAULT NULL,
  `report_type` enum('movie','crew') DEFAULT NULL,
  `report_details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','resolved') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$d4PA3x9e/Zgo/nc6GWcchemFNud6mdw16fIcaCb.zHy0DvRRW6q3e', '2024-08-02 12:54:42');

-- --------------------------------------------------------

--
-- Table structure for table `user_casthistory`
--

CREATE TABLE `user_casthistory` (
  `user_id` int(255) DEFAULT NULL,
  `crew_id` int(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_history`
--

CREATE TABLE `user_history` (
  `user_id` int(255) DEFAULT NULL,
  `imdbid` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crew`
--
ALTER TABLE `crew`
  ADD PRIMARY KEY (`crew_id`);

--
-- Indexes for table `crew_movie`
--
ALTER TABLE `crew_movie`
  ADD KEY `crew_movie_ibfk_1` (`imdbid`),
  ADD KEY `crew_movie_ibfk_2` (`tmdbid`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`genre_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`imdbid`),
  ADD UNIQUE KEY `tmdbid` (`tmdbid`);

--
-- Indexes for table `movie_genres`
--
ALTER TABLE `movie_genres`
  ADD PRIMARY KEY (`SN`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_casthistory`
--
ALTER TABLE `user_casthistory`
  ADD UNIQUE KEY `timestamp` (`timestamp`);

--
-- Indexes for table `user_history`
--
ALTER TABLE `user_history`
  ADD UNIQUE KEY `timestamp` (`timestamp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `movie_genres`
--
ALTER TABLE `movie_genres`
  MODIFY `SN` bigint(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1934;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crew_movie`
--
ALTER TABLE `crew_movie`
  ADD CONSTRAINT `crew_movie_ibfk_1` FOREIGN KEY (`imdbid`) REFERENCES `movies` (`imdbid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `crew_movie_ibfk_2` FOREIGN KEY (`tmdbid`) REFERENCES `movies` (`tmdbid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

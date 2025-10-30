-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 03:47 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `final_thesis_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(191) NOT NULL,
  `municipality_id` int(11) NOT NULL,
  `population` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`id`, `code`, `name`, `municipality_id`, `population`, `created_at`) VALUES
(1, 'BGY-500', 'Calumpang', 1, 2000, '2025-10-20 17:05:44'),
(2, 'BGY-400', 'Agpangi', 1, 5000, '2025-10-20 17:09:41'),
(3, 'BGY-700', 'Talustusan', 1, 6000, '2025-10-20 17:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `hazards`
--

CREATE TABLE `hazards` (
  `id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `municipality_id` int(11) NOT NULL,
  `hazard_type_id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `severity` varchar(100) DEFAULT NULL,
  `houses_affected` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hazards`
--

INSERT INTO `hazards` (`id`, `barangay_id`, `municipality_id`, `hazard_type_id`, `event_date`, `severity`, `houses_affected`, `created_at`) VALUES
(17, 1, 1, 2, '2024-06-12', 'High', '500', '2025-10-23 01:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `hazard_types`
--

CREATE TABLE `hazard_types` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hazard_types`
--

INSERT INTO `hazard_types` (`id`, `category`, `name`, `description`, `created_at`) VALUES
(1, 'Geological', 'Landslides', 'Downward movement of soil, rock, and debris under gravity', '2025-10-21 06:39:48'),
(2, 'Hydrological', 'Floods', 'Overflow of water that submerges normally dry land', '2025-10-21 06:40:13'),
(3, 'Meteorological', 'Storm Surge', 'Rise in sea level caused by a storm\'s winds pushing water ashore', '2025-10-21 06:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `municipalities`
--

CREATE TABLE `municipalities` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `province` varchar(191) DEFAULT NULL,
  `populations` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `municipalities`
--

INSERT INTO `municipalities` (`id`, `code`, `name`, `province`, `populations`, `created_at`) VALUES
(1, '6560', 'Naval', 'Biliran', 6000, '2025-10-19 16:22:03'),
(3, '4560', 'Biliran', 'Biliran', 7000, '2025-10-20 16:52:45'),
(4, '5068', 'Cabucgayan', 'Biliran', 6000, '2025-10-22 06:53:22'),
(5, '7085', 'Kawayan', 'Biliran', 4000, '2025-10-22 06:53:53'),
(6, '7056', 'Almeria', 'Biliran', 50000, '2025-10-22 06:54:16'),
(7, '4570', 'Culaba', 'Biliran', 40000, '2025-10-22 06:54:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('administrator','user') NOT NULL DEFAULT 'user',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `municipality_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(32) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `status`, `municipality_id`, `address`, `contact_number`, `profile_picture`, `created_at`, `approved_at`) VALUES
(13, 'Jet', 'Habac', 'jethabac@gmail.com', '$2y$10$Bw/Ge9ADRBg4uwzIrTFbk.XFxfD9tg4L1RhP3OrTyS9Lab0jHrDwq', 'administrator', 'approved', 1, 'Talustusan, Naval, Biliran', '09654309370', 'uploads/profiles/68f994a6e9b09_56a90cb8-85b2-4193-8e97-6f6fe48a038b.jpg', '2025-10-22 08:05:37', NULL),
(14, 'Althea', 'Bautista', 'jethabac161@gmail.com', '$2y$10$I1IEngJVUhmkXWM8/BkkwevfHVQ2lSXUa7mwZjdIVZW4vsiHOD.re', 'administrator', 'approved', 1, 'Bilwang Kawayan Biliran', '1234567898', 'uploads/profiles/68fab065e1d2c_98bcbcf5-27ed-482e-9ee4-3aa72ac4060c.jpg', '2025-10-23 07:30:28', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barangay_municipality` (`municipality_id`);

--
-- Indexes for table `hazards`
--
ALTER TABLE `hazards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hazard_barangay` (`barangay_id`),
  ADD KEY `fk_hazard_municipality` (`municipality_id`),
  ADD KEY `fk_hazard_type` (`hazard_type_id`);

--
-- Indexes for table `hazard_types`
--
ALTER TABLE `hazard_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `municipalities`
--
ALTER TABLE `municipalities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_municipality` (`municipality_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hazards`
--
ALTER TABLE `hazards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `hazard_types`
--
ALTER TABLE `hazard_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `municipalities`
--
ALTER TABLE `municipalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barangays`
--
ALTER TABLE `barangays`
  ADD CONSTRAINT `fk_barangay_municipality` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hazards`
--
ALTER TABLE `hazards`
  ADD CONSTRAINT `fk_hazard_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hazard_municipality` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hazard_type` FOREIGN KEY (`hazard_type_id`) REFERENCES `hazard_types` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_municipality` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE SET NULL;
COMMIT;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sender` (`sender_id`),
  ADD KEY `fk_receiver` (`receiver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`

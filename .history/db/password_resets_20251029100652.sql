-- Add password_resets table to existing database
-- Run this SQL to add the table for password reset functionality

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `hashed_code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

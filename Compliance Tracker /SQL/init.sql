-- SQL schema for Compliance Tracker
-- Run this in phpMyAdmin or the MySQL command line.

CREATE DATABASE IF NOT EXISTS `Securecompliancetracker` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `Securecompliancetracker`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Example: add a placeholder admin if you prefer to import direct (password must be hashed).
-- Better: use create_admin.php to create a properly hashed password.

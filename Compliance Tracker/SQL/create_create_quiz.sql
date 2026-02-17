-- Migration: create consolidated create_quiz table to store full quiz payloads
-- Run this in your database if you prefer migrations over runtime table creation.

CREATE TABLE IF NOT EXISTS `create_quiz` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `quiz_id` INT NOT NULL,
  `questions` TEXT,
  `due_date` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

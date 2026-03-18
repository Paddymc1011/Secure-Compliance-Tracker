-- Migration: create questions and options tables for quizzes
-- Run this SQL in your database (phpMyAdmin or mysql CLI) to enable quiz question storage.

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `quiz_id` INT NOT NULL,
  `text` TEXT NOT NULL,
  `position` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`quiz_id`),
  CONSTRAINT `fk_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `options` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `text` VARCHAR(1024) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `position` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`question_id`),
  CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

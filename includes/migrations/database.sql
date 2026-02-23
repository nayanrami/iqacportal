-- NAAC Feedback System Database Schema
-- Run this script in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS `naac_feedback` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `naac_feedback`;

-- Admins table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) DEFAULT 'IQAC Coordinator',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Departments table
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Courses table
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `code` VARCHAR(30) NOT NULL,
    `semester` INT DEFAULT NULL,
    `year` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Feedback Forms table
CREATE TABLE IF NOT EXISTS `feedback_forms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `course_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATE DEFAULT NULL,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Questions table
CREATE TABLE IF NOT EXISTS `questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `feedback_form_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `max_score` INT NOT NULL DEFAULT 5,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`feedback_form_id`) REFERENCES `feedback_forms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Responses table
CREATE TABLE IF NOT EXISTS `responses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `feedback_form_id` INT NOT NULL,
    `student_name` VARCHAR(100) DEFAULT NULL,
    `student_roll` VARCHAR(50) DEFAULT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (`feedback_form_id`) REFERENCES `feedback_forms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Response Answers table
CREATE TABLE IF NOT EXISTS `response_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `response_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `score` INT NOT NULL,
    FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin user (password: iqac)
INSERT INTO `admins` (`username`, `password`, `full_name`) VALUES 
('iqac', '$2y$10$YXVwZGF0ZTIwMjZpY2FjMO4yCT3UHr6mLQKgZ1J.K6W8gVmPO6hSe', 'IQAC Coordinator');

-- Insert sample departments
INSERT INTO `departments` (`name`, `code`) VALUES 
('Computer Science', 'CS'),
('Information Technology', 'IT'),
('Electronics & Communication', 'EC'),
('Mechanical Engineering', 'ME'),
('Civil Engineering', 'CE');

-- Insert sample courses
INSERT INTO `courses` (`department_id`, `name`, `code`, `semester`, `year`) VALUES
(1, 'Data Structures', 'CS301', 3, '2025-26'),
(1, 'Operating Systems', 'CS401', 4, '2025-26'),
(2, 'Web Technologies', 'IT302', 3, '2025-26'),
(3, 'Digital Electronics', 'EC201', 2, '2025-26'),
(4, 'Thermodynamics', 'ME301', 3, '2025-26');

-- Research Categories table
CREATE TABLE IF NOT EXISTS `research_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Research Records table
CREATE TABLE IF NOT EXISTS `research_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `faculty_name` VARCHAR(150),
    `publication_date` DATE,
    `journal_conference` VARCHAR(255),
    `impact_factor` DECIMAL(10,3) DEFAULT 0.000,
    `funding_amount` DECIMAL(15,2) DEFAULT 0.00,
    `status` ENUM('proposed', 'ongoing', 'completed', 'published', 'patented') DEFAULT 'proposed',
    `description` TEXT,
    `link` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `research_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default research categories
INSERT IGNORE INTO `research_categories` (`name`, `description`) VALUES 
('Journal Publication', 'Research articles published in peer-reviewed journals'),
('Conference Paper', 'Papers presented and published in academic conferences'),
('Research Grant', 'Research funding received from government or private agencies'),
('Patent', 'Intellectual property rights and patents filed or granted'),
('Book Chapter', 'Chapters contributed to academic or professional books'),
('Ongoing Project', 'Research projects currently in progress');

-- Portal Settings table
CREATE TABLE IF NOT EXISTS `portal_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT IGNORE INTO `portal_settings` (`setting_key`, `setting_value`) VALUES 
('app_name', 'IQAC Portal'),
('app_institute', 'IQAC Institute of Technology'),
('app_dept', 'Department of Technical Education'),
('app_url', '/feedback');

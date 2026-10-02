-- Online Voting System (Tomorrow Vote) Database Schema
-- Compatible with InfinityFree, cPanel, phpMyAdmin, and Localhost

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'voter') NOT NULL DEFAULT 'voter',
    `full_name` VARCHAR(150) NOT NULL,
    `student_id` VARCHAR(50) DEFAULT NULL,
    `has_voted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Positions Table
CREATE TABLE IF NOT EXISTS `positions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `position_name` VARCHAR(100) NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Candidates Table
CREATE TABLE IF NOT EXISTS `candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `position_id` INT NOT NULL,
    `candidate_name` VARCHAR(150) NOT NULL,
    `year_level` VARCHAR(50) NOT NULL DEFAULT '1st Year',
    `platform` TEXT,
    `profile_image` VARCHAR(255) DEFAULT 'default.jpg',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Votes Table (Encrypted Storage)
CREATE TABLE IF NOT EXISTS `votes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `voter_id` INT NOT NULL,
    `position_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    `encrypted_payload` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_voter_position` (`voter_id`, `position_id`),
    FOREIGN KEY (`voter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Election Settings Table
CREATE TABLE IF NOT EXISTS `election_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Settings & Seed Data
INSERT IGNORE INTO `election_settings` (`setting_key`, `setting_value`) VALUES 
('election_title', 'Student Council Election 2026'),
('voting_status', 'CLOSED'),
('start_date', '2026-08-08'),
('end_date', '2026-08-15');

-- Seed Default Accounts (Password: admin123 and voter123 hashed via password_hash)
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `full_name`, `student_id`, `has_voted`) VALUES
(1, 'admin', 'admin@tomorrowvote.com', '$2y$10$7tDAtX7MaGWppSAOLB1zX.O3p0ApiuX6EBAr5T4AxwwrNk5Aqn30e', 'admin', 'Admin', 'ADM-2026', 0),
(2, 'voter1', 'voter1@tomorrowvote.com', '$2y$10$v/N84j.BitsqUic9DkKscepNNzrc/RCbSzQRcY35HdZ2WnZAkP4Oy', 'voter', 'Maria Santos', 'STU-2026-01', 0),
(3, 'voter2', 'voter2@tomorrowvote.com', '$2y$10$v/N84j.BitsqUic9DkKscepNNzrc/RCbSzQRcY35HdZ2WnZAkP4Oy', 'voter', 'Juan Dela Cruz Voter', 'STU-2026-02', 0);

-- Seed Positions
INSERT IGNORE INTO `positions` (`id`, `position_name`, `display_order`) VALUES
(1, 'President', 1),
(2, 'Vice President', 2),
(3, 'Secretary', 3);

-- Seed Candidates
INSERT IGNORE INTO `candidates` (`id`, `position_id`, `candidate_name`, `year_level`, `platform`, `profile_image`) VALUES
(101, 1, 'Jesus Kyle F. Albos', '3rd Year', 'Commitment to transparency and student welfare.', 'Juan.jpg'),
(102, 1, 'Bagaipo', '3rd Year', 'Fostering campus unity and innovation.', 'candidate1.jpg'),
(103, 1, 'Fry', '4th Year', 'Empowering student voices through active dialogue.', 'candidate2.jpg'),
(104, 2, 'Ranola', '3rd Year', 'Dedicated service and efficient student programs.', 'candidate3.jpg'),
(105, 2, 'Jarapon', '2nd Year', 'Streamlining academic support and student events.', 'candidate4.jpg'),
(106, 2, 'Fajardo', '4th Year', 'Action-driven leadership for all student organizations.', 'candidate5.jpg'),
(107, 3, 'Navarro', '2nd Year', 'Accurate record-keeping and clear communications.', 'candidate6.jpg'),
(108, 3, 'Alvarez', '3rd Year', 'Digitalization of student services and requests.', 'Maria.png'),
(109, 3, 'Alejo', '1st Year', 'Fresh perspectives for student governance.', 'candidate7.jpg');

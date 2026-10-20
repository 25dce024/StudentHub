-- ============================================================================
-- StudentHub - Practical 14: Master Admin Dashboard Schema & Seed Data
-- Subject: ITUE203 - Web Development Frameworks
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `studenthub`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student', 'faculty') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Students Table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
  `course` VARCHAR(100) NOT NULL DEFAULT 'Computer Engineering',
    `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    `mobile` VARCHAR(15) NOT NULL,
    `college` VARCHAR(150) DEFAULT 'CSPIT / DEPSTAR, CHARUSAT',
    `branch` VARCHAR(100) DEFAULT 'CE',
    `semester` INT NOT NULL DEFAULT 3,
    `academic_year` VARCHAR(30) DEFAULT '2nd Year',
    `cgpa` DECIMAL(3,2) DEFAULT 8.50,
    `address` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Events Table
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'Technical',
    `event_date` DATE NOT NULL,
    `event_time` TIME DEFAULT '10:00:00',
    `venue` VARCHAR(150) DEFAULT 'Seminar Hall, CHARUSAT',
    `description` TEXT NULL,
    `capacity` INT DEFAULT 100,
    `status` ENUM('Open', 'Closed', 'Upcoming') DEFAULT 'Open',
    `poster` VARCHAR(255) DEFAULT 'default_poster.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Registrations Table
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    UNIQUE KEY `unique_student_event` (`student_id`, `event_id`),
    CONSTRAINT `fk_p14_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_p14_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `actor_username` VARCHAR(50) NOT NULL DEFAULT 'system',
    `action` VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT 'general',
    `entity_id` INT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_p14_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Seed Data
-- ============================================================================

-- Passwords:
-- admin / Admin@123 -> $2y$10$U3YhoLYdtJINHGwnHO/KDO4Dq6jy1mYhSe3G7Z2XN7rn4cd589B.O
-- student / Student@123 -> $2y$10$VAJBGHLESz8UL9/izfTvIumXqKdCUM.EGnmKpnj0xgKkLpvlLSQpO
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 'admin', 'admin@studenthub.edu', '$2y$10$U3YhoLYdtJINHGwnHO/KDO4Dq6jy1mYhSe3G7Z2XN7rn4cd589B.O', 'admin', 'active'),
(2, 'avibeladiya', '25dce007@charusat.edu.in', '$2y$10$VAJBGHLESz8UL9/izfTvIumXqKdCUM.EGnmKpnj0xgKkLpvlLSQpO', 'student', 'active'),
(3, 'faculty_patel', 'patel@charusat.edu.in', '$2y$10$U3YhoLYdtJINHGwnHO/KDO4Dq6jy1mYhSe3G7Z2XN7rn4cd589B.O', 'faculty', 'active')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- Seed Students
INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `course`, `gender`, `mobile`, `branch`, `semester`, `academic_year`, `cgpa`, `status`) VALUES
(1, '25DCE007', 'Avi Beladiya', '25dce007@charusat.edu.in', 'Computer Engineering', 'Male', '9876543210', 'CE', 3, '2nd Year', 9.45, 'active'),
(2, '25DCE012', 'Bhavya Shah', '25dce012@charusat.edu.in', 'Computer Engineering', 'Male', '9823456781', 'CE', 3, '2nd Year', 8.90, 'active'),
(3, '25DIT034', 'Dev Patel', '25dit034@charusat.edu.in', 'Information Technology', 'Male', '9898123456', 'IT', 3, '2nd Year', 9.15, 'active'),
(4, '25DAI003', 'Aryan Mehta', '25dai003@charusat.edu.in', 'Artificial Intelligence', 'Male', '9712345678', 'AI & DS', 3, '2nd Year', 8.70, 'active'),
(5, '25DEC019', 'Rohan Joshi', '25dec019@charusat.edu.in', 'Electronics & Communication', 'Male', '9612345678', 'EC', 3, '2nd Year', 8.35, 'active'),
(6, '25DME008', 'Kunal Trivedi', '25dme008@charusat.edu.in', 'Mechanical Engineering', 'Male', '9512345678', 'ME', 3, '2nd Year', 7.95, 'inactive')
ON DUPLICATE KEY UPDATE `student_id`=VALUES(`student_id`);

-- Seed Events
INSERT INTO `events` (`id`, `title`, `category`, `event_date`, `event_time`, `venue`, `description`, `capacity`, `status`, `poster`) VALUES
(1, 'HackCHARUSAT 2026', 'Technical', '2026-11-15', '09:00:00', 'CSPIT Computer Labs', 'Annual 36-hour national hackathon.', 300, 'Open', 'default_poster.png'),
(2, 'National AI Summit', 'Seminar', '2026-11-20', '10:30:00', 'Central Auditorium', 'Keynote addresses on AI.', 250, 'Upcoming', 'default_poster.png'),
(3, 'Spoural Cultural Fest', 'Cultural', '2026-12-05', '17:00:00', 'CHARUSAT OAT', 'Mega cultural festival.', 800, 'Upcoming', 'default_poster.png'),
(4, 'Inter-College Sports', 'Sports', '2026-10-18', '08:00:00', 'Sports Complex', 'Athletic competitions.', 400, 'Closed', 'default_poster.png')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Seed Event Registrations
INSERT INTO `registrations` (`id`, `student_id`, `event_id`, `status`) VALUES
(1, 1, 1, 'registered'),
(2, 2, 1, 'registered'),
(3, 3, 2, 'registered'),
(4, 4, 1, 'attended'),
(5, 5, 3, 'registered')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);

-- Seed Initial Audit Logs
INSERT INTO `audit_logs` (`user_id`, `actor_username`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES
(1, 'admin', 'AUTHENTICATION_LOGIN', 'user', 1, 'Admin logged in successfully from Chrome/Win11', '127.0.0.1'),
(1, 'admin', 'EVENT_CREATED', 'event', 1, 'Created new technical event: HackCHARUSAT 2026', '127.0.0.1'),
(1, 'admin', 'STUDENT_ENROLLED', 'student', 1, 'Admitted student Avi Beladiya (25DCE007) to CE Department', '127.0.0.1'),
(1, 'admin', 'SYSTEM_BACKUP', 'system', NULL, 'Automated database snapshot generated', '127.0.0.1');

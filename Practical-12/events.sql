-- ============================================================================
-- StudentHub - Practical 12: Events Database Schema & Seed Data
-- Subject: ITUE203 - Web Development Frameworks
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `studenthub`;

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
    `poster` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Events Seed Data
INSERT INTO `events` (`title`, `category`, `event_date`, `event_time`, `venue`, `description`, `capacity`, `status`, `poster`) VALUES
('HackCHARUSAT 2026', 'Technical', '2026-11-15', '09:00:00', 'CSPIT Computer Labs', 'Annual 36-hour national level hackathon focused on Web3, AI, and Sustainable Tech solutions.', 300, 'Open', 'default_poster.png'),
('National AI & Deep Learning Summit', 'Seminar', '2026-11-20', '10:30:00', 'Central Auditorium', 'Keynote addresses from premier industry research scientists on generative AI and transformer models.', 250, 'Upcoming', 'default_poster.png'),
('Spoural Annual Cultural Fest', 'Cultural', '2026-12-05', '17:00:00', 'CHARUSAT Open Air Theatre', 'Mega cultural showcase featuring classical music, contemporary dance, drama, and battle of the bands.', 800, 'Upcoming', 'default_poster.png'),
('Inter-College Sports Championship', 'Sports', '2026-10-18', '08:00:00', 'University Sports Complex', 'Annual sports festival including Cricket, Badminton, Volleyball, and Table Tennis competitions.', 400, 'Closed', 'default_poster.png'),
('Cloud DevOps & Docker Workshop', 'Workshop', '2026-11-25', '14:00:00', 'Lab 304, FTE Building', 'Hands-on training session on Docker containerization, Kubernetes clusters, and automated CI/CD pipelines.', 60, 'Open', 'default_poster.png'),
('Cyber Security & Ethical Hacking BootCamp', 'Workshop', '2026-12-10', '11:00:00', 'DEPSTAR Seminar Hall 2', 'Defensive security principles, network packet analysis, and live bug bounty demonstration.', 120, 'Open', 'default_poster.png');

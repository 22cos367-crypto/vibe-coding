-- ============================================================
-- VV EVENTS — MySQL Database Schema
-- Database Name: vv_events
-- ============================================================

CREATE DATABASE IF NOT EXISTS `vv_events` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vv_events`;

-- ------------------------------------------------------------
-- Table: admin_users
-- Stores administrator account login credentials
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (Username: admin | Password: admin123)
-- Hash generated via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admin_users` (`username`, `password_hash`)
VALUES ('admin', '$2y$10$r.7gq6fD8QGg6wN4W3Sg.O4U7z8/bJ0w4J0yF3F1E7c4f4H5b6a7C')
ON DUPLICATE KEY UPDATE `username` = `username`;


-- ------------------------------------------------------------
-- Table: bookings
-- Stores customer event booking requests & package customizations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_ref` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `event_location` TEXT NOT NULL,
  `preferred_time` TIME DEFAULT NULL,
  `guest_count` INT DEFAULT 100,
  `budget` VARCHAR(100) DEFAULT NULL,
  `special_requests` TEXT DEFAULT NULL,
  `event_date` DATE NOT NULL,
  `event_type` VARCHAR(50) NOT NULL DEFAULT 'wedding',
  `decoration` VARCHAR(50) NOT NULL DEFAULT 'basic',
  `entry` VARCHAR(50) NOT NULL DEFAULT 'none',
  `addons` TEXT DEFAULT NULL,
  `estimated_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table: contact_messages
-- Stores contact form inquiries submitted by website visitors
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') NOT NULL DEFAULT 'unread',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Sample Seed Data (For Testing)
-- ------------------------------------------------------------
INSERT INTO `bookings` (`booking_ref`, `full_name`, `phone`, `whatsapp`, `email`, `address`, `event_location`, `preferred_time`, `guest_count`, `budget`, `special_requests`, `event_date`, `event_type`, `decoration`, `entry`, `addons`, `estimated_price`, `status`)
VALUES 
('VV-2026-1001', 'Rahul Sharma', '9876543210', '9876543210', 'rahul@example.com', '15 Bypass Road, Madurai', 'Grand Palace Hall, Madurai', '18:00:00', 250, '₹80,000 - ₹1,000,000', 'Gold theme stage backdrop with rose entrance', CURDATE() + INTERVAL 5 DAY, 'wedding', 'luxury', 'pyro', '["photography","videography","host"]', 117000.00, 'confirmed'),
('VV-2026-1002', 'Priya Ananth', '9812345678', '9812345678', 'priya@example.com', '42 KK Nagar, Madurai', 'Le Royal Residency', '11:30:00', 100, '₹40,000', 'Need balloon arch for kid birthday', CURDATE() + INTERVAL 12 DAY, 'birthday', 'premium', 'balloon', '["cake","magicShow"]', 33000.00, 'pending');

INSERT INTO `contact_messages` (`full_name`, `phone`, `email`, `message`, `status`)
VALUES 
('Anand Kumar', '9988776655', 'anand@example.com', 'Hi, I would like to inquire about wedding packages for December 2026.', 'unread'),
('Meena Sundaram', '9776655443', 'meena@example.com', 'Do you provide outdoor lawn setup and lighting for corporate events?', 'unread');

-- FWBer Database Schema Setup
-- Run this in phpMyAdmin or MySQL command line to set up the database

-- Create the database (if not exists)
CREATE DATABASE IF NOT EXISTS `fwber` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `fwber`;

-- Users table (enhanced from original)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_salt` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_online` timestamp DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) DEFAULT 1,
  
  -- Profile information
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','non-binary','trans-male','trans-female','other') DEFAULT NULL,
  `seeking_gender` enum('male','female','non-binary','trans-male','trans-female','any') DEFAULT 'any',
  `relationship_type` enum('casual','relationship','friends','hookup','any') DEFAULT 'any',
  
  -- Physical attributes for avatar generation
  `hair_color` varchar(50) DEFAULT NULL,
  `hair_style` varchar(50) DEFAULT NULL,
  `eye_color` varchar(50) DEFAULT NULL,
  `ethnicity` varchar(50) DEFAULT NULL,
  `body_type` varchar(50) DEFAULT NULL,
  `height` int(11) DEFAULT NULL, -- in cm
  `interests` text DEFAULT NULL, -- comma-separated
  
  -- Location
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_updated_at` timestamp NULL DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  
  -- Preferences
  `age_preference_min` int(11) DEFAULT 18,
  `age_preference_max` int(11) DEFAULT 99,
  `max_distance` int(11) DEFAULT 50, -- in km
  
  -- Avatar and photos
  `avatar_url` varchar(500) DEFAULT NULL,
  `avatar_updated_at` timestamp NULL DEFAULT NULL,
  `profile_photo_url` varchar(500) DEFAULT NULL,
  `photo_verified` tinyint(1) DEFAULT 0,
  `verification_badge` tinyint(1) DEFAULT 0,
  
  -- 2FA
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(500) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `location_idx` (`latitude`,`longitude`),
  KEY `age_idx` (`age`),
  KEY `last_online_idx` (`last_online`),
  KEY `active_idx` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User preferences (for detailed sexual/lifestyle preferences)
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_preference_unique` (`user_id`,`preference_key`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User privacy settings
CREATE TABLE IF NOT EXISTS `user_privacy_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_setting_unique` (`user_id`,`setting_key`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos (encrypted storage)
CREATE TABLE IF NOT EXISTS `user_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `encrypted_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_private` tinyint(1) DEFAULT 1,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `user_primary_photo` (`user_id`,`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User interactions (likes, passes, matches)
CREATE TABLE IF NOT EXISTS `user_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `interaction_type` enum('like','pass','super_like','block','report') NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_interaction_unique` (`user_id`,`target_user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `target_user_idx` (`target_user_id`),
  KEY `interaction_type_idx` (`interaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches (mutual likes)
CREATE TABLE IF NOT EXISTS `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `matched_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `conversation_started` tinyint(1) DEFAULT 0,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `match_unique` (`user1_id`,`user2_id`),
  FOREIGN KEY (`user1_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user2_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `user2_idx` (`user2_id`),
  KEY `matched_at_idx` (`matched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages (encrypted)
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `encrypted_message` text NOT NULL,
  `message_hash` varchar(255) NOT NULL,
  `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `match_sent_idx` (`match_id`,`sent_at`),
  KEY `recipient_read_idx` (`recipient_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photo access log (privacy tracking)
CREATE TABLE IF NOT EXISTS `photo_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_id` int(11) NOT NULL,
  `viewer_id` int(11) NOT NULL,
  `accessed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`photo_id`) REFERENCES `user_photos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`viewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `photo_accessed_idx` (`photo_id`,`accessed_at`),
  KEY `viewer_accessed_idx` (`viewer_id`,`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(500) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `session_token_unique` (`session_token`),
  KEY `user_active_sessions` (`user_id`,`is_active`),
  KEY `expires_at_idx` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification requests
CREATE TABLE IF NOT EXISTS `verification_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `verification_challenge` varchar(255) NOT NULL,
  `original_photo_path` varchar(500) DEFAULT NULL,
  `verification_photo_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `status_requested_idx` (`status`,`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `user_read_created` (`user_id`,`read`,`created_at`),
  KEY `type_created_idx` (`type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security log
CREATE TABLE IF NOT EXISTS `security_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `event_type_created` (`event_type`,`created_at`),
  KEY `user_created_idx` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User actions (for rate limiting and analytics)
CREATE TABLE IF NOT EXISTS `user_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  KEY `user_action_created` (`user_id`,`action_type`,`created_at`),
  KEY `created_at_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User behavior tracking (for AI matching)
CREATE TABLE IF NOT EXISTS `user_behavior` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `behavior_type` varchar(100) NOT NULL,
  `behavior_data` json NOT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_behavior_unique` (`user_id`,`behavior_type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default privacy settings for new users
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `create_default_privacy_settings` AFTER INSERT ON `users`
FOR EACH ROW
BEGIN
  INSERT INTO `user_privacy_settings` (`user_id`, `setting_key`, `setting_value`) VALUES
  (NEW.id, 'show_online_status', 1),
  (NEW.id, 'show_last_seen', 1),
  (NEW.id, 'show_distance', 1),
  (NEW.id, 'allow_photo_requests', 1),
  (NEW.id, 'auto_share_photos', 0),
  (NEW.id, 'show_in_discovery', 1),
  (NEW.id, 'allow_message_requests', 1);
END$$
DELIMITER ;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `users_location_age_active` ON `users` (`latitude`,`longitude`,`age`,`active`,`last_online`);
CREATE INDEX IF NOT EXISTS `matches_last_message` ON `matches` (`last_message_at` DESC);
CREATE INDEX IF NOT EXISTS `messages_match_sent` ON `messages` (`match_id`,`sent_at` DESC);
CREATE INDEX IF NOT EXISTS `notifications_user_unread` ON `notifications` (`user_id`,`read`,`created_at` DESC);

-- Sample data for testing (optional - remove in production)
/*
INSERT INTO `users` (`email`, `username`, `password_hash`, `password_salt`, `age`, `gender`, `seeking_gender`) VALUES
('test@example.com', 'TestUser', '$2y$10$example', 'examplesalt', 25, 'female', 'any'),
('demo@example.com', 'DemoUser', '$2y$10$example2', 'examplesalt2', 28, 'male', 'female');
*/
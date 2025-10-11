-- FWBer Test Database Setup
-- Creates a clean test database snapshot for E2E testing
-- Run this before each test cycle to ensure clean state

-- WARNING: This will DROP and recreate the test database
-- Use: mysql -u root -p < db/test-setup.sql

DROP DATABASE IF EXISTS `fwber_test`;
CREATE DATABASE `fwber_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `fwber_test`;

-- Import main database schema
SOURCE setup-database.sql;

-- Add test-specific configurations
SET @test_mode = 1;

-- Create test user accounts (passwords are all 'TestPass123!')
-- Password hash for 'TestPass123!' using Argon2ID
-- These will need to be generated using password_hash() in PHP

-- Test persona 1: Male user (John Doe)
INSERT INTO `users` (`email`, `username`, `password_hash`, `password_salt`, `email_verified`, `age`, `gender`, `created_at`)
VALUES ('john.test@example.com', 'JohnTest', '', '', 1, 28, 'male', NOW());

-- Test persona 2: Female user (Jane Smith)
INSERT INTO `users` (`email`, `username`, `password_hash`, `password_salt`, `email_verified`, `age`, `gender`, `created_at`)
VALUES ('jane.test@example.com', 'JaneTest', '', '', 1, 25, 'female', NOW());

-- Test persona 3: Non-binary user (Alex Taylor)
INSERT INTO `users` (`email`, `username`, `password_hash`, `password_salt`, `email_verified`, `age`, `gender`, `created_at`)
VALUES ('alex.test@example.com', 'AlexTest', '', '', 1, 30, 'non-binary', NOW());

-- Note: Password hashes must be generated via PHP script (see generate-test-users.php)
-- This SQL file provides the schema and structure only

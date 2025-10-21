-- FWBer Database Setup Script
-- Create database and user for Laravel backend

-- Create database
CREATE DATABASE IF NOT EXISTS fwber CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (drop first if exists)
DROP USER IF EXISTS 'fwber'@'localhost';
CREATE USER 'fwber'@'localhost' IDENTIFIED BY 'Temppass0!';

-- Grant privileges
GRANT ALL PRIVILEGES ON fwber.* TO 'fwber'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Show result
SELECT 'Database and user created successfully' as status;
SHOW DATABASES LIKE 'fwber';
SELECT User, Host FROM mysql.user WHERE User = 'fwber';
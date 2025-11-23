-- Venue and Location-based Features Database Schema
-- Run this to add venue and check-in functionality

-- Venues table
CREATE TABLE IF NOT EXISTS venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    venue_type ENUM('bar', 'club', 'restaurant', 'festival', 'event', 'gym', 'beach', 'other') DEFAULT 'other',
    capacity INT,
    age_restriction ENUM('18+', '21+', 'all_ages') DEFAULT '18+',
    dress_code VARCHAR(255),
    cover_charge DECIMAL(10, 2) DEFAULT 0,
    website VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    social_media JSON,
    amenities JSON,
    active BOOLEAN DEFAULT TRUE,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_location (latitude, longitude),
    INDEX idx_venue_type (venue_type),
    INDEX idx_active (active)
);

-- User venue check-ins
CREATE TABLE IF NOT EXISTS user_venue_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    venue_id INT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    announcement TEXT,
    duration_hours INT DEFAULT 4,
    checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checked_out_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    
    INDEX idx_user_checkins (user_id, checked_in_at),
    INDEX idx_venue_checkins (venue_id, checked_in_at),
    INDEX idx_active_checkins (venue_id, checked_out_at)
);

-- Venue events
CREATE TABLE IF NOT EXISTS venue_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    event_type ENUM('party', 'concert', 'festival', 'meetup', 'special', 'other') DEFAULT 'other',
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    cover_charge DECIMAL(10, 2) DEFAULT 0,
    age_restriction ENUM('18+', '21+', 'all_ages') DEFAULT '18+',
    dress_code VARCHAR(255),
    capacity INT,
    expected_attendance INT,
    special_requirements TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    
    INDEX idx_venue_events (venue_id, start_date),
    INDEX idx_event_dates (start_date, end_date),
    INDEX idx_event_type (event_type)
);

-- User event attendance
CREATE TABLE IF NOT EXISTS user_event_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('interested', 'attending', 'checked_in', 'left') DEFAULT 'interested',
    checked_in_at TIMESTAMP NULL,
    checked_out_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES venue_events(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_user_events (user_id, status),
    INDEX idx_event_attendance (event_id, status)
);

-- Location-based matches
CREATE TABLE IF NOT EXISTS location_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    venue_id INT,
    event_id INT,
    match_type ENUM('location', 'venue', 'event') DEFAULT 'location',
    distance_km DECIMAL(8, 2),
    matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'expired', 'met', 'declined') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES venue_events(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_location_match (user1_id, user2_id, venue_id, matched_at),
    INDEX idx_user_matches (user1_id, status),
    INDEX idx_venue_matches (venue_id, matched_at),
    INDEX idx_expired_matches (expires_at, status)
);

-- Add venue-related columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS current_venue_id INT NULL,
ADD COLUMN IF NOT EXISTS current_venue_name VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS last_location_type ENUM('manual', 'gps', 'venue') DEFAULT 'manual',
ADD COLUMN IF NOT EXISTS location_privacy ENUM('public', 'friends', 'venue_only', 'private') DEFAULT 'venue_only',
ADD COLUMN IF NOT EXISTS auto_checkin BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS checkin_radius INT DEFAULT 100; -- meters

-- Add foreign key for current venue
ALTER TABLE users 
ADD CONSTRAINT fk_users_current_venue 
FOREIGN KEY (current_venue_id) REFERENCES venues(id) ON DELETE SET NULL;

-- Insert some sample venues for testing
INSERT IGNORE INTO venues (name, latitude, longitude, city, state, venue_type, description) VALUES
('Austin Music Hall', 30.2672, -97.7431, 'Austin', 'TX', 'club', 'Popular music venue in downtown Austin'),
('South by Southwest', 30.2672, -97.7431, 'Austin', 'TX', 'festival', 'Annual music, film, and interactive festival'),
('Rainey Street District', 30.2672, -97.7431, 'Austin', 'TX', 'bar', 'Historic district with bars and restaurants'),
('Zilker Park', 30.2672, -97.7431, 'Austin', 'TX', 'other', 'Large park with events and festivals'),
('6th Street', 30.2672, -97.7431, 'Austin', 'TX', 'bar', 'Famous entertainment district');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_location ON users(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_users_venue ON users(current_venue_id);
CREATE INDEX IF NOT EXISTS idx_users_last_online ON users(last_online);

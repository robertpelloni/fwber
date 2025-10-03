-- Admin and platform management tables
-- Run this after the main database setup

-- Table for platform settings
CREATE TABLE IF NOT EXISTS platform_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for content flags and moderation
CREATE TABLE IF NOT EXISTS content_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('user', 'profile', 'avatar', 'event', 'venue', 'message', 'other') NOT NULL,
    content_id INT,
    flagged_by INT,
    reason ENUM('spam', 'inappropriate', 'harassment', 'fake', 'underage', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected', 'resolved') DEFAULT 'pending',
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (flagged_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_content_type_id (content_type, content_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Table for admin actions and audit log
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action_type VARCHAR(100) NOT NULL,
    target_type ENUM('user', 'venue', 'event', 'content', 'system', 'other') NOT NULL,
    target_id INT,
    action_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
);

-- Table for system notifications
CREATE TABLE IF NOT EXISTS system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('info', 'warning', 'error', 'success') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_audience ENUM('all', 'admins', 'moderators', 'users', 'venues') DEFAULT 'all',
    target_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    read_by INT,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_target_audience (target_audience),
    INDEX idx_is_read (is_read),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at)
);

-- Table for user reports
CREATE TABLE IF NOT EXISTS user_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT,
    reported_content_type ENUM('user', 'profile', 'avatar', 'message', 'event', 'venue') NOT NULL,
    reported_content_id INT,
    reason ENUM('spam', 'harassment', 'inappropriate', 'fake', 'underage', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    assigned_to INT,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reporter (reporter_id),
    INDEX idx_reported_user (reported_user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Table for platform analytics
CREATE TABLE IF NOT EXISTS platform_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15, 2) NOT NULL,
    metric_date DATE NOT NULL,
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_metric_date (metric_name, metric_date),
    INDEX idx_metric_name (metric_name),
    INDEX idx_metric_date (metric_date)
);

-- Table for API usage tracking
CREATE TABLE IF NOT EXISTS api_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    api_endpoint VARCHAR(255) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    response_code INT,
    response_time_ms INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_api_endpoint (api_endpoint),
    INDEX idx_created_at (created_at)
);

-- Insert default platform settings
INSERT INTO platform_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'FWBer.me', 'string', 'Platform name'),
('site_description', 'Location-based dating platform with AI avatars', 'string', 'Platform description'),
('max_users', '0', 'integer', 'Maximum number of users (0 = unlimited)'),
('avatar_cost', '0.02', 'string', 'Cost per avatar generation in USD'),
('moderation_level', 'moderate', 'string', 'Content moderation level'),
('auto_moderation', '1', 'boolean', 'Enable automatic content moderation'),
('email_notifications', '1', 'boolean', 'Enable email notifications'),
('sms_notifications', '0', 'boolean', 'Enable SMS notifications'),
('push_notifications', '1', 'boolean', 'Enable push notifications'),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode'),
('registration_enabled', '1', 'boolean', 'Allow new user registrations'),
('guest_access', '0', 'boolean', 'Allow guest access to platform'),
('api_rate_limit', '100', 'integer', 'API requests per hour per user'),
('session_timeout', '120', 'integer', 'Session timeout in minutes'),
('password_policy', 'medium', 'string', 'Password strength requirement'),
('content_filtering', '1', 'boolean', 'Enable content filtering')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert sample system notifications
INSERT INTO system_notifications (notification_type, title, message, target_audience) VALUES
('info', 'Welcome to FWBer Admin Panel', 'The admin panel is now available for platform management.', 'admins'),
('warning', 'System Maintenance', 'Scheduled maintenance will occur tonight at 2 AM EST.', 'all'),
('success', 'New Feature Available', 'AI avatar generation is now live for all users.', 'all');

-- Insert sample analytics data
INSERT INTO platform_analytics (metric_name, metric_value, metric_date) VALUES
('total_users', 1250, CURDATE()),
('active_users', 890, CURDATE()),
('total_venues', 15, CURDATE()),
('total_events', 45, CURDATE()),
('matches_made', 320, CURDATE()),
('revenue_month', 2500.00, CURDATE()),
('api_calls_today', 15420, CURDATE()),
('avatar_generations', 180, CURDATE())
ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value);

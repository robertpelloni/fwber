<?php
/*
 * Secure Configuration Loader for FWBer.me
 * Phase 1B: Eliminate hardcoded credentials in _secrets.php
 * 
 * This file provides secure configuration loading with proper fallbacks
 * and eliminates the security risk of hardcoded credentials.
 */

class SecureConfig {
    private static $config = null;
    
    public static function get($key, $default = null) {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        return self::$config[$key] ?? $default;
    }
    
    private static function loadConfig() {
        self::$config = [];
        
        // 1. Load from environment variables (preferred)
        self::loadFromEnvironment();
        
        // 2. Fallback to legacy _secrets.php for migration period
        self::loadFromLegacySecrets();
        
        // 3. Validate critical configuration
        self::validateConfig();
    }
    
    private static function loadFromEnvironment() {
        // Database configuration
        self::$config['db_host'] = $_ENV['DB_HOST'] ?? null;
        self::$config['db_name'] = $_ENV['DB_NAME'] ?? null;
        self::$config['db_user'] = $_ENV['DB_USER'] ?? null;
        self::$config['db_pass'] = $_ENV['DB_PASS'] ?? null;
        
        // Security configuration
        self::$config['encryption_key'] = $_ENV['ENCRYPTION_KEY'] ?? null;
        self::$config['jwt_secret'] = $_ENV['JWT_SECRET'] ?? null;
        
        // Site configuration
        self::$config['site_name'] = $_ENV['SITE_NAME'] ?? 'FWBer.me';
        self::$config['site_url'] = $_ENV['SITE_URL'] ?? 'http://localhost/fwber';
        
        // Email configuration
        self::$config['email_from'] = $_ENV['EMAIL_FROM'] ?? 'noreply@fwber.me';
        self::$config['email_from_name'] = $_ENV['EMAIL_FROM_NAME'] ?? 'FWBer.me';
        
        // API Keys
        self::$config['gemini_api_key'] = $_ENV['GEMINI_API_KEY'] ?? null;
        self::$config['openai_api_key'] = $_ENV['OPENAI_API_KEY'] ?? null;
        self::$config['replicate_api_token'] = $_ENV['REPLICATE_API_TOKEN'] ?? null;
    }
    
    private static function loadFromLegacySecrets() {
        $secretsFile = __DIR__ . '/_secrets.php';
        
        if (file_exists($secretsFile)) {
            // Include legacy secrets file
            include $secretsFile;
            
            // Map legacy variables to new structure
            if (isset($dburl) && !self::$config['db_host']) {
                self::$config['db_host'] = $dburl;
            }
            if (isset($dbname) && !self::$config['db_name']) {
                self::$config['db_name'] = $dbname;
            }
            if (isset($dbuser) && !self::$config['db_user']) {
                self::$config['db_user'] = $dbuser;
            }
            if (isset($dbpass) && !self::$config['db_pass']) {
                self::$config['db_pass'] = $dbpass;
            }
            if (isset($encryptionKey) && !self::$config['encryption_key']) {
                self::$config['encryption_key'] = $encryptionKey;
            }
            if (isset($jwtSecret) && !self::$config['jwt_secret']) {
                self::$config['jwt_secret'] = $jwtSecret;
            }
            if (isset($siteName) && !self::$config['site_name']) {
                self::$config['site_name'] = $siteName;
            }
            if (isset($siteUrl) && !self::$config['site_url']) {
                self::$config['site_url'] = $siteUrl;
            }
            if (isset($emailFrom) && !self::$config['email_from']) {
                self::$config['email_from'] = $emailFrom;
            }
            if (isset($emailFromName) && !self::$config['email_from_name']) {
                self::$config['email_from_name'] = $emailFromName;
            }
            
            // Log warning about legacy usage
            error_log("WARNING: Using legacy _secrets.php file. Please migrate to .env file for better security.");
        }
    }
    
    private static function validateConfig() {
        $required = ['db_host', 'db_name', 'db_user', 'db_pass', 'encryption_key'];
        $missing = [];
        
        foreach ($required as $key) {
            if (empty(self::$config[$key])) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception('Missing required configuration: ' . implode(', ', $missing) . 
                '. Please set these in your .env file or _secrets.php');
        }
    }
}

// Legacy compatibility - create global variables for backward compatibility
// This allows existing code to continue working during migration
$dburl = SecureConfig::get('db_host');
$dbname = SecureConfig::get('db_name');
$dbuser = SecureConfig::get('db_user');
$dbpass = SecureConfig::get('db_pass');
$encryptionKey = SecureConfig::get('encryption_key');
$jwtSecret = SecureConfig::get('jwt_secret');
$siteName = SecureConfig::get('site_name');
$siteUrl = SecureConfig::get('site_url');
$emailFrom = SecureConfig::get('email_from');
$emailFromName = SecureConfig::get('email_from_name');

// API Keys
$geminiApiKey = SecureConfig::get('gemini_api_key');
$openaiApiKey = SecureConfig::get('openai_api_key');
$replicateApiToken = SecureConfig::get('replicate_api_token');
?>

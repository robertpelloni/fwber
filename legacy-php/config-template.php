<?php
/*
    Configuration Template for FWBer
    Copy this to config.php and fill in your actual values
*/

// ===========================================
// API KEYS AND EXTERNAL SERVICES
// ===========================================

// Replicate API for AI Avatar Generation
// Get your key from: https://replicate.com/account/api-tokens
define('REPLICATE_API_TOKEN', 'r8_your_token_here');

// OpenAI API (alternative for avatar generation)
// Get your key from: https://platform.openai.com/api-keys
define('OPENAI_API_KEY', 'sk-your_key_here');

// Google Analytics
// Get your ID from: https://analytics.google.com
define('GOOGLE_ANALYTICS_ID', 'G-XXXXXXXXXX');

// reCAPTCHA Keys
// Get from: https://www.google.com/recaptcha/admin
define('RECAPTCHA_SITE_KEY', '6LfUldISAAAAAJjP3rj8cCd1CEmBrfdEMVE_51eZ');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_here');

// ===========================================
// EMAIL CONFIGURATION
// ===========================================

// SMTP Settings for sending emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('SMTP_ENCRYPTION', 'tls');

// From email address
define('FROM_EMAIL', 'noreply@fwber.me');
define('FROM_NAME', 'FWBer Team');

// ===========================================
// SECURITY SETTINGS
// ===========================================

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('REQUIRE_2FA', false); // Set to true to require 2FA for all users

// Rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// Photo and message encryption
define('ENABLE_PHOTO_ENCRYPTION', true);
define('ENABLE_MESSAGE_ENCRYPTION', true);

// ===========================================
// AVATAR GENERATION SETTINGS
// ===========================================

// Default avatar provider: 'replicate', 'dalle', 'local'
define('DEFAULT_AVATAR_PROVIDER', 'replicate');

// Local Stable Diffusion URL (if using local setup)
define('LOCAL_SD_URL', 'http://127.0.0.1:7860');

// ComfyUI URL (if using ComfyUI)
define('COMFYUI_URL', 'http://127.0.0.1:8188');

// Avatar generation settings
define('AVATAR_STYLE', 'realistic portrait');
define('AVATAR_SIZE', '512x512');
define('AVATAR_STEPS', 30);
define('AVATAR_GUIDANCE_SCALE', 7.5);

// ===========================================
// LOCATION SERVICES
// ===========================================

// Enable location-based features
define('ENABLE_LOCATION_FEATURES', true);

// Default search radius in kilometers
define('DEFAULT_SEARCH_RADIUS', 50);

// Maximum search radius
define('MAX_SEARCH_RADIUS', 200);

// ===========================================
// MATCHING ALGORITHM WEIGHTS
// ===========================================

// Customize the AI matching algorithm weights (must sum to 1.0)
define('MATCHING_WEIGHTS', [
    'location_proximity' => 0.25,
    'age_compatibility' => 0.20,
    'interests_overlap' => 0.20,
    'activity_pattern' => 0.15,
    'interaction_history' => 0.10,
    'avatar_similarity' => 0.10
]);

// ===========================================
// DEVELOPMENT SETTINGS
// ===========================================

// Enable debug mode (set to false in production)
define('DEBUG_MODE', true);

// Log all SQL queries (for development)
define('LOG_SQL_QUERIES', true);

// Test mode (uses demo data)
define('TEST_MODE', false);

// ===========================================
// HELPER FUNCTIONS
// ===========================================

/**
 * Check if all required configuration is set
 */
function validateConfiguration() {
    $required = [
        'REPLICATE_API_TOKEN',
        'GOOGLE_ANALYTICS_ID',
        'RECAPTCHA_SITE_KEY',
        'RECAPTCHA_SECRET_KEY'
    ];
    
    $missing = [];
    foreach ($required as $const) {
        if (!defined($const) || constant($const) === 'your_token_here' || constant($const) === 'G-XXXXXXXXXX') {
            $missing[] = $const;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('Missing required configuration: ' . implode(', ', $missing));
    }
    
    return true;
}

/**
 * Get configuration value with fallback
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// ===========================================
// USAGE INSTRUCTIONS
// ===========================================

/*
1. Copy this file to 'config.php'
2. Fill in your actual API keys and settings
3. Include this file in your PHP scripts: require_once('config.php');
4. Call validateConfiguration() to check setup
5. Use getConfig('KEY', 'default') to get configuration values

Example usage:
try {
    require_once('config.php');
    validateConfiguration();
    echo "Configuration valid!";
} catch (Exception $e) {
    echo "Configuration error: " . $e->getMessage();
}
*/
?>
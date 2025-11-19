<?php
/*
    API endpoint for AI avatar generation
    Integrates with the AvatarGenerator class
*/

header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once('../_init.php');
    require_once('../avatar-generator.php');
    require_once('../ProfileManager.php');

    // Ensure the user is logged in
    if (!validateSessionOrCookiesReturnLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit();
    }

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit();
    }

    $userId = getUserIdByEmail($_SESSION['email']);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    // Initialize managers
    $profileManager = new ProfileManager($pdo);
    
    // Configuration for the AvatarGenerator
    $config = [
        'replicate_token' => $_ENV['REPLICATE_API_TOKEN'] ?? '',
        'openai_api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
        'gemini_api_key' => $_ENV['GEMINI_API_KEY'] ?? '',
        'local_sd_url' => 'http://127.0.0.1:7860',
        'default_style' => 'realistic portrait, professional headshot',
        'cache_avatars' => true,
        'max_retries' => 3
    ];

    // Determine which provider to use based on available configuration
    $provider = 'local'; // Default fallback
    if (!empty($config['gemini_api_key'])) {
        $provider = 'gemini'; // Prefer Gemini for better quality
    } elseif (!empty($config['replicate_token'])) {
        $provider = 'replicate';
    } elseif (!empty($config['openai_api_key'])) {
        $provider = 'dalle';
    }

    // The AvatarGenerator now requires the ProfileManager in its constructor
    $generator = new AvatarGenerator($profileManager, $provider, $config);

    // Generate the avatar using the secure userId
    $result = $generator->generateAvatar($userId);

    if ($result['success']) {
        // Save the avatar URL to the user's profile
        $updateData = ['avatar_url' => $result['image_url']];
        $profileManager->saveProfile($userId, $updateData);

        echo json_encode([
            'success' => true,
            'avatar_url' => $result['image_url'],
            'provider' => $result['provider']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Avatar generation failed'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
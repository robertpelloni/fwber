<?php
/*
    Modern AI-enhanced matching API endpoint
    Integrates with the AIMatchingEngine class while maintaining compatibility with legacy system
*/

header('Content-Type: application/json');

try {
    require_once('../_init.php');
    require_once('../ai-matching-engine.php');
    require_once('../ProfileManager.php');

    // Ensure the user is logged in
    if (!validateSessionOrCookiesReturnLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit();
    }

    $userId = getUserIdByEmail($_SESSION['email']);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    // Get filter parameters
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
    $filters = [];
    
    if (isset($_GET['max_distance'])) {
        $filters['max_distance'] = (float)$_GET['max_distance'];
    }
    if (isset($_GET['min_age'])) {
        $filters['min_age'] = (int)$_GET['min_age'];
    }
    if (isset($_GET['max_age'])) {
        $filters['max_age'] = (int)$_GET['max_age'];
    }

    // Get user's location if available for distance filtering
    $profileManager = new ProfileManager($pdo);
    $userProfile = $profileManager->getProfile($userId);
    
    if ($userProfile && isset($userProfile['latitude']) && isset($userProfile['longitude'])) {
        $filters['user_lat'] = $userProfile['latitude'];
        $filters['user_lng'] = $userProfile['longitude'];
    }

    // Initialize AI matching engine
    $config = [
        'avatar_provider' => 'local', // Default provider
        'weights' => [
            'location_proximity' => 0.30,
            'age_compatibility' => 0.25, 
            'interests_overlap' => 0.25,
            'activity_pattern' => 0.10,
            'interaction_history' => 0.05,
            'avatar_similarity' => 0.05
        ]
    ];

    $matchingEngine = new AIMatchingEngine($pdo, $config);

    // Get AI-enhanced matches
    $matches = $matchingEngine->findMatches($userId, $limit, $filters);

    // Format matches for frontend
    $formattedMatches = [];
    foreach ($matches as $match) {
        $user = $match['user'];
        $formattedMatches[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'age' => $user['age'],
            'gender' => $user['gender'],
            'location' => ($user['city'] ?? '') . ', ' . ($user['state'] ?? ''),
            'avatar_url' => $user['avatar_url'] ?? '/images/default-avatar.png',
            'interests' => $user['interests'] ?? '',
            'match_score' => round($match['score'] * 100, 1),
            'score_breakdown' => $match['breakdown'],
            'last_online' => $user['last_online'] ?? null,
            'distance_km' => isset($match['breakdown']['location']) ? 
                round($match['breakdown']['location'] * 100, 1) : null
        ];
    }

    echo json_encode([
        'success' => true,
        'matches' => $formattedMatches,
        'total_found' => count($formattedMatches),
        'user_profile_complete' => !empty($userProfile['age']) && !empty($userProfile['gender'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
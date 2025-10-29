<?php
/*
    API endpoint for enhanced matching with AI-powered scoring
    Returns ranked matches with compatibility breakdown
*/

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once('../_init.php');
    require_once('../MatchingEngine.php');
    require_once('../ProfileManager.php');

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

    // Get request parameters
    $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
    $filters = [];
    
    if (isset($_GET['online_only']) && $_GET['online_only'] === 'true') {
        $filters['online_only'] = true;
    }
    
    if (isset($_GET['new_users']) && $_GET['new_users'] === 'true') {
        $filters['new_users'] = true;
    }
    
    if (isset($_GET['max_distance'])) {
        $filters['max_distance'] = intval($_GET['max_distance']);
    }

    // Initialize matching engine
    $matchingEngine = new MatchingEngine($pdo, $userId);
    
    // Get matches
    $matches = $matchingEngine->getMatches($limit, $filters);
    
    // Format response
    $formattedMatches = [];
    foreach ($matches as $match) {
        $user = $match['user'];
        $formattedMatches[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'age' => $user['age'],
            'gender' => $user['gender'],
            'avatar_url' => $user['avatar_url'] ?? '/images/default-avatar.png',
            'city' => $user['city'] ?? 'Unknown',
            'state' => $user['state'] ?? '',
            'distance_km' => round($match['distance_km'], 1),
            'compatibility_score' => $match['score'],
            'score_breakdown' => $match['breakdown'],
            'online' => isUserOnline($user['last_seen'] ?? null),
            'last_seen' => $user['last_seen'],
            'preview_text' => substr($user['publicText'] ?? 'No bio yet', 0, 150)
        ];
    }

    echo json_encode([
        'success' => true,
        'matches' => $formattedMatches,
        'total' => count($formattedMatches),
        'filters_applied' => $filters
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Check if user is currently online
 */
function isUserOnline($lastSeen) {
    if (!$lastSeen) return false;
    $minutesSinceActive = (time() - strtotime($lastSeen)) / 60;
    return $minutesSinceActive < 30;
}
?>

<?php
/*
    API endpoint for updating user location
    Used by the real-time matching system
*/

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once('../_init.php');

// Check if user is logged in
$loggedIn = validateSessionOrCookiesReturnLoggedIn();
if (!$loggedIn) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = getUserId();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['latitude']) || !isset($input['longitude'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input. Latitude and longitude required.']);
    exit();
}

$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$timestamp = isset($input['timestamp']) ? intval($input['timestamp']) : time();

// Validate coordinates
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit();
}

// Update user location in database
$sql = "UPDATE users SET 
        latitude = ?, 
        longitude = ?, 
        location_updated_at = NOW(),
        last_online = NOW() 
        WHERE id = ?";

if ($stmt = $link->prepare($sql)) {
    $stmt->bind_param('ddi', $latitude, $longitude, $userId);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log location update for analytics
        error_log("Location updated for user $userId: $latitude, $longitude");
        
        // Check for nearby users and potential matches
        $nearbyMatches = findNearbyMatches($userId, $latitude, $longitude);
        
        echo json_encode([
            'success' => true,
            'message' => 'Location updated successfully',
            'nearby_matches' => count($nearbyMatches),
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update location']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

/**
 * Find potential matches nearby
 */
function findNearbyMatches($userId, $userLat, $userLng, $radiusKm = 10) {
    global $link;
    
    // Get user's preferences
    $userProfile = getProfile($userId);
    if (!$userProfile) {
        return [];
    }
    
    // Find users within radius with compatible preferences
    $sql = "SELECT u.id, u.username, u.latitude, u.longitude,
                   (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * 
                   cos(radians(u.longitude) - radians(?)) + 
                   sin(radians(?)) * sin(radians(u.latitude)))) AS distance
            FROM users u 
            WHERE u.id != ? 
            AND u.latitude IS NOT NULL 
            AND u.longitude IS NOT NULL
            AND u.last_online > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND u.id NOT IN (
                SELECT target_user_id FROM user_actions 
                WHERE user_id = ? AND action_type IN ('pass', 'block')
            )
            HAVING distance < ?
            ORDER BY distance ASC, u.last_online DESC
            LIMIT 20";
    
    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param('dddiii', $userLat, $userLng, $userLat, $userId, $userId, $radiusKm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $matches = [];
        while ($row = $result->fetch_assoc()) {
            // Check compatibility
            $targetProfile = getProfile($row['id']);
            if ($targetProfile && areCompatible($userProfile, $targetProfile)) {
                $matches[] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'distance' => round($row['distance'], 1),
                    'compatibility' => calculateCompatibility($userProfile, $targetProfile)
                ];
            }
        }
        
        $stmt->close();
        
        // If we found new nearby matches, trigger notifications
        if (!empty($matches)) {
            triggerMatchNotifications($userId, $matches);
        }
        
        return $matches;
    }
    
    return [];
}

/**
 * Check if two users are compatible based on preferences
 */
function areCompatible($user1, $user2) {
    // Basic compatibility checks
    // Age preferences
    if (isset($user1['age_min']) && $user2['age'] < $user1['age_min']) return false;
    if (isset($user1['age_max']) && $user2['age'] > $user1['age_max']) return false;
    if (isset($user2['age_min']) && $user1['age'] < $user2['age_min']) return false;
    if (isset($user2['age_max']) && $user1['age'] > $user2['age_max']) return false;
    
    // Gender preferences
    if (isset($user1['seeking_gender']) && $user1['seeking_gender'] != 'any') {
        if ($user1['seeking_gender'] != $user2['gender']) return false;
    }
    if (isset($user2['seeking_gender']) && $user2['seeking_gender'] != 'any') {
        if ($user2['seeking_gender'] != $user1['gender']) return false;
    }
    
    // Additional compatibility logic can be added here
    return true;
}

/**
 * Calculate compatibility score
 */
function calculateCompatibility($user1, $user2) {
    $score = 0;
    $factors = 0;
    
    // Age compatibility
    $ageDiff = abs($user1['age'] - $user2['age']);
    if ($ageDiff <= 5) $score += 20;
    else if ($ageDiff <= 10) $score += 10;
    $factors++;
    
    // Location proximity bonus (already filtered by distance)
    $score += 20;
    $factors++;
    
    // Interests matching (simplified)
    if (isset($user1['interests']) && isset($user2['interests'])) {
        $user1Interests = explode(',', $user1['interests']);
        $user2Interests = explode(',', $user2['interests']);
        $commonInterests = count(array_intersect($user1Interests, $user2Interests));
        $score += min($commonInterests * 10, 30);
        $factors++;
    }
    
    // Online activity bonus
    $user1LastOnline = strtotime($user1['last_online'] ?? 'now');
    $user2LastOnline = strtotime($user2['last_online'] ?? 'now');
    if (time() - $user1LastOnline < 3600 && time() - $user2LastOnline < 3600) {
        $score += 15; // Both online recently
    }
    $factors++;
    
    return min(round($score / $factors * 5), 100); // Scale to 0-100
}

/**
 * Trigger match notifications for nearby users
 */
function triggerMatchNotifications($userId, $matches) {
    // This could integrate with push notification services
    // For now, we'll log it for the real-time system to pick up
    
    foreach ($matches as $match) {
        error_log("New nearby match for user $userId: user {$match['id']} at {$match['distance']}km");
        
        // Store notification in database for real-time pickup
        $sql = "INSERT INTO notifications (user_id, type, data, created_at) 
                VALUES (?, 'nearby_match', ?, NOW())";
        
        if ($stmt = $GLOBALS['link']->prepare($sql)) {
            $notificationData = json_encode($match);
            $stmt->bind_param('is', $userId, $notificationData);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
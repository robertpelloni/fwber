<?php
/*
    Real-time match streaming using Server-Sent Events
    This provides live updates when new matches are found
*/

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Prevent timeout
set_time_limit(0);

// Include necessary files
require_once('_init.php');
require_once('_getMatches.php');

// Check if user is logged in
$loggedIn = validateSessionOrCookiesReturnLoggedIn();
if (!$loggedIn) {
    echo "event: error\n";
    echo "data: Not authenticated\n\n";
    exit();
}

$userId = getUserId();
$lastMatchCheck = time();

// Function to send SSE data
function sendSSE($event, $data) {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Send initial connection confirmation
sendSSE('connected', ['message' => 'Real-time matching active', 'userId' => $userId]);

// Main loop for real-time updates
while (true) {
    // Check for new matches every 30 seconds
    if (time() - $lastMatchCheck >= 30) {
        $matches = getMatches($userId, 1, 5); // Get latest 5 matches
        
        if (!empty($matches)) {
            sendSSE('new_matches', [
                'count' => count($matches),
                'matches' => array_map(function($match) {
                    return [
                        'id' => $match['id'],
                        'username' => $match['username'],
                        'avatar' => $match['avatar_url'],
                        'compatibility' => $match['compatibility_score'],
                        'distance' => $match['distance'],
                        'last_online' => $match['last_online']
                    ];
                }, $matches)
            ]);
        }
        
        $lastMatchCheck = time();
    }
    
    // Check for location updates from other users nearby
    $nearbyUsers = getNearbyUsers($userId, 5); // 5km radius
    if (!empty($nearbyUsers)) {
        sendSSE('nearby_users', [
            'count' => count($nearbyUsers),
            'users' => $nearbyUsers
        ]);
    }
    
    // Check for messages or interactions
    $newInteractions = getNewInteractions($userId);
    if (!empty($newInteractions)) {
        sendSSE('interactions', [
            'count' => count($newInteractions),
            'interactions' => $newInteractions
        ]);
    }
    
    // Sleep for 5 seconds before next check
    sleep(5);
    
    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }
}

function getNearbyUsers($userId, $radiusKm = 5) {
    global $link;
    
    // Get user's current location
    $userLocation = getUserLocation($userId);
    if (!$userLocation) {
        return [];
    }
    
    $lat = $userLocation['latitude'];
    $lng = $userLocation['longitude'];
    
    // Find users within radius using Haversine formula
    $sql = "SELECT u.id, u.username, u.latitude, u.longitude, u.last_online,
                   (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * 
                   cos(radians(u.longitude) - radians(?)) + 
                   sin(radians(?)) * sin(radians(u.latitude)))) AS distance
            FROM users u 
            WHERE u.id != ? 
            AND u.latitude IS NOT NULL 
            AND u.longitude IS NOT NULL
            AND u.last_online > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            HAVING distance < ?
            ORDER BY distance ASC
            LIMIT 10";
    
    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param('ddddi', $lat, $lng, $lat, $userId, $radiusKm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $nearbyUsers = [];
        while ($row = $result->fetch_assoc()) {
            $nearbyUsers[] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'distance' => round($row['distance'], 1),
                'last_online' => $row['last_online']
            ];
        }
        
        $stmt->close();
        return $nearbyUsers;
    }
    
    return [];
}

function getUserLocation($userId) {
    global $link;
    
    $sql = "SELECT latitude, longitude FROM users WHERE id = ?";
    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
    }
    
    return null;
}

function getNewInteractions($userId) {
    global $link;
    
    // Get new likes, messages, or match notifications
    $sql = "SELECT i.*, u.username, u.avatar_url 
            FROM interactions i 
            JOIN users u ON i.from_user_id = u.id 
            WHERE i.to_user_id = ? 
            AND i.created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            AND i.seen = 0
            ORDER BY i.created_at DESC";
    
    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $interactions = [];
        while ($row = $result->fetch_assoc()) {
            $interactions[] = [
                'id' => $row['id'],
                'type' => $row['interaction_type'],
                'from_user' => $row['username'],
                'from_avatar' => $row['avatar_url'],
                'message' => $row['message'],
                'created_at' => $row['created_at']
            ];
        }
        
        $stmt->close();
        return $interactions;
    }
    
    return [];
}
?>
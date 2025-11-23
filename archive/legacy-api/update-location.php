<?php
/*
    API endpoint for updating user location
    Handles GPS coordinates, venue check-ins, and location-based matching
*/

header('Content-Type: application/json');

try {
    require_once('../_init.php');
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

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit();
    }

    $userId = getUserIdByEmail($_SESSION['email']);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    $profileManager = new ProfileManager($pdo);

    // Get and validate location data
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $venueId = intval($_POST['venue_id'] ?? 0);
    $venueName = trim($_POST['venue_name'] ?? '');
    $checkInType = $_POST['check_in_type'] ?? 'manual'; // manual, gps, venue

    // Validate coordinates
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
        exit();
    }

    // Prepare location data
    $locationData = [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'location_updated_at' => date('Y-m-d H:i:s'),
        'last_location_type' => $checkInType
    ];

    // Add venue information if provided
    if ($venueId > 0) {
        $locationData['current_venue_id'] = $venueId;
    }
    if (!empty($venueName)) {
        $locationData['current_venue_name'] = $venueName;
    }

    // Update user location
    if ($profileManager->saveProfile($userId, $locationData)) {
        
        // Log the location update
        $securityManager->logAction('location_update', $userId, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'venue_id' => $venueId,
            'venue_name' => $venueName,
            'check_in_type' => $checkInType
        ]);

        // Get nearby users for potential matches
        $nearbyUsers = getNearbyUsers($pdo, $userId, $latitude, $longitude, 50); // 50km radius

        echo json_encode([
            'success' => true,
            'message' => 'Location updated successfully',
            'nearby_users' => count($nearbyUsers),
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'venue_name' => $venueName
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update location']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Get nearby users for location-based matching
 */
function getNearbyUsers($pdo, $userId, $latitude, $longitude, $radiusKm = 50) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.age,
            u.gender,
            u.latitude,
            u.longitude,
            u.avatar_url,
            u.last_online,
            (
                $earthRadius * acos(
                    cos(radians(?)) * 
                    cos(radians(u.latitude)) * 
                    cos(radians(u.longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(u.latitude))
                )
            ) AS distance_km
        FROM users u
        WHERE u.id != ?
        AND u.latitude IS NOT NULL 
        AND u.longitude IS NOT NULL
        AND u.active = 1
        AND u.last_online > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        HAVING distance_km <= ?
        ORDER BY distance_km ASC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$latitude, $longitude, $latitude, $userId, $radiusKm]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php
/*
    API endpoint for venue check-ins and presence announcements
    Handles venue discovery, check-ins, and presence announcements
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

    $action = $_POST['action'] ?? 'checkin';
    $venueId = intval($_POST['venue_id'] ?? 0);
    $venueName = trim($_POST['venue_name'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $announcement = trim($_POST['announcement'] ?? '');
    $duration = intval($_POST['duration'] ?? 4); // hours

    switch ($action) {
        case 'checkin':
            $result = handleVenueCheckin($pdo, $userId, $venueId, $venueName, $latitude, $longitude, $announcement, $duration);
            break;
            
        case 'checkout':
            $result = handleVenueCheckout($pdo, $userId, $venueId);
            break;
            
        case 'nearby':
            $result = getNearbyVenues($pdo, $latitude, $longitude);
            break;
            
        case 'announce':
            $result = handlePresenceAnnouncement($pdo, $userId, $venueId, $announcement, $duration);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit();
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Handle venue check-in
 */
function handleVenueCheckin($pdo, $userId, $venueId, $venueName, $latitude, $longitude, $announcement, $duration) {
    try {
        $pdo->beginTransaction();

        // Create or update venue
        if ($venueId > 0) {
            $venue = getVenueById($pdo, $venueId);
        } else {
            $venue = createOrFindVenue($pdo, $venueName, $latitude, $longitude);
            $venueId = $venue['id'];
        }

        // Check if user is already checked in somewhere else
        $stmt = $pdo->prepare("SELECT venue_id FROM user_venue_checkins WHERE user_id = ? AND checked_out_at IS NULL");
        $stmt->execute([$userId]);
        $currentCheckin = $stmt->fetch();

        if ($currentCheckin && $currentCheckin['venue_id'] != $venueId) {
            // Check out from previous venue
            $stmt = $pdo->prepare("UPDATE user_venue_checkins SET checked_out_at = NOW() WHERE user_id = ? AND checked_out_at IS NULL");
            $stmt->execute([$userId]);
        }

        // Create new check-in
        $stmt = $pdo->prepare("
            INSERT INTO user_venue_checkins (user_id, venue_id, latitude, longitude, announcement, duration_hours, checked_in_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $venueId, $latitude, $longitude, $announcement, $duration]);

        // Update user's current location
        $profileManager = new ProfileManager($pdo);
        $profileManager->saveProfile($userId, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current_venue_id' => $venueId,
            'current_venue_name' => $venue['name'],
            'location_updated_at' => date('Y-m-d H:i:s')
        ]);

        // Log the action
        $securityManager = new SecurityManager($pdo);
        $securityManager->logAction('venue_checkin', $userId, [
            'venue_id' => $venueId,
            'venue_name' => $venue['name'],
            'announcement' => $announcement,
            'duration' => $duration
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Checked in successfully',
            'venue' => $venue,
            'checkin_id' => $pdo->lastInsertId()
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle venue check-out
 */
function handleVenueCheckout($pdo, $userId, $venueId) {
    $stmt = $pdo->prepare("
        UPDATE user_venue_checkins 
        SET checked_out_at = NOW() 
        WHERE user_id = ? AND venue_id = ? AND checked_out_at IS NULL
    ");
    $stmt->execute([$userId, $venueId]);

    // Update user's current location
    $profileManager = new ProfileManager($pdo);
    $profileManager->saveProfile($userId, [
        'current_venue_id' => null,
        'current_venue_name' => null,
        'location_updated_at' => date('Y-m-d H:i:s')
    ]);

    return [
        'success' => true,
        'message' => 'Checked out successfully'
    ];
}

/**
 * Get nearby venues
 */
function getNearbyVenues($pdo, $latitude, $longitude, $radiusKm = 10) {
    $earthRadius = 6371;
    
    $sql = "
        SELECT 
            v.*,
            (
                $earthRadius * acos(
                    cos(radians(?)) * 
                    cos(radians(v.latitude)) * 
                    cos(radians(v.longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(v.latitude))
                )
            ) AS distance_km,
            COUNT(uvc.id) as active_users
        FROM venues v
        LEFT JOIN user_venue_checkins uvc ON v.id = uvc.venue_id AND uvc.checked_out_at IS NULL
        WHERE v.latitude IS NOT NULL 
        AND v.longitude IS NOT NULL
        AND v.active = 1
        HAVING distance_km <= ?
        GROUP BY v.id
        ORDER BY distance_km ASC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$latitude, $longitude, $latitude, $radiusKm]);
    
    return [
        'success' => true,
        'venues' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

/**
 * Handle presence announcement
 */
function handlePresenceAnnouncement($pdo, $userId, $venueId, $announcement, $duration) {
    $stmt = $pdo->prepare("
        UPDATE user_venue_checkins 
        SET announcement = ?, duration_hours = ?, updated_at = NOW()
        WHERE user_id = ? AND venue_id = ? AND checked_out_at IS NULL
    ");
    $stmt->execute([$announcement, $duration, $userId, $venueId]);

    return [
        'success' => true,
        'message' => 'Presence announcement updated'
    ];
}

/**
 * Create or find venue
 */
function createOrFindVenue($pdo, $name, $latitude, $longitude) {
    // First try to find existing venue within 100m
    $stmt = $pdo->prepare("
        SELECT * FROM venues 
        WHERE name = ? 
        AND latitude BETWEEN ? - 0.001 AND ? + 0.001
        AND longitude BETWEEN ? - 0.001 AND ? + 0.001
        LIMIT 1
    ");
    $stmt->execute([$name, $latitude, $latitude, $longitude, $longitude]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($venue) {
        return $venue;
    }

    // Create new venue
    $stmt = $pdo->prepare("
        INSERT INTO venues (name, latitude, longitude, created_at, active)
        VALUES (?, ?, ?, NOW(), 1)
    ");
    $stmt->execute([$name, $latitude, $longitude]);
    
    $venueId = $pdo->lastInsertId();
    
    return [
        'id' => $venueId,
        'name' => $name,
        'latitude' => $latitude,
        'longitude' => $longitude
    ];
}

/**
 * Get venue by ID
 */
function getVenueById($pdo, $venueId) {
    $stmt = $pdo->prepare("SELECT * FROM venues WHERE id = ?");
    $stmt->execute([$venueId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

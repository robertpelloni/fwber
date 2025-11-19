<?php
/*
    API endpoint for creating venue events
    Handles event creation and management
*/

header('Content-Type: application/json');

try {
    require_once('../_init.php');

    // Simple venue authentication (in production, use proper venue authentication)
    $venueId = $_POST['venue_id'] ?? 0;
    $venueToken = $_GET['token'] ?? 'demo_token';

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit();
    }

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit();
    }

    // Validate venue ID
    if (!$venueId || $venueId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid venue ID']);
        exit();
    }

    // Check if venue exists
    $stmt = $pdo->prepare("SELECT id FROM venues WHERE id = ?");
    $stmt->execute([$venueId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Venue not found']);
        exit();
    }

    // Validate required fields
    $requiredFields = ['name', 'start_date'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
            exit();
        }
    }

    // Prepare event data
    $eventData = [
        'venue_id' => $venueId,
        'name' => trim($_POST['name']),
        'description' => trim($_POST['description'] ?? ''),
        'event_type' => $_POST['event_type'] ?? 'other',
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'] ?? null,
        'cover_charge' => floatval($_POST['cover_charge'] ?? 0),
        'age_restriction' => $_POST['age_restriction'] ?? '18+',
        'dress_code' => trim($_POST['dress_code'] ?? ''),
        'capacity' => intval($_POST['capacity'] ?? 0),
        'expected_attendance' => intval($_POST['expected_attendance'] ?? 0),
        'special_requirements' => trim($_POST['special_requirements'] ?? ''),
        'active' => 1
    ];

    // Validate event type
    $validEventTypes = ['party', 'concert', 'festival', 'meetup', 'special', 'other'];
    if (!in_array($eventData['event_type'], $validEventTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid event type']);
        exit();
    }

    // Validate age restriction
    $validAgeRestrictions = ['18+', '21+', 'all_ages'];
    if (!in_array($eventData['age_restriction'], $validAgeRestrictions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid age restriction']);
        exit();
    }

    // Validate dates
    $startDate = new DateTime($eventData['start_date']);
    if ($startDate < new DateTime()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Start date must be in the future']);
        exit();
    }

    if ($eventData['end_date']) {
        $endDate = new DateTime($eventData['end_date']);
        if ($endDate <= $startDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'End date must be after start date']);
            exit();
        }
    }

    // Insert event
    $sql = "
        INSERT INTO venue_events (
            venue_id, name, description, event_type, start_date, end_date,
            cover_charge, age_restriction, dress_code, capacity, expected_attendance,
            special_requirements, active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ";

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        $eventData['venue_id'],
        $eventData['name'],
        $eventData['description'],
        $eventData['event_type'],
        $eventData['start_date'],
        $eventData['end_date'],
        $eventData['cover_charge'],
        $eventData['age_restriction'],
        $eventData['dress_code'],
        $eventData['capacity'],
        $eventData['expected_attendance'],
        $eventData['special_requirements'],
        $eventData['active']
    ]);

    if ($success) {
        $eventId = $pdo->lastInsertId();

        // Log the event creation
        $securityManager->logAction('event_created', null, [
            'venue_id' => $venueId,
            'event_id' => $eventId,
            'event_name' => $eventData['name'],
            'event_type' => $eventData['event_type']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Event created successfully',
            'event_id' => $eventId,
            'event' => [
                'id' => $eventId,
                'name' => $eventData['name'],
                'event_type' => $eventData['event_type'],
                'start_date' => $eventData['start_date'],
                'end_date' => $eventData['end_date']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create event']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

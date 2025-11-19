<?php
/*
    API endpoint for venue settings management
    Handles venue profile updates and configuration
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

    // Prepare update data
    $updateData = [];
    $allowedFields = [
        'name', 'description', 'venue_type', 'capacity', 'age_restriction',
        'dress_code', 'cover_charge', 'website', 'phone', 'email'
    ];

    foreach ($allowedFields as $field) {
        if (isset($_POST[$field])) {
            $updateData[$field] = trim($_POST[$field]);
        }
    }

    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No data to update']);
        exit();
    }

    // Build update query
    $setParts = [];
    $values = [];
    
    foreach ($updateData as $field => $value) {
        $setParts[] = "`$field` = ?";
        $values[] = $value;
    }
    
    $values[] = $venueId; // For WHERE clause

    $sql = "UPDATE venues SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($values);

    if ($success) {
        // Log the update
        $securityManager->logAction('venue_settings_update', null, [
            'venue_id' => $venueId,
            'updated_fields' => array_keys($updateData)
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Venue settings updated successfully',
            'updated_fields' => array_keys($updateData)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update venue settings']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

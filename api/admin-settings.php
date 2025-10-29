<?php
/*
    API endpoint for admin platform settings
    Handles platform configuration and system settings
*/

header('Content-Type: application/json');

try {
    require_once('../_init.php');

    // Check admin authentication
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
        exit();
    }

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

    // Check admin role permissions
    $adminRole = $_SESSION['admin_role'] ?? 'moderator';
    if ($adminRole === 'moderator') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit();
    }

    // Prepare settings data
    $settingsData = [];
    $allowedSettings = [
        'site_name', 'site_description', 'max_users', 'avatar_cost',
        'moderation_level', 'auto_moderation', 'email_notifications',
        'sms_notifications', 'push_notifications', 'maintenance_mode',
        'registration_enabled', 'guest_access', 'api_rate_limit',
        'session_timeout', 'password_policy', 'content_filtering'
    ];

    foreach ($allowedSettings as $setting) {
        if (isset($_POST[$setting])) {
            $value = $_POST[$setting];
            
            // Validate specific settings
            switch ($setting) {
                case 'max_users':
                    $value = max(0, intval($value));
                    break;
                case 'avatar_cost':
                    $value = max(0, floatval($value));
                    break;
                case 'moderation_level':
                    if (!in_array($value, ['strict', 'moderate', 'lenient'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Invalid moderation level']);
                        exit();
                    }
                    break;
                case 'auto_moderation':
                case 'email_notifications':
                case 'sms_notifications':
                case 'push_notifications':
                case 'maintenance_mode':
                case 'registration_enabled':
                case 'guest_access':
                case 'content_filtering':
                    $value = $value ? 1 : 0;
                    break;
                case 'api_rate_limit':
                    $value = max(1, intval($value));
                    break;
                case 'session_timeout':
                    $value = max(30, intval($value)); // Minimum 30 minutes
                    break;
            }
            
            $settingsData[$setting] = $value;
        }
    }

    if (empty($settingsData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No settings to update']);
        exit();
    }

    // Update settings in database
    $stmt = $pdo->prepare("
        INSERT INTO platform_settings (setting_key, setting_value, updated_by, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_by = VALUES(updated_by),
            updated_at = VALUES(updated_at)
    ");

    $success = true;
    foreach ($settingsData as $key => $value) {
        $result = $stmt->execute([$key, $value, $_SESSION['admin_username']]);
        if (!$result) {
            $success = false;
            break;
        }
    }

    if ($success) {
        // Log the settings update
        $securityManager->logAction('admin_settings_update', null, [
            'admin' => $_SESSION['admin_username'],
            'updated_settings' => array_keys($settingsData)
        ]);

        // If maintenance mode was enabled, log it
        if (isset($settingsData['maintenance_mode']) && $settingsData['maintenance_mode']) {
            $securityManager->logAction('maintenance_mode_enabled', null, [
                'admin' => $_SESSION['admin_username']
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Platform settings updated successfully',
            'updated_settings' => array_keys($settingsData)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update platform settings']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

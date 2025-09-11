<?php
/*
    API endpoint for match actions (interested, pass, etc.)
    Handles user interactions with potential matches
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

    $userId = getUserIdByEmail($_SESSION['email']);
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    $targetUserId = $_POST['user_id'] ?? '';

    if (empty($action) || empty($targetUserId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action and user_id required']);
        exit();
    }

    // Validate action
    $validActions = ['interested', 'pass', 'block', 'askprivate', 'authorizeprivate', 'notmytype'];
    if (!in_array($action, $validActions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit();
    }

    // Record the interaction
    $stmt = $pdo->prepare("
        INSERT INTO user_interactions (user_id, target_user_id, interaction_type) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE interaction_type = VALUES(interaction_type)
    ");
    $stmt->execute([$userId, $targetUserId, $action]);

    // Check for mutual interest (match)
    $mutualMatch = false;
    $matchData = null;
    
    if ($action === 'interested') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_interactions WHERE user_id = ? AND target_user_id = ? AND interaction_type = 'interested'");
        $stmt->execute([$targetUserId, $userId]);
        
        if ($stmt->fetchColumn() > 0) {
            $mutualMatch = true;
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO matches (user1_id, user2_id) VALUES (LEAST(?, ?), GREATEST(?, ?))");
            $stmt->execute([$userId, $targetUserId]);
            
            $stmt = $pdo->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $matchData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            sendMatchNotifications($userId, $targetUserId);
        }
    }

    // Update user behavior tracking for AI improvements
    updateUserBehavior($userId, $action, $targetUserId);

    $response = [
        'success' => true,
        'action' => $action,
        'mutual_match' => $mutualMatch
    ];
    
    if ($mutualMatch && $matchData) {
        $response['match_data'] = $matchData;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

function sendMatchNotifications($userId1, $userId2) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE id IN (?, ?) AND email_verified = 1");
        $stmt->execute([$userId1, $userId2]);
        $users = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        if (empty($users)) return;

        foreach ($users as $userId => $userData) {
            $otherUserId = ($userId == $userId1) ? $userId2 : $userId1;
            $otherUser = $users[$otherUserId] ?? ['username' => 'Your new match'];

            $subject = "It's a Match! You and {$otherUser['username']} are interested in each other.";
            $message = "You have a new mutual match with {$otherUser['username']}! Check your matches page to start connecting.";
            
            if (function_exists('doMail')) {
                doMail($userData['email'], $subject, $message, $message);
            }
        }
    } catch (Exception $e) {
        error_log("Failed to send match notifications: " . $e->getMessage());
    }
}

function updateUserBehavior($userId, $action, $targetUserId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_actions (user_id, action_type, target_user_id, details)
            VALUES (?, ?, ?, ?)
        ");
        
        $details = json_encode(['timestamp' => time()]);
        $stmt->execute([$userId, $action, $targetUserId, $details]);
        
    } catch (Exception $e) {
        error_log("Failed to update user behavior: " . $e->getMessage());
    }
}
?>
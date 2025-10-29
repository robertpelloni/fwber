<?php
header('Content-Type: application/json');

require_once('../_init.php');
require_once('../PhotoManager.php');

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

$photoId = $_POST['photo_id'] ?? null;
if (!$photoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Photo ID is required.']);
    exit();
}

$photoManager = new PhotoManager($pdo);
$result = $photoManager->deletePhoto($userId, $photoId);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete photo.']);
}

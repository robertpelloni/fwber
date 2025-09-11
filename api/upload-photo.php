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

$photoManager = new PhotoManager($pdo);
$isPrivate = isset($_POST['is_private']) && $_POST['is_private'] == '1';

$result = $photoManager->uploadPhoto($userId, $_FILES['photo'], $isPrivate);

echo json_encode($result);

<?php
require_once('_init.php');
require_once('ProfileManager.php');

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

$profileManager = new ProfileManager($pdo);
$userId = getUserIdByEmail($_SESSION['email']);

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you would sanitize and validate this data.
    $profileData = $_POST;
    if ($profileManager->saveProfile($userId, $profileData)) {
        $message = 'Profile saved successfully!';
    } else {
        $message = 'There was an error saving your profile.';
    }
}

// Get the user's current profile data
$userProfile = $profileManager->getProfile($userId);

// Include the view
require('profile-form.php');

<?php
require_once('_init.php');

$token = $_GET['token'] ?? null;
$message = 'Invalid verification link.';

if ($token) {
    // Find the user with this token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email_verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // User found, verify their email
        $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // Log the user in
        $sessionToken = $securityManager->generateSessionToken($user['id']);
        $_SESSION['email'] = $user['email'];
        $_SESSION['token'] = $sessionToken;

        // Redirect to the profile editing page
        header('Location: edit-profile.php');
        exit();
    } else {
        $message = 'This verification link has already been used or is invalid.';
    }
} 

// If we reach here, something went wrong. Display an error.
?>
<!doctype html>
<html lang="en">
<head>
    <title>Email Verification - <?php echo getSiteName(); ?></title>
    <?php include("head.php");?>
</head>
<body class="d-flex flex-column h-100">
    <?php include("h.php");?>
    <div class="col-sm-12 my-auto text-center">
        <h1 class="h3 mb-3 font-weight-normal text-center">Email Verification</h1>
        <p><?php echo $message; ?></p>
        <p><a href="/signin.php">Click here to log in.</a></p>
    </div>
    <?php include("f.php");?>
</body>
</html>

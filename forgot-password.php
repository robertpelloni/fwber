<?php
require_once('_init.php');

$message = '';
$error = '';
$show_form = 'request'; // Can be 'request' or 'reset'

// Step 2: Handle the password reset form submission
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    $token = $_POST['token'] ?? '';
    $newPass = $_POST['newPass'] ?? '';
    $verifyPass = $_POST['verifyPass'] ?? '';

    try {
        if ($newPass !== $verifyPass) {
            throw new Exception("Passwords do not match.");
        }

        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch();

        if (!$resetRequest) {
            throw new Exception("Invalid or expired password reset link.");
        }

        $hashedData = $securityManager->hashPassword($newPass);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_salt = ? WHERE email = ?");
        $updateStmt->execute([$hashedData['hash'], $hashedData['salt'], $resetRequest['email']]);

        // Delete the reset token so it can't be used again
        $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteStmt->execute([$resetRequest['email']]);

        $message = "Your password has been reset successfully. You can now log in.";
        $show_form = 'request'; // Show the request form again after success

    } catch (Exception $e) {
        $error = $e->getMessage();
        $show_form = 'reset'; // Show the reset form again on error
    }
}
// Step 1: Handle the reset request form submission
else if (isset($_POST['action']) && $_POST['action'] === 'request') {
    $email = $_POST['email'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));

            // Store the token in the database
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->execute([$email, $token]);

            // Send the password reset email
            require_once('_emailFunctions.php');
            sendPasswordResetLink($email, $token);
        }

        $message = "If an account with that email exists, a password reset link has been sent.";

    } catch (Exception $e) {
        $error = "An error occurred. Please try again later.";
        error_log($e->getMessage());
    }
}
// Display the reset form if a valid token is in the URL
else if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$token]);
    if ($stmt->fetch()) {
        $show_form = 'reset';
    } else {
        $error = "Invalid or expired password reset link.";
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <title>Forgot Password - <?php echo getSiteName(); ?></title>
	<?php include("head.php");?>
</head>
<body class="d-flex flex-column h-100">
	<?php include("h.php");?>
	<div class="col-sm-12 my-auto text-center">
        <br><br><br><br>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($show_form === 'request'): ?>
            <div class="card mb-5 shadow-sm p-5" style="display:inline-block; width: 500px;">
                <form action="forgot-password.php" method="POST">
                    <input type="hidden" name="action" value="request">
                    <h1 class="h3 mb-3 font-weight-normal text-center">Forgot Password</h1>
                    <p>Enter your email address and we will send you a link to reset your password.</p>
                    <input type="email" name="email" class="form-control" placeholder="Email address" required autofocus><br>
                    <button class="btn btn-lg btn-primary btn-block" type="submit">Send Reset Link</button>
                </form>
            </div>
        <?php elseif ($show_form === 'reset'): ?>
            <div class="card mb-5 shadow-sm p-5" style="display:inline-block; width: 500px;">
                <form action="forgot-password.php" method="POST">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <h1 class="h3 mb-3 font-weight-normal text-center">Reset Your Password</h1>
                    <input type="password" name="newPass" class="form-control" placeholder="New Password" required minlength="8"><br>
                    <input type="password" name="verifyPass" class="form-control" placeholder="Verify New Password" required><br>
                    <button class="btn btn-lg btn-primary btn-block" type="submit">Reset Password</button>
                </form>
            </div>
        <?php endif; ?>
	</div>
    <?php include("f.php");?>
</body>
</html>

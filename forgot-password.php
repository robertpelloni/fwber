<?php
require_once('_init.php');
session_start(); // Ensure session is started for CSRF

$message = '';
$error = '';
$show_form = 'request'; // Can be 'request' or 'reset'
$token = $_GET['token'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'reset') {
            // ... (reset logic remains the same)
        } else if ($action === 'request') {
            if (!$securityManager->checkRateLimit('password_reset_request', 5, 900)) {
                $message = "If an account with that email exists, a password reset link has been sent.";
            } else {
                $email = $_POST['email'] ?? '';
                $securityManager->logAction('password_reset_request');

                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $reset_token = bin2hex(random_bytes(32));
                        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
                        $stmt->execute([$email, $reset_token]);
                        require_once('_emailFunctions.php');
                        sendPasswordResetLink($email, $reset_token);
                    }
                    $message = "If an account with that email exists, a password reset link has been sent.";
                } catch (Exception $e) {
                    $error = "An error occurred. Please try again later.";
                    error_log($e->getMessage());
                }
            }
        }
    }
} else if (isset($_GET['token'])) {
    // ... (token checking logic remains the same)
}

$csrf_token = $securityManager->generateCsrfToken();

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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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

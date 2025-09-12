<?php
require_once('_init.php');
session_start(); // Ensure session is started for CSRF

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$securityManager->checkRateLimit('contact_form_submit', 5, 3600)) {
        $error = "You have submitted this form too many times. Please try again later.";
    } else if (!isset($_POST['csrf_token']) || !$securityManager->validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $subject = $_POST['subject'] ?? 'No Subject';
        $email = $_POST['email'] ?? 'Not Provided';
        $body = $_POST['message'] ?? '';

        if (empty($body)) {
            $error = "A message is required.";
        } else {
            require_once('_emailFunctions.php');
            if (sendContactEmail($subject, $body, $email)) {
                $message = "Thank you for contacting us. We will get back to you shortly.";
            } else {
                $error = "Sorry, there was an error sending your message. Please try again later.";
            }
            $securityManager->logAction('contact_form_submit');
        }
    }
}

$csrf_token = $securityManager->generateCsrfToken();

?>
<!doctype html>
<html lang="en">
<head>
    <title>Contact Us - <?php echo getSiteName(); ?></title>
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

        <?php if (!$message): // Hide form on success ?>
        <form class="form-signin" action="contact.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <fieldset style="text-align:left;">
                <h1 class="h3 mb-3 font-weight-normal text-center">Contact Us</h1>
                <div style="font-size:12pt;">
                    <p>If something is not working, please let us know.</p>
                    <p>We welcome hearing suggestions or new categories we can add.</p>
                </div>
                <br>
                <select name="subject" id="subject" class="form-control mb-2">
                    <option value="help">Help</option>
                    <option value="suggestion">Suggestion</option>
                    <option value="bugReport">Bug Report</option>
                    <option value="other">Other</option>
                </select>
                <br>
                <input type="email" name="email" id="emailAddress" class="form-control" placeholder="Your email address (optional)"><br>
                <textarea name="message" id="message" class="form-control" cols="60" rows="10" placeholder="Your message" required></textarea><br>
                <br>
                <div class="text-center">
                    <button class="btn btn-lg btn-primary" type="submit">Send Message</button>
                </div>
            </fieldset>
        </form>
        <?php endif; ?>
    </div>

<?php include("f.php");?>
</body>
</html>

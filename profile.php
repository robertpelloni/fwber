<?php
require_once('_init.php');
require_once('ProfileManager.php');

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

// Redirect to edit profile if it's not done yet
if (!isProfileDone()) {
    header('Location: /edit-profile.php');
    exit();
}

$profileManager = new ProfileManager($pdo);
$userId = getUserIdByEmail($_SESSION['email']);
$userProfile = $profileManager->getProfile($userId);

?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo getSiteName(); ?> - Your Profile</title>
	<?php include("head.php");?>
</head>
<body class="d-flex flex-column h-100">
<?php include("h.php");?>
<div class="container">
    <br>
    <br>
    <br>
    <br>
    <div class="card p-5 m-2 shadow-sm" style="display:inline-block;">
        <a class="btn btn-outline-secondary my-0 px-3 mx-1" href="/managepics.php">Manage Pictures</a>
        <a class="btn btn-outline-secondary my-0 px-3 mx-1" href="/edit-profile.php">Edit Profile</a>
        <a class="btn btn-outline-secondary my-0 px-3 mx-1" href="/settings.php">Account Settings</a>
    </div>

    <div class="card p-5 m-2 shadow-sm">
        <h2>Your Profile Information</h2>
        <p>This is the information you have provided. It is used to find your matches.</p>
        
        <table class="table">
            <?php foreach ($userProfile as $key => $value): ?>
                <tr>
                    <th scope="row"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php include("f.php");?>
</body>
</html>

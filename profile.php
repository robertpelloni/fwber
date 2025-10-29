<?php
/*
    Copyright 2025 FWBer.me

    This file is part of FWBer.

    FWBer is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    FWBer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero Public License for more details.

    You should have received a copy of the GNU Affero Public License
    along with FWBer.  If not, see <https://www.gnu.org/licenses/>.
*/

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

<?php
/*
    $db = mysqli_connect($dburl,$dbuser,$dbpass);
    if(!$db)exit(mysqli_connect_error());

    $email = mysqli_escape_string($db,$_SESSION["email"]);

    $dbquerystring =
    "SELECT * FROM ".$dbname.".users WHERE email = '{$email}'";

    $dbquery = mysqli_query($db,$dbquerystring);

    if($dbquery)
    {
        $g = mysqli_fetch_array($dbquery);
        mysqli_free_result($dbquery);
    }
    mysqli_close($db);

    $g['age'] = getAge($g['birthdayMonth'],$g['birthdayDay'],$g['birthdayYear']);
    $g['calculatedDistance']=getDistanceBetweenPoints($lat,$lon,$g['lat'],$g['lon']);
    $g['commonDesires']="All";

    include("_getProfile.php");

    //print_r($g);
?>
		<br>
        <div class="card p-5 m-2 shadow-sm blueToWhite" style="display:inline-block; color:#222; text-shadow:#eee 1px 1px 0px;">
            Your Public Profile
            <div class="smallText" style="color:#222;font-size:11pt">
                This is how others see your profile at first.
            </div>

            <?php getProfile($g,"public"); ?>
        </div>
		<br>
		<br>
        <div class="card p-5 m-2 shadow-sm pinkToWhite" style="display:inline-block; color:#222; text-shadow:#eee 1px 1px 0px;">
			    Your Private Profile
				<div class="smallText" style="color:#222;font-size:11pt">
				    This is how your profile looks fully unlocked after you have agreed to match someone.
				</div>
                <?php getProfile($g,"private"); ?>
		</div>
        <br>
		<?php //TODO: show map here, highlight searched area ?>

</div>
<?php include("f.php");?>

<script src="/js/jquery-3.4.1.min.js" type="text/javascript"></script>
<script src="/js/jquery-ui-1.8.21.custom.min.js" type="text/javascript"></script>



*/?>
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

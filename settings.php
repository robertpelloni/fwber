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

    //session_start();
    //include("_debug.php");
    //include("_names.php");
    //include("_init.php");
    //include("_secrets.php");

require_once('_init.php');
session_start(); // Ensure session is started for CSRF

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

$userId = getUserIdByEmail($_SESSION['email']);
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'change_password':
                    $oldPass = $_POST['oldPass'] ?? '';
                    $newPass = $_POST['newPass'] ?? '';
                    $verifyPass = $_POST['verifyPass'] ?? '';

                    if ($newPass !== $verifyPass) {
                        throw new Exception("New passwords do not match.");
                    }

                    $stmt = $pdo->prepare("SELECT password_hash, password_salt FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!$user || !$securityManager->verifyPassword($oldPass, $user['password_hash'], $user['password_salt'])) {
                        throw new Exception("Incorrect old password.");
                    }

                    $hashedData = $securityManager->hashPassword($newPass);
                    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_salt = ? WHERE id = ?");
                    $updateStmt->execute([$hashedData['hash'], $hashedData['salt'], $userId]);
                    $message = "Password changed successfully.";
                    break;

                case 'change_email_settings':
                    $settings = [
                        'emailMatches' => isset($_POST['emailMatches']) ? 1 : 0,
                        'emailInterested' => isset($_POST['emailInterested']) ? 1 : 0,
                        'emailApproved' => isset($_POST['emailApproved']) ? 1 : 0,
                    ];

                    $stmt = $pdo->prepare("INSERT INTO user_privacy_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    foreach ($settings as $key => $value) {
                        $stmt->execute([$userId, $key, $value]);
                    }
                    $message = "Email settings updated.";
                    break;

                case 'delete_account':
                    $myPass = $_POST['myPass'] ?? '';
                    $goodbyeCheck = $_POST['goodbyeCheck'] ?? '';

                    if ($goodbyeCheck !== 'goodbye') {
                        throw new Exception('You must type \'goodbye\' to confirm.');
                    }

                    $stmt = $pdo->prepare("SELECT password_hash, password_salt FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!$user || !$securityManager->verifyPassword($myPass, $user['password_hash'], $user['password_salt'])) {
                        throw new Exception("Incorrect password.");
                    }

                    $updateStmt = $pdo->prepare("UPDATE users SET active = 0, email = CONCAT(email, '_deleted_', id) WHERE id = ?");
                    $updateStmt->execute([$userId]);

                    session_destroy();
                    setcookie("email", "", time() - 3600, '/');
                    setcookie("token", "", time() - 3600, '/');
                    header('Location: /?message=account_deleted');
                    exit();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch current settings to display in the form
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_privacy_settings WHERE user_id = ?");
$stmt->execute([$userId]);
$currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$emailMatches = $currentSettings['emailMatches'] ?? 1;
$emailInterested = $currentSettings['emailInterested'] ?? 1;
$emailApproved = $currentSettings['emailApproved'] ?? 1;

$csrf_token = $securityManager->generateCsrfToken();


?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo getSiteName(); ?> - Account Settings</title>
	<?php include("head.php");?>

<?php

	////first make sure we are a legit user.
	//validateSessionOrCookiesReturnLoggedIn();
	//
	//goHomeIfCookieNotSet();
	//
    //$db = mysqli_connect($dburl,$dbuser,$dbpass);
    //if(!$db)exit(mysqli_connect_error());
	//
    //$email = mysqli_escape_string($db,$_SESSION["email"]);
	//
	//$dbquerystring = sprintf("SELECT id, emailMatches, emailInterested, emailApproved FROM ".$dbname.".users WHERE email='%s'",$email);
	//$dbquery = mysqli_query($db,$dbquerystring);
	//$dbresults = mysqli_fetch_array($dbquery);
	//
	//$emailMatches=0;
	//$emailInterested=0;
	//$emailApproved=0;
	//
	//if($dbresults['emailMatches']==1)$emailMatches=1;
	//if($dbresults['emailInterested']==1)$emailInterested=1;
	//if($dbresults['emailApproved']==1)$emailApproved=1;
	//
	//mysqli_close($db);

?>

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

        <div class="card mb-5 shadow-sm p-5" style="display:inline-block;">
            <form action="settings.php" method="POST" id="changePasswordForm">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <fieldset style="text-align:center;border:none;">
                    <h1 class="h3 mb-3 font-weight-normal text-center">Change Password</h1>
                    <input type="password" name="oldPass" id="oldPass" style="margin:4px;" placeholder="Old Password" required><br>
                    <input type="password" name="newPass" id="newPass" style="margin:4px;" placeholder="New Password" required minlength="8"><br>
                    <input type="password" name="verifyPass" id="verifyPass" style="margin:4px;" placeholder="Verify New Password" required><br>
                    <br>
                    <input type="submit" class="btn btn-sm btn-primary my-0 px-3 mx-1" value="Save Changes">
                </fieldset>
            </form>
        </div>

        <br>

        <div class="card mb-5 shadow-sm p-5" style="display:inline-block;">
            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="change_email_settings">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <fieldset style="text-align:center;border:none;">
                    <h1 class="h3 mb-3 font-weight-normal text-center">Email Settings</h1>
                    <table style="width:100%;">
                        <tbody>
                        <tr>
                            <td style="text-align:left;">
                                <label class="checkbox text-left d-sm-inline-block mb-0"><input type="checkbox" name="emailMatches" style="margin:4px;" <?php if($emailMatches) echo 'checked'; ?>>Email me for new matches</label><br>
                                <label class="checkbox text-left d-sm-inline-block mb-0"><input type="checkbox" name="emailInterested" style="margin:4px;" <?php if($emailInterested) echo 'checked'; ?>>Email me when someone is interested</label><br>
                                <label class="checkbox text-left d-sm-inline-block mb-0"><input type="checkbox" name="emailApproved" style="margin:4px;" <?php if($emailApproved) echo 'checked'; ?>>Email me when a match is approved</label>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <input type="submit" class="btn btn-sm btn-primary my-0 px-3 mx-1" value="Save Settings">
                </fieldset>
            </form>
        </div>

        <br>

        <div class="card mb-5 shadow-sm p-5" style="display:inline-block;">
            <form action="settings.php" method="POST" id="deleteAccountForm">
                <input type="hidden" name="action" value="delete_account">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <fieldset style="text-align:center;border:none;">
                    <h1 class="h3 mb-3 font-weight-normal text-center">Delete Account</h1>
                    <p class="text-danger">This action is irreversible.</p>
                    <input type="password" name="myPass" id="myPass" style="margin:4px;" placeholder="Confirm Password" required><br>
                    <input type="text" name="goodbyeCheck" id="goodbyeCheck" style="margin:4px;" placeholder="Type 'goodbye' here" required><br>
                    <br>
                    <input type="submit" class="btn btn-sm btn-danger my-0 px-3 mx-1" value="Delete My Account Permanently">
                </fieldset>
            </form>
        </div>

	</div>
    <?php include("f.php");?>
<?php /*

    <script src="/js/jquery-3.4.1.min.js" type="text/javascript"></script>
    <script src="/js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>

    <script type="text/javascript">

        function changeEmailSettings()
        {
            var emailMatches = document.getElementById("emailMatches").checked;
            var emailInterested = document.getElementById("emailInterested").checked;
            var emailApproved = document.getElementById("emailApproved").checked;

            $.ajax(
                {
                    type: "GET",
                    url: "_emailSettings",
                    data: "emailMatches="+emailMatches+"&emailInterested="+emailInterested+"&emailApproved="+emailApproved,
                    dataType: "html",
                    success: function(text)
                    {
                        if(text=="done")
                        {
                            document.getElementById("emailSettingsStatus").innerHTML="Settings Changed!";
                            $(document.getElementById("emailSettingsStatus")).show();
                            setTimeout(function()
                            {
                                $(document.getElementById("emailSettingsStatus")).fadeOut('fast');
                            },10000);
                        }
                        else
                        {
                            document.getElementById("emailSettingsStatus").innerHTML="Something went wrong. Please try again later.<br>Error: " + text;
                            $(document.getElementById("emailSettingsStatus")).show();
                            setTimeout(function()
                            {
                                $(document.getElementById("emailSettingsStatus")).fadeOut('fast');
                            },10000);
                        }
                    }
                });
        }
    </script>

    <script src="/js/jquery-validation-1.19.1/dist/jquery.validate.min.js" type="text/javascript"></script>

    <script type="text/javascript">
        $.validator.setDefaults({});
        $().ready
        (
            function()
            {
                $("#changePasswordForm").validate
                ({
                    errorElement:"div",
                    rules:
                        {
                            oldPass:
                                {
                                    required:true
                                },
                            newPass:
                                {
                                    required:true,
                                    minlength:5
                                },
                            verifyPass:
                                {
                                    equalTo: "#newPass"
                                }
                        }
                    ,
                    messages:
                        {
                        }
                });
                // validate the comment form when it is submitted
                $("#changeEmailForm").validate
                ({
                    errorElement:"div",
                    rules:
                        {
                            newEmail:
                                {
                                    email:true,
                                    required:true
                                },
                            verifyEmail:
                                {
                                    equalTo: "#newEmail"
                                }
                        }
                    ,
                    messages:
                        {
                        }
                });

                $("#deleteAccountForm").validate
                ({
                    errorElement:"div",
                    rules:
                        {
                            myPass:
                                {
                                    required:true
                                },
                            myPassAgain:
                                {
                                    equalTo: "#myPassAgain"
                                },
                            goodbyeCheck:
                                {
                                    equalTo: "#goodbye"
                                }
                        }
                    ,
                    messages:
                        {
                        }
                });
            }
        );
    </script>
	*/?>

</body>
</html>

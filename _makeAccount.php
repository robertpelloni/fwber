<?php
/*
    Copyright 2020 FWBer.me

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
    require_once("_init.php");
    include("_names.php");
    include("_debug.php");

    if($_SERVER["REQUEST_METHOD"] != "POST") {header("Location: ".getSiteURL()."/join");exit();}

    if(
        (
            isset($_POST['nameEmail']) &&!empty($_POST['nameEmail'])
            &&isset($_POST['namePassword']) &&!empty($_POST['namePassword'])
            &&isset($_POST['nameVerify']) &&!empty($_POST['nameVerify'])
            &&isset($_POST['nameAgreeLegalAge']) &&!empty($_POST['nameAgreeLegalAge'])
            &&isset($_POST['nameAgreeTOS']) &&!empty($_POST['nameAgreeTOS'])
        )==false
    )
    {
        header("Location: ".getSiteURL()."/join");
        exit();
    }

    $email = $_POST['nameEmail'];
    $password = $_POST['namePassword'];
    $verify = $_POST['nameVerify'];

    if(
            strlen($password)<8 || // Increased min length to 8 for better security
            strcmp($password,$verify)!=0 ||
            $_POST['nameAgreeLegalAge']!=true ||
            $_POST['nameAgreeTOS']!=true ||
            !filter_var($email,FILTER_VALIDATE_EMAIL)
    )
    {
        header("Location: ".getSiteURL()."/join");
        exit();
    }

    if(isset($_POST['g-recaptcha-response'])){
        $captcha=$_POST['g-recaptcha-response'];
    }
    if(!$captcha)
    {
        header("Location: ".getSiteURL()."/join");
        exit();
    }

    // Note: $reCaptchaSecretKey needs to be defined in _secrets.php
    include('_secrets.php');
    $ip = $_SERVER['REMOTE_ADDR'];
    // post request to server
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($reCaptchaSecretKey) .  '&response=' . urlencode($captcha);
    $response = file_get_contents($url);
    $responseKeys = json_decode($response,true);
    // should return JSON with success as true
    if($responseKeys["success"]==false)
    {
        header("Location: ".getSiteURL()."/join");
        exit();
    }

    // Check if the email is already registered.
    $stmt = $pdo->prepare("SELECT email, email_verification_token FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $dbresult = $stmt->fetch();

    $emailExists = false;
    if ($dbresult) {
        $emailExists = true;
    }
?>
<!doctype html>
<html lang="en">
<head>
	<title><?php require_once("_names.php"); echo getSiteName(); ?> - New Account Confirmation<?php require_once("_names.php"); echo getTitleTagline(); ?></title>
	<?php include("head.php");?>
</head>
<body class="d-flex flex-column h-100">
    <?php include("h.php");?>
    <div class="col-sm-12 my-auto text-center">
            <div style="color:#0096ff;font-size:14pt;text-shadow:#aad 1px 1px 1px;">
                Your account has been created.<br><br>
                <div style="color:#000;font-size:12pt;text-shadow:#aaa 1px 1px 1px;">
                    Please check your email for a verification link.<br>
                    If it is in the spam folder please mark it as not spam.<br>
                    <br><br>
                </div>
            </div>
<?php

    if($emailExists==false)
    {
        $verification_token = bin2hex(random_bytes(32));
        
        // Use the SecurityManager to hash the password
        $hashedData = $securityManager->hashPassword($password);
        $password_hash = $hashedData['hash'];
        $password_salt = $hashedData['salt'];

        // Generate a temporary username from the email
        $username = strstr($email, '@', true);

        $insertStmt = $pdo->prepare(
            "INSERT INTO users (email, username, password_hash, password_salt, email_verification_token) VALUES (?, ?, ?, ?, ?)"
        );
        $insertStmt->execute([$email, $username, $password_hash, $password_salt, $verification_token]);
    }
    else
    {
        $verification_token = $dbresult['email_verification_token'];
    }

    // Note: _emailFunctions.php and sendJoinVerificationEmail might need refactoring as well.
    include("_emailFunctions.php");
    sendJoinVerificationEmail($email, $verification_token, $emailExists);

?>
</div>
<?php include("f.php");?>
</body>
</html>

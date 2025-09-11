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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Use Composer's autoloader
require_once 'vendor/autoload.php';

    //=========================================================================================
    function doMail($mailToAddress, $mailSubject, $mailHTMLBody, $mailTextBody)
    {//=========================================================================================

        include('_secrets.php'); // Contains mail server settings ($mailHost, $mailPort, etc.)

        $mail = new PHPMailer(true); // Passing `true` enables exceptions

        try {
            //Server settings
            $mail->SMTPDebug = $mailSMTPDebug ?? 0; // Enable verbose debug output
            $mail->isSMTP();
            $mail->Host       = $mailHost;
            $mail->SMTPAuth   = $mailSMTPAuth ?? true;
            $mail->Username   = $mailUsername;
            $mail->Password   = $mailPassword;
            $mail->SMTPSecure = $mailSMTPSecurityType ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $mailPort;

            //Recipients
            $mail->setFrom(getEmailAddress(), getSiteName());
            $mail->addAddress($mailToAddress);
            $mail->addReplyTo(getEmailAddress(), getSiteName());

            // Content
            $mail->isHTML(true);
            $mail->Subject = $mailSubject;
            $mail->Body    = $mailHTMLBody;
            $mail->AltBody = $mailTextBody;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

	//=========================================================================================
	function sendContactEmail($subject, $message, $theirEmail)
	{//=========================================================================================

		$mailSubject = getSiteName() . " Contact Form: " . $subject;

        $mailHTMLBody = "";
		if($theirEmail!=""){
            $mailHTMLBody="<b>From:</b> ".$theirEmail."<br><br>";
        }

        $mailHTMLBody .= "<b>Subject:</b> ".$subject."<br><b>Message:</b><br>".nl2br($message);
		$mailTextBody = "From: ".$theirEmail."\n\nMessage:\n".$message;

        return doMail(getEmailAddress(), $mailSubject, $mailHTMLBody, $mailTextBody);
	}

	//=========================================================================================
	function sendJoinVerificationEmail($theirEmail, $verificationToken, $emailExists)
	{//=========================================================================================

		if($emailExists==false)
		{
			//send verification email
			$mailSubject = "Verify your new " . getSiteName() . " account";
            $verificationLink = getSiteURL()."/verify-email.php?token=".$verificationToken;
			$mailHTMLBody=
			"
			Your email address was recently signed up for an account at ".getSiteDomain().".<br>
			<br>
			Click on or paste this link into your browser to verify your account and sign in:<br>
			<a href='".$verificationLink."'>".$verificationLink."</a>
			";
			$mailTextBody = "To verify your account, please copy and paste this link into your browser: \n\n ".$verificationLink;
		}
		else
		{
			//send warning email
			$mailSubject = "You already have a " . getSiteName() . " account";
			$mailHTMLBody=
			"
			Your email address was recently used to sign up for an account at ".getSiteDomain().", but it is already in our system.<br>
			If this wasn't you, just ignore this email.<br>
			<br>
			If you can't remember your password, go here instead:<br>
			<a href='".getSiteURL()."/forgot-password.php'>".getSiteURL()."/forgot-password.php</a>
			";
			$mailTextBody = "Your email address was recently used to sign up for an account at ".getSiteDomain().", but it is already in our system. If this wasn't you, just ignore this email. If you forgot your password, visit ".getSiteURL()."/forgot-password.php";
		}

        return doMail($theirEmail, $mailSubject, $mailHTMLBody, $mailTextBody);
	}

    //=========================================================================================
    function sendPasswordResetLink($email, $token)
    {//=========================================================================================
        $mailSubject = "Password Reset Request for " . getSiteName();
        $resetLink = getSiteURL() . "/forgot-password.php?token=" . $token;

        $mailHTMLBody = "
            <p>Someone has requested a password reset for your account at ".getSiteName().".</p>
            <p>If this was you, click the link below to reset your password. This link is valid for one hour.</p>
            <p><a href='".$resetLink."'>".$resetLink."</a></p>
            <p>If you did not request a password reset, please ignore this email.</p>
        ";

        $mailTextBody = "To reset your password, please copy and paste this link into your browser: \n\n" . $resetLink;

        return doMail($email, $mailSubject, $mailHTMLBody, $mailTextBody);
    }

    // NOTE: The other email functions (sendMatchNoticeEmail, etc.) 
    // should also be reviewed and updated to match the new system as you build out those features.


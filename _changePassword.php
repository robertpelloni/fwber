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


 * Secure Password Change Handler for FWBer.me
 * Phase 1C: Fix SQL injection vulnerability in _changePassword.php
 * 
 * This file replaces the vulnerable _changePassword.php with secure PDO implementation
 * and modern password hashing using SecurityManager.
 */

session_start();


include("_init.php");
include("_debug.php");
include("_names.php");

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: ' . getSiteURL());
    exit();
}

include("_profileVars.php");

include("_secrets.php");

include("_globals.php");
include("_emailFunctions.php");

// Full authentication required for password changes
if (validateSessionOrCookiesReturnLoggedIn() == false) {
    header('Location: ' . getSiteURL());
    return;
}


goHomeIfCookieNotSet();

try {
    // Get PDO connection from _init.php
    global $pdo;
    if (!$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Validate input parameters
    if (!isset($_POST['oldPass']) || empty($_POST['oldPass'])) {
        exit('oldPass');
    }
    if (!isset($_POST['newPass']) || empty($_POST['newPass'])) {
        exit('newPass');
    }
    if (!isset($_POST['verifyPass']) || empty($_POST['verifyPass'])) {
        exit('verifyPass');
    }
    
    $oldPass = $_POST['oldPass'];
    $newPass = $_POST['newPass'];
    $verifyPass = $_POST['verifyPass'];
    
    // Validate password match
    if ($newPass !== $verifyPass) {
        exit("Passwords don't match");
    }
    
    // Validate password strength
    if (strlen($newPass) < 8) {
        exit("New password must be at least 8 characters long");
    }
    
    // Get user email from session
    if (!isset($_SESSION["email"])) {
        exit("Session expired");
    }
    $email = $_SESSION["email"];
    
    // SECURE: Use prepared statement to get user data
    $stmt = $pdo->prepare("SELECT id, passwordHash, dateJoined, dateLastSignedIn FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        exit("User not found");
    }
    
    $message = "";
    
    // Verify old password using SecurityManager
    $securityManager = new SecurityManager();
    
    // Check if old password is correct
    if (!$securityManager->verifyPassword($oldPass, $user['passwordHash'])) {
        $message = "Old password was wrong.";
    }
    
    if ($message == "") {
        // SECURE: Hash new password using SecurityManager (Argon2ID)
        $newPasswordHash = $securityManager->hashPassword($newPass);
        
        // SECURE: Use prepared statement to update password
        $updateStmt = $pdo->prepare("UPDATE users SET passwordHash = ?, updated_at = NOW() WHERE email = ?");
        $result = $updateStmt->execute([$newPasswordHash, $email]);
        
        if ($result) {
            $message = "Password changed successfully.";
            
            // Log the password change for security
            error_log("Password changed for user: " . $email . " at " . date('Y-m-d H:i:s'));
            
            // Optional: Send email notification
            try {
                $subject = "Password Changed - FWBer.me";
                $htmlBody = "
                    <h2>Password Changed Successfully</h2>
                    <p>Your password has been changed successfully.</p>
                    <p>If you did not make this change, please contact support immediately.</p>
                    <p>Time: " . date('Y-m-d H:i:s') . "</p>
                ";
                $textBody = "Your password has been changed successfully. If you did not make this change, please contact support immediately.";
                
                doMail($email, $subject, $htmlBody, $textBody);
            } catch (Exception $e) {
                // Email sending failed, but password change succeeded
                error_log("Failed to send password change email: " . $e->getMessage());
            }
        } else {
            $message = "Failed to update password. Please try again.";
        }
    }
    
    // Return success/error message
    echo $message;
    
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    exit("An error occurred. Please try again.");
}
?>

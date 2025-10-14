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

/**
 * SECURITY ENHANCED VERSION - Uses prepared statements to prevent SQL injection
 * Original function had critical SQL injection vulnerabilities
 */
session_start(); 

include("_init.php");
include("_names.php");
include("_debug.php");
include("_profileVars.php");
include("_secrets.php");
include("_globals.php");
include("_emailFunctions.php");

// Validate session and authentication
if(validateSessionOrCookiesReturnLoggedIn() == false) {
    header('Location: ' . getSiteURL());
    exit;
}

goHomeIfCookieNotSet();

// Validate input parameters
if (!isset($_GET['action']) || !isset($_GET['d']) || empty($_GET['action']) || empty($_GET['d'])) {
    error_log("Missing action or user ID parameters");
    exit('no action');
}

// Validate action parameter
$allowedActions = ['askprivate', 'notmytype', 'authorizeprivate', 'undonotmytype', 'rejection'];
if (!in_array($_GET['action'], $allowedActions)) {
    error_log("Invalid action: " . $_GET['action']);
    exit('bad action');
}

$action = $_GET['action'];
$theiruserid = filter_var($_GET['d'], FILTER_VALIDATE_INT);

if ($theiruserid === false || $theiruserid <= 0) {
    error_log("Invalid user ID: " . $_GET['d']);
    exit('invalid user id');
}

initAllVariablesFromDB();

try {
    // Use PDO for prepared statements
    $pdo = new PDO("mysql:host=$dburl;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $email = $_SESSION["email"];
    
    // Get current user's data using prepared statement
    $stmt = $pdo->prepare("SELECT id, firstName, notMyType, waitingForThemPrivate, private FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $myData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$myData) {
        error_log("User not found: " . $email);
        exit('user not found');
    }
    
    $myuserid = $myData['id'];
    $myFirstName = $myData['firstName'];
    
    $myNotMyType = array_filter(explode(",", trim($myData['notMyType'], ",")));
    $myWaitingForThemPrivate = array_filter(explode(",", trim($myData['waitingForThemPrivate'], ",")));
    $myPrivate = array_filter(explode(",", trim($myData['private'], ",")));
    
    // Validate that target user is accessible (distance, gender preferences)
    $distMiles = 0;
    switch($distance) {
        case "dist0m": $distMiles = 2.0; break;
        case "dist5m": $distMiles = 5.0; break;
        case "dist10m": $distMiles = 10.0; break;
        case "dist20m": $distMiles = 20.0; break;
        case "dist50m": $distMiles = 50.0; break;
        default: $distMiles = 10.0; break;
    }
    
    $latDist = (1.1 * $distMiles) / 49.1;
    $lonDist = (1.1 * $distMiles) / 69.1;
    
    $minLat = $lat - $latDist;
    $maxLat = $lat + $latDist;
    $minLon = $lon - $lonDist;
    $maxLon = $lon + $lonDist;
    
    // Build validation query with prepared statement
    $validationQuery = "SELECT * FROM users WHERE id = ? AND lat >= ? AND lat <= ? AND lon >= ? AND lon <= ?";
    $validationParams = [$theiruserid, $minLat, $maxLat, $minLon, $maxLon];
    
    // Add gender preference filters
    $genderFilters = [
        'male' => 'b_wantGenderMan',
        'female' => 'b_wantGenderWoman',
        'mtf' => 'b_wantGenderTSWoman',
        'ftm' => 'b_wantGenderTSMan',
        'cdmtf' => 'b_wantGenderCDWoman',
        'cdftm' => 'b_wantGenderCDMan',
        'mf' => 'b_wantGenderCoupleMF',
        'mm' => 'b_wantGenderCoupleMM',
        'ff' => 'b_wantGenderCoupleFF',
        'group' => 'b_wantGenderGroup'
    ];
    
    if (isset($genderFilters[$gender])) {
        $validationQuery .= " AND " . $genderFilters[$gender] . " = '1'";
    }
    
    // Add reverse gender preference filters
    $reverseGenderFilters = [
        'b_wantGenderMan' => 'male',
        'b_wantGenderWoman' => 'female',
        'b_wantGenderTSWoman' => 'mtf',
        'b_wantGenderTSMan' => 'ftm',
        'b_wantGenderCDWoman' => 'cdmtf',
        'b_wantGenderCDMan' => 'cdftm',
        'b_wantGenderCoupleMF' => 'mf',
        'b_wantGenderCoupleMM' => 'mm',
        'b_wantGenderCoupleFF' => 'ff',
        'b_wantGenderGroup' => 'group'
    ];
    
    foreach ($reverseGenderFilters as $pref => $genderValue) {
        if ($$pref == 0) {
            $validationQuery .= " AND gender != ?";
            $validationParams[] = $genderValue;
        }
    }
    
    // Add age validation
    $age = getAge($birthdayMonth, $birthdayDay, $birthdayYear);
    $validationQuery .= " AND wantAgeFrom <= ? AND wantAgeTo >= ?";
    $validationParams[] = $age;
    $validationParams[] = $age;
    
    // Exclude self and ensure profile is complete
    $validationQuery .= " AND email != ? AND profileDone = '1' AND verified = '1'";
    $validationParams[] = $email;
    
    $stmt = $pdo->prepare($validationQuery);
    $stmt->execute($validationParams);
    $theirData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$theirData) {
        error_log("Target user not accessible: " . $theiruserid);
        exit('no');
    }
    
    $theirFirstName = $theirData['firstName'];
    $theirEmail = $theirData['email'];
    $theirEmailMatches = $theirData['emailMatches'];
    $theirEmailInterested = $theirData['emailInterested'];
    $theirEmailApproved = $theirData['emailApproved'];
    $theirVerifyHash = $theirData['verifyHash'];
    
    $theirNotMyType = array_filter(explode(",", trim($theirData['notMyType'], ",")));
    $theirWaitingForThemPrivate = array_filter(explode(",", trim($theirData['waitingForThemPrivate'], ",")));
    $theirPrivate = array_filter(explode(",", trim($theirData['private'], ",")));
    
    // Check if we're on their "not my type" list
    if (in_array($myuserid, $theirNotMyType)) {
        error_log("User is on target's not my type list");
        exit("not their type");
    }
    
    // Process the action
    switch ($action) {
        case 'askprivate':
            if (!in_array($theiruserid, $myWaitingForThemPrivate)) {
                $myWaitingForThemPrivate[] = $theiruserid;
            }
            break;
            
        case 'authorizeprivate':
            if (!in_array($myuserid, $theirWaitingForThemPrivate)) {
                error_log("User not waiting for private authorization");
                exit("They aren't waiting for us to authorize private.");
            }
            
            if (!in_array($theiruserid, $myPrivate)) {
                $myPrivate[] = $theiruserid;
            }
            
            // Remove from waiting list and add to private
            $theirWaitingForThemPrivate = array_diff($theirWaitingForThemPrivate, [$myuserid]);
            if (!in_array($myuserid, $theirPrivate)) {
                $theirPrivate[] = $myuserid;
            }
            break;
            
        case 'notmytype':
            if (in_array($theiruserid, $myNotMyType)) {
                error_log("User already in not my type list");
                exit("Already not your type.");
            }
            
            // Remove from all lists
            $myWaitingForThemPrivate = array_diff($myWaitingForThemPrivate, [$theiruserid]);
            $myPrivate = array_diff($myPrivate, [$theiruserid]);
            $theirWaitingForThemPrivate = array_diff($theirWaitingForThemPrivate, [$myuserid]);
            $theirPrivate = array_diff($theirPrivate, [$myuserid]);
            
            // Add to not my type
            $myNotMyType[] = $theiruserid;
            break;
            
        case 'undonotmytype':
            if (!in_array($theiruserid, $myNotMyType)) {
                error_log("User not in not my type list");
                exit("They are not in your 'not my type' list.");
            }
            
            $myNotMyType = array_diff($myNotMyType, [$theiruserid]);
            break;
            
        case 'rejection':
            if (!in_array($theiruserid, $myPrivate)) {
                error_log("User not in private list");
                exit("They are not in your private list.");
            }
            
            $myPrivate = array_diff($myPrivate, [$theiruserid]);
            $theirPrivate = array_diff($theirPrivate, [$myuserid]);
            $myNotMyType[] = $theiruserid;
            break;
    }
    
    // Update current user's data
    $stmt = $pdo->prepare("UPDATE users SET notMyType = ?, waitingForThemPrivate = ?, private = ? WHERE id = ?");
    $stmt->execute([
        implode(",", $myNotMyType),
        implode(",", $myWaitingForThemPrivate),
        implode(",", $myPrivate),
        $myuserid
    ]);
    
    // Update target user's data
    $stmt = $pdo->prepare("UPDATE users SET notMyType = ?, waitingForThemPrivate = ?, private = ? WHERE id = ?");
    $stmt->execute([
        implode(",", $theirNotMyType),
        implode(",", $theirWaitingForThemPrivate),
        implode(",", $theirPrivate),
        $theiruserid
    ]);
    
    echo "done";
    
    // Send notification email if settings allow
    // (Email functionality would be implemented here)
    
} catch (PDOException $e) {
    error_log("Database error in matchAction: " . $e->getMessage());
    exit('database error');
}

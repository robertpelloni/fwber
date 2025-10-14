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

// Composer Autoloader - THIS MUST BE THE FIRST THING
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    // This is not a fatal error. It just means the .env file is not present.
    error_log("Could not find .env file: " . $e->getMessage());
}

// Configure secure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isSecure() ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Start session with secure configuration
session_start();

require_once("_db.php");
require_once("security-manager.php");
include("_debug.php");

// Initialize the Security Manager
$securityManager = new SecurityManager($pdo);

//=========================================================================================
function isSecure() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
}

//=========================================================================================
function currentPageURL()
{//=========================================================================================
    $pageURL = isSecure() ? 'https' : 'http';
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

//=========================================================================================
function validateSessionOrCookiesReturnLoggedIn()
{//=========================================================================================
    global $securityManager, $pdo;

    $token = $_SESSION['token'] ?? $_COOKIE['token'] ?? null;

    if (!$token) {
        return false;
    }

    $userId = $securityManager->validateSession($token);

    if ($userId) {
        if (!isset($_SESSION['token'])) {
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $email = $stmt->fetchColumn();

            if ($email) {
                $_SESSION['email'] = $email;
                $_SESSION['token'] = $token;
            }
        }
        
        $securityManager->generateCsrfToken();
        
        $updateStmt = $pdo->prepare("UPDATE users SET last_online = NOW() WHERE id = ?");
        $updateStmt->execute([$userId]);

        return true;
    } else {
        if (isset($_SESSION['token'])) {
            session_destroy();
        }
        if (isset($_COOKIE['token'])) {
            setcookie("email", "", time() - 3600, '/');
            setcookie("token", "", time() - 3600, '/');
        }
        return false;
    }
}


//=========================================================================================
function goHomeIfCookieNotSet()
{//=========================================================================================

    if(isCookieSet()==false)
    {
        header('Location: '.getSiteURL());
        echo '<meta http-equiv="refresh" content="1;url='.getSiteURL().'"/>';
        exit();
    }
	
}

//=========================================================================================
function isProfileDone()
{//=========================================================================================
    global $pdo;
    if (!isset($_SESSION["email"])) {
        return 0;
    }

    $stmt = $pdo->prepare("SELECT age FROM users WHERE email = ?");
    $stmt->execute([$_SESSION["email"]]);
    $result = $stmt->fetchColumn();

    return !empty($result) ? 1 : 0;
}

//=========================================================================================
function isCookieSet()
{//=========================================================================================

    if(isset($_SESSION["email"])&&$_SESSION["email"]!=null&&strlen($_SESSION["email"])>0)
        return 1;
    else
        return 0;
}

//=========================================================================================
function getDistanceBetweenPoints($lat1,$lon1,$lat2,$lon2)
{//=========================================================================================

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;

    return round($miles,2);
}

//=========================================================================================
function getGenderString($gender)
{//=========================================================================================
    if($gender=='male')return "Man";
    else if($gender=='female')return "Woman";
    else if($gender=='mtf')return "TS Woman";
    else if($gender=='ftm')return "TS Man";
    else if($gender=='cdmtf')return "CD Woman";
    else if($gender=='cdftm')return "CD Man";
    else if($gender=='mf')return "Man And Woman Couple";
    else if($gender=='mm')return "Man And Man Couple";
    else if($gender=='ff')return "Woman And Woman Couple";
    else if($gender=='group')return "Group Of People";
    else return $gender;
}

//=========================================================================================
function getAge($month,$day,$year)
{//=========================================================================================
    return (date("md", date("U", mktime(0,0,0, $month, $day, $year))) > date("md") ? ((date("Y")-$year)-1):(date("Y")-$year));
}

//=========================================================================================
function getTimeElapsedStringSinceTimestamp($time)
{//=========================================================================================
    $secs = time()-$time;
    $bit = array
    (
        ' year' => $secs / 31556926 % 12,
        ' week' => $secs / 604800 % 52,
        ' day' => $secs / 86400 % 7,
        ' hour' =>$secs / 3600 % 24
    );

    $ret = null;

    foreach($bit as $k => $v)
    {
        if($v > 1)$ret[] = $v . $k . 's';
        if($v == 1)$ret[] = $v . $k;
    }

    if($ret !== null && is_array($ret) && count($ret) > 0)
    {
        array_splice($ret, count($ret)-1, 0, 'and');
        $ret[] = 'ago.';

        return join(' ', $ret);
    }
    else
    return "Online Now!";

}

//=========================================================================================
function getDateStringFromTimestamp($time)
{//=========================================================================================

    return date("F j, Y, g:i a",$time);

}

//=========================================================================================
function printGenderChar($g)
{//=========================================================================================


    if($g['gender']=="male")	echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">&#x2642; </span>';
    else if($g['gender']=="female")	echo '<span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">&#x2640; </span>';
    else if($g['gender']=="mtf")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">&#x26A5; </span>';
    else if($g['gender']=="ftm")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">&#x26A5; </span>';
    else if($g['gender']=="cdmtf")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">&#x26A5; </span>';
    else if($g['gender']=="cdftm")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">&#x26A5; </span>';
    else if($g['gender']=="mf")		echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">&#x2642;</span><span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">&#x2640;</span>';
    else if($g['gender']=="mm")		echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">&#x2642;&#x2642;</span>';
    else if($g['gender']=="ff")		echo '<span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">&#x2640;&#x2640;</span>';
    else if($g['gender']=="group")		echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">&#x2642;</span><span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">&#x2640;</span><span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">&#x2642;</span><span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">&#x2640;</span>';
    else echo $g['gender'];
}
//=========================================================================================
function printGenderString($g)
{//=========================================================================================

    $genderString = getGenderString($g['gender']);

    if($g['gender']=="male")	echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="female")	echo '<span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="mtf")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="ftm")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="cdmtf")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="cdftm")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="mf")		echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="mm")		echo '<span style="color:#0397ff;text-shadow:#339 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="ff")		echo '<span style="color:#ff66cb;text-shadow:#917 1px 1px 2px;">'.$genderString.'</span>';
    else if($g['gender']=="group")		echo '<span style="color:#84f;text-shadow:#319 1px 1px 2px;">'.$genderString.'</span>';
    else echo $g['gender'];
}

//=========================================================================================
function hasPenis($g)
{//=========================================================================================
    if(
        $g['gender']=='male'||
        $g['gender']=='mtf'||
        $g['gender']=='cdmtf'||
        $g['gender']=='male'||
        $g['gender']=='mf'||
        $g['gender']=='mm'
        )
        return true;

    return false;

}
//=========================================================================================
function hasBreasts($g)
{//=========================================================================================
        if(
        $g['gender']=='female'||
        $g['gender']=='ftm'||
        $g['gender']=='mtf'||
        $g['gender']=='mf'||
        $g['gender']=='ff'
        )
        return true;

    return false;
}
//=========================================================================================
function hasBodyHair($g)
{//=========================================================================================
    if(
        $g['gender']=='male'||
        $g['gender']=='ftm'||
        $g['gender']=='mtf'||
        $g['gender']=='cdmtf'||
        $g['gender']=='mf'||
        $g['gender']=='mm'
    )
    return true;

    return false;
}


//=========================================================================================
function wantsPenis($g)
{//=========================================================================================

    if(
        $g['b_wantGenderMan']==1||
        $g['b_wantGenderTSWoman']==1||
        $g['b_wantGenderCDWoman']==1||
        $g['b_wantGenderCoupleMF']==1||
        $g['b_wantGenderCoupleMM']==1

    )
    return true;

    return false;
}
//=========================================================================================
function wantsBreasts($g)
{//=========================================================================================
    if(
        $g['b_wantGenderWoman']==1||
        $g['b_wantGenderTSWoman']==1||
        $g['b_wantGenderTSMan']==1||
        $g['b_wantGenderCoupleMF']==1||
        $g['b_wantGenderCoupleFF']==1

    )
    return true;

    return false;
}
//=========================================================================================
function wantsBodyHair($g)
{//=========================================================================================
    if(
        $g['b_wantGenderMan']==1||
        $g['b_wantGenderTSMan']==1||
        $g['b_wantGenderCDMan']==1||
        $g['b_wantGenderCoupleMF']==1||
        $g['b_wantGenderCoupleMM']==1

    )
    return true;

    return false;
}
	
//=========================================================================================
function usortByArrayKey(&$array, $key, $asc=SORT_ASC)
{//=========================================================================================
    $sort_flags = array(SORT_ASC, SORT_DESC);
    if(!in_array($asc, $sort_flags)) throw new InvalidArgumentException('sort flag only accepts SORT_ASC or SORT_DESC');
    $cmp = function(array $a, array $b) use ($key, $asc, $sort_flags)
	{
        if(!is_array($key))
		{ //just one key and sort direction
            if(!isset($a[$key]) || !isset($b[$key]))
			{
                throw new Exception('attempting to sort on non-existent keys');
            }
            if($a[$key] == $b[$key]) return 0;
            return ($asc==SORT_ASC xor $a[$key] < $b[$key]) ? 1 : -1;
        } 
		else
		{ //using multiple keys for sort and sub-sort
            foreach($key as $sub_key => $sub_asc)
			{
                //array can come as 'sort_key'=>SORT_ASC|SORT_DESC or just 'sort_key', so need to detect which
                if(!in_array($sub_asc, $sort_flags)) { $sub_key = $sub_asc; $sub_asc = $asc; }
                //just like above, except 'continue' in place of return 0
                if(!isset($a[$sub_key]) || !isset($b[$sub_key]))
				{
                    throw new Exception('attempting to sort on non-existent keys');
                }
                if($a[$sub_key] == $b[$sub_key]) continue;
                return ($sub_asc==SORT_ASC xor $a[$sub_key] < $b[$key]) ? 1 : -1;
            }
            return 0;
        }
    };
    usort($array, $cmp);
}

//=========================================================================================
function rem_array(&$array,$str)
{//=========================================================================================
	foreach($array as $key => $value)
	{	
		if($array[$key] == "$str") unset($array[$key]);
	}
	return $array;
}
//=========================================================================================
function convert_line_breaks($string, $line_break="<br>")
{//=========================================================================================
    $patterns = array(
        "/(<br>|<br \/>|<br\/>)\s*/i",
        "/(\r\n|\r|\n)/"
    );
    $replacements = array(
        PHP_EOL,
        $line_break
    );
    $string = preg_replace($patterns, $replacements, $string);
    return $string;
}

//=========================================================================================
function getUserIdByEmail($email)
{//=========================================================================================
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn();
}

//=========================================================================================
function getUserProfileById($userId)
{//=========================================================================================
    global $pdo;
    $stmt = $pdo->prepare("SELECT username, age FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

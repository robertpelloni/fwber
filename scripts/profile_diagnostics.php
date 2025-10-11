<?php
// scripts/profile_diagnostics.php
// Purpose: Inspect legacy matcher compatibility state for the current user
// Guards: Requires user login and DEBUG_MODE=true in environment

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/_init.php'; // initializes $pdo and $securityManager

// Require login
if (!validateSessionOrCookiesReturnLoggedIn()) {
    http_response_code(403);
    echo 'Forbidden: login required';
    exit;
}

// Require DEBUG_MODE enabled
$debug = getenv('DEBUG_MODE') ?: ($_ENV['DEBUG_MODE'] ?? '');
if (!in_array(strtolower((string)$debug), ['1','true','yes'], true)) {
    http_response_code(403);
    echo 'Forbidden: DEBUG_MODE must be enabled to view diagnostics.';
    exit;
}

$userId = getUserIdByEmail($_SESSION['email']);
if (!$userId) {
    http_response_code(400);
    echo 'Could not resolve current user ID.';
    exit;
}

// Collect users table columns
$cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
$colsSet = array_fill_keys($cols, true);

// Key columns the matcher uses
$keyCols = [
    'distance','profileDone','verified',
    'b_wantGenderMan','b_wantGenderWoman','b_wantGenderTSWoman','b_wantGenderTSMan','b_wantGenderCDWoman','b_wantGenderCDMan','b_wantGenderCoupleMF','b_wantGenderCoupleMM','b_wantGenderCoupleFF','b_wantGenderGroup',
    'b_wantBodyTiny','b_wantBodySlim','b_wantBodyAverage','b_wantBodyMuscular','b_wantBodyCurvy','b_wantBodyThick','b_wantBodyBBW',
    'b_wantEthnicityWhite','b_wantEthnicityAsian','b_wantEthnicityLatino','b_wantEthnicityIndian','b_wantEthnicityBlack','b_wantEthnicityOther',
    'b_wantHairColorLight','b_wantHairColorMedium','b_wantHairColorDark','b_wantHairColorRed','b_wantHairColorGray','b_wantHairColorOther',
    'b_wantHairLengthBald','b_wantHairLengthShort','b_wantHairLengthMedium','b_wantHairLengthLong',
    'b_wantTattoosNone','b_wantTattoosSome','b_wantTattoosAllOver',
    'b_wantLooksUgly','b_wantLooksPlain','b_wantLooksQuirky','b_wantLooksAverage','b_wantLooksAttractive','b_wantLooksHottie','b_wantLooksSuperModel',
    'b_wantIntelligenceGoodHands','b_wantIntelligenceBitSlow','b_wantIntelligenceAverage','b_wantIntelligenceFaster','b_wantIntelligenceGenius',
    'b_wantBedroomPersonalityPassive','b_wantBedroomPersonalityShy','b_wantBedroomPersonalityConfident','b_wantBedroomPersonalityAggressive',
    'b_wantPubicHairShaved','b_wantPubicHairTrimmed','b_wantPubicHairCropped','b_wantPubicHairNatural','b_wantPubicHairHairy',
    'b_wantPenisSizeTiny','b_wantPenisSizeSkinny','b_wantPenisSizeAverage','b_wantPenisSizeThick','b_wantPenisSizeHuge',
    'b_wantBodyHairSmooth','b_wantBodyHairAverage','b_wantBodyHairHairy',
    'b_wantBreastSizeTiny','b_wantBreastSizeSmall','b_wantBreastSizeAverage','b_wantBreastSizeLarge','b_wantBreastSizeHuge',
    'b_smokeCigarettes','b_noCigs','b_lightDrinker','b_noLightDrink','b_heavyDrinker','b_noHeavyDrink','b_smokeMarijuana','b_noMarijuana','b_psychedelics','b_noPsychedelics','b_otherDrugs','b_noDrugs','b_haveWarts','b_noWarts','b_haveHerpes','b_noHerpes','b_haveHepatitis','b_noHepatitis','b_haveOtherSTI','b_noOtherSTIs','b_haveHIV','b_noHIV','b_marriedTheyKnow','b_noMarriedTheyKnow','b_marriedSecret','b_noMarriedSecret','b_poly','b_noPoly','b_disability','b_noDisabled',
    'b_wantSafeSex','b_wantBarebackSex','b_wantOralGive','b_wantOralReceive','b_wantAnalTop','b_wantAnalBottom','b_wantFilming','b_wantVoyeur','b_wantExhibitionist','b_wantRoleplay','b_wantSpanking','b_wantDom','b_wantSub','b_wantStrapon','b_wantCuckold','b_wantFurry',
    'b_whereMyPlace','b_whereYouHost','b_whereCarDate','b_whereHotelIPay','b_whereHotelYouPay','b_whereHotelSplit','b_whereBarClub','b_whereGymSauna','b_whereNudeBeach','b_whereOther'
];

$missing = [];
foreach ($keyCols as $kc) {
    if (!isset($colsSet[$kc])) $missing[] = $kc;
}

// Current user values from users
$uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Current user preferences
$pStmt = $pdo->prepare("SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?");
$pStmt->execute([$userId]);
$prefs = $pStmt->fetchAll(PDO::FETCH_KEY_PAIR);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <title>FWBer Diagnostics</title>
  <style>
    body{font-family:system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; margin:20px}
    code{background:#f5f5f5;padding:2px 4px;border-radius:4px}
    .ok{color:#2e7d32}
    .warn{color:#ef6c00}
    .err{color:#c62828}
    table{border-collapse:collapse}
    td,th{border:1px solid #ccc;padding:6px 8px}
  </style>
</head>
<body>
<h1>FWBer Diagnostics</h1>
<p>User ID: <strong><?=htmlspecialchars((string)$userId)?></strong></p>

<h2>Users Table Columns</h2>
<p>Missing (<?=count($missing)?>):
<?php if ($missing): ?>
  <span class="err"><?=htmlspecialchars(implode(', ', $missing))?></span>
<?php else: ?>
  <span class="ok">None — all legacy matcher columns present.</span>
<?php endif; ?>
</p>

<h2>Key Values (users)</h2>
<table>
<tr><th>Field</th><th>Value</th></tr>
<?php foreach (['distance','profileDone','verified','gender','body','ethnicity','hairColor','hairLength','tattoos','overallLooks','intelligence','bedroomPersonality','pubicHair','penisSize','bodyHair','breastSize','wantAgeFrom','wantAgeTo'] as $f): ?>
<tr><td><?=htmlspecialchars($f)?></td><td><?=htmlspecialchars((string)($user[$f] ?? ''))?></td></tr>
<?php endforeach; ?>
</table>

<h2>Sample Preferences (users mirror)</h2>
<table>
<tr><th>Preference</th><th>users</th><th>user_preferences</th></tr>
<?php 
$sample = ['b_wantGenderWoman','b_wantLooksQuirky','b_wantIntelligenceGoodHands','b_wantPubicHairCropped','b_wantPenisSizeAverage','b_wantBodyHairSmooth','b_wantBreastSizeAverage'];
foreach ($sample as $k): ?>
<tr>
  <td><?=htmlspecialchars($k)?></td>
  <td><?=htmlspecialchars((string)($user[$k] ?? ''))?></td>
  <td><?=htmlspecialchars((string)($prefs[$k] ?? ''))?></td>
</tr>
<?php endforeach; ?>
</table>

<p style="margin-top:20px"><em>Tip:</em> Save your profile again from <code>/edit-profile.php</code> to populate mirrors if they show blank here.</p>

<hr>
<p><a href="/scripts/apply_migration_web.php">Run Migration (web)</a> — Requires DEBUG_MODE=true</p>
</body>
</html>

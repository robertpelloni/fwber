<?php
require_once('_init.php');
require_once('ProfileManager.php');

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

$profileManager = new ProfileManager($pdo);
$userId = getUserIdByEmail($_SESSION['email']);

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$securityManager->validateCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate form data
        $profileData = [];
        
        // Core profile fields
        $profileData['username'] = trim($_POST['username'] ?? '');
        $profileData['age'] = (int)($_POST['age'] ?? 0);
        $profileData['gender'] = $_POST['gender'] ?? '';
        $profileData['country'] = $_POST['country'] ?? '';
        $profileData['zip_code'] = trim($_POST['zip_code'] ?? '');
        $profileData['max_distance'] = (int)($_POST['max_distance'] ?? 50);
        
        // Convert max_distance to legacy distance format for matching compatibility
        $distanceMap = [5 => 'dist5m', 10 => 'dist10m', 20 => 'dist20m', 50 => 'dist50m'];
        $profileData['distance'] = $distanceMap[$profileData['max_distance']] ?? 'dist50m';
        
        // Physical attributes
        $profileData['body'] = $_POST['body'] ?? '';
        $profileData['ethnicity'] = $_POST['ethnicity'] ?? '';
        $profileData['hairColor'] = $_POST['hairColor'] ?? '';
        $profileData['hairLength'] = $_POST['hairLength'] ?? '';
        $profileData['tattoos'] = $_POST['tattoos'] ?? '';
        $profileData['overallLooks'] = $_POST['overallLooks'] ?? '';
        $profileData['intelligence'] = $_POST['intelligence'] ?? '';
        $profileData['bedroomPersonality'] = $_POST['bedroomPersonality'] ?? '';
        $profileData['pubicHair'] = $_POST['pubicHair'] ?? '';

        // Gender-specific physical attributes with validation
        $gender = $profileData['gender'];
        if ($gender === 'male' || $gender === 'non-binary') {
            $profileData['penisSize'] = $_POST['penisSize'] ?? '';
            $profileData['bodyHair'] = $_POST['bodyHair'] ?? '';
            // Ensure female-specific fields are not set
            unset($profileData['breastSize']);
        } elseif ($gender === 'female' || $gender === 'non-binary') {
            $profileData['breastSize'] = $_POST['breastSize'] ?? '';
            // Ensure male-specific fields are not set for female users
            if ($gender === 'female') {
                unset($profileData['penisSize']);
                unset($profileData['bodyHair']);
            }
        }

        // Age preferences
        $profileData['wantAgeFrom'] = (int)($_POST['wantAgeFrom'] ?? 18);
        $profileData['wantAgeTo'] = (int)($_POST['wantAgeTo'] ?? 99);
        
        // Gender preferences (b_* flags)
        $genderPrefs = ['b_wantGenderMan', 'b_wantGenderWoman', 'b_wantGenderTSWoman', 'b_wantGenderTSMan', 
                       'b_wantGenderCDWoman', 'b_wantGenderCDMan', 'b_wantGenderCoupleMF', 'b_wantGenderCoupleMM', 
                       'b_wantGenderCoupleFF', 'b_wantGenderGroup'];
        foreach ($genderPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }
        
        // Physical preferences
        $bodyPrefs = ['b_wantBodyTiny', 'b_wantBodySlim', 'b_wantBodyAverage', 'b_wantBodyMuscular', 
                     'b_wantBodyCurvy', 'b_wantBodyThick', 'b_wantBodyBBW'];
        foreach ($bodyPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }
        
        $ethnicityPrefs = ['b_wantEthnicityWhite', 'b_wantEthnicityAsian', 'b_wantEthnicityLatino', 
                          'b_wantEthnicityIndian', 'b_wantEthnicityBlack', 'b_wantEthnicityOther'];
        foreach ($ethnicityPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }
        
        $hairColorPrefs = ['b_wantHairColorLight', 'b_wantHairColorMedium', 'b_wantHairColorDark', 
                          'b_wantHairColorRed', 'b_wantHairColorGray', 'b_wantHairColorOther'];
        foreach ($hairColorPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }
        
        $hairLengthPrefs = ['b_wantHairLengthBald', 'b_wantHairLengthShort', 'b_wantHairLengthMedium', 'b_wantHairLengthLong'];
        foreach ($hairLengthPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }

        // Tattoo, looks, intelligence, personality preferences
        $tattooPrefs = ['b_wantTattoosNone', 'b_wantTattoosSome', 'b_wantTattoosAllOver'];
        foreach ($tattooPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }

        $looksPrefs = ['b_wantLooksUgly', 'b_wantLooksPlain', 'b_wantLooksQuirky', 'b_wantLooksAverage',
                      'b_wantLooksAttractive', 'b_wantLooksHottie', 'b_wantLooksSuperModel'];
        foreach ($looksPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }

        $intelligencePrefs = ['b_wantIntelligenceGoodHands', 'b_wantIntelligenceBitSlow', 'b_wantIntelligenceAverage',
                             'b_wantIntelligenceFaster', 'b_wantIntelligenceGenius'];
        foreach ($intelligencePrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }

        $bedroomPrefs = ['b_wantBedroomPersonalityPassive', 'b_wantBedroomPersonalityShy',
                        'b_wantBedroomPersonalityConfident', 'b_wantBedroomPersonalityAggressive'];
        foreach ($bedroomPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }

        // Grooming preferences
        $pubicHairPrefs = ['b_wantPubicHairShaved', 'b_wantPubicHairTrimmed', 'b_wantPubicHairCropped',
                          'b_wantPubicHairNatural', 'b_wantPubicHairHairy'];
        foreach ($pubicHairPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }

        // Gender-specific preference fields (only save if user selected matching gender preferences)
        $seeksMale = ($profileData['b_wantGenderMan'] ?? 0) || ($profileData['b_wantGenderTSMan'] ?? 0) ||
                     ($profileData['b_wantGenderCDMan'] ?? 0) || ($profileData['b_wantGenderCoupleMM'] ?? 0) ||
                     ($profileData['b_wantGenderCoupleMF'] ?? 0);

        $seeksFemale = ($profileData['b_wantGenderWoman'] ?? 0) || ($profileData['b_wantGenderTSWoman'] ?? 0) ||
                       ($profileData['b_wantGenderCDWoman'] ?? 0) || ($profileData['b_wantGenderCoupleFF'] ?? 0) ||
                       ($profileData['b_wantGenderCoupleMF'] ?? 0);

        if ($seeksMale) {
            $penisSizePrefs = ['b_wantPenisSizeTiny', 'b_wantPenisSizeSkinny', 'b_wantPenisSizeAverage',
                              'b_wantPenisSizeThick', 'b_wantPenisSizeHuge'];
            foreach ($penisSizePrefs as $pref) {
                $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
            }

            $bodyHairPrefs = ['b_wantBodyHairSmooth', 'b_wantBodyHairAverage', 'b_wantBodyHairHairy'];
            foreach ($bodyHairPrefs as $pref) {
                $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
            }
        }

        if ($seeksFemale) {
            $breastSizePrefs = ['b_wantBreastSizeTiny', 'b_wantBreastSizeSmall', 'b_wantBreastSizeAverage',
                               'b_wantBreastSizeLarge', 'b_wantBreastSizeHuge'];
            foreach ($breastSizePrefs as $pref) {
                $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
            }
        }

        // Lifestyle & Health flags
        $lifestyleFlags = ['b_smokeCigarettes', 'b_noCigs', 'b_lightDrinker', 'b_noLightDrink', 'b_heavyDrinker', 
                          'b_noHeavyDrink', 'b_smokeMarijuana', 'b_noMarijuana', 'b_psychedelics', 'b_noPsychedelics', 
                          'b_otherDrugs', 'b_noDrugs'];
        foreach ($lifestyleFlags as $flag) {
            $profileData[$flag] = isset($_POST[$flag]) ? 1 : 0;
        }
        
        $stiFlags = ['b_haveWarts', 'b_noWarts', 'b_haveHerpes', 'b_noHerpes', 'b_haveHepatitis', 'b_noHepatitis', 
                    'b_haveHIV', 'b_noHIV', 'b_haveOtherSTI', 'b_noOtherSTIs'];
        foreach ($stiFlags as $flag) {
            $profileData[$flag] = isset($_POST[$flag]) ? 1 : 0;
        }
        
        $relationshipFlags = ['b_marriedTheyKnow', 'b_noMarriedTheyKnow', 'b_marriedSecret', 'b_noMarriedSecret', 
                             'b_poly', 'b_noPoly', 'b_disability', 'b_noDisabled'];
        foreach ($relationshipFlags as $flag) {
            $profileData[$flag] = isset($_POST[$flag]) ? 1 : 0;
        }
        
        // Sexual activity preferences
        $sexualPrefs = ['b_wantSafeSex', 'b_wantBarebackSex', 'b_wantOralGive', 'b_wantOralReceive', 
                       'b_wantAnalTop', 'b_wantAnalBottom', 'b_wantFilming', 'b_wantVoyeur', 'b_wantExhibitionist', 
                       'b_wantRoleplay', 'b_wantSpanking', 'b_wantDom', 'b_wantSub', 'b_wantStrapon', 
                       'b_wantCuckold', 'b_wantFurry'];
        foreach ($sexualPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }
        
        // Meeting preferences
        $meetingPrefs = ['b_whereMyPlace', 'b_whereYouHost', 'b_whereCarDate', 'b_whereHotelIPay', 
                        'b_whereHotelYouPay', 'b_whereHotelSplit', 'b_whereBarClub', 'b_whereGymSauna', 
                        'b_whereNudeBeach', 'b_whereOther'];
        foreach ($meetingPrefs as $pref) {
            $profileData[$pref] = isset($_POST[$pref]) ? 1 : 0;
        }
        
        // Basic validation
        if (empty($profileData['username'])) {
            $error = 'Username is required.';
        } elseif ($profileData['age'] < 18) {
            $error = 'You must be 18 or older.';
        } elseif (empty($profileData['gender'])) {
            $error = 'Please select your gender.';
        } elseif (empty($profileData['body'])) {
            $error = 'Please select your body type.';
        } elseif (empty($profileData['ethnicity'])) {
            $error = 'Please select your ethnicity.';
        } else {
            // Check if at least one gender preference is selected
            $hasGenderPref = false;
            foreach ($genderPrefs as $pref) {
                if ($profileData[$pref] == 1) {
                    $hasGenderPref = true;
                    break;
                }
            }
            if (!$hasGenderPref) {
                $error = 'Please select at least one gender preference.';
            } else {
                // Save the profile
                if ($profileManager->saveProfile($userId, $profileData)) {
                    $message = 'Profile saved successfully!';
                } else {
                    $error = 'There was an error saving your profile.';
                }
            }
        }
    }
}

// Get the user's current profile data
$userProfile = $profileManager->getProfile($userId);

// Include the view
require('profile-form.php');

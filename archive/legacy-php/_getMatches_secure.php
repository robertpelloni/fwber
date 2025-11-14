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
function getMatches($email)
{
    include("_debug.php");
    include("_secrets.php");
    include("_globals.php");

    // Validate email input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email format: " . $email);
        return [];
    }

    // Use PDO for prepared statements instead of mysqli
    try {
        $pdo = new PDO("mysql:host=$dburl;dbname=$dbname", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user data using prepared statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $me = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$me) {
            error_log("User not found: " . $email);
            return [];
        }
        
        // Calculate distance parameters
        $distMiles = 0;
        switch($me['distance']) {
            case "dist0m": $distMiles = 2.0; break;
            case "dist5m": $distMiles = 5.0; break;
            case "dist10m": $distMiles = 10.0; break;
            case "dist20m": $distMiles = 20.0; break;
            case "dist50m": $distMiles = 50.0; break;
            default: $distMiles = 10.0; break;
        }
        
        $latDist = (1.1 * $distMiles) / 49.1;
        $lonDist = (1.1 * $distMiles) / 69.1;
        
        $minLat = $me['lat'] - $latDist;
        $maxLat = $me['lat'] + $latDist;
        $minLon = $me['lon'] - $lonDist;
        $maxLon = $me['lon'] + $lonDist;
        
        // Build base query with prepared statement parameters
        $baseQuery = "SELECT * FROM users WHERE lat >= ? AND lat <= ? AND lon >= ? AND lon <= ?";
        $params = [$minLat, $maxLat, $minLon, $maxLon];
        
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
        
        if (isset($genderFilters[$me['gender']])) {
            $baseQuery .= " AND " . $genderFilters[$me['gender']] . " = '1'";
        }
        
        // Add looks preference filters
        $looksFilters = [
            'quirky' => 'b_wantLooksQuirky',
            'hottie' => 'b_wantLooksHottie',
            'superModel' => 'b_wantLooksSuperModel'
        ];
        
        if (isset($looksFilters[$me['overallLooks']])) {
            $baseQuery .= " AND " . $looksFilters[$me['overallLooks']] . " = '1'";
        }
        
        // Add intelligence preference filters
        $intelligenceFilters = [
            'goodHands' => 'b_wantIntelligenceGoodHands',
            'bitSlow' => 'b_wantIntelligenceBitSlow',
            'average' => 'b_wantIntelligenceAverage',
            'faster' => 'b_wantIntelligenceFaster',
            'genius' => 'b_wantIntelligenceGenius'
        ];
        
        if (isset($intelligenceFilters[$me['intelligence']])) {
            $baseQuery .= " AND " . $intelligenceFilters[$me['intelligence']] . " = '1'";
        }
        
        // Add bedroom personality filters
        $bedroomFilters = [
            'passive' => 'b_wantBedroomPersonalityPassive',
            'shy' => 'b_wantBedroomPersonalityShy',
            'confident' => 'b_wantBedroomPersonalityConfident',
            'aggressive' => 'b_wantBedroomPersonalityAggressive'
        ];
        
        if (isset($bedroomFilters[$me['bedroomPersonality']])) {
            $baseQuery .= " AND " . $bedroomFilters[$me['bedroomPersonality']] . " = '1'";
        }
        
        // Add pubic hair filters
        $pubicHairFilters = [
            'shaved' => 'b_wantPubicHairShaved',
            'trimmed' => 'b_wantPubicHairTrimmed',
            'cropped' => 'b_wantPubicHairAverage',
            'natural' => 'b_wantPubicHairNatural',
            'hairy' => 'b_wantPubicHairHairy'
        ];
        
        if (isset($pubicHairFilters[$me['pubicHair']])) {
            $baseQuery .= " AND " . $pubicHairFilters[$me['pubicHair']] . " = '1'";
        }
        
        // Add penis size filters (for male/mtf users)
        if (in_array($me['gender'], ['male', 'mtf', 'cdmtf', 'mf', 'mm'])) {
            $penisFilters = [
                'tiny' => 'b_wantPenisSizeTiny',
                'skinny' => 'b_wantPenisSizeSkinny',
                'average' => 'b_wantPenisSizeAverage',
                'thick' => 'b_wantPenisSizeThick',
                'huge' => 'b_wantPenisSizeHuge'
            ];
            
            if (isset($penisFilters[$me['penisSize']])) {
                $baseQuery .= " AND " . $penisFilters[$me['penisSize']] . " = '1'";
            }
        }
        
        // Add body hair filters (for male/ftm users)
        if (in_array($me['gender'], ['male', 'ftm', 'mf', 'mm'])) {
            $bodyHairFilters = [
                'smooth' => 'b_wantBodyHairSmooth',
                'average' => 'b_wantBodyHairAverage',
                'hairy' => 'b_wantBodyHairHairy'
            ];
            
            if (isset($bodyHairFilters[$me['bodyHair']])) {
                $baseQuery .= " AND " . $bodyHairFilters[$me['bodyHair']] . " = '1'";
            }
        }
        
        // Add breast size filters (for female/mtf users)
        if (in_array($me['gender'], ['female', 'mtf', 'cdmtf', 'cdftm', 'ftm', 'mf', 'ff'])) {
            $breastFilters = [
                'tiny' => 'b_wantBreastSizeTiny',
                'small' => 'b_wantBreastSizeSmall',
                'average' => 'b_wantBreastSizeAverage',
                'large' => 'b_wantBreastSizeLarge',
                'huge' => 'b_wantBreastSizeHuge'
            ];
            
            if (isset($breastFilters[$me['breastSize']])) {
                $baseQuery .= " AND " . $breastFilters[$me['breastSize']] . " = '1'";
            }
        }
        
        // Add lifestyle filters
        $lifestyleFilters = [
            'b_smokeCigarettes' => 'b_noCigs',
            'b_lightDrinker' => 'b_noLightDrink',
            'b_heavyDrinker' => 'b_noHeavyDrink',
            'b_smokeMarijuana' => 'b_noMarijuana',
            'b_psychedelics' => 'b_noPsychedelics',
            'b_otherDrugs' => 'b_noDrugs',
            'b_haveWarts' => 'b_noWarts',
            'b_haveHerpes' => 'b_noHerpes',
            'b_haveHepatitis' => 'b_noHepatitis',
            'b_haveOtherSTI' => 'b_noOtherSTIs',
            'b_haveHIV' => 'b_noHIV',
            'b_marriedTheyKnow' => 'b_noMarriedTheyKnow',
            'b_marriedSecret' => 'b_noMarriedSecret',
            'b_poly' => 'b_noPoly',
            'b_disability' => 'b_noDisabled'
        ];
        
        foreach ($lifestyleFilters as $userPref => $targetPref) {
            if ($me[$userPref] == 1) {
                $baseQuery .= " AND " . $targetPref . " = '0'";
            }
            if ($me[$targetPref] == 1) {
                $baseQuery .= " AND " . $userPref . " = '0'";
            }
        }
        
        // Add sexual preference filters (OR conditions)
        $sexualPrefs = [];
        if ($me['b_wantSafeSex'] == 1) $sexualPrefs[] = "b_wantSafeSex = '1'";
        if ($me['b_wantBarebackSex'] == 1) $sexualPrefs[] = "b_wantBarebackSex = '1'";
        if ($me['b_wantOralGive'] == 1) $sexualPrefs[] = "b_wantOralReceive = '1'";
        if ($me['b_wantOralReceive'] == 1) $sexualPrefs[] = "b_wantOralGive = '1'";
        if ($me['b_wantAnalTop'] == 1) $sexualPrefs[] = "b_wantAnalBottom = '1'";
        if ($me['b_wantAnalBottom'] == 1) $sexualPrefs[] = "b_wantAnalTop = '1'";
        if ($me['b_wantFilming'] == 1) $sexualPrefs[] = "b_wantFilming = '1'";
        if ($me['b_wantVoyeur'] == 1) $sexualPrefs[] = "b_wantExhibitionist = '1'";
        if ($me['b_wantExhibitionist'] == 1) $sexualPrefs[] = "b_wantVoyeur = '1'";
        if ($me['b_wantRoleplay'] == 1) $sexualPrefs[] = "b_wantRoleplay = '1'";
        if ($me['b_wantSpanking'] == 1) $sexualPrefs[] = "b_wantSpanking = '1'";
        if ($me['b_wantDom'] == 1) $sexualPrefs[] = "b_wantSub = '1'";
        if ($me['b_wantSub'] == 1) $sexualPrefs[] = "b_wantDom = '1'";
        if ($me['b_wantStrapon'] == 1) $sexualPrefs[] = "b_wantStrapon = '1'";
        if ($me['b_wantCuckold'] == 1) $sexualPrefs[] = "b_wantCuckold = '1'";
        if ($me['b_wantFurry'] == 1) $sexualPrefs[] = "b_wantFurry = '1'";
        
        if (!empty($sexualPrefs)) {
            $baseQuery .= " AND (" . implode(" OR ", $sexualPrefs) . ")";
        }
        
        // Add location preference filters (OR conditions)
        $locationPrefs = [];
        if ($me['b_whereMyPlace'] == 1) $locationPrefs[] = "b_whereYouHost = '1'";
        if ($me['b_whereYouHost'] == 1) $locationPrefs[] = "b_whereMyPlace = '1'";
        if ($me['b_whereCarDate'] == 1) $locationPrefs[] = "b_whereCarDate = '1'";
        if ($me['b_whereHotelIPay'] == 1) $locationPrefs[] = "b_whereHotelYouPay = '1'";
        if ($me['b_whereHotelYouPay'] == 1) $locationPrefs[] = "b_whereHotelIPay = '1'";
        if ($me['b_whereHotelSplit'] == 1) $locationPrefs[] = "b_whereHotelSplit = '1'";
        if ($me['b_whereBarClub'] == 1) $locationPrefs[] = "b_whereBarClub = '1'";
        if ($me['b_whereGymSauna'] == 1) $locationPrefs[] = "b_whereGymSauna = '1'";
        if ($me['b_whereNudeBeach'] == 1) $locationPrefs[] = "b_whereNudeBeach = '1'";
        if ($me['b_whereOther'] == 1) $locationPrefs[] = "b_whereOther = '1'";
        
        if (!empty($locationPrefs)) {
            $baseQuery .= " AND (" . implode(" OR ", $locationPrefs) . ")";
        }
        
        // Exclude self and ensure profile is complete
        $baseQuery .= " AND email != ? AND profileDone = '1' AND verified = '1'";
        $params[] = $email;
        
        // Execute the query
        $stmt = $pdo->prepare($baseQuery);
        $stmt->execute($params);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sort by distance
        usort($matches, function($a, $b) use ($me) {
            $distA = getDistanceBetweenPoints($me['lat'], $me['lon'], $a['lat'], $a['lon']);
            $distB = getDistanceBetweenPoints($me['lat'], $me['lon'], $b['lat'], $b['lon']);
            return $distA <=> $distB;
        });
        
        return $matches;
        
    } catch (PDOException $e) {
        error_log("Database error in getMatches: " . $e->getMessage());
        return [];
    }
}

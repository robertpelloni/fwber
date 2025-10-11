<?php
// This is the view file for the profile form.
// It is included by edit-profile.php and uses the $userProfile and $message variables.
?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo getSiteName(); ?> - Edit Profile<?php echo getTitleTagline(); ?></title>
    <?php include("head.php");?>
</head>
<body class="d-flex flex-column h-100">
    <?php include("h.php");?>
    <div class="col-sm-12 my-auto text-center">
        <br><br><br><br>
        <h1 class="h3 mb-3 font-weight-normal text-center"> Edit Profile</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

	<form class="form-signin" action="edit-profile.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
	<fieldset style="text-align:center;border:none;">

		<div class="captionText">Area</div>
        <table>
            <tbody>
                <tr>
                    <td style="text-align:right; width:40%;"><label for="country">Country</label></td>
                    <td style="text-align:left;">
                        <select name="country" id="country" class="required" style="max-width:200px;">
                        <?php include("_countrySelector.php"); // This might need updating to set the selected value correctly ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;"><label for="zip_code">Zip / Postal Code</label></td>
                    <td style="text-align:left;"><input type="text" class="required" name="zip_code" id="zip_code" style="width:8em" value="<?php echo htmlspecialchars($userProfile['zip_code'] ?? ''); ?>"></td>
                </tr>
                <tr>
                    <td style="text-align:right;"><label for="max_distance">Distance To Search</label></td>
                    <td style="text-align:left;">
                        <select name="max_distance" id="max_distance" class="required">
                            <option value="5"  <?php if (($userProfile['max_distance'] ?? '') == '5') echo 'selected'; ?>>5 Miles</option>
                            <option value="10" <?php if (($userProfile['max_distance'] ?? '') == '10') echo 'selected'; ?>>10 Miles</option>
                            <option value="20" <?php if (($userProfile['max_distance'] ?? '') == '20') echo 'selected'; ?>>20 Miles</option>
                            <option value="50" <?php if (($userProfile['max_distance'] ?? '50') == '50') echo 'selected'; ?>>50 Miles</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

		<div class="captionText">About You</div>
        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;"><label for="username">Your Nickname</label></td>
                <td style="text-align:left;"><input type="text" class="required" id="username" name="username" style="width:12em" value="<?php echo htmlspecialchars($userProfile['username'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="age">Your Age</label></td>
                <td style="text-align:left;"><input type="number" class="required" id="age" name="age" style="width:5em" value="<?php echo htmlspecialchars($userProfile['age'] ?? ''); ?>" placeholder="##"></td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="gender">Your Gender</label></td>
                <td style="text-align:left;">
                    <select name="gender" id="gender" class="required">
                        <option value="male" <?php if (($userProfile['gender'] ?? '') == 'male') echo 'selected'; ?>>Man</option>
                        <option value="female" <?php if (($userProfile['gender'] ?? '') == 'female') echo 'selected'; ?>>Woman</option>
                        <option value="non-binary" <?php if (($userProfile['gender'] ?? '') == 'non-binary') echo 'selected'; ?>>Non-Binary</option>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Physical Attributes</div>
        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;"><label for="body">Body Type</label></td>
                <td style="text-align:left;">
                    <select name="body" id="body" class="required">
                        <option value="">Select body type</option>
                        <option value="tiny" <?php if (($userProfile['body'] ?? '') == 'tiny') echo 'selected'; ?>>Tiny</option>
                        <option value="slim" <?php if (($userProfile['body'] ?? '') == 'slim') echo 'selected'; ?>>Slim</option>
                        <option value="average" <?php if (($userProfile['body'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="muscular" <?php if (($userProfile['body'] ?? '') == 'muscular') echo 'selected'; ?>>Muscular</option>
                        <option value="curvy" <?php if (($userProfile['body'] ?? '') == 'curvy') echo 'selected'; ?>>Curvy</option>
                        <option value="thick" <?php if (($userProfile['body'] ?? '') == 'thick') echo 'selected'; ?>>Thick</option>
                        <option value="bbw" <?php if (($userProfile['body'] ?? '') == 'bbw') echo 'selected'; ?>>BBW</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="ethnicity">Ethnicity</label></td>
                <td style="text-align:left;">
                    <select name="ethnicity" id="ethnicity" class="required">
                        <option value="">Select ethnicity</option>
                        <option value="white" <?php if (($userProfile['ethnicity'] ?? '') == 'white') echo 'selected'; ?>>White</option>
                        <option value="asian" <?php if (($userProfile['ethnicity'] ?? '') == 'asian') echo 'selected'; ?>>Asian</option>
                        <option value="latino" <?php if (($userProfile['ethnicity'] ?? '') == 'latino') echo 'selected'; ?>>Latino</option>
                        <option value="indian" <?php if (($userProfile['ethnicity'] ?? '') == 'indian') echo 'selected'; ?>>Indian</option>
                        <option value="black" <?php if (($userProfile['ethnicity'] ?? '') == 'black') echo 'selected'; ?>>Black</option>
                        <option value="other" <?php if (($userProfile['ethnicity'] ?? '') == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="hairColor">Hair Color</label></td>
                <td style="text-align:left;">
                    <select name="hairColor" id="hairColor" class="required">
                        <option value="">Select hair color</option>
                        <option value="light" <?php if (($userProfile['hairColor'] ?? '') == 'light') echo 'selected'; ?>>Light</option>
                        <option value="medium" <?php if (($userProfile['hairColor'] ?? '') == 'medium') echo 'selected'; ?>>Medium</option>
                        <option value="dark" <?php if (($userProfile['hairColor'] ?? '') == 'dark') echo 'selected'; ?>>Dark</option>
                        <option value="red" <?php if (($userProfile['hairColor'] ?? '') == 'red') echo 'selected'; ?>>Red</option>
                        <option value="gray" <?php if (($userProfile['hairColor'] ?? '') == 'gray') echo 'selected'; ?>>Gray</option>
                        <option value="other" <?php if (($userProfile['hairColor'] ?? '') == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="hairLength">Hair Length</label></td>
                <td style="text-align:left;">
                    <select name="hairLength" id="hairLength" class="required">
                        <option value="">Select hair length</option>
                        <option value="bald" <?php if (($userProfile['hairLength'] ?? '') == 'bald') echo 'selected'; ?>>Bald</option>
                        <option value="short" <?php if (($userProfile['hairLength'] ?? '') == 'short') echo 'selected'; ?>>Short</option>
                        <option value="medium" <?php if (($userProfile['hairLength'] ?? '') == 'medium') echo 'selected'; ?>>Medium</option>
                        <option value="long" <?php if (($userProfile['hairLength'] ?? '') == 'long') echo 'selected'; ?>>Long</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="tattoos">Tattoos</label></td>
                <td style="text-align:left;">
                    <select name="tattoos" id="tattoos" class="required">
                        <option value="">Select tattoo preference</option>
                        <option value="none" <?php if (($userProfile['tattoos'] ?? '') == 'none') echo 'selected'; ?>>None</option>
                        <option value="some" <?php if (($userProfile['tattoos'] ?? '') == 'some') echo 'selected'; ?>>Some</option>
                        <option value="allOver" <?php if (($userProfile['tattoos'] ?? '') == 'allOver') echo 'selected'; ?>>All Over</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="overallLooks">Overall Looks</label></td>
                <td style="text-align:left;">
                    <select name="overallLooks" id="overallLooks" class="required">
                        <option value="">Select looks level</option>
                        <option value="ugly" <?php if (($userProfile['overallLooks'] ?? '') == 'ugly') echo 'selected'; ?>>Ugly</option>
                        <option value="plain" <?php if (($userProfile['overallLooks'] ?? '') == 'plain') echo 'selected'; ?>>Plain</option>
                        <option value="quirky" <?php if (($userProfile['overallLooks'] ?? '') == 'quirky') echo 'selected'; ?>>Quirky</option>
                        <option value="average" <?php if (($userProfile['overallLooks'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="attractive" <?php if (($userProfile['overallLooks'] ?? '') == 'attractive') echo 'selected'; ?>>Attractive</option>
                        <option value="hottie" <?php if (($userProfile['overallLooks'] ?? '') == 'hottie') echo 'selected'; ?>>Hottie</option>
                        <option value="superModel" <?php if (($userProfile['overallLooks'] ?? '') == 'superModel') echo 'selected'; ?>>Super Model</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="intelligence">Intelligence</label></td>
                <td style="text-align:left;">
                    <select name="intelligence" id="intelligence" class="required">
                        <option value="">Select intelligence level</option>
                        <option value="goodHands" <?php if (($userProfile['intelligence'] ?? '') == 'goodHands') echo 'selected'; ?>>Good Hands</option>
                        <option value="bitSlow" <?php if (($userProfile['intelligence'] ?? '') == 'bitSlow') echo 'selected'; ?>>Bit Slow</option>
                        <option value="average" <?php if (($userProfile['intelligence'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="faster" <?php if (($userProfile['intelligence'] ?? '') == 'faster') echo 'selected'; ?>>Faster</option>
                        <option value="genius" <?php if (($userProfile['intelligence'] ?? '') == 'genius') echo 'selected'; ?>>Genius</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="bedroomPersonality">Bedroom Personality</label></td>
                <td style="text-align:left;">
                    <select name="bedroomPersonality" id="bedroomPersonality" class="required">
                        <option value="">Select bedroom personality</option>
                        <option value="passive" <?php if (($userProfile['bedroomPersonality'] ?? '') == 'passive') echo 'selected'; ?>>Passive</option>
                        <option value="shy" <?php if (($userProfile['bedroomPersonality'] ?? '') == 'shy') echo 'selected'; ?>>Shy</option>
                        <option value="confident" <?php if (($userProfile['bedroomPersonality'] ?? '') == 'confident') echo 'selected'; ?>>Confident</option>
                        <option value="aggressive" <?php if (($userProfile['bedroomPersonality'] ?? '') == 'aggressive') echo 'selected'; ?>>Aggressive</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;"><label for="pubicHair">Pubic Hair</label></td>
                <td style="text-align:left;">
                    <select name="pubicHair" id="pubicHair" class="required">
                        <option value="">Select pubic hair style</option>
                        <option value="shaved" <?php if (($userProfile['pubicHair'] ?? '') == 'shaved') echo 'selected'; ?>>Shaved</option>
                        <option value="trimmed" <?php if (($userProfile['pubicHair'] ?? '') == 'trimmed') echo 'selected'; ?>>Trimmed</option>
                        <option value="cropped" <?php if (($userProfile['pubicHair'] ?? '') == 'cropped') echo 'selected'; ?>>Cropped</option>
                        <option value="natural" <?php if (($userProfile['pubicHair'] ?? '') == 'natural') echo 'selected'; ?>>Natural</option>
                        <option value="hairy" <?php if (($userProfile['pubicHair'] ?? '') == 'hairy') echo 'selected'; ?>>Hairy</option>
                    </select>
                </td>
            </tr>
            <tr class="gender-specific male-only">
                <td style="text-align:right;"><label for="penisSize">Penis Size</label></td>
                <td style="text-align:left;">
                    <select name="penisSize" id="penisSize">
                        <option value="">Select penis size</option>
                        <option value="tiny" <?php if (($userProfile['penisSize'] ?? '') == 'tiny') echo 'selected'; ?>>Tiny</option>
                        <option value="skinny" <?php if (($userProfile['penisSize'] ?? '') == 'skinny') echo 'selected'; ?>>Skinny</option>
                        <option value="average" <?php if (($userProfile['penisSize'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="thick" <?php if (($userProfile['penisSize'] ?? '') == 'thick') echo 'selected'; ?>>Thick</option>
                        <option value="huge" <?php if (($userProfile['penisSize'] ?? '') == 'huge') echo 'selected'; ?>>Huge</option>
                    </select>
                </td>
            </tr>
            <tr class="gender-specific male-only">
                <td style="text-align:right;"><label for="bodyHair">Body Hair</label></td>
                <td style="text-align:left;">
                    <select name="bodyHair" id="bodyHair">
                        <option value="">Select body hair level</option>
                        <option value="smooth" <?php if (($userProfile['bodyHair'] ?? '') == 'smooth') echo 'selected'; ?>>Smooth</option>
                        <option value="average" <?php if (($userProfile['bodyHair'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="hairy" <?php if (($userProfile['bodyHair'] ?? '') == 'hairy') echo 'selected'; ?>>Hairy</option>
                    </select>
                </td>
            </tr>
            <tr class="gender-specific female-only">
                <td style="text-align:right;"><label for="breastSize">Breast Size</label></td>
                <td style="text-align:left;">
                    <select name="breastSize" id="breastSize">
                        <option value="">Select breast size</option>
                        <option value="tiny" <?php if (($userProfile['breastSize'] ?? '') == 'tiny') echo 'selected'; ?>>Tiny</option>
                        <option value="small" <?php if (($userProfile['breastSize'] ?? '') == 'small') echo 'selected'; ?>>Small</option>
                        <option value="average" <?php if (($userProfile['breastSize'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="large" <?php if (($userProfile['breastSize'] ?? '') == 'large') echo 'selected'; ?>>Large</option>
                        <option value="huge" <?php if (($userProfile['breastSize'] ?? '') == 'huge') echo 'selected'; ?>>Huge</option>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Age Preferences</div>
        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;"><label for="wantAgeFrom">Seeking Age Range</label></td>
                <td style="text-align:left;">
                    <input type="number" name="wantAgeFrom" id="wantAgeFrom" min="18" max="99" style="width:5em" value="<?php echo htmlspecialchars($userProfile['wantAgeFrom'] ?? '18'); ?>"> to 
                    <input type="number" name="wantAgeTo" id="wantAgeTo" min="18" max="99" style="width:5em" value="<?php echo htmlspecialchars($userProfile['wantAgeTo'] ?? '99'); ?>">
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Gender Preferences</div>
        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>What gender(s) are you seeking?</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantGenderMan" value="1" <?php if ($userProfile['b_wantGenderMan'] ?? 0) echo 'checked'; ?>> Men</label><br>
                        <label><input type="checkbox" name="b_wantGenderWoman" value="1" <?php if ($userProfile['b_wantGenderWoman'] ?? 0) echo 'checked'; ?>> Women</label><br>
                        <label><input type="checkbox" name="b_wantGenderTSWoman" value="1" <?php if ($userProfile['b_wantGenderTSWoman'] ?? 0) echo 'checked'; ?>> Trans Women</label><br>
                        <label><input type="checkbox" name="b_wantGenderTSMan" value="1" <?php if ($userProfile['b_wantGenderTSMan'] ?? 0) echo 'checked'; ?>> Trans Men</label><br>
                        <label><input type="checkbox" name="b_wantGenderCDWoman" value="1" <?php if ($userProfile['b_wantGenderCDWoman'] ?? 0) echo 'checked'; ?>> Crossdresser Women</label><br>
                        <label><input type="checkbox" name="b_wantGenderCDMan" value="1" <?php if ($userProfile['b_wantGenderCDMan'] ?? 0) echo 'checked'; ?>> Crossdresser Men</label><br>
                        <label><input type="checkbox" name="b_wantGenderCoupleMF" value="1" <?php if ($userProfile['b_wantGenderCoupleMF'] ?? 0) echo 'checked'; ?>> MF Couples</label><br>
                        <label><input type="checkbox" name="b_wantGenderCoupleMM" value="1" <?php if ($userProfile['b_wantGenderCoupleMM'] ?? 0) echo 'checked'; ?>> MM Couples</label><br>
                        <label><input type="checkbox" name="b_wantGenderCoupleFF" value="1" <?php if ($userProfile['b_wantGenderCoupleFF'] ?? 0) echo 'checked'; ?>> FF Couples</label><br>
                        <label><input type="checkbox" name="b_wantGenderGroup" value="1" <?php if ($userProfile['b_wantGenderGroup'] ?? 0) echo 'checked'; ?>> Groups</label><br>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Physical Preferences</div>
        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Body Type Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantBodyTiny" value="1" <?php if ($userProfile['b_wantBodyTiny'] ?? 0) echo 'checked'; ?>> Tiny</label>
                        <label><input type="checkbox" name="b_wantBodySlim" value="1" <?php if ($userProfile['b_wantBodySlim'] ?? 0) echo 'checked'; ?>> Slim</label>
                        <label><input type="checkbox" name="b_wantBodyAverage" value="1" <?php if ($userProfile['b_wantBodyAverage'] ?? 0) echo 'checked'; ?>> Average</label>
                        <label><input type="checkbox" name="b_wantBodyMuscular" value="1" <?php if ($userProfile['b_wantBodyMuscular'] ?? 0) echo 'checked'; ?>> Muscular</label>
                        <label><input type="checkbox" name="b_wantBodyCurvy" value="1" <?php if ($userProfile['b_wantBodyCurvy'] ?? 0) echo 'checked'; ?>> Curvy</label>
                        <label><input type="checkbox" name="b_wantBodyThick" value="1" <?php if ($userProfile['b_wantBodyThick'] ?? 0) echo 'checked'; ?>> Thick</label>
                        <label><input type="checkbox" name="b_wantBodyBBW" value="1" <?php if ($userProfile['b_wantBodyBBW'] ?? 0) echo 'checked'; ?>> BBW</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Ethnicity Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantEthnicityWhite" value="1" <?php if ($userProfile['b_wantEthnicityWhite'] ?? 0) echo 'checked'; ?>> White</label>
                        <label><input type="checkbox" name="b_wantEthnicityAsian" value="1" <?php if ($userProfile['b_wantEthnicityAsian'] ?? 0) echo 'checked'; ?>> Asian</label>
                        <label><input type="checkbox" name="b_wantEthnicityLatino" value="1" <?php if ($userProfile['b_wantEthnicityLatino'] ?? 0) echo 'checked'; ?>> Latino</label>
                        <label><input type="checkbox" name="b_wantEthnicityIndian" value="1" <?php if ($userProfile['b_wantEthnicityIndian'] ?? 0) echo 'checked'; ?>> Indian</label>
                        <label><input type="checkbox" name="b_wantEthnicityBlack" value="1" <?php if ($userProfile['b_wantEthnicityBlack'] ?? 0) echo 'checked'; ?>> Black</label>
                        <label><input type="checkbox" name="b_wantEthnicityOther" value="1" <?php if ($userProfile['b_wantEthnicityOther'] ?? 0) echo 'checked'; ?>> Other</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Hair Color Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantHairColorLight" value="1" <?php if ($userProfile['b_wantHairColorLight'] ?? 0) echo 'checked'; ?>> Light</label>
                        <label><input type="checkbox" name="b_wantHairColorMedium" value="1" <?php if ($userProfile['b_wantHairColorMedium'] ?? 0) echo 'checked'; ?>> Medium</label>
                        <label><input type="checkbox" name="b_wantHairColorDark" value="1" <?php if ($userProfile['b_wantHairColorDark'] ?? 0) echo 'checked'; ?>> Dark</label>
                        <label><input type="checkbox" name="b_wantHairColorRed" value="1" <?php if ($userProfile['b_wantHairColorRed'] ?? 0) echo 'checked'; ?>> Red</label>
                        <label><input type="checkbox" name="b_wantHairColorGray" value="1" <?php if ($userProfile['b_wantHairColorGray'] ?? 0) echo 'checked'; ?>> Gray</label>
                        <label><input type="checkbox" name="b_wantHairColorOther" value="1" <?php if ($userProfile['b_wantHairColorOther'] ?? 0) echo 'checked'; ?>> Other</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Hair Length Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantHairLengthBald" value="1" <?php if ($userProfile['b_wantHairLengthBald'] ?? 0) echo 'checked'; ?>> Bald</label>
                        <label><input type="checkbox" name="b_wantHairLengthShort" value="1" <?php if ($userProfile['b_wantHairLengthShort'] ?? 0) echo 'checked'; ?>> Short</label>
                        <label><input type="checkbox" name="b_wantHairLengthMedium" value="1" <?php if ($userProfile['b_wantHairLengthMedium'] ?? 0) echo 'checked'; ?>> Medium</label>
                        <label><input type="checkbox" name="b_wantHairLengthLong" value="1" <?php if ($userProfile['b_wantHairLengthLong'] ?? 0) echo 'checked'; ?>> Long</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Tattoo Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantTattoosNone" value="1" <?php if ($userProfile['b_wantTattoosNone'] ?? 0) echo 'checked'; ?>> None</label>
                        <label><input type="checkbox" name="b_wantTattoosSome" value="1" <?php if ($userProfile['b_wantTattoosSome'] ?? 0) echo 'checked'; ?>> Some</label>
                        <label><input type="checkbox" name="b_wantTattoosAllOver" value="1" <?php if ($userProfile['b_wantTattoosAllOver'] ?? 0) echo 'checked'; ?>> All Over</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Looks Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantLooksUgly" value="1" <?php if ($userProfile['b_wantLooksUgly'] ?? 0) echo 'checked'; ?>> Ugly</label>
                        <label><input type="checkbox" name="b_wantLooksPlain" value="1" <?php if ($userProfile['b_wantLooksPlain'] ?? 0) echo 'checked'; ?>> Plain</label>
                        <label><input type="checkbox" name="b_wantLooksQuirky" value="1" <?php if ($userProfile['b_wantLooksQuirky'] ?? 0) echo 'checked'; ?>> Quirky</label>
                        <label><input type="checkbox" name="b_wantLooksAverage" value="1" <?php if ($userProfile['b_wantLooksAverage'] ?? 0) echo 'checked'; ?>> Average</label>
                        <label><input type="checkbox" name="b_wantLooksAttractive" value="1" <?php if ($userProfile['b_wantLooksAttractive'] ?? 0) echo 'checked'; ?>> Attractive</label>
                        <label><input type="checkbox" name="b_wantLooksHottie" value="1" <?php if ($userProfile['b_wantLooksHottie'] ?? 0) echo 'checked'; ?>> Hottie</label>
                        <label><input type="checkbox" name="b_wantLooksSuperModel" value="1" <?php if ($userProfile['b_wantLooksSuperModel'] ?? 0) echo 'checked'; ?>> Super Model</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Intelligence Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantIntelligenceGoodHands" value="1" <?php if ($userProfile['b_wantIntelligenceGoodHands'] ?? 0) echo 'checked'; ?>> Good Hands</label>
                        <label><input type="checkbox" name="b_wantIntelligenceBitSlow" value="1" <?php if ($userProfile['b_wantIntelligenceBitSlow'] ?? 0) echo 'checked'; ?>> Bit Slow</label>
                        <label><input type="checkbox" name="b_wantIntelligenceAverage" value="1" <?php if ($userProfile['b_wantIntelligenceAverage'] ?? 0) echo 'checked'; ?>> Average</label>
                        <label><input type="checkbox" name="b_wantIntelligenceFaster" value="1" <?php if ($userProfile['b_wantIntelligenceFaster'] ?? 0) echo 'checked'; ?>> Faster</label>
                        <label><input type="checkbox" name="b_wantIntelligenceGenius" value="1" <?php if ($userProfile['b_wantIntelligenceGenius'] ?? 0) echo 'checked'; ?>> Genius</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Bedroom Personality Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantBedroomPersonalityPassive" value="1" <?php if ($userProfile['b_wantBedroomPersonalityPassive'] ?? 0) echo 'checked'; ?>> Passive</label>
                        <label><input type="checkbox" name="b_wantBedroomPersonalityShy" value="1" <?php if ($userProfile['b_wantBedroomPersonalityShy'] ?? 0) echo 'checked'; ?>> Shy</label>
                        <label><input type="checkbox" name="b_wantBedroomPersonalityConfident" value="1" <?php if ($userProfile['b_wantBedroomPersonalityConfident'] ?? 0) echo 'checked'; ?>> Confident</label>
                        <label><input type="checkbox" name="b_wantBedroomPersonalityAggressive" value="1" <?php if ($userProfile['b_wantBedroomPersonalityAggressive'] ?? 0) echo 'checked'; ?>> Aggressive</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Pubic Hair Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantPubicHairShaved" value="1" <?php if ($userProfile['b_wantPubicHairShaved'] ?? 0) echo 'checked'; ?>> Shaved</label>
                        <label><input type="checkbox" name="b_wantPubicHairTrimmed" value="1" <?php if ($userProfile['b_wantPubicHairTrimmed'] ?? 0) echo 'checked'; ?>> Trimmed</label>
                        <label><input type="checkbox" name="b_wantPubicHairCropped" value="1" <?php if ($userProfile['b_wantPubicHairCropped'] ?? 0) echo 'checked'; ?>> Cropped</label>
                        <label><input type="checkbox" name="b_wantPubicHairNatural" value="1" <?php if ($userProfile['b_wantPubicHairNatural'] ?? 0) echo 'checked'; ?>> Natural</label>
                        <label><input type="checkbox" name="b_wantPubicHairHairy" value="1" <?php if ($userProfile['b_wantPubicHairHairy'] ?? 0) echo 'checked'; ?>> Hairy</label>
                    </div>
                </td>
            </tr>
            <tr class="gender-preference male-pref">
                <td colspan="2" style="text-align:left;">
                    <strong>Penis Size Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantPenisSizeTiny" value="1" <?php if ($userProfile['b_wantPenisSizeTiny'] ?? 0) echo 'checked'; ?>> Tiny</label>
                        <label><input type="checkbox" name="b_wantPenisSizeSkinny" value="1" <?php if ($userProfile['b_wantPenisSizeSkinny'] ?? 0) echo 'checked'; ?>> Skinny</label>
                        <label><input type="checkbox" name="b_wantPenisSizeAverage" value="1" <?php if ($userProfile['b_wantPenisSizeAverage'] ?? 0) echo 'checked'; ?>> Average</label>
                        <label><input type="checkbox" name="b_wantPenisSizeThick" value="1" <?php if ($userProfile['b_wantPenisSizeThick'] ?? 0) echo 'checked'; ?>> Thick</label>
                        <label><input type="checkbox" name="b_wantPenisSizeHuge" value="1" <?php if ($userProfile['b_wantPenisSizeHuge'] ?? 0) echo 'checked'; ?>> Huge</label>
                    </div>
                </td>
            </tr>
            <tr class="gender-preference male-pref">
                <td colspan="2" style="text-align:left;">
                    <strong>Body Hair Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantBodyHairSmooth" value="1" <?php if ($userProfile['b_wantBodyHairSmooth'] ?? 0) echo 'checked'; ?>> Smooth</label>
                        <label><input type="checkbox" name="b_wantBodyHairAverage" value="1" <?php if ($userProfile['b_wantBodyHairAverage'] ?? 0) echo 'checked'; ?>> Average</label>
                        <label><input type="checkbox" name="b_wantBodyHairHairy" value="1" <?php if ($userProfile['b_wantBodyHairHairy'] ?? 0) echo 'checked'; ?>> Hairy</label>
                    </div>
                </td>
            </tr>
            <tr class="gender-preference female-pref">
                <td colspan="2" style="text-align:left;">
                    <strong>Breast Size Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantBreastSizeTiny" value="1" <?php if ($userProfile['b_wantBreastSizeTiny'] ?? 0) echo 'checked'; ?>> Tiny</label>
                        <label><input type="checkbox" name="b_wantBreastSizeSmall" value="1" <?php if ($userProfile['b_wantBreastSizeSmall'] ?? 0) echo 'checked'; ?>> Small</label>
                        <label><input type="checkbox" name="b_wantBreastSizeAverage" value="1" <?php if ($userProfile['b_wantBreastSizeAverage'] ?? 0) echo 'checked'; ?>> Average</label>
                        <label><input type="checkbox" name="b_wantBreastSizeLarge" value="1" <?php if ($userProfile['b_wantBreastSizeLarge'] ?? 0) echo 'checked'; ?>> Large</label>
                        <label><input type="checkbox" name="b_wantBreastSizeHuge" value="1" <?php if ($userProfile['b_wantBreastSizeHuge'] ?? 0) echo 'checked'; ?>> Huge</label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Lifestyle & Health</div>
        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Substances:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_smokeCigarettes" value="1" <?php if ($userProfile['b_smokeCigarettes'] ?? 0) echo 'checked'; ?>> Smoke Cigarettes</label>
                        <label><input type="checkbox" name="b_noCigs" value="1" <?php if ($userProfile['b_noCigs'] ?? 0) echo 'checked'; ?>> No Cigarettes</label><br>
                        <label><input type="checkbox" name="b_lightDrinker" value="1" <?php if ($userProfile['b_lightDrinker'] ?? 0) echo 'checked'; ?>> Light Drinker</label>
                        <label><input type="checkbox" name="b_noLightDrink" value="1" <?php if ($userProfile['b_noLightDrink'] ?? 0) echo 'checked'; ?>> No Light Drinking</label><br>
                        <label><input type="checkbox" name="b_heavyDrinker" value="1" <?php if ($userProfile['b_heavyDrinker'] ?? 0) echo 'checked'; ?>> Heavy Drinker</label>
                        <label><input type="checkbox" name="b_noHeavyDrink" value="1" <?php if ($userProfile['b_noHeavyDrink'] ?? 0) echo 'checked'; ?>> No Heavy Drinking</label><br>
                        <label><input type="checkbox" name="b_smokeMarijuana" value="1" <?php if ($userProfile['b_smokeMarijuana'] ?? 0) echo 'checked'; ?>> Smoke Marijuana</label>
                        <label><input type="checkbox" name="b_noMarijuana" value="1" <?php if ($userProfile['b_noMarijuana'] ?? 0) echo 'checked'; ?>> No Marijuana</label><br>
                        <label><input type="checkbox" name="b_psychedelics" value="1" <?php if ($userProfile['b_psychedelics'] ?? 0) echo 'checked'; ?>> Psychedelics</label>
                        <label><input type="checkbox" name="b_noPsychedelics" value="1" <?php if ($userProfile['b_noPsychedelics'] ?? 0) echo 'checked'; ?>> No Psychedelics</label><br>
                        <label><input type="checkbox" name="b_otherDrugs" value="1" <?php if ($userProfile['b_otherDrugs'] ?? 0) echo 'checked'; ?>> Other Drugs</label>
                        <label><input type="checkbox" name="b_noDrugs" value="1" <?php if ($userProfile['b_noDrugs'] ?? 0) echo 'checked'; ?>> No Drugs</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>STI Status (be honest for safety):</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_haveWarts" value="1" <?php if ($userProfile['b_haveWarts'] ?? 0) echo 'checked'; ?>> Have Warts</label>
                        <label><input type="checkbox" name="b_noWarts" value="1" <?php if ($userProfile['b_noWarts'] ?? 0) echo 'checked'; ?>> No Warts</label><br>
                        <label><input type="checkbox" name="b_haveHerpes" value="1" <?php if ($userProfile['b_haveHerpes'] ?? 0) echo 'checked'; ?>> Have Herpes</label>
                        <label><input type="checkbox" name="b_noHerpes" value="1" <?php if ($userProfile['b_noHerpes'] ?? 0) echo 'checked'; ?>> No Herpes</label><br>
                        <label><input type="checkbox" name="b_haveHepatitis" value="1" <?php if ($userProfile['b_haveHepatitis'] ?? 0) echo 'checked'; ?>> Have Hepatitis</label>
                        <label><input type="checkbox" name="b_noHepatitis" value="1" <?php if ($userProfile['b_noHepatitis'] ?? 0) echo 'checked'; ?>> No Hepatitis</label><br>
                        <label><input type="checkbox" name="b_haveHIV" value="1" <?php if ($userProfile['b_haveHIV'] ?? 0) echo 'checked'; ?>> Have HIV</label>
                        <label><input type="checkbox" name="b_noHIV" value="1" <?php if ($userProfile['b_noHIV'] ?? 0) echo 'checked'; ?>> No HIV</label><br>
                        <label><input type="checkbox" name="b_haveOtherSTI" value="1" <?php if ($userProfile['b_haveOtherSTI'] ?? 0) echo 'checked'; ?>> Have Other STI</label>
                        <label><input type="checkbox" name="b_noOtherSTIs" value="1" <?php if ($userProfile['b_noOtherSTIs'] ?? 0) echo 'checked'; ?>> No Other STIs</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Relationship Status:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_marriedTheyKnow" value="1" <?php if ($userProfile['b_marriedTheyKnow'] ?? 0) echo 'checked'; ?>> Married (They Know)</label>
                        <label><input type="checkbox" name="b_noMarriedTheyKnow" value="1" <?php if ($userProfile['b_noMarriedTheyKnow'] ?? 0) echo 'checked'; ?>> No Married (They Know)</label><br>
                        <label><input type="checkbox" name="b_marriedSecret" value="1" <?php if ($userProfile['b_marriedSecret'] ?? 0) echo 'checked'; ?>> Married (Secret)</label>
                        <label><input type="checkbox" name="b_noMarriedSecret" value="1" <?php if ($userProfile['b_noMarriedSecret'] ?? 0) echo 'checked'; ?>> No Married (Secret)</label><br>
                        <label><input type="checkbox" name="b_poly" value="1" <?php if ($userProfile['b_poly'] ?? 0) echo 'checked'; ?>> Polyamorous</label>
                        <label><input type="checkbox" name="b_noPoly" value="1" <?php if ($userProfile['b_noPoly'] ?? 0) echo 'checked'; ?>> No Polyamorous</label><br>
                        <label><input type="checkbox" name="b_disability" value="1" <?php if ($userProfile['b_disability'] ?? 0) echo 'checked'; ?>> Have Disability</label>
                        <label><input type="checkbox" name="b_noDisabled" value="1" <?php if ($userProfile['b_noDisabled'] ?? 0) echo 'checked'; ?>> No Disability</label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Sexual Activities</div>
        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Sexual Preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantSafeSex" value="1" <?php if ($userProfile['b_wantSafeSex'] ?? 0) echo 'checked'; ?>> Safe Sex</label>
                        <label><input type="checkbox" name="b_wantBarebackSex" value="1" <?php if ($userProfile['b_wantBarebackSex'] ?? 0) echo 'checked'; ?>> Bareback Sex</label><br>
                        <label><input type="checkbox" name="b_wantOralGive" value="1" <?php if ($userProfile['b_wantOralGive'] ?? 0) echo 'checked'; ?>> Give Oral</label>
                        <label><input type="checkbox" name="b_wantOralReceive" value="1" <?php if ($userProfile['b_wantOralReceive'] ?? 0) echo 'checked'; ?>> Receive Oral</label><br>
                        <label><input type="checkbox" name="b_wantAnalTop" value="1" <?php if ($userProfile['b_wantAnalTop'] ?? 0) echo 'checked'; ?>> Anal Top</label>
                        <label><input type="checkbox" name="b_wantAnalBottom" value="1" <?php if ($userProfile['b_wantAnalBottom'] ?? 0) echo 'checked'; ?>> Anal Bottom</label><br>
                        <label><input type="checkbox" name="b_wantFilming" value="1" <?php if ($userProfile['b_wantFilming'] ?? 0) echo 'checked'; ?>> Filming</label><br>
                        <label><input type="checkbox" name="b_wantVoyeur" value="1" <?php if ($userProfile['b_wantVoyeur'] ?? 0) echo 'checked'; ?>> Voyeur</label>
                        <label><input type="checkbox" name="b_wantExhibitionist" value="1" <?php if ($userProfile['b_wantExhibitionist'] ?? 0) echo 'checked'; ?>> Exhibitionist</label><br>
                        <label><input type="checkbox" name="b_wantRoleplay" value="1" <?php if ($userProfile['b_wantRoleplay'] ?? 0) echo 'checked'; ?>> Roleplay</label>
                        <label><input type="checkbox" name="b_wantSpanking" value="1" <?php if ($userProfile['b_wantSpanking'] ?? 0) echo 'checked'; ?>> Spanking</label><br>
                        <label><input type="checkbox" name="b_wantDom" value="1" <?php if ($userProfile['b_wantDom'] ?? 0) echo 'checked'; ?>> Dominant</label>
                        <label><input type="checkbox" name="b_wantSub" value="1" <?php if ($userProfile['b_wantSub'] ?? 0) echo 'checked'; ?>> Submissive</label><br>
                        <label><input type="checkbox" name="b_wantStrapon" value="1" <?php if ($userProfile['b_wantStrapon'] ?? 0) echo 'checked'; ?>> Strap-on</label>
                        <label><input type="checkbox" name="b_wantCuckold" value="1" <?php if ($userProfile['b_wantCuckold'] ?? 0) echo 'checked'; ?>> Cuckold</label>
                        <label><input type="checkbox" name="b_wantFurry" value="1" <?php if ($userProfile['b_wantFurry'] ?? 0) echo 'checked'; ?>> Furry</label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">Meeting Preferences</div>
        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Where to meet:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_whereMyPlace" value="1" <?php if ($userProfile['b_whereMyPlace'] ?? 0) echo 'checked'; ?>> My Place</label>
                        <label><input type="checkbox" name="b_whereYouHost" value="1" <?php if ($userProfile['b_whereYouHost'] ?? 0) echo 'checked'; ?>> You Host</label><br>
                        <label><input type="checkbox" name="b_whereCarDate" value="1" <?php if ($userProfile['b_whereCarDate'] ?? 0) echo 'checked'; ?>> Car Date</label><br>
                        <label><input type="checkbox" name="b_whereHotelIPay" value="1" <?php if ($userProfile['b_whereHotelIPay'] ?? 0) echo 'checked'; ?>> Hotel (I Pay)</label>
                        <label><input type="checkbox" name="b_whereHotelYouPay" value="1" <?php if ($userProfile['b_whereHotelYouPay'] ?? 0) echo 'checked'; ?>> Hotel (You Pay)</label>
                        <label><input type="checkbox" name="b_whereHotelSplit" value="1" <?php if ($userProfile['b_whereHotelSplit'] ?? 0) echo 'checked'; ?>> Hotel (Split)</label><br>
                        <label><input type="checkbox" name="b_whereBarClub" value="1" <?php if ($userProfile['b_whereBarClub'] ?? 0) echo 'checked'; ?>> Bar/Club</label>
                        <label><input type="checkbox" name="b_whereGymSauna" value="1" <?php if ($userProfile['b_whereGymSauna'] ?? 0) echo 'checked'; ?>> Gym/Sauna</label><br>
                        <label><input type="checkbox" name="b_whereNudeBeach" value="1" <?php if ($userProfile['b_whereNudeBeach'] ?? 0) echo 'checked'; ?>> Nude Beach</label>
                        <label><input type="checkbox" name="b_whereOther" value="1" <?php if ($userProfile['b_whereOther'] ?? 0) echo 'checked'; ?>> Other</label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="captionText">AI Avatar Generation</div>
        <div class="text-center" style="margin: 20px 0;">
            <button type="button" class="btn btn-secondary" onclick="generateAvatar()" id="generateAvatarBtn">
                <i class="fas fa-magic"></i> Generate AI Avatar
            </button>
            <div id="avatarStatus" style="margin-top: 10px;"></div>
            <div id="avatarPreview" style="margin-top: 10px;"></div>
        </div>

        <br>
        <div class="text-center">
            <button class="btn btn-lg btn-primary" type="submit">Save Profile</button>
        </div>

	</fieldset>
	</form>


</div>

<?php include("f.php");?>

<script src="/js/jquery-3.4.1.min.js" type="text/javascript"></script>
<script type="text/javascript">
function generateAvatar() {
    const btn = document.getElementById('generateAvatarBtn');
    const status = document.getElementById('avatarStatus');
    const preview = document.getElementById('avatarPreview');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    status.innerHTML = '<div class="alert alert-info">Creating your personalized avatar...</div>';
    preview.innerHTML = '';
    
    $.ajax({
        url: 'api/generate-avatar.php',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                status.innerHTML = '<div class="alert alert-success">Avatar generated successfully!</div>';
                preview.innerHTML = '<img src="' + response.avatar_url + '" alt="Generated Avatar" style="max-width: 200px; border-radius: 10px; margin-top: 10px;">';
            } else {
                status.innerHTML = '<div class="alert alert-danger">Error: ' + (response.error || 'Avatar generation failed') + '</div>';
            }
        },
        error: function() {
            status.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic"></i> Generate AI Avatar';
        }
    });
}

// Gender-specific field visibility
function updateGenderSpecificFields() {
    const gender = $('#gender').val();

    // Hide all gender-specific fields first
    $('.gender-specific').hide();
    $('.gender-preference').hide();

    // Show male-specific fields
    if (gender === 'male') {
        $('.male-only').show();
    }

    // Show female-specific fields
    if (gender === 'female') {
        $('.female-only').show();
    }

    // Show preference fields based on gender preferences selected
    const showMalePrefs = $('input[name="b_wantGenderMan"]:checked').length > 0 ||
                          $('input[name="b_wantGenderTSMan"]:checked').length > 0 ||
                          $('input[name="b_wantGenderCDMan"]:checked').length > 0 ||
                          $('input[name="b_wantGenderCoupleMM"]:checked').length > 0 ||
                          $('input[name="b_wantGenderCoupleMF"]:checked').length > 0;

    const showFemalePrefs = $('input[name="b_wantGenderWoman"]:checked').length > 0 ||
                            $('input[name="b_wantGenderTSWoman"]:checked').length > 0 ||
                            $('input[name="b_wantGenderCDWoman"]:checked').length > 0 ||
                            $('input[name="b_wantGenderCoupleFF"]:checked').length > 0 ||
                            $('input[name="b_wantGenderCoupleMF"]:checked').length > 0;

    if (showMalePrefs) {
        $('.male-pref').show();
    }

    if (showFemalePrefs) {
        $('.female-pref').show();
    }
}

// Form validation and UX improvements
$(document).ready(function() {
    // Initialize gender-specific field visibility
    updateGenderSpecificFields();

    // Update on gender change
    $('#gender').on('change', updateGenderSpecificFields);

    // Update on gender preference change
    $('input[name^="b_wantGender"]').on('change', updateGenderSpecificFields);

    // Add collapsible sections
    $('.captionText').each(function() {
        const $this = $(this);
        const $nextTable = $this.next('table');

        if ($nextTable.length) {
            $this.css({
                'cursor': 'pointer',
                'user-select': 'none',
                'background-color': '#f8f9fa',
                'padding': '10px',
                'border-radius': '5px',
                'border': '1px solid #dee2e6'
            });

            $this.append(' <span style="float: right;">▼</span>');

            $this.click(function() {
                $nextTable.slideToggle();
                const $arrow = $(this).find('span');
                $arrow.text($arrow.text() === '▼' ? '▶' : '▼');
            });
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        
        // Check required fields
        const requiredFields = ['username', 'age', 'gender', 'body', 'ethnicity', 'hairColor', 'hairLength', 'tattoos', 'overallLooks', 'intelligence', 'bedroomPersonality'];
        
        requiredFields.forEach(function(field) {
            const $field = $('#' + field);
            if (!$field.val()) {
                isValid = false;
                errorMessage += 'Please fill in all required fields.\n';
                $field.css('border-color', '#dc3545');
            } else {
                $field.css('border-color', '');
            }
        });
        
        // Check age
        const age = parseInt($('#age').val());
        if (age < 18) {
            isValid = false;
            errorMessage += 'You must be 18 or older.\n';
            $('#age').css('border-color', '#dc3545');
        }
        
        // Check gender preferences
        const genderPrefs = $('input[name^="b_wantGender"]:checked');
        if (genderPrefs.length === 0) {
            isValid = false;
            errorMessage += 'Please select at least one gender preference.\n';
        }
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
    
    // Real-time validation
    $('input[type="number"]').on('input', function() {
        const $this = $(this);
        const val = parseInt($this.val());
        
        if ($this.attr('id') === 'age' && val < 18) {
            $this.css('border-color', '#dc3545');
        } else if ($this.attr('id') === 'wantAgeFrom' && val < 18) {
            $this.css('border-color', '#dc3545');
        } else if ($this.attr('id') === 'wantAgeTo' && val > 99) {
            $this.css('border-color', '#dc3545');
        } else {
            $this.css('border-color', '');
        }
    });
    
    // Auto-save progress (optional)
    let saveTimeout;
    $('input, select').on('change', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            // Could implement auto-save here
            console.log('Form changed - could auto-save');
        }, 2000);
    });
});
</script>

</body>
</html>

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
        <br>
        <br>
        <br>
        <br>
        <h1 class="h3 mb-3 font-weight-normal text-center"> Edit Profile</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="smallText" style="font-size:16px;">
        <br>
        <span style="font-weight:bold;font-size:24pt;color:#8af;"><?php echo getSiteName();?> is different.</span><br>
        <?php echo getSiteName();?> automatically connects you with matches based on your preferences.<br>
        We want to match you with the right results, so please choose your options carefully.<br>
        </div>
        <br>

	<form class="form-signin" action="edit-profile.php" method="POST" enctype="multipart/form-data" name="editProfileFormName" id="editProfileFormID">
	<fieldset style="text-align:center;border:none;">

		<div class="captionText">
		Area
		<div class="smallText">
		We'll only match you in the area you specify.<br>
		</div>
		</div>

        <table>
            <tbody>
                <tr>
                    <td style="text-align:right; width:40%;">
                        <label for="country">Country</label>
                    </td>
                    <td style="text-align:left;">
                        <select name="country" id="country" class="required" style="max-width:200px;">
                        <?php include("_countrySelector.php"); ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td style="text-align:right;">
                        <label for="zip_code">Zip / Postal Code</label>
                    </td>
                    <td style="text-align:left;">
                        <input type="text" class="required" name="zip_code" id="zip_code" style="width:8em" value="<?php echo htmlspecialchars($userProfile['zip_code'] ?? ''); ?>">
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                     <label for="max_distance">Distance To Search</label>
                    </td>
                    <td style="text-align:left;">
                        <select name="max_distance" id="max_distance" class="required" >
                            <option value="5"  <?php if (($userProfile['max_distance'] ?? '') == '5') echo 'selected'; ?>>5 Miles</option>
                            <option value="10" <?php if (($userProfile['max_distance'] ?? '') == '10') echo 'selected'; ?>>10 Miles</option>
                            <option value="20" <?php if (($userProfile['max_distance'] ?? '') == '20') echo 'selected'; ?>>20 Miles</option>
                            <option value="50" <?php if (($userProfile['max_distance'] ?? '50') == '50') echo 'selected'; ?>>50 Miles</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

		<div class="saveButton"></div>
		
		<div class="captionText">
		About You
		</div>

        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;">
                    <label for="username">Your Nickname</label>
                </td>
                <td style="text-align:left;">
                    <input type="text" class="required" id="username" name="username" style="width:12em" value="<?php echo htmlspecialchars($userProfile['username'] ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <td style="text-align:right;">
                    <label for="age">Your Age</label>
                </td>
                <td style="text-align:left;">
                    <input type="number" class="required" id="age" name="age" style="width:5em" value="<?php echo htmlspecialchars($userProfile['age'] ?? ''); ?>" placeholder="##">
                </td>
            </tr>

            <tr>
                <td style="text-align:right;">
		            <label for="gender">Your Gender</label>
                </td>
                <td style="text-align:left;">
                    <select name="gender" id="gender" class="required">
                        <option value="male" 	<?php if (($userProfile['gender'] ?? '') == 'male') echo 'selected'; ?>>Man</option>
                        <option value="female" 	<?php if (($userProfile['gender'] ?? '') == 'female') echo 'selected'; ?>>Woman</option>
                        <option value="non-binary" 	<?php if (($userProfile['gender'] ?? '') == 'non-binary') echo 'selected'; ?>>Non-Binary</option>
                        <option value="trans-male" 	<?php if (($userProfile['gender'] ?? '') == 'trans-male') echo 'selected'; ?>>Trans-Male</option>
                        <option value="trans-female" 	<?php if (($userProfile['gender'] ?? '') == 'trans-female') echo 'selected'; ?>>Trans-Female</option>
                        <option value="other" 	<?php if (($userProfile['gender'] ?? '') == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <td style="text-align:right;">
                    <label for="seeking_gender">Seeking</label>
                </td>
                <td style="text-align:left;">
                    <select name="seeking_gender" id="seeking_gender" class="required">
                        <option value="male" <?php if (($userProfile['seeking_gender'] ?? '') == 'male') echo 'selected'; ?>>Men</option>
                        <option value="female" <?php if (($userProfile['seeking_gender'] ?? '') == 'female') echo 'selected'; ?>>Women</option>
                        <option value="non-binary" <?php if (($userProfile['seeking_gender'] ?? '') == 'non-binary') echo 'selected'; ?>>Non-Binary</option>
                        <option value="trans-male" <?php if (($userProfile['seeking_gender'] ?? '') == 'trans-male') echo 'selected'; ?>>Trans-Male</option>
                        <option value="trans-female" <?php if (($userProfile['seeking_gender'] ?? '') == 'trans-female') echo 'selected'; ?>>Trans-Female</option>
                        <option value="all" <?php if (($userProfile['seeking_gender'] ?? '') == 'all') echo 'selected'; ?>>All Genders</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td style="text-align:right;">
                    <label for="relationship_type">Relationship Type</label>
                </td>
                <td style="text-align:left;">
                    <select name="relationship_type" id="relationship_type" class="required">
                        <option value="casual" <?php if (($userProfile['relationship_type'] ?? '') == 'casual') echo 'selected'; ?>>Casual Dating</option>
                        <option value="friends_with_benefits" <?php if (($userProfile['relationship_type'] ?? '') == 'friends_with_benefits') echo 'selected'; ?>>Friends with Benefits</option>
                        <option value="hookup" <?php if (($userProfile['relationship_type'] ?? '') == 'hookup') echo 'selected'; ?>>Hookup</option>
                        <option value="serious" <?php if (($userProfile['relationship_type'] ?? '') == 'serious') echo 'selected'; ?>>Serious Relationship</option>
                        <option value="open" <?php if (($userProfile['relationship_type'] ?? '') == 'open') echo 'selected'; ?>>Open Relationship</option>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		Physical Attributes
		<div class="smallText">
		Help us create better matches and generate your AI avatar<br>
		</div>
		</div>

        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;">
                    <label for="height">Height</label>
                </td>
                <td style="text-align:left;">
                    <input type="number" id="height" name="height" style="width:5em" value="<?php echo htmlspecialchars($userProfile['height'] ?? ''); ?>" placeholder="cm">
                </td>
            </tr>
            
            <tr>
                <td style="text-align:right;">
                    <label for="body_type">Body Type</label>
                </td>
                <td style="text-align:left;">
                    <select name="body_type" id="body_type">
                        <option value="slim" <?php if (($userProfile['body_type'] ?? '') == 'slim') echo 'selected'; ?>>Slim</option>
                        <option value="athletic" <?php if (($userProfile['body_type'] ?? '') == 'athletic') echo 'selected'; ?>>Athletic</option>
                        <option value="average" <?php if (($userProfile['body_type'] ?? '') == 'average') echo 'selected'; ?>>Average</option>
                        <option value="curvy" <?php if (($userProfile['body_type'] ?? '') == 'curvy') echo 'selected'; ?>>Curvy</option>
                        <option value="plus_size" <?php if (($userProfile['body_type'] ?? '') == 'plus_size') echo 'selected'; ?>>Plus Size</option>
                        <option value="muscular" <?php if (($userProfile['body_type'] ?? '') == 'muscular') echo 'selected'; ?>>Muscular</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td style="text-align:right;">
                    <label for="ethnicity">Ethnicity</label>
                </td>
                <td style="text-align:left;">
                    <select name="ethnicity" id="ethnicity">
                        <option value="caucasian" <?php if (($userProfile['ethnicity'] ?? '') == 'caucasian') echo 'selected'; ?>>Caucasian</option>
                        <option value="african" <?php if (($userProfile['ethnicity'] ?? '') == 'african') echo 'selected'; ?>>African</option>
                        <option value="asian" <?php if (($userProfile['ethnicity'] ?? '') == 'asian') echo 'selected'; ?>>Asian</option>
                        <option value="hispanic" <?php if (($userProfile['ethnicity'] ?? '') == 'hispanic') echo 'selected'; ?>>Hispanic</option>
                        <option value="middle_eastern" <?php if (($userProfile['ethnicity'] ?? '') == 'middle_eastern') echo 'selected'; ?>>Middle Eastern</option>
                        <option value="mixed" <?php if (($userProfile['ethnicity'] ?? '') == 'mixed') echo 'selected'; ?>>Mixed</option>
                        <option value="other" <?php if (($userProfile['ethnicity'] ?? '') == 'other') echo 'selected'; ?>>Other</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td style="text-align:right;">
                    <label for="hair_color">Hair Color</label>
                </td>
                <td style="text-align:left;">
                    <select name="hair_color" id="hair_color">
                        <option value="black" <?php if (($userProfile['hair_color'] ?? '') == 'black') echo 'selected'; ?>>Black</option>
                        <option value="brown" <?php if (($userProfile['hair_color'] ?? '') == 'brown') echo 'selected'; ?>>Brown</option>
                        <option value="blonde" <?php if (($userProfile['hair_color'] ?? '') == 'blonde') echo 'selected'; ?>>Blonde</option>
                        <option value="red" <?php if (($userProfile['hair_color'] ?? '') == 'red') echo 'selected'; ?>>Red</option>
                        <option value="gray" <?php if (($userProfile['hair_color'] ?? '') == 'gray') echo 'selected'; ?>>Gray</option>
                        <option value="white" <?php if (($userProfile['hair_color'] ?? '') == 'white') echo 'selected'; ?>>White</option>
                        <option value="other" <?php if (($userProfile['hair_color'] ?? '') == 'other') echo 'selected'; ?>>Other/Dyed</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td style="text-align:right;">
                    <label for="hair_style">Hair Style</label>
                </td>
                <td style="text-align:left;">
                    <select name="hair_style" id="hair_style">
                        <option value="short" <?php if (($userProfile['hair_style'] ?? '') == 'short') echo 'selected'; ?>>Short</option>
                        <option value="medium" <?php if (($userProfile['hair_style'] ?? '') == 'medium') echo 'selected'; ?>>Medium</option>
                        <option value="long" <?php if (($userProfile['hair_style'] ?? '') == 'long') echo 'selected'; ?>>Long</option>
                        <option value="curly" <?php if (($userProfile['hair_style'] ?? '') == 'curly') echo 'selected'; ?>>Curly</option>
                        <option value="wavy" <?php if (($userProfile['hair_style'] ?? '') == 'wavy') echo 'selected'; ?>>Wavy</option>
                        <option value="straight" <?php if (($userProfile['hair_style'] ?? '') == 'straight') echo 'selected'; ?>>Straight</option>
                        <option value="bald" <?php if (($userProfile['hair_style'] ?? '') == 'bald') echo 'selected'; ?>>Bald</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td style="text-align:right;">
                    <label for="eye_color">Eye Color</label>
                </td>
                <td style="text-align:left;">
                    <select name="eye_color" id="eye_color">
                        <option value="brown" <?php if (($userProfile['eye_color'] ?? '') == 'brown') echo 'selected'; ?>>Brown</option>
                        <option value="blue" <?php if (($userProfile['eye_color'] ?? '') == 'blue') echo 'selected'; ?>>Blue</option>
                        <option value="green" <?php if (($userProfile['eye_color'] ?? '') == 'green') echo 'selected'; ?>>Green</option>
                        <option value="hazel" <?php if (($userProfile['eye_color'] ?? '') == 'hazel') echo 'selected'; ?>>Hazel</option>
                        <option value="gray" <?php if (($userProfile['eye_color'] ?? '') == 'gray') echo 'selected'; ?>>Gray</option>
                        <option value="amber" <?php if (($userProfile['eye_color'] ?? '') == 'amber') echo 'selected'; ?>>Amber</option>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		Preferences
		<div class="smallText">
		Who you're looking to meet<br>
		</div>
		</div>

        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;">
                    <label for="age_preference_min">Minimum Age</label>
                </td>
                <td style="text-align:left;">
                    <input type="number" id="age_preference_min" name="age_preference_min" style="width:5em" value="<?php echo htmlspecialchars($userProfile['age_preference_min'] ?? '18'); ?>" min="18" max="99">
                </td>
            </tr>
            
            <tr>
                <td style="text-align:right;">
                    <label for="age_preference_max">Maximum Age</label>
                </td>
                <td style="text-align:left;">
                    <input type="number" id="age_preference_max" name="age_preference_max" style="width:5em" value="<?php echo htmlspecialchars($userProfile['age_preference_max'] ?? '65'); ?>" min="18" max="99">
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		Interests & About
		<div class="smallText">
		Tell us about yourself and what you enjoy<br>
		</div>
		</div>

        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;">
                    <label for="interests">Interests</label>
                </td>
                <td style="text-align:left;">
                    <textarea id="interests" name="interests" style="width:100%; min-width:300px; height:60px;" placeholder="List your interests, hobbies, and activities (comma separated)"><?php echo htmlspecialchars($userProfile['interests'] ?? ''); ?></textarea>
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		Adult Preferences
		<div class="smallText">
		Your preferences and what you're looking for (adults only, confidential)<br>
		</div>
		</div>

        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>What gender(s) are you seeking?</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantGenderMan" value="1" <?php if (($userProfile['b_wantGenderMan'] ?? '0') == '1') echo 'checked'; ?>> Men</label><br>
                        <label><input type="checkbox" name="b_wantGenderWoman" value="1" <?php if (($userProfile['b_wantGenderWoman'] ?? '0') == '1') echo 'checked'; ?>> Women</label><br>
                        <label><input type="checkbox" name="b_wantGenderTSWoman" value="1" <?php if (($userProfile['b_wantGenderTSWoman'] ?? '0') == '1') echo 'checked'; ?>> Trans Women</label><br>
                        <label><input type="checkbox" name="b_wantGenderTSMan" value="1" <?php if (($userProfile['b_wantGenderTSMan'] ?? '0') == '1') echo 'checked'; ?>> Trans Men</label><br>
                        <label><input type="checkbox" name="b_wantGenderCoupleMF" value="1" <?php if (($userProfile['b_wantGenderCoupleMF'] ?? '0') == '1') echo 'checked'; ?>> Couples (M/F)</label><br>
                        <label><input type="checkbox" name="b_wantGenderGroup" value="1" <?php if (($userProfile['b_wantGenderGroup'] ?? '0') == '1') echo 'checked'; ?>> Group encounters</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Body type preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantBodyTiny" value="1" <?php if (($userProfile['b_wantBodyTiny'] ?? '0') == '1') echo 'checked'; ?>> Petite/Tiny</label><br>
                        <label><input type="checkbox" name="b_wantBodySlim" value="1" <?php if (($userProfile['b_wantBodySlim'] ?? '0') == '1') echo 'checked'; ?>> Slim</label><br>
                        <label><input type="checkbox" name="b_wantBodyAverage" value="1" <?php if (($userProfile['b_wantBodyAverage'] ?? '0') == '1') echo 'checked'; ?>> Average</label><br>
                        <label><input type="checkbox" name="b_wantBodyMuscular" value="1" <?php if (($userProfile['b_wantBodyMuscular'] ?? '0') == '1') echo 'checked'; ?>> Muscular</label><br>
                        <label><input type="checkbox" name="b_wantBodyCurvy" value="1" <?php if (($userProfile['b_wantBodyCurvy'] ?? '0') == '1') echo 'checked'; ?>> Curvy</label><br>
                        <label><input type="checkbox" name="b_wantBodyThick" value="1" <?php if (($userProfile['b_wantBodyThick'] ?? '0') == '1') echo 'checked'; ?>> Thick</label><br>
                        <label><input type="checkbox" name="b_wantBodyBBW" value="1" <?php if (($userProfile['b_wantBodyBBW'] ?? '0') == '1') echo 'checked'; ?>> BBW/Plus Size</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Intimacy preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantSafeSex" value="1" <?php if (($userProfile['b_wantSafeSex'] ?? '0') == '1') echo 'checked'; ?>> Safe sex</label><br>
                        <label><input type="checkbox" name="b_wantOralGive" value="1" <?php if (($userProfile['b_wantOralGive'] ?? '0') == '1') echo 'checked'; ?>> Give oral</label><br>
                        <label><input type="checkbox" name="b_wantOralReceive" value="1" <?php if (($userProfile['b_wantOralReceive'] ?? '0') == '1') echo 'checked'; ?>> Receive oral</label><br>
                        <label><input type="checkbox" name="b_wantAnalTop" value="1" <?php if (($userProfile['b_wantAnalTop'] ?? '0') == '1') echo 'checked'; ?>> Anal (top)</label><br>
                        <label><input type="checkbox" name="b_wantAnalBottom" value="1" <?php if (($userProfile['b_wantAnalBottom'] ?? '0') == '1') echo 'checked'; ?>> Anal (bottom)</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Kinks and interests:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_wantVoyeur" value="1" <?php if (($userProfile['b_wantVoyeur'] ?? '0') == '1') echo 'checked'; ?>> Voyeurism</label><br>
                        <label><input type="checkbox" name="b_wantExhibitionist" value="1" <?php if (($userProfile['b_wantExhibitionist'] ?? '0') == '1') echo 'checked'; ?>> Exhibitionism</label><br>
                        <label><input type="checkbox" name="b_wantRoleplay" value="1" <?php if (($userProfile['b_wantRoleplay'] ?? '0') == '1') echo 'checked'; ?>> Role play</label><br>
                        <label><input type="checkbox" name="b_wantDom" value="1" <?php if (($userProfile['b_wantDom'] ?? '0') == '1') echo 'checked'; ?>> Dominant</label><br>
                        <label><input type="checkbox" name="b_wantSub" value="1" <?php if (($userProfile['b_wantSub'] ?? '0') == '1') echo 'checked'; ?>> Submissive</label><br>
                        <label><input type="checkbox" name="b_wantSpanking" value="1" <?php if (($userProfile['b_wantSpanking'] ?? '0') == '1') echo 'checked'; ?>> Spanking</label><br>
                        <label><input type="checkbox" name="b_wantFilming" value="1" <?php if (($userProfile['b_wantFilming'] ?? '0') == '1') echo 'checked'; ?>> Filming/Photography</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Meeting preferences:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_whereMyPlace" value="1" <?php if (($userProfile['b_whereMyPlace'] ?? '0') == '1') echo 'checked'; ?>> My place</label><br>
                        <label><input type="checkbox" name="b_whereYouHost" value="1" <?php if (($userProfile['b_whereYouHost'] ?? '0') == '1') echo 'checked'; ?>> Your place</label><br>
                        <label><input type="checkbox" name="b_whereHotelIPay" value="1" <?php if (($userProfile['b_whereHotelIPay'] ?? '0') == '1') echo 'checked'; ?>> Hotel (I pay)</label><br>
                        <label><input type="checkbox" name="b_whereHotelSplit" value="1" <?php if (($userProfile['b_whereHotelSplit'] ?? '0') == '1') echo 'checked'; ?>> Hotel (split cost)</label><br>
                        <label><input type="checkbox" name="b_whereCarDate" value="1" <?php if (($userProfile['b_whereCarDate'] ?? '0') == '1') echo 'checked'; ?>> Car date</label><br>
                        <label><input type="checkbox" name="b_whereBarClub" value="1" <?php if (($userProfile['b_whereBarClub'] ?? '0') == '1') echo 'checked'; ?>> Bar/Club first</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Relationship style:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_mutualLongTerm" value="1" <?php if (($userProfile['b_mutualLongTerm'] ?? '0') == '1') echo 'checked'; ?>> Long-term arrangement</label><br>
                        <label><input type="checkbox" name="b_mutualNoStrings" value="1" <?php if (($userProfile['b_mutualNoStrings'] ?? '0') == '1') echo 'checked'; ?>> No strings attached</label><br>
                        <label><input type="checkbox" name="b_poly" value="1" <?php if (($userProfile['b_poly'] ?? '0') == '1') echo 'checked'; ?>> Polyamorous</label><br>
                        <label><input type="checkbox" name="b_marriedTheyKnow" value="1" <?php if (($userProfile['b_marriedTheyKnow'] ?? '0') == '1') echo 'checked'; ?>> Married (partner knows)</label><br>
                        <label><input type="checkbox" name="b_marriedSecret" value="1" <?php if (($userProfile['b_marriedSecret'] ?? '0') == '1') echo 'checked'; ?>> Married (discreet)</label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		Health & Safety
		<div class="smallText">
		Important information for safe encounters<br>
		</div>
		</div>

        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Your health status (confidential):</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_haveWarts" value="1" <?php if (($userProfile['b_haveWarts'] ?? '0') == '1') echo 'checked'; ?>> HPV/Warts</label><br>
                        <label><input type="checkbox" name="b_haveHerpes" value="1" <?php if (($userProfile['b_haveHerpes'] ?? '0') == '1') echo 'checked'; ?>> Herpes</label><br>
                        <label><input type="checkbox" name="b_haveHepatitis" value="1" <?php if (($userProfile['b_haveHepatitis'] ?? '0') == '1') echo 'checked'; ?>> Hepatitis</label><br>
                        <label><input type="checkbox" name="b_haveOtherSTI" value="1" <?php if (($userProfile['b_haveOtherSTI'] ?? '0') == '1') echo 'checked'; ?>> Other STI</label><br>
                        <label><input type="checkbox" name="b_haveHIV" value="1" <?php if (($userProfile['b_haveHIV'] ?? '0') == '1') echo 'checked'; ?>> HIV</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Substance use:</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_smokeCigarettes" value="1" <?php if (($userProfile['b_smokeCigarettes'] ?? '0') == '1') echo 'checked'; ?>> Smoke cigarettes</label><br>
                        <label><input type="checkbox" name="b_lightDrinker" value="1" <?php if (($userProfile['b_lightDrinker'] ?? '0') == '1') echo 'checked'; ?>> Light drinker</label><br>
                        <label><input type="checkbox" name="b_heavyDrinker" value="1" <?php if (($userProfile['b_heavyDrinker'] ?? '0') == '1') echo 'checked'; ?>> Heavy drinker</label><br>
                        <label><input type="checkbox" name="b_smokeMarijuana" value="1" <?php if (($userProfile['b_smokeMarijuana'] ?? '0') == '1') echo 'checked'; ?>> Use marijuana</label><br>
                        <label><input type="checkbox" name="b_psychedelics" value="1" <?php if (($userProfile['b_psychedelics'] ?? '0') == '1') echo 'checked'; ?>> Use psychedelics</label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>Deal-breakers (will NOT match with):</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="b_noHerpes" value="1" <?php if (($userProfile['b_noHerpes'] ?? '0') == '1') echo 'checked'; ?>> No herpes</label><br>
                        <label><input type="checkbox" name="b_noWarts" value="1" <?php if (($userProfile['b_noWarts'] ?? '0') == '1') echo 'checked'; ?>> No HPV/warts</label><br>
                        <label><input type="checkbox" name="b_noHIV" value="1" <?php if (($userProfile['b_noHIV'] ?? '0') == '1') echo 'checked'; ?>> No HIV</label><br>
                        <label><input type="checkbox" name="b_noCigs" value="1" <?php if (($userProfile['b_noCigs'] ?? '0') == '1') echo 'checked'; ?>> No smokers</label><br>
                        <label><input type="checkbox" name="b_noHeavyDrink" value="1" <?php if (($userProfile['b_noHeavyDrink'] ?? '0') == '1') echo 'checked'; ?>> No heavy drinkers</label><br>
                        <label><input type="checkbox" name="b_noMarriedSecret" value="1" <?php if (($userProfile['b_noMarriedSecret'] ?? '0') == '1') echo 'checked'; ?>> No cheaters</label><br>
                        <label><input type="checkbox" name="b_mutualOnlyPlaySafe" value="1" <?php if (($userProfile['b_mutualOnlyPlaySafe'] ?? '0') == '1') echo 'checked'; ?>> Safe sex only</label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		About You
		<div class="smallText">
		Additional details about yourself<br>
		</div>
		</div>

        <table>
            <tbody>
            <tr>
                <td style="text-align:right; width:40%;">
                    <label for="publicText">Public Profile Text</label>
                </td>
                <td style="text-align:left;">
                    <textarea id="publicText" name="publicText" style="width:100%; min-width:300px; height:80px;" placeholder="Write something about yourself that everyone can see..."><?php echo htmlspecialchars($userProfile['publicText'] ?? ''); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <td style="text-align:right;">
                    <label for="privateText">Private Profile Text</label>
                </td>
                <td style="text-align:left;">
                    <textarea id="privateText" name="privateText" style="width:100%; min-width:300px; height:80px;" placeholder="More detailed info shared only with matches..."><?php echo htmlspecialchars($userProfile['privateText'] ?? ''); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <td style="text-align:right;">
                    <label for="firstName">First Name</label>
                </td>
                <td style="text-align:left;">
                    <input type="text" id="firstName" name="firstName" style="width:12em" value="<?php echo htmlspecialchars($userProfile['firstName'] ?? ''); ?>" placeholder="For matched connections">
                </td>
            </tr>
            </tbody>
        </table>

		<div class="captionText">
		AI Avatar Generation
		<div class="smallText">
		Generate a personalized avatar based on your profile<br>
		</div>
		</div>

        <div class="text-center" style="margin: 20px 0;">
            <button type="button" class="btn btn-secondary" onclick="generateAvatar()" id="generateAvatarBtn">
                <i class="fas fa-magic"></i> Generate AI Avatar
            </button>
            <div id="avatarStatus" style="margin-top: 10px;"></div>
            <div id="avatarPreview" style="margin-top: 10px;"></div>
        </div>

        <br>
        <div class="text-center">
            <button class="btn btn-lg btn-primary" type="submit" name="saveButtonName"
                    id="saveButtonID">Save Profile
            </button>
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
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    status.innerHTML = '<div class="alert alert-info">Creating your personalized avatar...</div>';
    preview.innerHTML = '';
    
    // Collect profile data
    const profileData = {
        age: document.getElementById('age').value,
        gender: document.getElementById('gender').value,
        hair_color: document.getElementById('hair_color').value,
        hair_style: document.getElementById('hair_style').value,
        eye_color: document.getElementById('eye_color').value,
        ethnicity: document.getElementById('ethnicity').value,
        body_type: document.getElementById('body_type').value
    };
    
    // Send AJAX request
    $.ajax({
        url: 'api/generate-avatar.php',
        method: 'POST',
        data: { profile: profileData },
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
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic"></i> Generate AI Avatar';
        }
    });
}
</script>

</body>
</html>

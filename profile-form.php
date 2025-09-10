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
            </tbody>
        </table>

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

</body>
</html>

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

        <div class="captionText">Preferences</div>
        <table>
            <tbody>
            <tr>
                <td colspan="2" style="text-align:left;">
                    <strong>What gender(s) are you seeking?</strong><br>
                    <div style="margin: 10px 0;">
                        <label><input type="checkbox" name="wantGenderMan" value="1" <?php if ($userProfile['wantGenderMan'] ?? 0) echo 'checked'; ?>> Men</label><br>
                        <label><input type="checkbox" name="wantGenderWoman" value="1" <?php if ($userProfile['wantGenderWoman'] ?? 0) echo 'checked'; ?>> Women</label><br>
                        <label><input type="checkbox" name="wantGenderNonBinary" value="1" <?php if ($userProfile['wantGenderNonBinary'] ?? 0) echo 'checked'; ?>> Non-Binary</label><br>
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
</script>

</body>
</html>

<?php

require __DIR__ . '/vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

echo "=================================================================\n";
echo "       FWBER PHOTO UPLOAD DIAGNOSTIC TOOL (v3)      \n";
echo "=================================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "User (Process): " . get_current_user() . " (UID: " . getmyuid() . ")\n";
echo "User (System): " . trim(shell_exec('whoami')) . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "-----------------------------------------------------------------\n\n";

echo "[1] Checking Required Extensions...\n";
$missingExtensions = [];
if (!extension_loaded('gd')) $missingExtensions[] = 'gd';
if (!extension_loaded('exif')) echo "    ! WARNING: 'exif' extension missing. Auto-orientation will be disabled.\n";
if (!extension_loaded('fileinfo')) $missingExtensions[] = 'fileinfo';

if (!empty($missingExtensions)) {
    echo "    !!! CRITICAL FAILURE: Missing extensions: " . implode(', ', $missingExtensions) . "\n";
    exit(1);
}
echo "    ✓ GD loaded\n";
echo "    ✓ Fileinfo loaded\n";

echo "\n[2] Checking Storage Permissions...\n";
$storagePath = __DIR__ . '/storage/app/public';
echo "    Target: $storagePath\n";

if (!is_dir($storagePath)) {
    echo "    ! Directory missing. Attempting to create...\n";
    if (!mkdir($storagePath, 0755, true)) {
        echo "    !!! CRITICAL FAILURE: Cannot create directory.\n";
        exit(1);
    }
    echo "    ✓ Directory created.\n";
}

$owner = fileowner($storagePath);
echo "    Directory Owner UID: " . $owner . "\n";
echo "    Is Writable? " . (is_writable($storagePath) ? "YES" : "NO") . "\n";

if (!is_writable($storagePath)) {
    echo "    !!! CRITICAL FAILURE: Directory is not writable by current user.\n";
    exit(1);
}

$testFile = $storagePath . '/diag_test_' . time() . '.txt';
if (@file_put_contents($testFile, 'test') === false) {
    echo "    !!! CRITICAL FAILURE: file_put_contents failed despite is_writable=true.\n";
    exit(1);
}
echo "    ✓ Write test passed.\n";
unlink($testFile);

echo "\n[3] Simulating Image Processing...\n";
try {
    ini_set('memory_limit', '512M');
    
    $manager = new ImageManager(new Driver());
    
    $img = imagecreatetruecolor(3000, 3000);
    $text_color = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 5, 10, 10, 'Diagnostic Test', $text_color);
    
    $tempInput = sys_get_temp_dir() . '/diag_input_' . time() . '.jpg';
    imagejpeg($img, $tempInput);
    imagedestroy($img);
    echo "    ✓ Created temp input image: $tempInput\n";

    $image = $manager->read($tempInput);
    echo "    ✓ Image read successfully.\n";

    if (extension_loaded('exif')) {
        $image->orient();
        echo "    ✓ Orientation fixed.\n";
    }

    $image = $image->scaleDown(width: 2000, height: 2000);
    echo "    ✓ Scaled down to 2000x2000.\n";

    $encoded = $image->toJpeg(quality: 80);
    echo "    ✓ Encoded to JPEG.\n";

    $finalPath = $storagePath . '/diag_output_' . time() . '.jpg';
    if (file_put_contents($finalPath, (string) $encoded) === false) {
        throw new Exception("Failed to write processed image to $finalPath");
    }
    echo "    ✓ Saved to storage: $finalPath\n";

    if (file_exists($tempInput)) unlink($tempInput);
    if (file_exists($finalPath)) unlink($finalPath);

} catch (Throwable $e) {
    echo "    !!! CRITICAL FAILURE in Image Processing !!!\n";
    echo "    Error: " . $e->getMessage() . "\n";
    echo "    Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "                   DIAGNOSTIC PASSED                             \n";
echo "=================================================================\n";
echo "Next Steps if 500 Error Persists on Production:\n";
echo "1. Run this script ON THE SERVER as the web user (e.g., sudo -u www-data php reproduce_issue.php)\n";
echo "2. Check 'storage/logs/laravel.log' for stack traces.\n";
echo "3. Verify 'config/media_analysis.php' driver settings.\n";

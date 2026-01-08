<?php

require __DIR__ . '/vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

echo "Starting reproduction script (v2 - Permission & GD Check)...\n";

if (!extension_loaded('gd')) {
    echo "CRITICAL ERROR: GD extension is NOT loaded. This will cause the application to crash when initializing the image driver.\n";
    exit(1);
}
echo "✓ GD extension is loaded.\n";

if (!extension_loaded('exif')) {
    echo "! WARNING: Exif extension is NOT loaded. The application code handles this, but orientation fixes will be skipped.\n";
} else {
    echo "✓ Exif extension is loaded.\n";
}

ini_set('memory_limit', '512M');

try {
    $storagePath = __DIR__ . '/storage/app/public';
    
    echo "Checking storage path: $storagePath\n";
    
    if (!is_dir($storagePath)) {
        echo "Directory does not exist. Attempting to create...\n";
        if (!mkdir($storagePath, 0755, true)) {
             throw new Exception("PERMISSION DENIED: Failed to create storage directory: $storagePath");
        }
        echo "✓ Directory created.\n";
    }

    $testFile = $storagePath . '/permission_test_' . time() . '.txt';
    echo "Testing write permissions at: $testFile\n";
    
    if (file_put_contents($testFile, 'test') === false) {
        throw new Exception("PERMISSION DENIED: Cannot write to $storagePath. The web server user may not have ownership or write access.");
    }
    echo "✓ Write permission confirmed.\n";
    unlink($testFile);
    echo "✓ Test file deleted.\n";

    echo "\nStarting Image Processing Simulation...\n";
    $manager = new ImageManager(new Driver());
    echo "ImageManager created.\n";

    $width = 3000;
    $height = 3000;
    $img = imagecreatetruecolor($width, $height);
    $textColor = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 5, 10, 10, 'Test Image', $textColor);
    
    $tempFile = sys_get_temp_dir() . '/test_image.jpg';
    imagejpeg($img, $tempFile);
    imagedestroy($img);
    echo "Temporary input image created at: $tempFile\n";

    echo "Reading image...\n";
    $image = $manager->read($tempFile);
    echo "Image read successfully.\n";

    if (extension_loaded('exif')) {
        echo "Attempting orient()...\n";
        $image->orient();
        echo "Orient success.\n";
    } else {
        echo "Skipping orient() (exif missing).\n";
    }

    echo "Scaling down (3000x3000 -> 2000x2000)...\n";
    $image = $image->scaleDown(width: 2000, height: 2000);
    echo "Scale success.\n";

    echo "Encoding to JPEG...\n";
    $encoded = $image->toJpeg(quality: 80);
    echo "Encoding success.\n";
    
    $finalPath = $storagePath . '/processed_test_' . time() . '.jpg';
    echo "Saving processed image to: $finalPath\n";
    
    if (file_put_contents($finalPath, (string) $encoded) === false) {
        throw new Exception("WRITE FAILED: Could not save processed image to $finalPath");
    }
    echo "✓ File saved successfully.\n";
    
    if (file_exists($tempFile)) unlink($tempFile);
    if (file_exists($finalPath)) unlink($finalPath);

    echo "\nSUCCESS: Reproduction script completed without errors.\n";
    echo "If this script passes but the 500 error persists, check:\n";
    echo "1. The actual Laravel Error Logs (storage/logs/laravel.log)\n";
    echo "2. Web Server user (e.g., www-data) permissions vs Command Line user permissions.\n";

} catch (Throwable $e) {
    echo "\n!!! CRASH DETECTED !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

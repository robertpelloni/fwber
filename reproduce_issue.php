<?php

require __DIR__ . '/fwber-backend/vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

try {
    echo "Checking extensions...\n";
    echo "GD: " . (extension_loaded('gd') ? 'OK' : 'MISSING') . "\n";
    echo "Exif: " . (extension_loaded('exif') ? 'OK' : 'MISSING') . "\n";
    echo "FileInfo: " . (extension_loaded('fileinfo') ? 'OK' : 'MISSING') . "\n";

    echo "Creating ImageManager...\n";
    $manager = new ImageManager(new Driver());

    echo "Creating dummy image...\n";
    $image = $manager->create(100, 100);
    
    echo "Attempting orient()...\n";
    $image = $image->orient();
    
    echo "Success!\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

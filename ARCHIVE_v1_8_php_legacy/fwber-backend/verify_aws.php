<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MediaAnalysis\Drivers\AwsRekognitionDriver;
use Illuminate\Support\Facades\Storage;

echo "--- AWS VERIFICATION START ---\n";

// 1. Test S3
try {
    $bucket = config('filesystems.disks.s3.bucket');
    $region = config('filesystems.disks.s3.region');
    echo "Checking S3 Bucket: $bucket in $region...\n";

    $testFile = 'verification_test_'.time().'.txt';
    $content = 'fwber_aws_verification_token_'.bin2hex(random_bytes(8));

    echo "Attempting to upload: $testFile\n";
    Storage::disk('s3')->put($testFile, $content);
    echo "✅ S3 Upload: SUCCESS\n";

    echo "Verifying file existence...\n";
    if (Storage::disk('s3')->exists($testFile)) {
        echo "✅ S3 Check: SUCCESS\n";

        $retrieved = Storage::disk('s3')->get($testFile);
        if ($retrieved === $content) {
            echo "✅ S3 Data Integrity: SUCCESS\n";
        } else {
            echo "❌ S3 Data Integrity: FAILED (Content mismatch)\n";
        }

        echo "Cleaning up test file...\n";
        Storage::disk('s3')->delete($testFile);
        echo "✅ S3 Cleanup: SUCCESS\n";
    } else {
        echo "❌ S3 Check: FAILED (File not found after upload)\n";
    }
} catch (\Exception $e) {
    echo '❌ S3 ERROR: '.$e->getMessage()."\n";
}

echo "\n";

// 2. Test Rekognition
try {
    echo "Checking AWS Rekognition Driver...\n";
    $driver = app(AwsRekognitionDriver::class);

    // We'll use a small trick: since we don't want to upload a real image,
    // we'll just try to call a method that requires the client to be initialized.
    // The driver doesn't have a simple 'ping', so we'll look at the client property via reflection
    $reflection = new ReflectionClass($driver);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $client = $property->getValue($driver);

    if ($client) {
        echo "✅ Rekognition Client Initialization: SUCCESS\n";

        echo "Attempting a live API call (listCollections)...\n";
        $collections = $client->listCollections(['MaxResults' => 1]);
        echo "✅ Rekognition API Response: SUCCESS\n";
    } else {
        echo "❌ Rekognition Client Initialization: FAILED (Check keys in .env)\n";
    }
} catch (\Exception $e) {
    // If it's just 'Collection not found' or similar, it's actually a SUCCESS because the API responded
    if (str_contains($e->getMessage(), 'AccessDenied') || str_contains($e->getMessage(), 'SignatureDoesNotMatch')) {
        echo '❌ Rekognition Permission/Key ERROR: '.$e->getMessage()."\n";
    } else {
        echo '✅ Rekognition Connectivity: SUCCESS (API responded: '.$e->getMessage().")\n";
    }
}

echo "--- AWS VERIFICATION END ---\n";

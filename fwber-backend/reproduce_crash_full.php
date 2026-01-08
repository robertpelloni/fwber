<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

use App\Http\Controllers\PhotoController;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Console\Kernel;
use Mockery as m;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

config(['database.default' => 'sqlite']);
config(['database.connections.sqlite' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
]]);
config(['app.debug' => true]);
config(['features.media_analysis' => true]);

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name')->default('Test User');
    $table->string('email')->default('test@example.com');
    $table->timestamps();
});

Schema::create('photos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id');
    $table->string('filename');
    $table->string('original_filename');
    $table->string('file_path');
    $table->string('thumbnail_path');
    $table->string('mime_type');
    $table->integer('file_size');
    $table->integer('width');
    $table->integer('height');
    $table->boolean('is_primary')->default(false);
    $table->boolean('is_private')->default(false);
    $table->decimal('unlock_price', 10, 2)->nullable();
    $table->integer('sort_order')->default(0);
    $table->json('metadata')->nullable();
    $table->timestamps();
});

echo "--- Starting Controller Simulation with Mocked Service ---\n";

$mockUser = m::mock('App\Models\User')->makePartial();
$mockUser->shouldReceive('getAttribute')->with('id')->andReturn(123);
$mockProfile = m::mock('stdClass');
$mockProfile->shouldReceive('update')->andReturn(true);
$mockUser->shouldReceive('profile')->andReturn($mockProfile);
$mockPhotosRelation = m::mock('stdClass');
$mockPhotosRelation->shouldReceive('count')->andReturn(0);
$mockUser->shouldReceive('photos')->andReturn($mockPhotosRelation);

Auth::shouldReceive('user')->andReturn($mockUser);
Auth::shouldReceive('id')->andReturn(123);

$mockMediaService = m::mock(MediaAnalysisInterface::class);
$mockMediaService->shouldReceive('analyze')
    ->once()
    ->andThrow(new Exception("Simulated Critical AWS Failure"));

$app->instance(MediaAnalysisInterface::class, $mockMediaService);

$tmpFile = sys_get_temp_dir() . '/test_photo.jpg';
$im = imagecreatetruecolor(100, 100);
imagejpeg($im, $tmpFile);
imagedestroy($im);

$uploadedFile = new UploadedFile(
    $tmpFile,
    'test_photo.jpg',
    'image/jpeg',
    null,
    true
);

$request = Request::create(
    '/api/photos',
    'POST',
    ['is_private' => 0], 
    [], 
    ['photo' => $uploadedFile], 
    [] 
);

try {
    $controller = $app->make(PhotoController::class);
    
    echo "Calling PhotoController::store()...\n";
    $response = $controller->store($request);
    
    $status = $response->getStatusCode();
    $content = $response->getData(true);
    
    echo "\n--- Response Received ---\n";
    echo "Status Code: $status\n";
    
    if ($status === 503) {
        echo "\n[SUCCESS] Controller caught the exception and returned 503.\n";
        echo "Error Message: " . ($content['message'] ?? 'N/A') . "\n";
        echo "Debug Info: " . ($content['debug_error'] ?? 'N/A') . "\n";
    } else {
        echo "\n[FAILURE] Unexpected status code: $status\n";
        print_r($content);
    }

} catch (\Throwable $e) {
    echo "\n[CRITICAL FAILURE] Uncaught Exception!\n";
    echo "Message: " . $e->getMessage() . "\n";
    exit(1);
}

@unlink($tmpFile);
m::close();
echo "\nTest Complete.\n";

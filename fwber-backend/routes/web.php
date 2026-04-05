<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'service' => 'fwber-backend',
        'status' => 'ok',
        'environment' => app()->environment(),
        'up' => url('/up'),
        'health' => url('/api/health'),
        'nodeinfo' => url('/nodeinfo/2.0'),
    ]);
});

// ActivityPub Discovery (Public, No Auth)
Route::group(['middleware' => ['edge.cache:60,30']], function () {
    Route::get('/.well-known/webfinger', [\App\Http\Controllers\WebFingerController::class, 'handle']);
    Route::get('/.well-known/nodeinfo', [\App\Http\Controllers\NodeInfoController::class, 'index']);
    Route::get('/nodeinfo/2.0', [\App\Http\Controllers\NodeInfoController::class, 'schema20']);
});

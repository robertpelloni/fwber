<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ActivityPub Discovery (Public, No Auth)
Route::group(['middleware' => ['edge.cache:60,30']], function () {
    Route::get('/.well-known/webfinger', [\App\Http\Controllers\WebFingerController::class, 'handle']);
    Route::get('/.well-known/nodeinfo', [\App\Http\Controllers\NodeInfoController::class, 'index']);
    Route::get('/nodeinfo/2.0', [\App\Http\Controllers\NodeInfoController::class, 'schema20']);
});

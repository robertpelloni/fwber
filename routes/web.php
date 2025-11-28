<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;

Route::get('/', function () {
    return view('welcome');
});

// Dev fallback for serving storage files when symlink is unavailable (e.g., Windows/PHP built-in server)
if (App::environment('local')) {
    Route::get('/storage/{path}', function (string $path) {
        $fullPath = storage_path('app/public/' . $path);
        if (!File::exists($fullPath)) {
            abort(404);
        }
        return response()->file($fullPath);
    })->where('path', '.*')->name('storage.local');

    Route::get('/files/{path}', function (string $path) {
        $fullPath = storage_path('app/public/' . $path);
        if (!File::exists($fullPath)) {
            abort(404);
        }
        return response()->file($fullPath);
    })->where('path', '.*')->name('files.local');
}

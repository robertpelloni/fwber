<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DiagnosticsController extends Controller
{
    /**
     * Secret endpoint to fetch production logs for debugging.
     * This is temporary and protected by a hardcoded secret.
     */
    public function logs(Request $request)
    {
        // Force OPcache reset to pick up \env() fix
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $secret = $request->header('X-Diagnostics-Secret');

        if ($secret !== 'fwber_debug_2026_!@#') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $logPath = storage_path('logs/laravel.log');

        if ($request->has('clear')) {
            File::put($logPath, 'Log cleared at '.now()->toIso8601String()."\n");
        }

        // Return some debug info first
        $info = 'TIME: '.now()->toIso8601String()."\n";
        $info .= 'PHP: '.PHP_VERSION."\n";
        $info .= 'File: '.__FILE__."\n";
        $info .= 'Base Path: '.base_path()."\n";
        $info .= 'Log Path: '.$logPath."\n";
        $info .= "----------------------------------\n\n";

        if (! File::exists($logPath)) {
            return response()->json(['message' => 'Log file not found'], 404);
        }

        // Return last 100 lines
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $reader = new \SplFileObject($logPath, 'r');
        $lines = new \LimitIterator($reader, max(0, $totalLines - 100), $totalLines);

        $output = $info;
        foreach ($lines as $line) {
            $output .= $line;
        }

        return response($output)->header('Content-Type', 'text/plain');
    }
}

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
        $secret = $request->header('X-Diagnostics-Secret');
        
        if ($secret !== 'fwber_debug_2026_!@#') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            return response()->json(['message' => 'Log file not found'], 404);
        }

        // Return last 100 lines
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $reader = new \SplFileObject($logPath, 'r');
        $lines = new \LimitIterator($reader, max(0, $totalLines - 100), $totalLines);
        
        $output = "";
        foreach ($lines as $line) {
            $output .= $line;
        }

        return response($output)->header('Content-Type', 'text/plain');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class LogController extends Controller
{
    /**
     * List available log files.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $logPath = storage_path('logs');
        $files = File::glob($logPath . '/*.log');
        
        $logs = collect($files)->map(function ($file) {
            return [
                'name' => basename($file),
                'size' => File::size($file),
                'updated_at' => date('Y-m-d H:i:s', File::lastModified($file)),
            ];
        })->sortByDesc('updated_at')->values();

        return response()->json($logs);
    }

    /**
     * Get the content of a specific log file.
     *
     * @param  string  $filename
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($filename, Request $request)
    {
        $path = storage_path('logs/' . $filename);

        if (!File::exists($path)) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        $lines = $request->input('lines', 1000);
        $content = $this->tailCustom($path, $lines);

        return response()->json([
            'filename' => $filename,
            'content' => $content,
        ]);
    }

    /**
     * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     * @author Torleif Berger, Lorenzo Stanco
     * @link http://stackoverflow.com/a/15025877/995958
     * @license MIT
     */
    private function tailCustom($filepath, $lines = 1, $adaptive = true)
    {
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;

        // Sets buffer size, according to the number of lines to retrieve.
        // This gives a performance boost when reading a few lines from the file.
        if (!$adaptive) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n") $lines -= 1;

        $output = '';
        $chunk = '';

        // Start reading
        $output = '';
        $chunk = '';

        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }

        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }

        fclose($f);
        return trim($output);
    }
}

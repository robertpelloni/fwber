<?php

namespace App\Http\Controllers;

use App\Jobs\ExportUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DataExportController extends Controller
{
    /**
     * Request a new data export.
     *
     * @OA\Post(
     *     path="/api/user/export",
     *     summary="Request a GDPR data export",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=202,
     *         description="Export started",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function requestExport(Request $request)
    {
        $user = Auth::user();

        // Rate limiting check could go here
        
        ExportUserData::dispatch($user);

        return response()->json([
            'message' => 'Data export started. This process may take a few minutes.'
        ], 202);
    }

    /**
     * Check status and get latest export link.
     *
     * @OA\Get(
     *     path="/api/user/export/status",
     *     summary="Get export status",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Export status",
     *         @OA\JsonContent(
     *             @OA\Property(property="available", type="boolean"),
     *             @OA\Property(property="download_url", type="string", nullable=true),
     *             @OA\Property(property="generated_at", type="string", nullable=true)
     *         )
     *     )
     * )
     */
    public function checkStatus(Request $request)
    {
        $userId = Auth::id();
        $directory = "exports/{$userId}";
        
        // Find latest zip file
        $files = Storage::files($directory);
        $latestFile = null;
        $latestTime = 0;

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $time = Storage::lastModified($file);
                if ($time > $latestTime) {
                    $latestTime = $time;
                    $latestFile = $file;
                }
            }
        }

        if ($latestFile) {
            return response()->json([
                'available' => true,
                'download_url' => route('api.user.export.download', ['filename' => basename($latestFile)]),
                'generated_at' => date('c', $latestTime)
            ]);
        }

        return response()->json([
            'available' => false,
            'download_url' => null,
            'generated_at' => null
        ]);
    }

    /**
     * Download the export file.
     *
     * @OA\Get(
     *     path="/api/user/export/{filename}",
     *     summary="Download export file",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="File download"
     *     )
     * )
     */
    public function download(Request $request, string $filename)
    {
        $userId = Auth::id();
        $path = "exports/{$userId}/{$filename}";

        if (!Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }
}

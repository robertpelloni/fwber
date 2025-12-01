<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaAnalysisController extends Controller
{
    protected MediaAnalysisService $analysisService;

    public function __construct(MediaAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Analyze a media file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'media_url' => 'required|url',
            'media_type' => 'required|in:image,video,audio',
        ]);

        $result = $this->analysisService->analyze(
            $request->input('media_url'),
            $request->input('media_type')
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}

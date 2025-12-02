<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AnalyzeMediaRequest;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Http\JsonResponse;

class MediaAnalysisController extends Controller
{
    protected MediaAnalysisInterface $analysisService;

    public function __construct(MediaAnalysisInterface $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Analyze a media file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(AnalyzeMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->analysisService->analyze(
            $validated['media_url'],
            $validated['media_type']
        );

        return response()->json([
            'status' => 'success',
            'data' => $result->toArray(),
        ]);
    }
}

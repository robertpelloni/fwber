<?php

namespace App\Http\Controllers;

use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Media Analysis Controller
 * 
 * Handles requests for analyzing media content (images, video, audio)
 * for safety, moderation, and content labeling.
 */
class MediaAnalysisController extends Controller
{
    protected MediaAnalysisInterface $service;

    public function __construct(MediaAnalysisInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Analyze media content
     * 
     * @OA\Post(
     *   path="/media/analyze",
     *   tags={"Media Analysis"},
     *   summary="Analyze media for safety",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"url", "type"},
     *       @OA\Property(property="url", type="string", format="uri", example="https://example.com/image.jpg"),
     *       @OA\Property(property="type", type="string", enum={"image", "video", "audio"}, example="image")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200, 
     *     description="Analysis result",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="safe", type="boolean"),
     *         @OA\Property(property="labels", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="moderation_labels", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="confidence", type="number", format="float")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'type' => 'required|in:image,video,audio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->service->analyze(
                $request->input('url'),
                $request->input('type')
            );

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error analyzing media',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\OptimizeContentRequest;
use App\Services\ContentGenerationService;
use App\Services\ContentOptimizationService;
use App\Models\User;
use App\Models\BulletinBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ContentGenerationController extends Controller
{
    protected ContentGenerationService $contentGenerationService;
    protected ContentOptimizationService $contentOptimizationService;

    public function __construct(
        ContentGenerationService $contentGenerationService,
        ContentOptimizationService $contentOptimizationService
    ) {
        $this->contentGenerationService = $contentGenerationService;
        $this->contentOptimizationService = $contentOptimizationService;
    }

    /**
     * Generate profile content for the authenticated user
     * 
     * @OA\Post(
     *     path="/content-generation/profile",
     *     tags={"Content Generation"},
     *     summary="Generate profile content",
     *     description="AI-generated profile bio, about section, and interests based on user preferences",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="personality", type="string", maxLength=255, example="Outgoing and adventurous"),
     *             @OA\Property(property="interests", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="goals", type="string", maxLength=1000, example="Looking for meaningful connections"),
     *             @OA\Property(property="style", type="string", enum={"casual", "professional", "humorous", "romantic"}, example="casual"),
     *             @OA\Property(property="target_audience", type="string", example="25-35 year olds")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile content generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="generated_at", type="string", format="date-time")
     *         )
     *     ),
    *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
     * )
     */
    public function generateProfileContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'personality' => 'nullable|string|max:255',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:255',
            'goals' => 'nullable|string|max:1000',
            'style' => 'nullable|string|in:casual,professional,humorous,romantic',
            'target_audience' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();
        $preferences = $request->only([
            'personality', 'interests', 'goals', 'style', 'target_audience'
        ]);

        try {
            $result = $this->contentGenerationService->generateProfileContent($user, $preferences);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'user_id' => $user->id,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Content generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate post suggestions for a bulletin board
     * 
     * @OA\Post(
     *     path="/content-generation/posts/{boardId}/suggestions",
     *     tags={"Content Generation"},
     *     summary="Generate post suggestions",
     *     description="AI-generated post ideas for a bulletin board based on context",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="boardId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="context", type="object",
     *                 @OA\Property(property="location", type="object"),
     *                 @OA\Property(property="time", type="string"),
     *                 @OA\Property(property="topics", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Post suggestions generated"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function generatePostSuggestions(Request $request, int $boardId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'context' => 'nullable|array',
            'context.location' => 'nullable|array',
            'context.time' => 'nullable|string',
            'context.topics' => 'nullable|array',
            'context.topics.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();
        $board = BulletinBoard::find($boardId);

        if (!$board) {
            return response()->json([
                'error' => 'Bulletin board not found'
            ], 404);
        }

        $context = $request->input('context', []);

        try {
            $result = $this->contentGenerationService->generatePostSuggestions($board, $user, $context);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'board_id' => $boardId,
                'user_id' => $user->id,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Post suggestions generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate conversation starters
     * 
     * @OA\Post(
     *     path="/content-generation/conversation-starters",
     *     tags={"Content Generation"},
     *     summary="Generate conversation starters",
     *     description="AI-generated conversation openers based on context and match data",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="context", type="object",
     *                 @OA\Property(property="type", type="string", enum={"general", "romantic", "casual", "professional"}),
     *                 @OA\Property(property="target_user", type="object"),
     *                 @OA\Property(property="previous_messages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="hints", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
    *     @OA\Response(response=200, description="Conversation starters generated"),
    *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
     * )
     */
    public function generateConversationStarters(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'context' => 'nullable|array',
            'context.type' => 'nullable|string|in:general,romantic,casual,professional',
            'context.target_user' => 'nullable|array',
            'context.previous_messages' => 'nullable|array',
            'context.hints' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();
        $context = $request->input('context', []);

        try {
            $result = $this->contentGenerationService->generateConversationStarters($user, $context);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'user_id' => $user->id,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Conversation starters generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize content for better engagement, clarity, safety, and relevance
     * 
     * @OA\Post(
     *     path="/content-generation/optimize",
     *     tags={"Content Generation"},
     *     summary="Optimize content",
     *     description="AI-powered content optimization for engagement, clarity, safety, and relevance",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", maxLength=2000, example="Original content to optimize"),
     *             @OA\Property(property="context", type="object"),
     *             @OA\Property(property="optimization_types", type="array",
     *                 @OA\Items(type="string", enum={"engagement", "clarity", "safety", "relevance"})
     *             )
     *         )
     *     ),
    *     @OA\Response(response=200, description="Content optimized",
    *         @OA\JsonContent(ref="#/components/schemas/ContentOptimizationResponse")
    *     ),
    *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
     * )
     */
    public function optimizeContent(OptimizeContentRequest $request): JsonResponse
    {
        $content = $request->validated('content');
        $context = $request->input('context', []);
        $optimizationTypes = $request->validated('optimization_types', ['engagement', 'clarity', 'safety', 'relevance']);

        try {
            $result = $this->contentOptimizationService->optimizeContent($content, $context);
            
            // Filter results based on requested optimization types
            if (!empty($optimizationTypes)) {
                $filteredImprovements = [];
                foreach ($optimizationTypes as $type) {
                    if (isset($result['improvements'][$type])) {
                        $filteredImprovements[$type] = $result['improvements'][$type];
                    }
                }
                $result['improvements'] = $filteredImprovements;
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'user_id' => Auth::id(),
                'optimized_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Content optimization failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content generation statistics
     * 
     * @OA\Get(
     *     path="/content-generation/stats",
     *     tags={"Content Generation"},
     *     summary="Get generation statistics",
     *     description="Retrieve statistics about AI content generation usage and performance",
     *     security={{"bearerAuth":{}}},
    *     @OA\Response(response=200, description="Generation statistics",
    *         @OA\JsonContent(ref="#/components/schemas/GenerationStatsResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function getGenerationStats(): JsonResponse
    {
        try {
            $stats = [
                'total_generations' => 0, // This would come from a database query
                'successful_generations' => 0,
                'failed_generations' => 0,
                'average_generation_time' => 0,
                'most_popular_types' => [],
                'user_satisfaction' => 0,
                'generated_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve generation statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content optimization statistics
     * 
     * @OA\Get(
     *     path="/content-generation/optimization-stats",
     *     tags={"Content Generation"},
     *     summary="Get optimization statistics",
     *     description="Retrieve statistics about content optimization usage and improvements",
     *     security={{"bearerAuth":{}}},
    *     @OA\Response(response=200, description="Optimization statistics",
    *         @OA\JsonContent(ref="#/components/schemas/OptimizationStatsResponse")
    *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function getOptimizationStats(): JsonResponse
    {
        try {
            $stats = [
                'total_optimizations' => 0, // This would come from a database query
                'successful_optimizations' => 0,
                'failed_optimizations' => 0,
                'average_improvement_score' => 0,
                'most_common_improvements' => [],
                'optimization_types_usage' => [],
                'generated_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve optimization statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Provide feedback on generated content
     * 
     * @OA\Post(
     *     path="/content-generation/feedback",
     *     tags={"Content Generation"},
     *     summary="Submit content feedback",
     *     description="Provide feedback on AI-generated content to improve future suggestions",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"suggestion_id", "content_type", "rating"},
     *             @OA\Property(property="suggestion_id", type="string", format="uuid"),
     *             @OA\Property(property="content_type", type="string", enum={"profile", "post_suggestion", "conversation_starter"}),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="feedback", type="string", maxLength=1000),
     *             @OA\Property(property="improvements", type="array", @OA\Items(type="string"))
     *         )
     *     ),
    *     @OA\Response(response=200, description="Feedback submitted"),
    *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
    *     @OA\Response(response=400, ref="#/components/responses/BadRequest")
     * )
     */
    public function submitContentFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'suggestion_id' => 'required|string|uuid',
            'content_type' => 'required|string|in:profile,post_suggestion,conversation_starter',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'improvements' => 'nullable|array',
            'improvements.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();
        $feedbackData = $request->only([
            'suggestion_id', 'content_type', 'rating', 'feedback', 'improvements'
        ]);

        try {
            // Store feedback in database (this would be implemented)
            // For now, just log the feedback with the suggestion ID
            \Log::info('Content feedback received', [
                'user_id' => $user->id,
                'suggestion_id' => $feedbackData['suggestion_id'],
                'feedback' => $feedbackData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback submitted successfully',
                'feedback_id' => uniqid('feedback_'),
                'suggestion_id' => $feedbackData['suggestion_id'],
                'submitted_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to submit feedback',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's content generation history
     * 
     * @OA\Get(
     *     path="/content-generation/history",
     *     tags={"Content Generation"},
     *     summary="Get generation history",
     *     description="Retrieve user's AI content generation history with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=10, maximum=100)),
     *     @OA\Response(response=200, description="Generation history retrieved"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function getGenerationHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        try {
            // This would query the database for user's generation history
            $history = [
                'generations' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'has_more' => false,
                ],
                'generated_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve generation history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete generated content
     * 
     * @OA\Delete(
     *     path="/content-generation/content/{contentId}",
     *     tags={"Content Generation"},
     *     summary="Delete generated content",
     *     description="Remove AI-generated content from history",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="contentId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Content deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content deleted successfully"),
     *             @OA\Property(property="content_id", type="string", example="gen_abc123"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function deleteGeneratedContent(Request $request, string $contentId): JsonResponse
    {
        $user = Auth::user();

        try {
            // This would delete the content from the database
            // For now, just return success
            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully',
                'content_id' => $contentId,
                'deleted_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete content',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

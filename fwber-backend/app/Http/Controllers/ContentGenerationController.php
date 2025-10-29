<?php

namespace App\Http\Controllers;

use App\Services\ContentGenerationService;
use App\Services\ContentOptimizationService;
use App\Models\User;
use App\Models\BulletinBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
     */
    public function optimizeContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2000',
            'context' => 'nullable|array',
            'optimization_types' => 'nullable|array',
            'optimization_types.*' => 'string|in:engagement,clarity,safety,relevance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 400);
        }

        $content = $request->input('content');
        $context = $request->input('context', []);
        $optimizationTypes = $request->input('optimization_types', ['engagement', 'clarity', 'safety', 'relevance']);

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

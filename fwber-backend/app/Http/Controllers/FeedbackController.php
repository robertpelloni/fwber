<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Submit feedback
     * 
     * @OA\Post(
     *   path="/feedback",
     *   tags={"Feedback"},
     *   summary="Submit user feedback",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"message", "category"},
     *       @OA\Property(property="category", type="string", enum={"bug", "feature", "general", "safety"}),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="page_url", type="string"),
     *       @OA\Property(property="metadata", type="object")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Feedback submitted"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|in:bug,feature,general,safety',
            'message' => 'required|string|max:2000',
            'page_url' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $feedback = Feedback::create([
                'user_id' => auth()->id(), // Nullable if not logged in, but usually we require auth
                'category' => $request->category,
                'message' => $request->message,
                'page_url' => $request->page_url,
                'metadata' => $request->metadata,
            ]);

            Log::info('Feedback received', ['id' => $feedback->id, 'category' => $feedback->category]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!',
                'data' => $feedback
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error saving feedback', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}

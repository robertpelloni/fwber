<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Translate a message
     * 
     * @OA\Post(
     *   path="/messages/translate",
     *   tags={"Messages"},
     *   summary="Translate text",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"text", "target_language"},
     *       @OA\Property(property="text", type="string"),
     *       @OA\Property(property="target_language", type="string", example="es")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Translated text"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
    public function translate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:1000',
            'target_language' => 'required|string|size:2', // ISO 639-1
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $translated = $this->translationService->translate(
            $request->input('text'),
            $request->input('target_language')
        );

        return response()->json([
            'original' => $request->input('text'),
            'translated' => $translated,
            'target_language' => $request->input('target_language')
        ]);
    }
}

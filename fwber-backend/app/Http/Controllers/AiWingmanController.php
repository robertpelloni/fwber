<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wingman\AnalyzeDraftRequest;
use App\Http\Requests\Wingman\AnalyzeQuirkRequest;
use App\Http\Requests\Wingman\RoastPublicRequest;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMatch;
use App\Models\ViralContent;
use App\Services\AiWingmanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AiWingmanController extends Controller
{
    public function __construct(
        protected AiWingmanService $wingmanService
    ) {}

    protected function saveViralContent(User $user, string $type, array $content): ?string
    {
        // Deploy targets may not have the restored table yet. We degrade gracefully
        // instead of breaking the AI response entirely.
        if (! Schema::hasTable('viral_contents')) {
            return null;
        }

        $viral = ViralContent::create([
            'user_id' => $user->id,
            'type' => $type,
            'content' => $content,
        ]);

        return $viral->id;
    }

    protected function resolveMatchedUserOrFail(User $user, string $targetId): User|JsonResponse
    {
        $target = User::findOrFail($targetId);

        $isMatched = UserMatch::where(function ($q) use ($user, $targetId) {
            $q->where('user1_id', $user->id)->where('user2_id', $targetId);
        })->orWhere(function ($q) use ($user, $targetId) {
            $q->where('user1_id', $targetId)->where('user2_id', $user->id);
        })->where('is_active', true)->exists();

        if (! $isMatched) {
            return response()->json(['error' => 'You are not matched with this user.'], 403);
        }

        return $target;
    }

    public function getIceBreakers(Request $request, string $matchId): JsonResponse
    {
        $user = Auth::user();
        $match = $this->resolveMatchedUserOrFail($user, $matchId);
        if ($match instanceof JsonResponse) {
            return $match;
        }

        return response()->json([
            'suggestions' => $this->wingmanService->generateIceBreakers($user, $match),
        ]);
    }

    public function getReplySuggestions(Request $request, string $matchId): JsonResponse
    {
        $user = Auth::user();
        $match = $this->resolveMatchedUserOrFail($user, $matchId);
        if ($match instanceof JsonResponse) {
            return $match;
        }

        $messages = Message::where(function ($q) use ($user, $matchId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $matchId);
        })->orWhere(function ($q) use ($user, $matchId) {
            $q->where('sender_id', $matchId)->where('receiver_id', $user->id);
        })->orderBy('created_at', 'asc')->take(10)->get()->map(function (Message $message) {
            return [
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'transcription' => $message->transcription,
                'message_type' => $message->message_type,
            ];
        })->toArray();

        $suggestions = $messages === []
            ? $this->wingmanService->generateIceBreakers($user, $match)
            : $this->wingmanService->generateReplySuggestions($user, $match, $messages);

        return response()->json(['suggestions' => $suggestions]);
    }

    public function analyzeDraft(AnalyzeDraftRequest $request, string $matchId): JsonResponse
    {
        $user = Auth::user();
        $match = $this->resolveMatchedUserOrFail($user, $matchId);
        if ($match instanceof JsonResponse) {
            return $match;
        }

        $messages = Message::where(function ($q) use ($user, $matchId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $matchId);
        })->orWhere(function ($q) use ($user, $matchId) {
            $q->where('sender_id', $matchId)->where('receiver_id', $user->id);
        })->orderBy('created_at', 'asc')->take(10)->get()->map(function (Message $message) {
            return [
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'transcription' => $message->transcription,
                'message_type' => $message->message_type,
            ];
        })->toArray();

        return response()->json(
            $this->wingmanService->analyzeDraftMessage($user, $match, $request->input('draft'), $messages)
        );
    }

    public function getProfileAnalysis(Request $request): JsonResponse
    {
        return response()->json($this->wingmanService->analyzeProfile(Auth::user()));
    }

    public function getDateIdeas(Request $request, string $matchId): JsonResponse
    {
        $user = Auth::user();
        $match = $this->resolveMatchedUserOrFail($user, $matchId);
        if ($match instanceof JsonResponse) {
            return $match;
        }

        return response()->json([
            'ideas' => $this->wingmanService->suggestDateIdeas($user, $match, $request->input('location')),
        ]);
    }

    public function roastPublic(RoastPublicRequest $request): JsonResponse
    {
        $mode = $request->input('mode', 'roast');

        return response()->json([
            'roast' => $this->wingmanService->roastGeneric(
                $request->input('name'),
                $request->input('job'),
                $request->input('trait'),
                $mode,
            ),
            'is_preview' => true,
            'cta' => 'Sign up to save this roast and share it with friends!',
        ]);
    }

    public function roastProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        $mode = in_array($request->input('mode', 'roast'), ['roast', 'hype'], true)
            ? $request->input('mode', 'roast')
            : 'roast';

        $result = $this->wingmanService->roastProfile($user, $mode);
        $shareId = $this->saveViralContent($user, $mode, ['text' => $result]);

        return response()->json([
            'roast' => $result,
            'share_id' => $shareId,
        ]);
    }

    public function checkVibe(Request $request): JsonResponse
    {
        $user = Auth::user();
        $result = $this->wingmanService->checkVibe($user);
        $shareId = $this->saveViralContent($user, 'vibe', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    public function predictFortune(Request $request): JsonResponse
    {
        $user = Auth::user();
        $fortune = $this->wingmanService->predictFortune($user);
        $shareId = $this->saveViralContent($user, 'fortune', ['text' => $fortune]);

        return response()->json([
            'fortune' => $fortune,
            'share_id' => $shareId,
        ]);
    }

    public function getCosmicMatch(Request $request): JsonResponse
    {
        $user = Auth::user();
        $result = $this->wingmanService->predictCosmicMatch($user);
        $shareId = $this->saveViralContent($user, 'cosmic', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    public function findNemesis(Request $request): JsonResponse
    {
        $user = Auth::user();
        $result = $this->wingmanService->findNemesis($user);
        $shareId = $this->saveViralContent($user, 'nemesis', $result);

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    public function checkQuirk(AnalyzeQuirkRequest $request): JsonResponse
    {
        $user = Auth::user();
        $quirk = $request->input('quirk');
        $result = $this->wingmanService->analyzeQuirk($quirk);
        $shareId = $this->saveViralContent($user, 'quirk', array_merge($result, ['quirk' => $quirk]));

        return response()->json(array_merge($result, ['share_id' => $shareId]));
    }

    public function compatibilityAudit(Request $request, string $targetId): JsonResponse
    {
        $user = Auth::user();
        $target = $this->resolveMatchedUserOrFail($user, $targetId);
        if ($target instanceof JsonResponse) {
            return $target;
        }

        $audit = $this->wingmanService->generateCompatibilityAudit($user, $target);
        $shareId = $this->saveViralContent($user, 'compatibility_audit', $audit);

        return response()->json(array_merge($audit, ['share_id' => $shareId]));
    }
}

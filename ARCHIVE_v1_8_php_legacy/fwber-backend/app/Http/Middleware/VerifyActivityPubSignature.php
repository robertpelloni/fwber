<?php

namespace App\Http\Middleware;

use App\Services\HttpSignatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyActivityPubSignature
{
    public function __construct(
        protected HttpSignatureService $httpSignatureService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $verification = $this->httpSignatureService->verifyRequest($request);

        if (! $verification['valid']) {
            Log::warning('ActivityPub: rejected unsigned or invalid inbox request', [
                'path' => $request->path(),
                'actor' => data_get($request->json()->all(), 'actor'),
                'reason' => $verification['error'] ?? 'Unknown signature error',
            ]);

            return response()->json([
                'error' => $verification['error'] ?? 'Invalid ActivityPub signature',
            ], 401);
        }

        $request->attributes->set('verified_activitypub_actor_uri', $verification['actor_uri'] ?? null);
        $request->attributes->set('verified_activitypub_key_id', $verification['key_id'] ?? null);

        return $next($request);
    }
}

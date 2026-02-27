<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZKProximityService;
use Illuminate\Support\Facades\Auth;

class ZKProximityController extends Controller
{
    protected $zkService;

    public function __construct(ZKProximityService $zkService)
    {
        $this->zkService = $zkService;
    }

    /**
     * Verify a zero-knowledge proximity proof from the client.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'target_entity_type' => 'required|string|in:venue,user,event,chatroom',
            'target_entity_id' => 'required|integer',
            'proof_payload' => 'required|array',
            'public_signals' => 'required|array',
            'proof_hash' => 'required|string',
        ]);

        $userId = Auth::id();

        // Pass circuit evaluation to the Service
        $isValid = $this->zkService->verifyProof(
            $request->proof_payload, 
            $request->public_signals
        );

        if (!$isValid) {
            return response()->json([
                'message' => 'Zero-Knowledge Proximity Proof failed verification.',
                'verified' => false
            ], 422);
        }

        // Record the mathematical presence 
        $proofRecord = $this->zkService->recordVerifiedPresence(
            $userId, 
            $request->target_entity_type, 
            $request->target_entity_id, 
            $request->proof_hash
        );

        return response()->json([
            'message' => 'Proximity Cryptographically Verified',
            'verified' => true,
            'record_id' => $proofRecord->id
        ], 200);
    }
}

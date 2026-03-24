<?php

namespace App\Http\Controllers;

use App\Services\Security\ZkIdentityVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdentityController extends Controller
{
    public function __construct(
        private readonly ZkIdentityVerificationService $idService
    ) {}

    /**
     * Submit a ZK-Identity Proof for verification.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'proof' => 'required|string',
            'issuer' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        $success = $this->idService->verifyProof($user, $request->only(['proof', 'issuer']));

        if ($success) {
            return response()->json([
                'message' => 'Identity verified successfully via Zero-Knowledge proof.',
                'is_id_verified' => true,
                'verified_at' => $user->profile->id_verified_at,
            ]);
        }

        return response()->json([
            'message' => 'Invalid identity proof.',
            'is_id_verified' => false,
        ], 422);
    }

    /**
     * Get the current user's identity status.
     */
    public function status()
    {
        $profile = Auth::user()->profile;

        return response()->json([
            'is_id_verified' => (bool)$profile->is_id_verified,
            'issuer' => $profile->zk_id_issuer,
            'verified_at' => $profile->id_verified_at,
        ]);
    }
}

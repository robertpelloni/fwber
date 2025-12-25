<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\TokenDistributionService;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, TokenDistributionService $tokenService)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'referral_code' => $tokenService->generateReferralCode(),
        ]);

        // Process tokens
        $tokenService->processSignupBonus($user, $validated['referral_code'] ?? null);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'two_factor' => true,
                'message' => 'Two factor authentication required.',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('profile')->loadCount(['referrals', 'vouches']);

        // Ensure the count is accessible via the attribute name expected by frontend
        // loadCount adds 'referrals_count' attribute.
        return $user;
    }

    public function checkReferralCode($code)
    {
        $referrer = User::where('referral_code', $code)->withCount('vouches')->first();

        if (!$referrer) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer_name' => $referrer->name,
            'referrer_avatar' => $referrer->avatar_url, // Assuming this exists
            'has_golden_tickets' => $referrer->golden_tickets_remaining > 0,
            'vouches_count' => $referrer->vouches_count,
        ]);
    }

    public function loginWithWallet(Request $request)
    {
        $validated = $request->validate([
            'wallet_address' => 'required|string',
            'signature' => 'required|string',
            'message' => 'required|string',
        ]);

        $walletAddress = $validated['wallet_address'];
        $signature = $validated['signature'];
        $message = $validated['message'];

        // Verify the signature using the Node.js script
        $scriptPath = base_path('scripts/solana/verify_signature.cjs');
        $command = "node {$scriptPath} " . escapeshellarg($message) . " " . escapeshellarg($signature) . " " . escapeshellarg($walletAddress);

        $output = shell_exec($command);
        $result = json_decode($output, true);

        if (!$result || !isset($result['verified']) || !$result['verified']) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid wallet signature.'],
            ]);
        }

        // Check if user exists with this wallet address
        $user = User::where('wallet_address', $walletAddress)->first();

        if (!$user) {
            // Create a new user for this wallet
            // We use a placeholder email and a random password
            $shortAddress = substr($walletAddress, 0, 6) . '...' . substr($walletAddress, -4);
            $user = User::create([
                'name' => "Wallet User {$shortAddress}",
                'email' => "wallet_{$walletAddress}@fwber.com", // Unique placeholder
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                'wallet_address' => $walletAddress,
                'token_balance' => 0, // Signup bonus handled below
            ]);

            // Distribute signup bonus for new wallet user
             /** @var \App\Services\TokenDistributionService $tokenService */
             $tokenService = app(\App\Services\TokenDistributionService::class);
             $tokenService->processSignupBonus($user);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'is_new_user' => $user->wasRecentlyCreated,
        ]);
    }
}

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
        $user = $request->user()->load('profile')->loadCount('referrals');

        // Ensure the count is accessible via the attribute name expected by frontend
        // loadCount adds 'referrals_count' attribute.
        return $user;
    }

    public function checkReferralCode($code)
    {
        $referrer = User::where('referral_code', $code)->first();

        if (!$referrer) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer_name' => $referrer->name,
            'referrer_avatar' => $referrer->avatar_url, // Assuming this exists
            'has_golden_tickets' => $referrer->golden_tickets_remaining > 0,
        ]);
    }
}

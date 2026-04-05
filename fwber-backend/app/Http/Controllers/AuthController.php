<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\ReferralCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ReferralCommissionService $referralCommissionService)
    {
    }

    private function hydrateAuthUser(User $user): User
    {
        // The frontend still expects social-proof and referral counters on the
        // auth payload. We hydrate them here so login/register/me all share the
        // same richer response shape.
        $query = $user->load('profile');

        $counts = [];
        if (Schema::hasTable('vouches')) {
            $counts[] = 'vouches';
        }
        if (Schema::hasColumn('users', 'referrer_id')) {
            $counts[] = 'referrals';
        }

        return empty($counts) ? $query : $query->loadCount($counts);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        $referrer = $this->referralCommissionService->lookupReferrer($validated['referral_code'] ?? null);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ];

        if (Schema::hasColumn('users', 'referrer_id')) {
            $payload['referrer_id'] = $referrer?->id;
        }

        $user = User::create($payload);

        $user = $this->referralCommissionService->ensureReferralCode($user);

        if (Schema::hasColumn('users', 'referrer_id') && $referrer && $referrer->id !== $user->id) {
            $this->referralCommissionService->awardSignupRewards($user, $referrer);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->hydrateAuthUser($user->fresh()),
        ]);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $isStandardAuth = Hash::check($validated['password'], $user->password);
        $isDecoyAuth = ! $isStandardAuth && $user->decoy_password && Hash::check($validated['password'], $user->decoy_password);

        if (! $isStandardAuth && ! $isDecoyAuth) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        if ($isDecoyAuth) {
            if (! $user->decoy_user_id) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials'],
                ]);
            }
            $user = User::find($user->decoy_user_id);
            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials'],
                ]);
            }
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
            'user' => $this->hydrateAuthUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return $this->hydrateAuthUser($request->user());
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    /**
     * Verify the 2FA code or recovery code and issue a token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'Invalid request.'], 400);
        }

        // Note: In a real flow, we should verify the password first or have a temporary signed token.
        // But since we are intercepting the login flow, the client should have already sent credentials
        // and received a "2fa_required" response.
        // Ideally, we should pass a temporary token from the login step to this step.
        // For simplicity in this implementation, we will require password again OR assume the client
        // sends the credentials again with the code.
        
        // Let's require password again for security, or use a signed "challenge" token.
        // Using a signed challenge token is better.
        
        // However, to keep it simple and stateless:
        // The client sends email + password + code.
        
        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($request->code) {
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'code' => ['The provided two factor authentication code was invalid.'],
                ]);
            }
        } elseif ($request->recovery_code) {
            $recoveryCodes = $user->getTwoFactorRecoveryCodes();
            $key = array_search($request->recovery_code, $recoveryCodes);

            if ($key === false) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided recovery code was invalid.'],
                ]);
            }

            unset($recoveryCodes[$key]);
            $user->forceFill([
                'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
            ])->save();
        } else {
            return response()->json(['message' => 'Code or recovery code required.'], 400);
        }

        // Issue token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}

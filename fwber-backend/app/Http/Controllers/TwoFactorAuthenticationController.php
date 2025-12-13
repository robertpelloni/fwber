<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Enable 2FA for the user.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(collect(range(1, 8))->map(function () {
                return \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
            })->all())),
        ])->save();

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $user->twoFactorQrCodeUrl(),
            'recovery_codes' => $user->getTwoFactorRecoveryCodes(),
        ]);
    }

    /**
     * Get the QR code for 2FA.
     */
    public function showQrCode(Request $request)
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA is not enabled.'], 400);
        }

        return response()->json([
            'svg' => $user->twoFactorQrCodeSvg(),
            'url' => $user->twoFactorQrCodeUrl(),
        ]);
    }

    /**
     * Confirm 2FA with a code.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA is not enabled.'], 400);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return response()->json(['message' => 'Two factor authentication confirmed and enabled.']);
    }

    /**
     * Disable 2FA.
     */
    public function destroy(Request $request)
    {
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Two factor authentication disabled.']);
    }

    /**
     * Get recovery codes.
     */
    public function recoveryCodes(Request $request)
    {
        if (! $request->user()->two_factor_secret || ! $request->user()->two_factor_confirmed_at) {
            return response()->json(['message' => '2FA is not enabled.'], 400);
        }

        return response()->json([
            'recovery_codes' => $request->user()->getTwoFactorRecoveryCodes(),
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        if (! $request->user()->two_factor_secret || ! $request->user()->two_factor_confirmed_at) {
            return response()->json(['message' => '2FA is not enabled.'], 400);
        }

        $request->user()->replaceTwoFactorRecoveryCodes();

        return response()->json([
            'recovery_codes' => $request->user()->getTwoFactorRecoveryCodes(),
        ]);
    }
}

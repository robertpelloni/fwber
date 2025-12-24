<?php

namespace App\Models\Traits;

use PragmaRX\Google2FA\Google2FA;

trait HasTwoFactorAuthentication
{
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        try {
            return ! is_null($this->two_factor_secret) &&
                   ! is_null($this->two_factor_confirmed_at);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getTwoFactorRecoveryCodes(): array
    {
        return json_decode(decrypt($this->two_factor_recovery_codes), true) ?? [];
    }

    public function replaceTwoFactorRecoveryCodes(): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(collect(range(1, 8))->map(function () {
                return \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
            })->all())),
        ])->save();
    }

    public function twoFactorQrCodeSvg(): string
    {
        $google2fa = new Google2FA();

        return $google2fa->getQRCodeInline(
            config('app.name'),
            $this->email,
            decrypt($this->two_factor_secret)
        );
    }

    public function twoFactorQrCodeUrl(): string
    {
        $google2fa = new Google2FA();

        return $google2fa->getQRCodeUrl(
            config('app.name'),
            $this->email,
            decrypt($this->two_factor_secret)
        );
    }
}

<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->where(function ($query) {
                $query->whereNull('referral_code')
                    ->orWhere('referral_code', '');
            })
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    do {
                        $code = Str::upper(Str::random(8));
                    } while (User::where('referral_code', $code)->exists());

                    $user->forceFill(['referral_code' => $code])->save();
                }
            });
    }

    public function down(): void
    {
        // Intentionally left blank. Generated referral codes should remain stable once issued.
    }
};

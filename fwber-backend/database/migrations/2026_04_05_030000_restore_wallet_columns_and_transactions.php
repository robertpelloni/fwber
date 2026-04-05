<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'wallet_address')) {
                $table->string('wallet_address')->nullable()->after('avatar_url');
            }

            if (! Schema::hasColumn('users', 'token_balance')) {
                $table->decimal('token_balance', 10, 2)->default(0)->after('wallet_address');
            }
        });

        DB::table('users')->select(['id', 'name', 'referral_code'])->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                if (! empty($user->referral_code)) {
                    continue;
                }

                $base = strtoupper(Str::slug((string) ($user->name ?: 'FWB'), ''));
                $base = substr($base ?: 'FWB', 0, 6);
                $candidate = $base . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);

                DB::table('users')->where('id', $user->id)->update(['referral_code' => $candidate]);
            }
        });

        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->string('type');
                $table->string('description');
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'token_balance')) {
                $table->dropColumn('token_balance');
            }
            if (Schema::hasColumn('users', 'wallet_address')) {
                $table->dropColumn('wallet_address');
            }
            if (Schema::hasColumn('users', 'referral_code')) {
                $table->dropUnique(['referral_code']);
                $table->dropColumn('referral_code');
            }
        });
    }
};

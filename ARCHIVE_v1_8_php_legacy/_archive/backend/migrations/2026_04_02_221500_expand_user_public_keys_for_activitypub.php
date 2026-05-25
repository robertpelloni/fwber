<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::rename('user_public_keys', 'user_public_keys_legacy');

            Schema::create('user_public_keys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('public_key');
                $table->text('private_key')->nullable();
                $table->string('key_type')->default('ECDH');
                $table->string('device_id')->nullable();
                $table->timestamp('last_rotated_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'key_type'], 'user_public_keys_user_id_key_type_unique');
            });

            DB::table('user_public_keys')->insertUsing(
                ['id', 'user_id', 'public_key', 'private_key', 'key_type', 'device_id', 'last_rotated_at', 'created_at', 'updated_at'],
                DB::table('user_public_keys_legacy')->selectRaw('id, user_id, public_key, NULL as private_key, key_type, device_id, last_rotated_at, created_at, updated_at')
            );

            Schema::drop('user_public_keys_legacy');

            return;
        }

        try {
            Schema::table('user_public_keys', function (Blueprint $table) {
                $table->dropUnique('user_public_keys_user_id_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist
        }

        Schema::table('user_public_keys', function (Blueprint $table) {
            $table->text('private_key')->nullable()->after('public_key');
            $table->unique(['user_id', 'key_type'], 'user_public_keys_user_id_key_type_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::rename('user_public_keys', 'user_public_keys_activitypub');

            Schema::create('user_public_keys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
                $table->text('public_key');
                $table->string('key_type')->default('ECDH');
                $table->string('device_id')->nullable();
                $table->timestamp('last_rotated_at')->nullable();
                $table->timestamps();
            });

            DB::table('user_public_keys')->insertUsing(
                ['id', 'user_id', 'public_key', 'key_type', 'device_id', 'last_rotated_at', 'created_at', 'updated_at'],
                DB::table('user_public_keys_activitypub')->selectRaw('id, user_id, public_key, key_type, device_id, last_rotated_at, created_at, updated_at')
            );

            Schema::drop('user_public_keys_activitypub');

            return;
        }

        Schema::table('user_public_keys', function (Blueprint $table) {
            $table->dropUnique('user_public_keys_user_id_key_type_unique');
            $table->dropColumn('private_key');
            $table->unique('user_id', 'user_public_keys_user_id_unique');
        });
    }
};

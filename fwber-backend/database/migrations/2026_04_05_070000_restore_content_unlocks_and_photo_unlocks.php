<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('photos') && ! Schema::hasColumn('photos', 'unlock_price')) {
            Schema::table('photos', function (Blueprint $table): void {
                $table->decimal('unlock_price', 8, 2)->nullable()->after('is_private');
            });
        }

        if (! Schema::hasTable('photo_unlocks')) {
            Schema::create('photo_unlocks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
                $table->decimal('cost', 8, 2);
                $table->timestamp('unlocked_at');
                $table->timestamps();

                $table->unique(['user_id', 'photo_id']);
            });
        }

        if (! Schema::hasTable('content_unlocks')) {
            Schema::create('content_unlocks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('content_type');
                $table->string('content_id');
                $table->decimal('cost', 8, 2);
                $table->timestamps();

                $table->unique(['user_id', 'content_type', 'content_id']);
                $table->index(['user_id', 'content_type', 'content_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_unlocks');
        Schema::dropIfExists('photo_unlocks');

        if (Schema::hasTable('photos') && Schema::hasColumn('photos', 'unlock_price')) {
            Schema::table('photos', function (Blueprint $table): void {
                $table->dropColumn('unlock_price');
            });
        }
    }
};

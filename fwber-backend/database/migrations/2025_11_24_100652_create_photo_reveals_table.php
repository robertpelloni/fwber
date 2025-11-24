<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photo_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'active', 'revoked'])->default('active');
            $table->timestamps();
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('original_path')->nullable()->after('file_path');
            $table->boolean('is_encrypted')->default(false)->after('original_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['original_path', 'is_encrypted']);
        });
        Schema::dropIfExists('photo_reveals');
    }
};

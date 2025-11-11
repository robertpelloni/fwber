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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('media_url')->nullable()->after('content');
            $table->string('media_type')->nullable()->after('media_url'); // audio/mpeg, image/jpeg, etc.
            $table->integer('media_duration')->nullable()->after('media_type'); // Duration in seconds for audio/video
            $table->string('thumbnail_url')->nullable()->after('media_duration'); // For video thumbnails
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['media_url', 'media_type', 'media_duration', 'thumbnail_url']);
        });
    }
};

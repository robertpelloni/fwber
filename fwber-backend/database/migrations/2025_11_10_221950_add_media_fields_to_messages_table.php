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
        // Guard against duplicate column additions if another migration already added these.
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'media_url')) {
                $table->string('media_url')->nullable()->after('content');
            }
            if (!Schema::hasColumn('messages', 'media_type')) {
                $table->string('media_type')->nullable()->after('media_url'); // audio/mpeg, image/jpeg, etc.
            }
            if (!Schema::hasColumn('messages', 'media_duration')) {
                $table->integer('media_duration')->nullable()->after('media_type'); // Duration in seconds for audio/video
            }
            if (!Schema::hasColumn('messages', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('media_duration'); // For video thumbnails
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: This migration is a compatibility guard to avoid duplicate column errors.
        // The authoritative migration managing these columns is 2025_11_10_000020_add_media_fields_to_messages_table.
    }
};

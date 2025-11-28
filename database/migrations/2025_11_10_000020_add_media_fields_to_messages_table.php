<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard against duplicate columns if applied multiple times
        if (!Schema::hasColumn('messages', 'media_url')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('media_url')->nullable()->after('content');
                $table->string('media_type')->nullable()->after('media_url');
                $table->unsignedInteger('media_duration')->nullable()->after('media_type');
                $table->string('thumbnail_url')->nullable()->after('media_duration');
                // Optional index; skip for SQLite compatibility to avoid duplicate index errors
                // $table->index(['message_type', 'sent_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('messages', 'media_url')) {
            Schema::table('messages', function (Blueprint $table) {
                // if (Schema::hasIndex('messages', ['message_type','sent_at'])) { $table->dropIndex(['message_type','sent_at']); }
                $table->dropColumn(['media_url','media_type','media_duration','thumbnail_url']);
            });
        }
    }
};

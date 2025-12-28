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
        Schema::table('bulletin_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('bulletin_messages', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false);
            }
            if (!Schema::hasColumn('bulletin_messages', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (!Schema::hasColumn('bulletin_messages', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (!Schema::hasColumn('bulletin_messages', 'reaction_count')) {
                $table->integer('reaction_count')->default(0);
            }
            if (!Schema::hasColumn('bulletin_messages', 'reply_count')) {
                $table->integer('reply_count')->default(0);
            }
            if (!Schema::hasColumn('bulletin_messages', 'parent_message_id')) {
                $table->foreignId('parent_message_id')->nullable()->constrained('bulletin_messages')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulletin_messages', function (Blueprint $table) {
            $table->dropColumn([
                'is_anonymous',
                'expires_at',
                'metadata',
                'reaction_count',
                'reply_count',
                'parent_message_id'
            ]);
        });
    }
};

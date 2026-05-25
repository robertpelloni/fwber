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
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('sentiment')->nullable()->after('status'); // positive, negative, neutral
            $table->text('ai_analysis')->nullable()->after('sentiment'); // Summary or tags
            $table->boolean('is_analyzed')->default(false)->after('ai_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['sentiment', 'ai_analysis', 'is_analyzed']);
        });
    }
};

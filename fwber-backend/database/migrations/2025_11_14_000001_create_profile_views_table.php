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
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewed_user_id')->index();
            $table->unsignedBigInteger('viewer_user_id')->nullable()->index();
            $table->string('viewer_ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('viewed_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('viewer_user_id')->references('id')->on('users')->onDelete('set null');
            
            // Prevent duplicate views from same viewer within 24 hours
            $table->unique(['viewed_user_id', 'viewer_user_id', 'viewer_ip'], 'unique_profile_view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};

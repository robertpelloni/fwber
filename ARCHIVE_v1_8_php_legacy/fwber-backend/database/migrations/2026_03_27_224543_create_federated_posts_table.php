<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federated_posts', function (Blueprint $table) {
            $table->id();
            $table->string('guid')->unique();
            $table->string('actor_uri')->index();
            $table->string('actor_username')->nullable();
            $table->string('actor_domain')->nullable();
            $table->string('actor_avatar')->nullable();
            $table->text('content');
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federated_posts');
    }
};

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
        if (!Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('business_type')->nullable();
                $table->text('description')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->default('US');
                $table->string('zip_code')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->json('operating_hours')->nullable();
                $table->integer('max_capacity')->default(100);
                $table->decimal('commission_rate', 5, 2)->default(5.00);
                $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->enum('subscription_tier', ['starter', 'professional', 'enterprise'])->default('starter');
                $table->timestamp('subscription_expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('features')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('messages', 'media_url')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('media_url')->nullable()->after('content');
                $table->string('media_type')->nullable()->after('media_url');
                $table->unsignedInteger('media_duration')->nullable()->after('media_type');
                $table->string('thumbnail_url')->nullable()->after('media_duration');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
        if (Schema::hasColumn('messages', 'media_url')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn(['media_url','media_type','media_duration','thumbnail_url']);
            });
        }
    }
};

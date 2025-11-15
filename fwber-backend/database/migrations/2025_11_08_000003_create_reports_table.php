<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('accused_id');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('open'); // open|reviewing|resolved|dismissed
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('moderator_id')->nullable();
            $table->timestamps();

            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accused_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('set null');
            $table->foreign('moderator_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

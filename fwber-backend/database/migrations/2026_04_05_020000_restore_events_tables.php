<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->string('type')->nullable();
                $table->string('location_name');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->integer('max_attendees')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->decimal('token_cost', 10, 2)->nullable();
                $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
                $table->timestamps();

                $table->index(['latitude', 'longitude']);
                $table->index(['status', 'starts_at']);
            });
        }

        if (! Schema::hasTable('event_attendees')) {
            Schema::create('event_attendees', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['attending', 'maybe', 'declined'])->default('attending');
                $table->boolean('paid')->default(false);
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('event_invitations')) {
            Schema::create('event_invitations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
                $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('invitee_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
                $table->timestamps();

                $table->unique(['event_id', 'invitee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invitations');
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('events');
    }
};

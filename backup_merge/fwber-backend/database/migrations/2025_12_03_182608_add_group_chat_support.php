<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'group' to chatroom types
        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            // Create temp table with new schema
            Schema::create('chatrooms_temp', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('description', 500)->nullable();
                $table->enum('type', ['interest', 'city', 'event', 'private', 'group']);
                $table->string('category', 50)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('neighborhood', 100)->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->boolean('is_public')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('member_count')->default(0);
                $table->integer('message_count')->default(0);
                $table->timestamp('last_activity_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });

            // Copy data
            DB::statement('INSERT INTO chatrooms_temp SELECT * FROM chatrooms');

            // Drop old table
            Schema::drop('chatrooms');

            // Rename new table
            Schema::rename('chatrooms_temp', 'chatrooms');

            Schema::enableForeignKeyConstraints();
        } else {
            // Note: We use raw SQL for enum modification to avoid doctrine/dbal dependency issues
            DB::statement("ALTER TABLE chatrooms MODIFY COLUMN type ENUM('interest', 'city', 'event', 'private', 'group') NOT NULL");
        }

        // Add chatroom_id to groups table
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('chatroom_id')->nullable()->after('id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['chatroom_id']);
            $table->dropColumn('chatroom_id');
        });

        // Revert enum (warning: this will fail if there are 'group' type chatrooms)
        // We generally don't revert enum expansions in production to avoid data loss
        // DB::statement("ALTER TABLE chatrooms MODIFY COLUMN type ENUM('interest', 'city', 'event', 'private') NOT NULL");
    }
};

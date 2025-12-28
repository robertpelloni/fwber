<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouches', function (Blueprint $table) {
            // relationship_type: friend, colleague, ex-partner, etc.
            $table->string('relationship_type')->nullable()->after('type');
            
            // comment: The actual vouch text
            $table->text('comment')->nullable()->after('relationship_type');
            
            // is_verified: Whether the vouch has been verified by the system/admin
            $table->boolean('is_verified')->default(false)->after('comment');
            
            // voucher_name: For non-user vouches (if we allow them later, though migration assumes user_id)
            $table->string('voucher_name')->nullable()->after('from_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('vouches', function (Blueprint $table) {
            $table->dropColumn(['relationship_type', 'comment', 'is_verified', 'voucher_name']);
        });
    }
};

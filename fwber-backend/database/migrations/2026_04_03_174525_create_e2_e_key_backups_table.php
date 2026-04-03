<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e2e_key_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key_type')->default('ecdh'); // ecdh, rsa
            $table->text('encrypted_private_key'); // Client-encrypted JWK or raw bytes
            $table->string('salt'); // PBKDF2 salt used by client
            $table->string('iv'); // AES-GCM IV used by client
            $table->timestamps();

            // A user can only have one backup per key type
            $table->unique(['user_id', 'key_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e2e_key_backups');
    }
};

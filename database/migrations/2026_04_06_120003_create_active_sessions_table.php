<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id_hash', 64)->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_label', 255)->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->index();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'last_activity_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_sessions');
    }
};

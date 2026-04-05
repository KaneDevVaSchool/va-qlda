<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint', 64);
            $table->string('event', 64);
            $table->string('location_country', 2)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};

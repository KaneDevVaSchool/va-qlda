<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppms_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 24)->default('in_app');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppms_notifications');
    }
};

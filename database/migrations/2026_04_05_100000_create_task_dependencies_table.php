<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('successor_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('predecessor_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['successor_task_id', 'predecessor_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};

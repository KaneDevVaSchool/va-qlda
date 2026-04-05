<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 16); // quarterly, annual
            $table->string('period_label', 32);
            $table->foreignId('person_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('p1', 6, 2)->nullable();
            $table->decimal('p2', 6, 2)->nullable();
            $table->decimal('p3', 6, 2)->nullable();
            $table->decimal('total', 6, 2)->nullable();
            $table->string('grade', 2)->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('draft');
            $table->text('adjustment_reason')->nullable();
            $table->decimal('adjustment_delta', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};

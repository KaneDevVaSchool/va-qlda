<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('week_start');
            $table->text('problem');
            $table->text('solution');
            $table->string('outcome_measurable');
            $table->string('status', 24)->default('submitted');
            $table->unsignedTinyInteger('tl_rating')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizens');
    }
};

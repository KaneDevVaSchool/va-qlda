<?php

use App\Support\MigrationCms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cmsUsers = MigrationCms::usersTable();

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 16); // quarterly, annual
            $table->string('period_label', 32);
            $table->foreignId('person_id');
            $table->decimal('p1', 6, 2)->nullable();
            $table->decimal('p2', 6, 2)->nullable();
            $table->decimal('p3', 6, 2)->nullable();
            $table->decimal('total', 6, 2)->nullable();
            $table->string('grade', 2)->nullable();
            $table->foreignId('reviewer_id')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('adjustment_reason')->nullable();
            $table->decimal('adjustment_delta', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::table('evaluations', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('person_id')->references('id')->on($cmsUsers)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('reviewer_id')->references('id')->on($cmsUsers)->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};

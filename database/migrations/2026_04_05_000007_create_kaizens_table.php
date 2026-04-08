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

        Schema::create('kaizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_id');
            $table->date('week_start');
            $table->text('problem');
            $table->text('solution');
            $table->string('outcome_measurable');
            $table->string('status', 24)->default('submitted');
            $table->unsignedTinyInteger('tl_rating')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamps();
        });

        Schema::table('kaizens', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('submitter_id')->references('id')->on($cmsUsers)->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on($cmsUsers)->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizens');
    }
};

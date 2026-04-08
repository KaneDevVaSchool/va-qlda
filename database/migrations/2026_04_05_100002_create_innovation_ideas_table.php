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

        Schema::create('innovation_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter_id');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 24)->default('submitted');
            $table->timestamps();
        });

        Schema::table('innovation_ideas', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('submitter_id')->references('id')->on($cmsUsers)->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innovation_ideas');
    }
};

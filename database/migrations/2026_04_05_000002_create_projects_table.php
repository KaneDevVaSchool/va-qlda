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

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32); // maintenance, delivery, rnd
            $table->string('phase', 32)->default('planning');
            $table->string('status', 32)->default('on_track');
            $table->foreignId('owner_id');
            $table->date('deadline')->nullable();
            $table->text('description')->nullable();
            $table->decimal('progress', 8, 4)->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('owner_id')->references('id')->on($cmsUsers)->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

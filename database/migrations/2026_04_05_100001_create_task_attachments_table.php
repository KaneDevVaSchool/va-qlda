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

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by');
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });

        Schema::table('task_attachments', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('uploaded_by')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};

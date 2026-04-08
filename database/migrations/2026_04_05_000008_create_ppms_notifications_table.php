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

        Schema::create('ppms_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->foreignId('recipient_id');
            $table->string('channel', 24)->default('in_app');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('ppms_notifications', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('recipient_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppms_notifications');
    }
};

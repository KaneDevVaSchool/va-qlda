<?php

use App\Support\MigrationCms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CMS may already ship `user_info` in production. Local / sqlite tests need a minimal table.
     */
    public function up(): void
    {
        $conn = 'cms';
        if (Schema::connection($conn)->hasTable('user_info')) {
            return;
        }

        $cmsUsers = MigrationCms::usersTable();

        Schema::connection($conn)->create('user_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::connection($conn)->table('user_info', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('user_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally empty: production CMS may already own `user_info`.
    }
};

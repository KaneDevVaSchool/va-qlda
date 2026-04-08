<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('executor_user_ids')->nullable();
            $table->json('follower_user_ids')->nullable();
            $table->string('permission_preset', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['executor_user_ids', 'follower_user_ids', 'permission_preset']);
        });
    }
};

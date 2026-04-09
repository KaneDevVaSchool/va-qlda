<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('cms')->table('team_user', function (Blueprint $table) {
            $table->string('position', 255)->nullable();
            $table->json('permissions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('cms')->table('team_user', function (Blueprint $table) {
            $table->dropColumn(['position', 'permissions']);
        });
    }
};

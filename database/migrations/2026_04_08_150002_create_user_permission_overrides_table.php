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

        Schema::create('user_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('permission_key', 96);
            $table->boolean('granted')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'permission_key']);
            $table->index('expires_at');
        });

        Schema::table('user_permission_overrides', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('user_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
    }
};

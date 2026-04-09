<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbac_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 32);
            $table->string('permission_key', 96);
            $table->boolean('granted')->default(true);
            $table->timestamps();

            $table->unique(['role', 'permission_key']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_role_permissions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conn = 'cms';
        $tableName = 'users';

        if (! Schema::connection($conn)->hasColumn($tableName, 'avatar_path')) {
            Schema::connection($conn)->table($tableName, function (Blueprint $table) {
                $table->string('avatar_path', 512)->nullable();
            });
        }

        if (! Schema::connection($conn)->hasColumn($tableName, 'profile_updated_at')) {
            Schema::connection($conn)->table($tableName, function (Blueprint $table) {
                $table->timestamp('profile_updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        $conn = 'cms';
        $tableName = 'users';
        $schema = Schema::connection($conn);

        $drop = [];
        if ($schema->hasColumn($tableName, 'avatar_path')) {
            $drop[] = 'avatar_path';
        }
        if ($schema->hasColumn($tableName, 'profile_updated_at')) {
            $drop[] = 'profile_updated_at';
        }
        if ($drop !== []) {
            Schema::connection($conn)->table($tableName, function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }
};

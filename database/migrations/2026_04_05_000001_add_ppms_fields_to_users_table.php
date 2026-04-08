<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('cms')->hasColumn('users', 'role')) {
            Schema::connection('cms')->table('users', function (Blueprint $table) {
                $table->string('role', 32)->default('developer')->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('cms')->hasColumn('users', 'role')) {
            Schema::connection('cms')->table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};

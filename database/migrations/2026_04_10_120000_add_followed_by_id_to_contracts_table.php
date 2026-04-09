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

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('followed_by_id')->nullable()->after('approved_by');
        });

        Schema::table('contracts', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('followed_by_id')->references('id')->on($cmsUsers)->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['followed_by_id']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('followed_by_id');
        });
    }
};

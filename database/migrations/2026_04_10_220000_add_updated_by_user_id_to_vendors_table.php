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

        if (! Schema::hasColumn('vendors', 'updated_by_user_id')) {
            Schema::table('vendors', function (Blueprint $table) use ($cmsUsers) {
                $table->foreignId('updated_by_user_id')->nullable()->after('updated_at')->constrained($cmsUsers)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'updated_by_user_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            });
        }
    }
};

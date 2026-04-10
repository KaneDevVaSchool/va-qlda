<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vendors', 'services_offered')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->text('services_offered')->nullable()->after('main_products');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'services_offered')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('services_offered');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot was created without timestamps while BelongsToMany uses withTimestamps().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_department')) {
            return;
        }

        if (Schema::hasColumn('vendor_department', 'created_at')) {
            return;
        }

        Schema::table('vendor_department', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_department') || ! Schema::hasColumn('vendor_department', 'created_at')) {
            return;
        }

        Schema::table('vendor_department', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};

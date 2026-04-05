<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kaizens', function (Blueprint $table) {
            $table->decimal('estimated_value', 14, 2)->nullable()->after('outcome_measurable');
        });
    }

    public function down(): void
    {
        Schema::table('kaizens', function (Blueprint $table) {
            $table->dropColumn('estimated_value');
        });
    }
};

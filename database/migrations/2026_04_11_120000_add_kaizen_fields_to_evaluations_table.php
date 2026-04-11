<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->boolean('kaizen_verified')->default(false)->after('criteria_scores');
            $table->text('kaizen_action')->nullable()->after('kaizen_verified');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['kaizen_verified', 'kaizen_action']);
        });
    }
};

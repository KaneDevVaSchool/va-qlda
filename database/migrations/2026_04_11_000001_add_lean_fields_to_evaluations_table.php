<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('scoring_mode', 16)->default('legacy')->after('period_label');
            $table->string('lean_track', 24)->nullable()->after('scoring_mode');
            $table->foreignId('project_id')->nullable()->after('person_id')->constrained('projects')->nullOnDelete();
            $table->json('criteria_scores')->nullable()->after('p3');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn(['scoring_mode', 'lean_track', 'project_id', 'criteria_scores']);
        });
    }
};

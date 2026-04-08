<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('progress_mode', 32)->default('status_default');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 14, 4)->default(0);
            $table->string('unit', 32)->default('');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_phase_id')->nullable()->after('project_id')->constrained('project_phases')->nullOnDelete();
            $table->string('progress_mode', 32)->default('status_default')->after('status');
            $table->decimal('manual_progress_pct', 5, 2)->nullable()->after('progress_mode');
            $table->unsignedInteger('volume_total')->nullable()->after('manual_progress_pct');
            $table->unsignedInteger('volume_done')->nullable()->after('volume_total');
            $table->unsignedInteger('checklist_total')->nullable()->after('volume_done');
            $table->unsignedInteger('checklist_done')->nullable()->after('checklist_total');
            $table->string('category', 128)->nullable()->after('checklist_done');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_phase_id']);
            $table->dropColumn([
                'project_phase_id',
                'progress_mode',
                'manual_progress_pct',
                'volume_total',
                'volume_done',
                'checklist_total',
                'checklist_done',
                'category',
            ]);
        });

        Schema::dropIfExists('project_supplies');
        Schema::dropIfExists('project_phases');
    }
};

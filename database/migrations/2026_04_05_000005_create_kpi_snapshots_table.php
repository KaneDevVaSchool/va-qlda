<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('week_ending'); // Sunday of snapshot week
            $table->morphs('entity');
            $table->string('metric_name', 64);
            $table->decimal('value', 16, 6)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['week_ending', 'entity_type', 'entity_id', 'metric_name'], 'kpi_snap_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};

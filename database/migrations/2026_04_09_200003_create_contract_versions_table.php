<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('file_path', 2048);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['contract_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
    }
};

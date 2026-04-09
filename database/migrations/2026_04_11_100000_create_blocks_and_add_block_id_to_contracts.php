<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->nullable()->index();
            $table->timestamps();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('block_id')->nullable()->after('department_id')->constrained('blocks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('block_id');
        });
        Schema::dropIfExists('blocks');
    }
};

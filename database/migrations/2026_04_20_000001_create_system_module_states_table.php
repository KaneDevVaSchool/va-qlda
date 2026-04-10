<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_module_states', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 64)->unique();
            $table->boolean('maintenance')->default(false);
            $table->text('message')->nullable();
            // User rows may live on the `cms` connection; avoid cross-DB FK.
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_module_states');
    }
};

<?php

use App\Support\MigrationCms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cmsUsers = MigrationCms::usersTable();

        Schema::create('contract_logs', function (Blueprint $table) {
            $table->id();
            // Keep history if parent is removed; callers may delete rows explicitly.
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->string('action', 128)->index();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['contract_id', 'created_at']);
        });

        Schema::table('contract_logs', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('user_id')->references('id')->on($cmsUsers)->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_logs');
    }
};

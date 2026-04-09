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

        Schema::create('user_audit_log_reads', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained($cmsUsers)->cascadeOnDelete();
            $table->foreignId('audit_log_id')->constrained('audit_logs')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();

            $table->primary(['user_id', 'audit_log_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_audit_log_reads');
    }
};

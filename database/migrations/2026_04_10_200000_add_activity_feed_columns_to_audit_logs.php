<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('activity_kind', 32)->nullable()->after('action');
            $table->string('ip_address', 45)->nullable()->after('new_values');
            $table->string('source', 16)->default('web')->after('ip_address');
            $table->string('subject_label', 512)->nullable()->after('source');
            $table->json('metadata')->nullable()->after('subject_label');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('activity_kind');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['activity_kind']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['activity_kind', 'ip_address', 'source', 'subject_label', 'metadata']);
        });
    }
};

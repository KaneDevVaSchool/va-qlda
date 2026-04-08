<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('active_sessions')) {
            return;
        }

        Schema::table('active_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('active_sessions', 'browser')) {
                $table->string('browser', 96)->nullable();
            }
            if (! Schema::hasColumn('active_sessions', 'os')) {
                $table->string('os', 96)->nullable();
            }
            if (! Schema::hasColumn('active_sessions', 'device_name')) {
                $table->string('device_name', 128)->nullable();
            }
            if (! Schema::hasColumn('active_sessions', 'login_at')) {
                $table->timestamp('login_at')->nullable();
            }
            if (! Schema::hasColumn('active_sessions', 'logout_at')) {
                $table->timestamp('logout_at')->nullable();
            }
            if (! Schema::hasColumn('active_sessions', 'is_suspicious')) {
                $table->boolean('is_suspicious')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('active_sessions')) {
            return;
        }

        $cols = ['browser', 'os', 'device_name', 'login_at', 'logout_at', 'is_suspicious'];
        $present = array_filter($cols, fn (string $c) => Schema::hasColumn('active_sessions', $c));
        if ($present === []) {
            return;
        }

        Schema::table('active_sessions', function (Blueprint $table) use ($present) {
            $table->dropColumn($present);
        });
    }
};

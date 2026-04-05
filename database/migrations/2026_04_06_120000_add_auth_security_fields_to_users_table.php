<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->unsignedSmallInteger('password_expiry_days')->default(90)->after('password_changed_at');
            $table->boolean('must_change_password')->default(false)->after('password_expiry_days');
            $table->json('password_history')->nullable()->after('must_change_password');

            $table->unsignedSmallInteger('failed_login_count')->default(0)->after('password_history');
            $table->timestamp('locked_until')->nullable()->after('failed_login_count');
            $table->unsignedSmallInteger('lockout_threshold')->default(5)->after('locked_until');

            $table->unsignedSmallInteger('session_timeout_minutes')->default(30)->after('lockout_threshold');
            $table->timestamp('last_login_at')->nullable()->after('session_timeout_minutes');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->string('last_login_device', 512)->nullable()->after('last_login_ip');

            $table->timestamp('terms_accepted_at')->nullable()->after('last_login_device');
            $table->boolean('security_banner_acknowledged')->default(false)->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_changed_at',
                'password_expiry_days',
                'must_change_password',
                'password_history',
                'failed_login_count',
                'locked_until',
                'lockout_threshold',
                'session_timeout_minutes',
                'last_login_at',
                'last_login_ip',
                'last_login_device',
                'terms_accepted_at',
                'security_banner_acknowledged',
            ]);
        });
    }
};

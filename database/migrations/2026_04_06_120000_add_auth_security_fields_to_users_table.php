<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $conn = 'cms';
        $schema = Schema::connection($conn);
        $tableName = 'users';

        $add = function (string $column, \Closure $define) use ($conn, $schema, $tableName) {
            if ($schema->hasColumn($tableName, $column)) {
                return;
            }
            Schema::connection($conn)->table($tableName, function (Blueprint $table) use ($define) {
                $define($table);
            });
        };

        $add('password_changed_at', fn (Blueprint $t) => $t->timestamp('password_changed_at')->nullable());
        $add('password_expiry_days', fn (Blueprint $t) => $t->unsignedSmallInteger('password_expiry_days')->default(90));
        $add('must_change_password', fn (Blueprint $t) => $t->boolean('must_change_password')->default(false));
        $add('password_history', fn (Blueprint $t) => $t->json('password_history')->nullable());
        $add('failed_login_count', fn (Blueprint $t) => $t->unsignedSmallInteger('failed_login_count')->default(0));
        $add('locked_until', fn (Blueprint $t) => $t->timestamp('locked_until')->nullable());
        $add('lockout_threshold', fn (Blueprint $t) => $t->unsignedSmallInteger('lockout_threshold')->default(5));
        $add('session_timeout_minutes', fn (Blueprint $t) => $t->unsignedSmallInteger('session_timeout_minutes')->default(30));
        $add('last_login_at', fn (Blueprint $t) => $t->timestamp('last_login_at')->nullable());
        $add('last_login_ip', fn (Blueprint $t) => $t->string('last_login_ip', 45)->nullable());
        $add('last_login_device', fn (Blueprint $t) => $t->string('last_login_device', 512)->nullable());
        $add('terms_accepted_at', fn (Blueprint $t) => $t->timestamp('terms_accepted_at')->nullable());
        $add('security_banner_acknowledged', fn (Blueprint $t) => $t->boolean('security_banner_acknowledged')->default(false));
    }

    public function down(): void
    {
        $conn = 'cms';
        $schema = Schema::connection($conn);
        $tableName = 'users';

        // Do not drop `last_login_at` here: it may be native to CMS `users`.
        $cols = [
            'password_changed_at',
            'password_expiry_days',
            'must_change_password',
            'password_history',
            'failed_login_count',
            'locked_until',
            'lockout_threshold',
            'session_timeout_minutes',
            'last_login_ip',
            'last_login_device',
            'terms_accepted_at',
            'security_banner_acknowledged',
        ];

        $present = array_filter($cols, fn (string $c) => $schema->hasColumn($tableName, $c));
        if ($present === []) {
            return;
        }

        Schema::connection($conn)->table($tableName, function (Blueprint $table) use ($present) {
            $table->dropColumn($present);
        });
    }
};

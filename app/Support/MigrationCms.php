<?php

namespace App\Support;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class MigrationCms
{
    /**
     * Table reference for foreign keys: MySQL uses `db.users`; SQLite (tests) uses `users`.
     */
    public static function usersTable(): string
    {
        $driver = config('database.connections.cms.driver');

        if ($driver === 'sqlite') {
            return 'users';
        }

        return config('database.connections.cms.database').'.users';
    }

    /**
     * Target for QueryBuilder::join() when the app DB query must join CMS `users` (cross-database on MySQL).
     */
    public static function usersJoinTarget(): Expression|string
    {
        if (config('database.connections.cms.driver') === 'sqlite') {
            return 'users';
        }

        $db = config('database.connections.cms.database');

        return DB::raw("`{$db}`.`users` as users");
    }
}

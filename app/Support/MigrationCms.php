<?php

namespace App\Support;

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
}

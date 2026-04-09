<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filesystem disk for contract files (S3-compatible via Laravel filesystems)
    |--------------------------------------------------------------------------
    */
    'filesystem_disk' => env('CONTRACTS_FILESYSTEM_DISK', env('FILESYSTEM_DISK', 'local')),

    /*
    |--------------------------------------------------------------------------
    | Reminder: days before contract end_date to notify stakeholders
    |--------------------------------------------------------------------------
    */
    'expiration_reminder_days' => (int) env('CONTRACT_EXPIRATION_REMINDER_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Reminder: days before payment due_date to notify (pending payments)
    |--------------------------------------------------------------------------
    */
    'payment_reminder_days' => (int) env('CONTRACT_PAYMENT_REMINDER_DAYS', 7),

];

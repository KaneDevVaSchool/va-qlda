<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Phải trùng ký tự với "Authorized redirect URIs" trong Google Cloud Console (thường: https://your-domain/auth/google/callback).
        // Chuẩn hóa khi APP_URL kết thúc bằng / và GOOGLE_REDIRECT_URI="${APP_URL}/auth/..." → tránh // sau tên miền.
        'redirect' => (static function (): string {
            $raw = (string) (env('GOOGLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback');
            $raw = preg_replace('#^(https?://[^/]+)/{2,}#i', '$1/', $raw) ?? $raw;

            return rtrim($raw, '/');
        })(),
        'allow_register' => (bool) env('GOOGLE_LOGIN_ALLOW_REGISTER', false),
    ],

];

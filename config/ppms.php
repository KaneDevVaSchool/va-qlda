<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account lockout (soft)
    |--------------------------------------------------------------------------
    |
    | After lockout_threshold failed password attempts (per user row), locked_until
    | is set for this many minutes. Throttle middleware still applies separately.
    |
    */
    'lockout_minutes' => (int) env('PPMS_LOCKOUT_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Login MFA (email OTP) — password login only
    |--------------------------------------------------------------------------
    |
    | When enabled, users whose role is in login_mfa_roles receive a 6-digit code
    | by mail after a correct password, before a Sanctum token is issued.
    | OAuth (Google) is not subject to this step.
    |
    */
    'login_mfa_enabled' => filter_var(env('PPMS_LOGIN_MFA', false), FILTER_VALIDATE_BOOL),

    'login_mfa_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PPMS_LOGIN_MFA_ROLES', 'admin,pm'))
    ))),

    'login_mfa_ttl_minutes' => (int) env('PPMS_LOGIN_MFA_TTL', 10),

    /*
    |--------------------------------------------------------------------------
    | Upload max file size (API validation)
    |--------------------------------------------------------------------------
    |
    | Laravel file rule "max" is in kilobytes. Applies to project document
    | uploads and task attachments. Ensure PHP upload_max_filesize / post_max_size
    | are not lower than this value in production.
    |
    */
    'upload_max_file_kb' => max(1, (int) env('PPMS_UPLOAD_MAX_FILE_KB', 51200)),

    /*
    | Google OAuth: nếu danh sách không rỗng, chỉ email có phần sau @ trùng một phần tử
    | (ví dụ hcm.vaschools.edu.vn) mới được hoàn tất đăng nhập Google.
    */
    'google_allowed_email_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ACCEPT_DOMAIN_MAIL', ''))
    ))),

];

<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Sanctum tokens live on the same DB connection as {@see User} (CMS) so morphTo('tokenable')
 * resolves correctly. Tokens were previously on the app DB while users are in cms_db — that
 * mismatch caused valid Bearer tokens to 401 on /api/user.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @var string */
    protected $connection = 'cms';
}

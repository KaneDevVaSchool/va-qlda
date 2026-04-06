<?php

namespace Tests\Support;

use App\Services\LoginMfaService;

class FixedCodeLoginMfaService extends LoginMfaService
{
    protected function generateNumericCode(): string
    {
        return '444444';
    }
}

<?php

/**
 * Entry point khi Document Root = thư mục gốc project (public_html trỏ vào đây).
 * public/index.php chỉ require file này.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Authorization header (Bearer / Sanctum)
|--------------------------------------------------------------------------
| Một số stack (Apache + CGI, OpenLiteSpeed, reverse proxy) không đưa
| HTTP_AUTHORIZATION vào $_SERVER — Laravel không đọc được token → 401.
| Chuẩn hóa trước khi Request::capture().
*/
if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (! empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (! empty($_SERVER['REDIRECT_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strcasecmp((string) $key, 'Authorization') === 0 && $value !== '') {
                    $_SERVER['HTTP_AUTHORIZATION'] = $value;
                    break;
                }
            }
        }
    }
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

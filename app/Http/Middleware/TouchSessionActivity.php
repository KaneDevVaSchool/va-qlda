<?php

namespace App\Http\Middleware;

use App\Services\SessionDeviceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TouchSessionActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $token = $user?->currentAccessToken();
        if ($user && $token) {
            app(SessionDeviceService::class)->touchActivity((int) $token->id);
        }

        return $response;
    }
}

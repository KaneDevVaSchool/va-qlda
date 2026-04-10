<?php

namespace App\Http\Middleware;

use App\Services\ModuleMaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleNotUnderMaintenance
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $service = app(ModuleMaintenanceService::class);

        if (! $service->shouldBlock($user, $module)) {
            return $next($request);
        }

        $states = $service->allStatesCached();
        $message = $states[$module]['message'] ?? null;

        return response()->json([
            'message' => $message ?: 'This module is temporarily unavailable.',
            'code' => 'MODULE_MAINTENANCE',
            'module' => $module,
        ], 503);
    }
}

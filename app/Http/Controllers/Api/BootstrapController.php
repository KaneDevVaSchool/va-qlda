<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ModuleMaintenanceService;
use App\Services\UserRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function show(
        Request $request,
        UserRbacService $rbac,
        ModuleMaintenanceService $modules,
    ): JsonResponse {
        $user = $request->user();

        return response()->json([
            'module_maintenance' => $modules->allStatesCached(),
            'module_catalog' => config('ppms_modules.modules'),
            'maintenance_bypass_roles' => config('ppms_modules.maintenance_bypass_roles', ['admin']),
            'rbac' => [
                'role' => $user->role,
                'effective' => $rbac->effectivePermissions($user),
                'can_manage' => $rbac->isPermissionAdmin($user),
            ],
        ]);
    }
}

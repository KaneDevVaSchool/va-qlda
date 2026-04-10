<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ModuleMaintenanceService;
use App\Services\UserRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemModuleController extends Controller
{
    public function index(Request $request, UserRbacService $rbac, ModuleMaintenanceService $modules): JsonResponse
    {
        if (! $rbac->isPermissionAdmin($request->user())) {
            abort(403);
        }

        $catalog = config('ppms_modules.modules', []);
        $states = $modules->allStates();

        $rows = [];
        foreach ($catalog as $key => $meta) {
            $rows[] = [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'maintenance' => $states[$key]['maintenance'] ?? false,
                'message' => $states[$key]['message'] ?? null,
            ];
        }

        return response()->json([
            'modules' => $rows,
            'maintenance_bypass_roles' => config('ppms_modules.maintenance_bypass_roles', ['admin']),
        ]);
    }

    public function update(
        Request $request,
        string $module,
        UserRbacService $rbac,
        ModuleMaintenanceService $modules,
    ): JsonResponse {
        if (! $rbac->isPermissionAdmin($request->user())) {
            abort(403);
        }

        if (! $modules->isValidModuleKey($module)) {
            abort(422, 'Invalid module key.');
        }

        $data = $request->validate([
            'maintenance' => 'required|boolean',
            'message' => 'nullable|string|max:2000',
        ]);

        $modules->setState(
            $module,
            $data['maintenance'],
            $data['message'] ?? null,
            $request->user(),
        );

        return response()->json([
            'module_maintenance' => $modules->allStatesCached(),
        ]);
    }
}

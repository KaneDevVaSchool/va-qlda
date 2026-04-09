<?php

namespace App\Http\Controllers\Api;

use App\Enums\VendorKind;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

/**
 * Dropdown data for contract forms (read-only lists).
 */
class ContractLookupController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'vendors' => Vendor::query()
                ->where('kind', VendorKind::Active)
                ->orderBy('name')
                ->get(['id', 'name', 'tax_code']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}

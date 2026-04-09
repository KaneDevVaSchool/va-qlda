<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
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
                ->orderBy('name')
                ->get(['id', 'name', 'kind', 'tax_code']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'code']),
            'blocks' => Block::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendors\StoreVendorRequest;
use App\Http\Requests\Vendors\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\Vendors\VendorMetricsService;
use App\Support\Vendors\VendorListCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function __construct(
        protected VendorMetricsService $metrics
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vendor::class);

        $innerKey = md5(json_encode($request->query->all()));

        $paginator = VendorListCache::remember($innerKey, 120, function () use ($request) {
            $q = Vendor::query()
                ->with(['departments:id,name,code'])
                ->when($request->query('kind'), fn ($b, $kind) => $b->where('kind', $kind))
                ->when($request->query('status'), fn ($b, $status) => $b->where('status', $status))
                ->when($request->query('industry'), fn ($b, $industry) => $b->where('industry', $industry))
                ->when($request->query('q'), fn ($b, $term) => $b->searchName($term))
                ->when(
                    $request->query('min_score') !== null && $request->query('min_score') !== '',
                    fn ($b) => $b->where('vendor_score', '>=', (float) $request->query('min_score'))
                )
                ->orderByDesc('updated_at');

            return $q->paginate(min((int) $request->query('per_page', 20), 100));
        });

        return VendorResource::collection($paginator);
    }

    public function store(StoreVendorRequest $request): JsonResponse
    {
        $this->authorize('create', Vendor::class);

        $vendor = DB::transaction(function () use ($request) {
            $data = collect($request->validated())->except(['department_ids', 'products'])->all();
            $data['updated_by_user_id'] = $request->user()?->id;
            $vendor = Vendor::query()->create($data);
            $this->metrics->recalculateCriterionScore($vendor);
            $vendor->save();

            if ($request->has('department_ids')) {
                $vendor->departments()->sync($request->input('department_ids', []));
            }
            $this->syncProducts($vendor, $request->input('products'));

            return $vendor->fresh(['departments', 'products', 'updatedBy:id,name']);
        });

        VendorListCache::bump();

        return (new VendorResource($vendor))->response()->setStatusCode(201);
    }

    public function show(Vendor $vendor): VendorResource
    {
        $this->authorize('view', $vendor);

        $vendor->load([
            'departments:id,name,code',
            'products',
            'updatedBy:id,name',
            'reviews' => fn ($q) => $q->latest('created_at')->limit(50),
            'reviews.author:id,name,email',
            'contracts' => fn ($q) => $q->latest('updated_at')->limit(50),
            'contracts.department:id,name,code',
        ]);

        return new VendorResource($vendor);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): VendorResource
    {
        $this->authorize('update', $vendor);

        DB::transaction(function () use ($request, $vendor) {
            $data = collect($request->validated())->except(['department_ids', 'products'])->all();
            if ($data !== []) {
                $vendor->fill($data);
            }
            $vendor->updated_by_user_id = $request->user()?->id;
            $this->metrics->recalculateCriterionScore($vendor);
            $vendor->save();

            if ($request->has('department_ids')) {
                $vendor->departments()->sync($request->input('department_ids', []));
            }
            if ($request->has('products')) {
                $this->syncProducts($vendor, $request->input('products'));
            }
        });

        VendorListCache::bump();

        return new VendorResource($vendor->fresh(['departments', 'products', 'updatedBy:id,name']));
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->authorize('delete', $vendor);

        if ($vendor->contracts()->exists()) {
            abort(422, 'Cannot delete a vendor that still has contracts. Remove or reassign contracts first.');
        }

        $vendor->delete();
        VendorListCache::bump();

        return response()->json(null, 204);
    }

    /**
     * @param  list<array{name: string, description?: string|null}>|null  $products
     */
    private function syncProducts(Vendor $vendor, ?array $products): void
    {
        if ($products === null) {
            return;
        }

        $vendor->products()->delete();
        foreach (array_values($products) as $i => $row) {
            $vendor->products()->create([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'position' => $i,
            ]);
        }
    }
}

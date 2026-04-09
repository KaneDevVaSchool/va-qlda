<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendors\StoreVendorTimelineRequest;
use App\Http\Requests\Vendors\UpdateVendorTimelineRequest;
use App\Http\Resources\VendorTimelineResource;
use App\Models\Vendor;
use App\Models\VendorTimeline;
use App\Support\Vendors\VendorListCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class VendorTimelineController extends Controller
{
    public function index(Request $request, Vendor $vendor): AnonymousResourceCollection
    {
        $this->authorize('view', $vendor);

        $items = $vendor->timelines()
            ->with('performedBy:id,name,email')
            ->paginate(min((int) $request->query('per_page', 30), 100));

        return VendorTimelineResource::collection($items);
    }

    public function store(StoreVendorTimelineRequest $request, Vendor $vendor): JsonResponse
    {
        $this->authorize('update', $vendor);

        $row = DB::transaction(function () use ($request, $vendor) {
            $data = $request->validated();
            if (! empty($data['is_current'])) {
                VendorTimeline::query()->where('vendor_id', $vendor->id)->update(['is_current' => false]);
            }

            return $vendor->timelines()->create([
                'phase' => $data['phase'],
                'occurred_at' => $data['occurred_at'],
                'performed_by_user_id' => $data['performed_by_user_id'] ?? null,
                'note' => $data['note'] ?? null,
                'is_current' => (bool) ($data['is_current'] ?? false),
            ]);
        });

        $row->load('performedBy:id,name,email');

        VendorListCache::bump();

        return (new VendorTimelineResource($row))->response()->setStatusCode(201);
    }

    public function update(UpdateVendorTimelineRequest $request, VendorTimeline $timeline): VendorTimelineResource
    {
        $vendor = $timeline->vendor;
        $this->authorize('update', $vendor);

        DB::transaction(function () use ($request, $timeline, $vendor) {
            $data = $request->validated();
            if (array_key_exists('is_current', $data) && $data['is_current']) {
                VendorTimeline::query()->where('vendor_id', $vendor->id)->update(['is_current' => false]);
            }
            $timeline->fill($data);
            $timeline->save();
        });

        $timeline->load('performedBy:id,name,email');

        VendorListCache::bump();

        return new VendorTimelineResource($timeline->fresh());
    }

    public function destroy(VendorTimeline $timeline): JsonResponse
    {
        $this->authorize('update', $timeline->vendor);

        $timeline->delete();

        VendorListCache::bump();

        return response()->json(null, 204);
    }
}

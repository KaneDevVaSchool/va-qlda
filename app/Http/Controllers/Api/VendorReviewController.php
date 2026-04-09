<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendors\StoreVendorReviewRequest;
use App\Http\Resources\VendorReviewResource;
use App\Models\VendorReview;
use App\Services\Vendors\VendorMetricsService;
use App\Support\Vendors\VendorListCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorReviewController extends Controller
{
    public function __construct(
        protected VendorMetricsService $metrics
    ) {}

    public function store(StoreVendorReviewRequest $request, Vendor $vendor): JsonResponse
    {
        $this->authorize('update', $vendor);

        $review = $vendor->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $request->validated('rating'),
            'body' => $request->validated('body'),
        ]);

        $review->load('author:id,name,email');

        $vendor->refresh();
        $this->metrics->recalculateReviewAverage($vendor);
        $vendor->save();

        VendorListCache::bump();

        return (new VendorReviewResource($review))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, VendorReview $review): JsonResponse
    {
        $vendor = $review->vendor;
        $this->authorize('update', $vendor);

        if ($review->user_id !== $request->user()->id && ! in_array($request->user()->role, ['admin', 'pm', 'tl'], true)) {
            abort(403);
        }

        $review->delete();

        $vendor->refresh();
        $this->metrics->recalculateReviewAverage($vendor);
        $vendor->save();

        VendorListCache::bump();

        return response()->json(null, 204);
    }
}

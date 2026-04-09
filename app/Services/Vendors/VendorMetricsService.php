<?php

namespace App\Services\Vendors;

use App\Models\Vendor;

class VendorMetricsService
{
    public function recalculateCriterionScore(Vendor $vendor): void
    {
        $parts = array_filter([
            $vendor->score_price !== null ? (float) $vendor->score_price : null,
            $vendor->score_quality !== null ? (float) $vendor->score_quality : null,
            $vendor->score_sla !== null ? (float) $vendor->score_sla : null,
            $vendor->score_support !== null ? (float) $vendor->score_support : null,
        ], static fn ($v) => $v !== null);

        if ($parts === []) {
            $vendor->vendor_score = null;
        } else {
            $vendor->vendor_score = round(array_sum($parts) / count($parts), 2);
        }
    }

    public function recalculateReviewAverage(Vendor $vendor): void
    {
        $avg = $vendor->reviews()->avg('rating');
        $vendor->review_rating_avg = $avg !== null ? round((float) $avg, 2) : null;
    }
}

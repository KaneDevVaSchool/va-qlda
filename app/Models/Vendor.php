<?php

namespace App\Models;

use App\Enums\VendorKind;
use App\Enums\VendorRiskLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'kind',
        'status',
        'legal_name',
        'country',
        'website',
        'tax_code',
        'contact_info',
        'industry',
        'main_products',
        'services_offered',
        'contract_value',
        'estimated_cost',
        'reference_price',
        'vendor_score',
        'score_price',
        'score_quality',
        'score_sla',
        'score_support',
        'risk_level',
        'internal_note',
        'research_source',
        'research_note',
        'pros',
        'cons',
        'fit_score',
        'review_rating_avg',
    ];

    protected $casts = [
        'kind' => VendorKind::class,
        'risk_level' => VendorRiskLevel::class,
        'contract_value' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'reference_price' => 'decimal:2',
        'vendor_score' => 'decimal:2',
        'score_price' => 'decimal:1',
        'score_quality' => 'decimal:1',
        'score_sla' => 'decimal:1',
        'score_support' => 'decimal:1',
        'review_rating_avg' => 'decimal:2',
        'fit_score' => 'integer',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(VendorProduct::class)->orderBy('position')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class)->latest('created_at');
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(VendorTimeline::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'vendor_department');
    }

    public function scopeKind(Builder $query, VendorKind|string $kind): Builder
    {
        $value = $kind instanceof VendorKind ? $kind->value : $kind;

        return $query->where('kind', $value);
    }

    /** Alias for {@see scopeSearchAll} (index `q` filter). */
    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        return $query->searchAll($term);
    }

    /**
     * Full-text style match on vendor fields and product lines (param `q` on index API).
     */
    public function scopeSearchAll(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($term)).'%';

        return $query->where(function (Builder $w) use ($like) {
            $w->where('name', 'like', $like)
                ->orWhere('legal_name', 'like', $like)
                ->orWhere('tax_code', 'like', $like)
                ->orWhere('country', 'like', $like)
                ->orWhere('website', 'like', $like)
                ->orWhere('industry', 'like', $like)
                ->orWhere('research_source', 'like', $like)
                ->orWhere('contact_info', 'like', $like)
                ->orWhere('internal_note', 'like', $like)
                ->orWhere('main_products', 'like', $like)
                ->orWhere('services_offered', 'like', $like)
                ->orWhere('research_note', 'like', $like)
                ->orWhere('pros', 'like', $like)
                ->orWhere('cons', 'like', $like)
                ->orWhereHas('products', function (Builder $pq) use ($like) {
                    $pq->where(function (Builder $p2) use ($like) {
                        $p2->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
                });
        });
    }

    /**
     * Lọc theo giải pháp (main_products) và dịch vụ (services_offered): nhiều từ cách nhau bởi khoảng trắng / phẩy / ; / | —
     * mỗi từ phải xuất hiện ở ít nhất một trong hai cột (AND giữa các từ).
     */
    public function scopeSearchOfferings(Builder $query, ?string $raw): Builder
    {
        if ($raw === null || trim($raw) === '') {
            return $query;
        }

        $tokens = preg_split('/[\s,;|]+/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === []) {
            return $query;
        }

        foreach ($tokens as $token) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $token).'%';
            $query->where(function (Builder $w) use ($like) {
                $w->where('main_products', 'like', $like)
                    ->orWhere('services_offered', 'like', $like);
            });
        }

        return $query;
    }
}
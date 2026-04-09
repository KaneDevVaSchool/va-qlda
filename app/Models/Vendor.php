<?php

namespace App\Models;

use App\Enums\VendorKind;
use App\Enums\VendorRiskLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        return $this->belongsToMany(Department::class, 'vendor_department')->withTimestamps();
    }

    public function scopeKind(Builder $query, VendorKind|string $kind): Builder
    {
        $value = $kind instanceof VendorKind ? $kind->value : $kind;

        return $query->where('kind', $value);
    }

    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($term)).'%';

        return $query->where('name', 'like', $like);
    }
}

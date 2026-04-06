<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared filter/sort logic for project list, CSV/PDF export, and counts.
 */
class ProjectListQueryService
{
    public function filteredQuery(Request $request, bool $withOwner = true): Builder
    {
        $q = Project::query();
        if ($withOwner) {
            $q->with('owner:id,name,email');
        }

        $q->withCount('tasks');

        $this->applyFilters($q, $request);
        $this->applySort($q, $request);

        return $q;
    }

    public function applyFilters(Builder $q, Request $request): void
    {
        if ($type = $request->query('type')) {
            if (in_array($type, ['maintenance', 'delivery', 'rnd'], true)) {
                $q->where('type', $type);
            }
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['on_track', 'at_risk', 'delayed', 'blocked'], true)) {
                $q->where('status', $status);
            }
        }

        if ($request->filled('phase')) {
            $phase = (string) $request->query('phase');
            if (in_array($phase, ['planning', 'development', 'uat', 'done', 'maintenance', 'rnd'], true)) {
                $q->where('phase', $phase);
            }
        } elseif ($request->boolean('active_phase')) {
            $q->where('phase', '!=', 'done');
        }

        if ($request->filled('owner_id')) {
            $q->where('owner_id', (int) $request->query('owner_id'));
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $like = '%'.$escaped.'%';
            $q->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('customer_name', 'like', $like);
            });
        }

        if ($request->filled('progress_min')) {
            $q->where('progress', '>=', max(0, min(100, (float) $request->query('progress_min'))));
        }
        if ($request->filled('progress_max')) {
            $q->where('progress', '<=', max(0, min(100, (float) $request->query('progress_max'))));
        }

        if ($request->boolean('archived')) {
            $q->whereNotNull('archived_at');
        } else {
            $q->whereNull('archived_at');
        }

        if ($label = trim((string) $request->query('label', ''))) {
            $q->whereJsonContains('labels', $label);
        }
    }

    public function applySort(Builder $q, Request $request): void
    {
        $sort = (string) $request->query('sort', 'updated_desc');
        if (! in_array($sort, ['updated_desc', 'progress_desc', 'progress_asc', 'name_asc', 'deadline_asc', 'type_asc'], true)) {
            $sort = 'updated_desc';
        }
        match ($sort) {
            'name_asc' => $q->orderBy('name'),
            'type_asc' => $q->orderBy('type')->orderBy('name'),
            'progress_asc' => $q->orderBy('progress'),
            'progress_desc' => $q->orderByDesc('progress'),
            'deadline_asc' => $q->orderByRaw('(deadline IS NULL) ASC, deadline ASC'),
            default => $q->orderByDesc('updated_at'),
        };
    }
}

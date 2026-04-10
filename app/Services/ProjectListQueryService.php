<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Shared filter/sort logic for project list, CSV/PDF export, and counts.
 */
class ProjectListQueryService
{
    public function filteredQuery(Request $request, bool $withOwner = true): Builder
    {
        $q = Project::query();
        if ($withOwner) {
            $q->with(['owner:id,name,email', 'team:id,name', 'department:id,name,code', 'block:id,name,code']);
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

        if ($request->filled('team_id')) {
            $tid = (int) $request->query('team_id');
            if ($tid > 0) {
                $q->where('team_id', $tid);
            }
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $like = '%' . $escaped . '%';
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
        if (!in_array($sort, ['updated_desc', 'progress_desc', 'progress_asc', 'name_asc', 'deadline_asc', 'type_asc'], true)) {
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

    /**
     * Attach executor_users / follower_users (id, name, email) for API consumers (list / cards).
     */
    public function hydrateParticipantUsers(LengthAwarePaginator $paginator): void
    {
        $this->hydrateParticipantUsersForProjects($paginator->getCollection());
    }

    /**
     * @param  Collection<int, Project>  $projects
     */
    public function hydrateParticipantUsersForProjects(Collection $projects): void
    {
        $ids = [];
        foreach ($projects as $p) {
            foreach (array_merge($p->executor_user_ids ?? [], $p->follower_user_ids ?? []) as $id) {
                $ids[(int) $id] = true;
            }
        }
        $idList = array_keys($ids);
        if ($idList === []) {
            foreach ($projects as $p) {
                $p->setAttribute('executor_users', collect());
                $p->setAttribute('follower_users', collect());
            }

            return;
        }
        $users = User::query()->whereIn('id', $idList)->get(['id', 'name', 'email'])->keyBy('id');
        foreach ($projects as $p) {
            $ex = collect($p->executor_user_ids ?? [])
                ->map(fn($id) => $users->get((int) $id))
                ->filter()
                ->values();
            $fo = collect($p->follower_user_ids ?? [])
                ->map(fn($id) => $users->get((int) $id))
                ->filter()
                ->values();
            $p->setAttribute('executor_users', $ex);
            $p->setAttribute('follower_users', $fo);
        }
    }
}

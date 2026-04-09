<?php

namespace App\Services\ActivityFeed;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\Vendor;
use App\Models\UserAuditLogRead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityFeedService
{
    public const TZ = 'Asia/Ho_Chi_Minh';

    public function visibleQuery(User $user): Builder
    {
        $canP = $user->can('viewAny', Project::class);
        $canC = $user->can('viewAny', Contract::class);

        if (! $canP && ! $canC) {
            return AuditLog::query()->whereRaw('0 = 1');
        }

        return AuditLog::query()->where(function ($q) use ($canP, $canC) {
            if ($canP) {
                $q->orWhere('auditable_type', Project::class);
            }
            if ($canC) {
                $q->orWhere('auditable_type', Contract::class);
            }
        });
    }

    /**
     * @param  array{subject_type?: string, activity_kind?: string, user_id?: int, from?: string, to?: string, q?: string}  $filters
     * @return array{data: Collection<int, AuditLog>, next_cursor: ?int, has_more: bool}
     */
    public function paginate(User $user, array $filters, int $perPage, ?int $beforeId = null): array
    {
        $q = $this->applyFilters($user, $filters)
            ->with('user:id,name,email');

        if ($beforeId) {
            $q->where('id', '<', $beforeId);
        }

        $rows = $q->orderByDesc('id')->limit($perPage + 1)->get();

        $hasMore = $rows->count() > $perPage;
        if ($hasMore) {
            $rows = $rows->take($perPage);
        }

        if ($rows->isNotEmpty()) {
            $readSet = array_flip(UserAuditLogRead::query()
                ->where('user_id', $user->id)
                ->whereIn('audit_log_id', $rows->pluck('id'))
                ->pluck('audit_log_id')
                ->all());
            foreach ($rows as $row) {
                $row->setAttribute('read_by_user', isset($readSet[$row->id]));
            }
        }

        $nextCursor = $hasMore && $rows->isNotEmpty() ? $rows->last()->id : null;

        return [
            'data' => $rows,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    public const PAGE_ALL_CAP = 2000;

    /**
     * Offset pagination for the activity feed UI (page + per_page).
     *
     * @param  array{subject_type?: string, activity_kind?: string, user_id?: int, from?: string, to?: string, q?: string}  $filters
     * @return array{data: Collection<int, AuditLog>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginatePage(User $user, array $filters, int $page, int $perPage): array
    {
        $total = $this->applyFilters($user, $filters)->count();

        $q = $this->applyFilters($user, $filters)
            ->with('user:id,name,email');

        if ($perPage === 0) {
            $rows = $q->orderByDesc('id')->limit(self::PAGE_ALL_CAP)->get();
            $effectivePerPage = $rows->count();
            $currentPage = 1;
            $lastPage = 1;
        } else {
            $lastPage = max(1, (int) ceil($total / $perPage));
            $currentPage = min(max(1, $page), $lastPage);
            $rows = $q->orderByDesc('id')->forPage($currentPage, $perPage)->get();
            $effectivePerPage = $perPage;
        }

        if ($rows->isNotEmpty()) {
            $readSet = array_flip(UserAuditLogRead::query()
                ->where('user_id', $user->id)
                ->whereIn('audit_log_id', $rows->pluck('id'))
                ->pluck('audit_log_id')
                ->all());
            foreach ($rows as $row) {
                $row->setAttribute('read_by_user', isset($readSet[$row->id]));
            }
        }

        return [
            'data' => $rows,
            'total' => $total,
            'per_page' => $perPage === 0 ? $effectivePerPage : $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * @param  array{subject_type?: string, activity_kind?: string, user_id?: int, from?: string, to?: string, q?: string}  $filters
     */
    public function applyFilters(User $user, array $filters): Builder
    {
        $query = $this->visibleQuery($user);

        if (! empty($filters['subject_type'])) {
            $st = (string) $filters['subject_type'];
            if ($st === 'project') {
                $query->where('auditable_type', Project::class);
            } elseif ($st === 'contract') {
                $query->where('auditable_type', Contract::class);
            }
        }

        if (! empty($filters['activity_kind'])) {
            $query->where('activity_kind', $filters['activity_kind']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            if ($term !== '') {
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
                $like = '%'.$escaped.'%';
                $query->where(function ($q2) use ($like) {
                    $q2->where('subject_label', 'like', $like)
                        ->orWhere('action', 'like', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
            }
        }

        return $query;
    }

    public function unreadCount(User $user): int
    {
        return $this->visibleQuery($user)
            ->whereDoesntHave('readMarks', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }

    /**
     * @param  array{subject_type?: string, activity_kind?: string, user_id?: int, from?: string, to?: string, q?: string}  $filters
     */
    public function markAllRead(User $user, array $filters): int
    {
        $ids = $this->applyFilters($user, $filters)
            ->whereDoesntHave('readMarks', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $count = 0;
        $now = now();
        $ids->chunk(500)->each(function (Collection $chunk) use ($user, $now, &$count) {
            $rows = $chunk->map(fn ($id) => [
                'user_id' => $user->id,
                'audit_log_id' => $id,
                'read_at' => $now,
            ])->all();
            if ($rows !== []) {
                DB::table('user_audit_log_reads')->insertOrIgnore($rows);
                $count += count($rows);
            }
        });

        return $count;
    }

    public function markRead(User $user, AuditLog $log): void
    {
        UserAuditLogRead::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'audit_log_id' => $log->id,
            ],
            ['read_at' => now()]
        );
    }

    public function userCanSeeLog(User $user, AuditLog $log): bool
    {
        if ($log->auditable_type === Project::class) {
            $p = Project::query()->find($log->auditable_id);
            if ($p) {
                return $user->can('view', $p);
            }

            return $user->can('viewAny', Project::class);
        }

        if ($log->auditable_type === Contract::class) {
            $c = Contract::query()->find($log->auditable_id);
            if ($c) {
                return $user->can('view', $c);
            }

            return $user->can('viewAny', Contract::class);
        }

        return false;
    }

    /**
     * @return list<array{field: string, label_key: string, old: mixed, new: mixed}>
     */
    public function buildChanges(AuditLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        if (! is_array($old)) {
            $old = [];
        }
        if (! is_array($new)) {
            $new = [];
        }

        $labels = ActivityFieldLabels::forSubject($log->auditable_type);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $contractRelationMaps = [];
        if ($log->auditable_type === Contract::class) {
            $contractRelationMaps = $this->resolveContractRelationMaps($old, $new);
        }

        $out = [];
        foreach ($keys as $k) {
            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;
            if ($ov != $nv) {
                $ov = $this->normalizeValue($ov);
                $nv = $this->normalizeValue($nv);
                if ($log->auditable_type === Contract::class) {
                    $ov = $this->mapContractRelationDisplay($k, $ov, $contractRelationMaps);
                    $nv = $this->mapContractRelationDisplay($k, $nv, $contractRelationMaps);
                }
                $out[] = [
                    'field' => $k,
                    'label_key' => $labels[$k] ?? 'field.'.$k,
                    'old' => $ov,
                    'new' => $nv,
                ];
            }
        }

        return $out;
    }

    /**
     * Resolve foreign keys on contract audit payloads to human-readable labels (vendor, product, department, users).
     *
     * @return array{vendors: array<int, string>, products: array<int, string>, departments: array<int, string>, users: array<int, string>}
     */
    private function resolveContractRelationMaps(array $old, array $new): array
    {
        $vendorIds = $this->collectNumericIdsForKeys($old, $new, ['vendor_id']);
        $productIds = $this->collectNumericIdsForKeys($old, $new, ['product_id']);
        $departmentIds = $this->collectNumericIdsForKeys($old, $new, ['department_id']);
        $userIds = $this->collectNumericIdsForKeys($old, $new, ['followed_by_id', 'approved_by', 'approver_id']);

        $vendors = $vendorIds !== []
            ? Vendor::query()->whereIn('id', $vendorIds)->pluck('name', 'id')->all()
            : [];
        $products = $productIds !== []
            ? Product::query()->whereIn('id', $productIds)->pluck('name', 'id')->all()
            : [];
        $departments = $departmentIds !== []
            ? Department::query()->whereIn('id', $departmentIds)->pluck('name', 'id')->all()
            : [];
        $users = $userIds !== []
            ? User::query()->whereIn('id', $userIds)->pluck('name', 'id')->all()
            : [];

        return [
            'vendors' => $vendors,
            'products' => $products,
            'departments' => $departments,
            'users' => $users,
        ];
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    private function collectNumericIdsForKeys(array $old, array $new, array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) {
            foreach ([$old[$key] ?? null, $new[$key] ?? null] as $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                if (is_numeric($v)) {
                    $ids[] = (int) $v;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array{vendors: array<int, string>, products: array<int, string>, departments: array<int, string>, users: array<int, string>}  $maps
     */
    private function mapContractRelationDisplay(string $field, mixed $value, array $maps): mixed
    {
        if ($value === null) {
            return null;
        }

        $bucket = match ($field) {
            'vendor_id' => 'vendors',
            'product_id' => 'products',
            'department_id' => 'departments',
            'followed_by_id', 'approved_by', 'approver_id' => 'users',
            default => null,
        };

        if ($bucket === null) {
            return $value;
        }

        if (! is_numeric($value)) {
            return $value;
        }

        $id = (int) $value;
        $label = $maps[$bucket][$id] ?? null;

        return ($label !== null && $label !== '') ? $label : $value;
    }

    private function normalizeValue(mixed $v): mixed
    {
        if ($v === null) {
            return null;
        }
        if (is_array($v)) {
            return $v;
        }
        if (is_object($v) && enum_exists($v::class)) {
            return $v instanceof \BackedEnum ? $v->value : (string) $v;
        }
        if (is_object($v)) {
            return (string) $v;
        }

        return $v;
    }
}

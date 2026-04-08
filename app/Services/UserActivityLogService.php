<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class UserActivityLogService
{
    /**
     * @param  array{q?: string, event?: string, from?: string, to?: string}  $filters
     */
    public function paginate(User $user, int $perPage, array $filters): LengthAwarePaginator
    {
        $uid = $user->id;

        $lh = DB::table('login_histories')
            ->where('user_id', $uid)
            ->select([
                'id',
                'event',
                'ip_address',
                'user_agent',
                'created_at',
                DB::raw("'security' as module"),
                DB::raw("'login_history' as source"),
            ]);

        $this->applyLoginFilters($lh, $filters);

        $al = DB::table('audit_logs')
            ->where('user_id', $uid)
            ->select([
                'id',
                DB::raw('action as event'),
                DB::raw("'' as ip_address"),
                DB::raw("'' as user_agent"),
                'created_at',
                'auditable_type as module',
                DB::raw("'audit_log' as source"),
            ]);

        $this->applyAuditFilters($al, $filters);

        $union = $lh->unionAll($al);
        $base = DB::query()->fromSub($union, 'activity')->orderByDesc('created_at');

        $page = max(1, (int) Paginator::resolveCurrentPage());
        $total = (clone $base)->count();
        $rows = $base->forPage($page, $perPage)->get();

        $items = $rows->map(function ($row) {
            return [
                'id' => ($row->source === 'audit_log' ? 'a-' : 'l-').$row->id,
                'action' => $row->event,
                'module' => $this->shortModule((string) ($row->module ?? '')),
                'ip' => $row->ip_address ?: '—',
                'device' => $row->user_agent ? mb_substr((string) $row->user_agent, 0, 160) : '—',
                'time' => $row->created_at,
                'source' => $row->source,
            ];
        })->values()->all();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * @param  array{q?: string, event?: string, from?: string, to?: string}  $filters
     */
    public function streamCsvLines(User $user, array $filters): \Generator
    {
        $uid = $user->id;

        $lh = DB::table('login_histories')
            ->where('user_id', $uid)
            ->select([
                'id',
                'event',
                'ip_address',
                'user_agent',
                'created_at',
                DB::raw("'security' as module"),
                DB::raw("'login_history' as source"),
            ]);
        $this->applyLoginFilters($lh, $filters);

        $al = DB::table('audit_logs')
            ->where('user_id', $uid)
            ->select([
                'id',
                DB::raw('action as event'),
                DB::raw("'' as ip_address"),
                DB::raw("'' as user_agent"),
                'created_at',
                'auditable_type as module',
                DB::raw("'audit_log' as source"),
            ]);
        $this->applyAuditFilters($al, $filters);

        $union = $lh->unionAll($al);
        $rows = DB::query()->fromSub($union, 'activity')->orderByDesc('created_at')->cursor();

        foreach ($rows as $row) {
            yield [
                'action' => $row->event,
                'module' => $this->shortModule((string) ($row->module ?? '')),
                'ip' => $row->ip_address ?: '',
                'device' => $row->user_agent ? mb_substr((string) $row->user_agent, 0, 200) : '',
                'time' => (string) $row->created_at,
            ];
        }
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $q
     * @param  array{q?: string, event?: string, from?: string, to?: string}  $filters
     */
    protected function applyLoginFilters($q, array $filters): void
    {
        if (! empty($filters['event'])) {
            $q->where('event', $filters['event']);
        }
        if (! empty($filters['from'])) {
            $q->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['q'])) {
            $s = '%'.$filters['q'].'%';
            $q->where(function ($w) use ($s) {
                $w->where('ip_address', 'like', $s)
                    ->orWhere('user_agent', 'like', $s)
                    ->orWhere('event', 'like', $s);
            });
        }
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $q
     * @param  array{q?: string, event?: string, from?: string, to?: string}  $filters
     */
    protected function applyAuditFilters($q, array $filters): void
    {
        if (! empty($filters['event'])) {
            $q->where('action', $filters['event']);
        }
        if (! empty($filters['from'])) {
            $q->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['q'])) {
            $s = '%'.$filters['q'].'%';
            $q->where(function ($w) use ($s) {
                $w->where('action', 'like', $s)
                    ->orWhere('auditable_type', 'like', $s);
            });
        }
    }

    protected function shortModule(string $auditableType): string
    {
        if ($auditableType === 'security' || $auditableType === '') {
            return 'Security';
        }

        $parts = explode('\\', $auditableType);

        return end($parts) ?: $auditableType;
    }
}

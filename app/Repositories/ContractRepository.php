<?php

namespace App\Repositories;

use App\Contracts\Repositories\ContractRepositoryInterface;
use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContractRepository implements ContractRepositoryInterface
{
    public function paginateWithRelations(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->indexBaseQuery($filters)->paginate($perPage);
    }

    public function allMatchingIndexFilters(array $filters, int $limit = 5000): Collection
    {
        return $this->indexBaseQuery($filters)->limit($limit)->get();
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    private function indexBaseQuery(array $filters): Builder
    {
        $query = Contract::query()->with($this->indexEagerLoads());

        return $this->applyIndexSort(
            $this->applyIndexFilters($query, $filters),
            $filters
        );
    }

    public function findDetailById(int $id): Contract
    {
        return Contract::query()
            ->with($this->detailEagerLoads())
            ->findOrFail($id);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Contract>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Contract>
     */
    private function applyIndexFilters($query, array $filters = [])
    {
        if ($filters === []) {
            return $query;
        }

        if (! empty($filters['status'])) {
            $enum = ContractStatus::tryFrom((string) $filters['status']);
            if ($enum) {
                $query->where('status', $enum->value);
            }
        }
        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (! empty($filters['code'])) {
            $query->where('code', 'like', '%'.$filters['code'].'%');
        }
        if (! empty($filters['end_from'])) {
            $query->whereDate('end_date', '>=', $filters['end_from']);
        }
        if (! empty($filters['end_to'])) {
            $query->whereDate('end_date', '<=', $filters['end_to']);
        }

        return $query;
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    private function applyIndexSort(Builder $query, array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'id';
        $order = strtolower((string) ($filters['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'code', 'end_date', 'start_date', 'total_value', 'status', 'updated_at', 'created_by'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'id';
        }
        $query->orderBy($sort, $order);
        if ($sort !== 'id') {
            $query->orderByDesc('id');
        }

        return $query;
    }

    /**
     * @return list<string|array<string, mixed>>
     */
    private function indexEagerLoads(): array
    {
        return ['vendor', 'product', 'department', 'creator:id,name,email', 'approver:id,name,email'];
    }

    /**
     * @return list<string|array<string, mixed>>
     */
    private function detailEagerLoads(): array
    {
        return [
            'vendor',
            'product',
            'department',
            'creator:id,name,email',
            'approver:id,name,email',
            'versions',
            'files.uploader:id,name,email',
            'payments',
            'approvals.approver:id,name,email',
        ];
    }
}

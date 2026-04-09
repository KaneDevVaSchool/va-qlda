<?php

namespace App\Repositories;

use App\Contracts\Repositories\ContractRepositoryInterface;
use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContractRepository implements ContractRepositoryInterface
{
    public function paginateWithRelations(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->applyIndexFilters(
            Contract::query()->with($this->indexEagerLoads()),
            $filters
        )
            ->orderByDesc('id')
            ->paginate($perPage);
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

<?php

namespace App\Services\Contracts;

use App\Contracts\Repositories\ContractRepositoryInterface;
use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Read-side operations for contracts: filters, pagination, and consistent detail loading for thin controllers.
 */
class ContractReadService
{
    public function __construct(
        protected ContractRepositoryInterface $contractRepository
    ) {}

    public function paginateIndex(Request $request): LengthAwarePaginator
    {
        return $this->contractRepository->paginateWithRelations(
            $this->parseIndexFilters($request),
            $this->clampPerPage($request)
        );
    }

    public function detailById(int $id): Contract
    {
        return $this->contractRepository->findDetailById($id);
    }

    public function paginateLogs(Contract $contract, int $perPage = 50): LengthAwarePaginator
    {
        return $contract->logs()
            ->with('user:id,name,email')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{status?: string, vendor_id?: int|string, department_id?: int|string, code?: string}
     */
    private function parseIndexFilters(Request $request): array
    {
        return array_filter([
            'status' => $request->query('status'),
            'vendor_id' => $request->query('vendor_id'),
            'department_id' => $request->query('department_id'),
            'code' => $request->query('code'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function clampPerPage(Request $request): int
    {
        return min(100, max(10, (int) $request->query('per_page', 25)));
    }
}

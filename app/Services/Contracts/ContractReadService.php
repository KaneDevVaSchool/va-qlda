<?php

namespace App\Services\Contracts;

use App\Contracts\Repositories\ContractRepositoryInterface;
use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function paginateLogs(Contract $contract, ?string $action = null, int $perPage = 50): LengthAwarePaginator
    {
        $query = $contract->logs()
            ->with('user:id,name,email')
            ->orderByDesc('id');

        if ($action !== null && $action !== '') {
            $query->where('action', $action);
        }

        return $query->paginate($perPage);
    }

    public function exportCsvResponse(Request $request): StreamedResponse
    {
        $filters = $this->parseIndexFilters($request);
        $contracts = $this->contractRepository->allMatchingIndexFilters($filters, 5000);

        $filename = 'contracts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($contracts): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'code',
                'vendor',
                'product',
                'department',
                'status',
                'start_date',
                'end_date',
                'total_value',
                'payment_cycle',
                'updated_at',
                'follower',
            ]);
            foreach ($contracts as $c) {
                fputcsv($out, [
                    $c->code,
                    $c->vendor?->name,
                    $c->product?->name,
                    $c->department?->name,
                    $c->status?->value,
                    $c->start_date?->toDateString(),
                    $c->end_date?->toDateString(),
                    (string) $c->total_value,
                    $c->payment_cycle?->value,
                    $c->updated_at?->toIso8601String(),
                    $c->creator?->name,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseIndexFilters(Request $request): array
    {
        return array_filter([
            'status' => $request->query('status'),
            'vendor_id' => $request->query('vendor_id'),
            'department_id' => $request->query('department_id'),
            'code' => $request->query('code'),
            'end_from' => $request->query('end_from'),
            'end_to' => $request->query('end_to'),
            'sort' => $request->query('sort'),
            'order' => $request->query('order'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function clampPerPage(Request $request): int
    {
        return min(100, max(10, (int) $request->query('per_page', 25)));
    }
}

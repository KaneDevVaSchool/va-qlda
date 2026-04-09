<?php

namespace App\Contracts\Repositories;

use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ContractRepositoryInterface
{
    public function paginateWithRelations(array $filters, int $perPage): LengthAwarePaginator;

    /** Same filters/sort as index, capped for CSV export. */
    public function allMatchingIndexFilters(array $filters, int $limit = 5000): Collection;

    /** Full detail payload for API responses (eager loads). */
    public function findDetailById(int $id): Contract;
}

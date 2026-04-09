<?php

namespace App\Contracts\Repositories;

use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContractRepositoryInterface
{
    public function paginateWithRelations(array $filters, int $perPage): LengthAwarePaginator;

    /** Full detail payload for API responses (eager loads). */
    public function findDetailById(int $id): Contract;
}

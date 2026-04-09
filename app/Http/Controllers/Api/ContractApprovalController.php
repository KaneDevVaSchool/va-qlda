<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Services\Contracts\ApprovalService;
use App\Services\Contracts\ContractReadService;
use Illuminate\Http\Request;

class ContractApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
        protected ContractReadService $contractRead
    ) {}

    public function approve(Request $request, Contract $contract)
    {
        $this->authorize('approve', $contract);

        $this->approvalService->approve($contract, $request->user());

        return new ContractResource($this->contractRead->detailById($contract->id));
    }

    public function reject(Request $request, Contract $contract)
    {
        $this->authorize('reject', $contract);

        $this->approvalService->reject($contract, $request->user());

        return new ContractResource($this->contractRead->detailById($contract->id));
    }
}

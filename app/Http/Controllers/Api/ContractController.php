<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\StoreContractRequest;
use App\Http\Requests\Contracts\SubmitContractRequest;
use App\Http\Requests\Contracts\UpdateContractRequest;
use App\Http\Resources\ContractLogResource;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Services\Contracts\ApprovalService;
use App\Services\Contracts\ContractReadService;
use App\Services\Contracts\ContractService;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        protected ContractReadService $contractRead,
        protected ContractService $contractService,
        protected ApprovalService $approvalService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        return ContractResource::collection($this->contractRead->paginateIndex($request));
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Contract::class);

        return $this->contractRead->exportCsvResponse($request);
    }

    public function store(StoreContractRequest $request)
    {
        $this->authorize('create', Contract::class);

        $contract = $this->contractService->create($request->validated(), $request->user());

        return (new ContractResource($contract))->response()->setStatusCode(201);
    }

    public function show(Contract $contract)
    {
        $this->authorize('view', $contract);

        return new ContractResource($this->contractRead->detailById($contract->id));
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $this->authorize('update', $contract);

        return new ContractResource(
            $this->contractService->update($contract, $request->validated(), $request->user())
        );
    }

    public function destroy(Request $request, Contract $contract)
    {
        $this->authorize('delete', $contract);

        $this->contractService->delete($contract, $request->user());

        return response()->json(null, 204);
    }

    public function submit(SubmitContractRequest $request, Contract $contract)
    {
        $this->authorize('submit', $contract);

        $approverIds = array_column($request->validated()['approvers'], 'user_id');
        $this->approvalService->submit($contract, $approverIds, $request->user());

        return new ContractResource($this->contractRead->detailById($contract->id));
    }

    public function terminate(Request $request, Contract $contract)
    {
        $this->authorize('terminate', $contract);

        return new ContractResource(
            $this->contractService->terminate($contract, $request->user())
        );
    }

    public function logs(Contract $contract)
    {
        $this->authorize('view', $contract);

        return ContractLogResource::collection($this->contractRead->paginateLogs($contract));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\StoreContractFileRequest;
use App\Http\Resources\ContractFileResource;
use App\Http\Resources\ContractVersionResource;
use App\Models\Contract;
use App\Models\ContractFile;
use App\Services\Contracts\FileService;

class ContractFileController extends Controller
{
    public function __construct(
        protected FileService $files
    ) {}

    public function index(Contract $contract)
    {
        $this->authorize('view', $contract);

        return ContractFileResource::collection($this->files->filesWithUploaders($contract));
    }

    public function store(StoreContractFileRequest $request, Contract $contract)
    {
        $this->authorize('uploadFiles', $contract);

        $data = $request->validated();
        $result = $this->files->store(
            $contract,
            $request->file('file'),
            $request->user(),
            (bool) ($data['create_version'] ?? false),
            $data['version_note'] ?? null
        );

        return response()->json([
            'file' => new ContractFileResource($result['file']->load('uploader:id,name,email')),
            'version' => $result['version'] ? new ContractVersionResource($result['version']) : null,
        ], 201);
    }

    public function download(Contract $contract, ContractFile $file)
    {
        $this->authorize('view', $contract);

        return $this->files->downloadResponse($contract, $file);
    }
}

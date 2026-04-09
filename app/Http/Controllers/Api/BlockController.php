<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlockRequest;
use App\Http\Resources\BlockResource;
use App\Models\Block;

class BlockController extends Controller
{
    public function store(StoreBlockRequest $request)
    {
        $this->authorize('create', Block::class);

        $block = Block::query()->create($request->validated());

        return (new BlockResource($block))->response()->setStatusCode(201);
    }
}

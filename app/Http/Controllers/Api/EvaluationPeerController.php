<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationPeer;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class EvaluationPeerController extends Controller
{
    public function store(Request $request, Evaluation $evaluation)
    {
        if ($evaluation->status === 'approved') {
            abort(422, 'Evaluation is locked.');
        }

        $data = $request->validate([
            'reviewer_id' => 'required|exists:cms.users,id',
            'attitude_score' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);

        if ((int) $data['reviewer_id'] === (int) $evaluation->person_id) {
            abort(422, 'Peer cannot be the same as evaluatee.');
        }

        $peer = EvaluationPeer::query()->updateOrCreate(
            [
                'evaluation_id' => $evaluation->id,
                'reviewer_id' => $data['reviewer_id'],
            ],
            [
                'attitude_score' => $data['attitude_score'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        AuditLogger::log('evaluation_peer.upsert', $peer, null, $peer->toArray());

        return response()->json($peer->load('reviewer:id,name'), 201);
    }

    public function destroy(Request $request, EvaluationPeer $peer)
    {
        $evaluation = $peer->evaluation;
        if ($evaluation->status === 'approved') {
            abort(422, 'Evaluation is locked.');
        }

        $peer->delete();

        return response()->noContent();
    }
}

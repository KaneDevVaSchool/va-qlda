<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CsatResponse;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CsatController extends Controller
{
    public function index(Project $project)
    {
        return $project->csatResponses()->orderByDesc('id')->limit(100)->get();
    }

    public function store(Request $request, Project $project)
    {
        if ($project->type !== 'delivery') {
            abort(422, 'CSAT applies to delivery (Type 2) projects.');
        }

        $data = $request->validate([
            'milestone_label' => 'nullable|string|max:128',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'rater_email' => 'nullable|email',
        ]);

        $row = $project->csatResponses()->create(array_merge($data, [
            'user_id' => $request->user()->id,
        ]));

        AuditLogger::log('csat.created', $row, null, $row->toArray());

        return response()->json($row, 201);
    }
}

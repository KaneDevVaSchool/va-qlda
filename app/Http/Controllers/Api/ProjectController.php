<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectProgressService $progressService
    ) {}

    public function index(Request $request)
    {
        $q = Project::query()->with('owner:id,name,email');

        if ($type = $request->query('type')) {
            $q->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($request->boolean('archived')) {
            $q->whereNotNull('archived_at');
        } else {
            $q->whereNull('archived_at');
        }

        return $q->orderByDesc('updated_at')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:maintenance,delivery,rnd',
            'phase' => 'sometimes|in:planning,development,uat,done,maintenance',
            'status' => 'sometimes|in:on_track,at_risk,delayed,blocked',
            'owner_id' => 'required|exists:users,id',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'stakeholder_emails' => 'nullable|array',
            'stakeholder_emails.*' => 'email',
        ]);

        $project = Project::create(array_merge([
            'phase' => $data['phase'] ?? 'planning',
            'status' => $data['status'] ?? 'on_track',
            'progress' => 0,
        ], $data));

        AuditLogger::log('project.created', $project, null, $project->only(array_keys($data)));

        return response()->json($project->load('owner:id,name,email'), 201);
    }

    public function show(Project $project)
    {
        $project->load([
            'owner:id,name,email',
            'tasks.assignee:id,name,email',
            'tasks.children',
            'tasks.predecessors:id,name,status',
        ]);
        $project->loadCount('csatResponses');

        $inv = max(1, (int) $project->csat_invites_sent);

        return response()->json(array_merge($project->toArray(), [
            'csat_metrics' => [
                'response_count' => (int) $project->csat_responses_count,
                'invites_sent' => (int) $project->csat_invites_sent,
                'response_rate_pct' => round(min(100, ($project->csat_responses_count / $inv) * 100), 2),
            ],
        ]));
    }

    /**
     * BR-PM-04: dữ liệu Gantt + meta layout (filter assignee, status, root_only).
     */
    public function gantt(Request $request, Project $project)
    {
        $q = $project->tasks()->with(['assignee:id,name', 'predecessors:id,name']);

        if ($request->filled('assignee_id')) {
            $q->where('assignee_id', (int) $request->query('assignee_id'));
        }

        if ($request->filled('status')) {
            $statuses = array_values(array_filter(explode(',', (string) $request->query('status'))));
            if ($statuses !== []) {
                $q->whereIn('status', $statuses);
            }
        }

        if ($request->boolean('root_only')) {
            $q->whereNull('parent_id');
        }

        $tasks = $q->orderBy('sort_order')->orderBy('id')->get();

        $dates = $tasks->pluck('due_date')->filter();
        if ($dates->isEmpty()) {
            $min = now()->copy()->startOfWeek();
            $max = now()->copy()->addWeeks(4)->endOfWeek();
        } else {
            $min = \Illuminate\Support\Carbon::parse($dates->min())->startOfWeek();
            $max = \Illuminate\Support\Carbon::parse($dates->max())->endOfWeek();
        }

        $windowDays = max(1, $min->diffInDays($max));

        $bars = $tasks->map(function (Task $t) use ($min, $windowDays) {
            $end = $t->due_date
                ? \Illuminate\Support\Carbon::parse($t->due_date)->startOfDay()
                : $min->copy()->addDays(3);
            $estDays = max(1, min(21, (int) ceil((float) $t->estimate_hours / 8)));
            $start = $end->copy()->subDays($estDays);
            if ($start->lt($min)) {
                $start = $min->copy();
            }

            $leftPct = ($start->diffInDays($min) / $windowDays) * 100;
            $widthPct = (max(1, $start->diffInDays($end)) / $windowDays) * 100;

            return [
                'task_id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'assignee_id' => $t->assignee_id,
                'assignee_name' => $t->assignee?->name,
                'due_date' => $t->due_date?->toDateString(),
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'progress_pct' => $t->status === 'done' ? 100 : ($t->status === 'in_progress' ? 50 : 0),
                'layout' => [
                    'left_pct' => round(min(92, max(0, $leftPct)), 2),
                    'width_pct' => round(min(90, max(3, $widthPct)), 2),
                ],
            ];
        });

        return response()->json([
            'window' => [
                'start' => $min->toDateString(),
                'end' => $max->toDateString(),
                'days' => $windowDays,
            ],
            'bars' => $bars,
            'filters' => [
                'assignee_id' => $request->query('assignee_id'),
                'status' => $request->query('status'),
                'root_only' => $request->boolean('root_only'),
            ],
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $before = $project->getAttributes();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:maintenance,delivery,rnd',
            'phase' => 'sometimes|in:planning,development,uat,done,maintenance,rnd',
            'status' => 'sometimes|in:on_track,at_risk,delayed,blocked',
            'owner_id' => 'sometimes|exists:users,id',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'stakeholder_emails' => 'nullable|array',
            'stakeholder_emails.*' => 'email',
        ]);

        $project->update($data);
        $this->progressService->syncProjectProgress($project->fresh());

        AuditLogger::log('project.updated', $project, $before, $project->getAttributes());

        return $project->fresh()->load('owner:id,name,email');
    }

    public function destroy(Project $project)
    {
        AuditLogger::log('project.deleted', $project, $project->getAttributes(), null);
        $project->delete();

        return response()->noContent();
    }

    public function archive(Project $project)
    {
        $project->update(['archived_at' => now()]);
        AuditLogger::log('project.archived', $project, null, ['archived_at' => (string) $project->archived_at]);

        return $project->fresh();
    }

    /**
     * BR-PM-06: Clone project (optional reset dates / open tasks).
     */
    public function duplicate(Request $request, Project $project)
    {
        $reset = $request->boolean('reset_dates', true);

        $newId = DB::transaction(function () use ($project, $reset) {
            $np = $project->replicate();
            $np->name = $project->name.' (copy)';
            $np->progress = 0;
            $np->archived_at = null;
            $np->status = 'on_track';
            if ($reset) {
                $np->deadline = null;
            }
            $np->csat_invites_sent = 0;
            $np->csat_survey_sent_at = null;
            $np->save();

            $tasks = $project->tasks()->orderBy('id')->get();
            $map = [];
            foreach ($tasks as $t) {
                $nt = $t->replicate();
                $nt->project_id = $np->id;
                $nt->parent_id = null;
                if ($reset) {
                    $nt->due_date = null;
                    $nt->blocked_at = null;
                    $nt->blocked_reason = null;
                    $nt->actual_hours = 0;
                    $nt->status = 'todo';
                }
                $nt->save();
                $map[$t->id] = $nt->id;
            }
            foreach ($tasks as $t) {
                if ($t->parent_id && isset($map[$t->parent_id], $map[$t->id])) {
                    Task::query()->whereKey($map[$t->id])->update(['parent_id' => $map[$t->parent_id]]);
                }
            }

            AuditLogger::log('project.duplicated', $np, null, ['source_id' => $project->id]);

            return $np->id;
        });

        $fresh = Project::query()->with('owner:id,name,email')->findOrFail($newId);
        $this->progressService->syncProjectProgress($fresh);

        return response()->json($fresh->fresh()->load('owner:id,name,email'), 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Block;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskParticipant;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ProjectCodeGenerator;
use App\Services\ProjectListQueryService;
use App\Services\ProjectMediaService;
use App\Services\ProjectProgressService;
use App\Services\TaskProgressDisplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectProgressService $progressService,
        protected ProjectListQueryService $projectListQuery
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $perPage = min(100, max(10, (int) $request->query('per_page', 50)));

        $paginator = $this->projectListQuery->filteredQuery($request)->paginate($perPage);
        $this->projectListQuery->hydrateParticipantUsers($paginator);

        return $paginator;
    }

    /**
     * Counts for project list scope tabs (non-archived projects only).
     */
    public function tabCounts()
    {
        $this->authorize('viewAny', Project::class);

        return response()->json([
            'active' => Project::query()->whereNull('archived_at')->where('phase', '!=', 'done')->count(),
            'done' => Project::query()->whereNull('archived_at')->where('phase', 'done')->count(),
            'all' => Project::query()->whereNull('archived_at')->count(),
            'maintenance' => Project::query()->whereNull('archived_at')->where('type', 'maintenance')->count(),
            'delivery' => Project::query()->whereNull('archived_at')->where('type', 'delivery')->count(),
            'rnd' => Project::query()->whereNull('archived_at')->where('type', 'rnd')->count(),
        ]);
    }

    /**
     * Distinct project labels (non-archived), for filters and autocomplete.
     */
    public function labelSuggestions(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $includeArchived = $request->boolean('archived');
        $q = Project::query()->whereNotNull('labels');
        if (! $includeArchived) {
            $q->whereNull('archived_at');
        }
        $rows = $q->pluck('labels');
        $map = [];
        foreach ($rows as $cell) {
            foreach ((array) $cell as $raw) {
                $t = trim((string) $raw);
                if ($t === '') {
                    continue;
                }
                $k = function_exists('mb_strtolower') ? mb_strtolower($t) : strtolower($t);
                $map[$k] = $map[$k] ?? $t;
            }
        }
        $list = array_values($map);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return response()->json(['labels' => $list]);
    }

    /**
     * Bulk update owner, phase, archive, and/or labels. Logs per project (audit).
     */
    public function bulk(Request $request)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'tl'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'project_ids' => 'required|array|min:1|max:50',
            'project_ids.*' => 'integer|exists:projects,id',
            'owner_id' => ['nullable', Rule::exists(User::class, 'id')],
            'phase' => 'nullable|in:planning,development,uat,done,maintenance,rnd',
            'archived' => 'nullable|boolean',
            'add_labels' => 'nullable|array|max:20',
            'add_labels.*' => 'required|string|max:64',
            'remove_labels' => 'nullable|array|max:20',
            'remove_labels.*' => 'required|string|max:64',
        ]);

        $hasOwner = $request->filled('owner_id');
        $hasPhase = $request->filled('phase');
        $hasArchived = $request->has('archived');
        $addLabels = $request->input('add_labels', []);
        $removeLabels = $request->input('remove_labels', []);
        $hasLabelOps = (is_array($addLabels) && $addLabels !== []) || (is_array($removeLabels) && $removeLabels !== []);

        if (! $hasOwner && ! $hasPhase && ! $hasArchived && ! $hasLabelOps) {
            throw ValidationException::withMessages([
                'project_ids' => ['Specify owner_id, phase, archived, or label operations.'],
            ]);
        }

        $addNormalized = $hasLabelOps ? Project::normalizeLabelList(array_values($addLabels)) : [];
        $removeNormalized = $hasLabelOps ? Project::normalizeLabelList(array_values($removeLabels)) : [];

        $projects = Project::query()->whereIn('id', $data['project_ids'])->get();
        $updated = 0;

        foreach ($projects as $project) {
            $before = $project->getAttributes();
            $updates = [];

            if ($hasOwner) {
                $updates['owner_id'] = (int) $request->input('owner_id');
            }
            if ($hasPhase) {
                $updates['phase'] = $request->input('phase');
            }
            if ($hasArchived) {
                $updates['archived_at'] = $request->boolean('archived') ? now() : null;
            }
            if ($hasLabelOps) {
                $labs = Project::normalizeLabelList($project->labels ?? []) ?? [];
                if (is_array($addNormalized) && $addNormalized !== []) {
                    $labs = Project::normalizeLabelList(array_merge($labs, $addNormalized)) ?? [];
                }
                if (is_array($removeNormalized) && $removeNormalized !== []) {
                    $rm = array_map(
                        fn ($x) => function_exists('mb_strtolower') ? mb_strtolower(trim((string) $x)) : strtolower(trim((string) $x)),
                        $removeNormalized
                    );
                    $labs = array_values(array_filter($labs, function ($l) use ($rm) {
                        $k = function_exists('mb_strtolower') ? mb_strtolower($l) : strtolower($l);

                        return ! in_array($k, $rm, true);
                    }));
                }
                $updates['labels'] = $labs;
            }

            if ($updates === []) {
                continue;
            }

            $project->update($updates);
            $fresh = $project->fresh();
            $this->progressService->syncProjectProgress($fresh);

            $after = $fresh->fresh();
            AuditLogger::log('project.bulk_updated', $project, [
                'owner_id' => $before['owner_id'] ?? null,
                'phase' => $before['phase'] ?? null,
                'archived_at' => isset($before['archived_at']) ? (string) $before['archived_at'] : null,
                'progress' => $before['progress'] ?? null,
                'status' => $before['status'] ?? null,
            ], [
                'owner_id' => $after->owner_id,
                'phase' => $after->phase,
                'archived_at' => $after->archived_at?->toIso8601String(),
                'progress' => (string) $after->progress,
                'status' => $after->status,
            ]);
            $updated++;
        }

        return response()->json(['updated' => $updated]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:maintenance,delivery,rnd',
            'phase' => 'sometimes|in:planning,development,uat,done,maintenance',
            'status' => 'sometimes|in:on_track,at_risk,delayed,blocked',
            'owner_id' => ['required', Rule::exists(User::class, 'id')],
            'deadline' => 'nullable|date',
            'start_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'description' => 'nullable|string',
            'progress_calc' => 'nullable|in:weighted_tasks,average_task_pct,time_proportion',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'block_id' => ['nullable', 'integer', Rule::exists(Block::class, 'id')],
            'suppliers' => 'nullable|array',
            'suppliers.*.name' => 'required|string|max:255',
            'suppliers.*.contact' => 'nullable|string|max:255',
            'process_timeline' => 'nullable|array',
            'process_timeline.*.phase' => 'required|in:planning,development,uat,done,maintenance',
            'process_timeline.*.completed_at' => 'nullable|date',
            'stakeholder_emails' => 'nullable|array',
            'stakeholder_emails.*' => 'email',
            'labels' => 'nullable|array',
            'labels.*' => 'string|max:64',
            'executor_user_ids' => 'nullable|array|max:40',
            'executor_user_ids.*' => ['integer', Rule::exists(User::class, 'id')],
            'follower_user_ids' => 'nullable|array|max:40',
            'follower_user_ids.*' => ['integer', Rule::exists(User::class, 'id')],
            'permission_preset' => 'nullable|in:org_default,members_only,owner_only',
            'estimated_value' => 'nullable|numeric|min:0',
            'team_id' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
        ]);

        if (array_key_exists('labels', $data)) {
            $data['labels'] = Project::normalizeLabelList($data['labels'] ?? []) ?? [];
        }

        $data['executor_user_ids'] = $this->normalizeProjectUserIdList($data['executor_user_ids'] ?? null);
        $data['follower_user_ids'] = $this->normalizeProjectUserIdList($data['follower_user_ids'] ?? null);
        if (empty($data['permission_preset'])) {
            $data['permission_preset'] = 'org_default';
        }

        $project = Project::create(array_merge([
            'phase' => $data['phase'] ?? 'planning',
            'status' => $data['status'] ?? 'on_track',
            'progress' => 0,
        ], $data));

        if (trim((string) $project->code) === '') {
            ProjectCodeGenerator::assignIfEmpty($project);
        }

        AuditLogger::log('project.created', $project, null, $project->only(array_keys($data)));

        $project->load(['owner:id,name,email', 'team:id,name', 'department:id,name,code', 'block:id,name,code']);
        $this->projectListQuery->hydrateParticipantUsersForProjects(collect([$project]));

        return response()->json($project, 201);
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'owner:id,name,email',
            'team:id,name',
            'department:id,name,code',
            'block:id,name,code',
            'phases',
            'supplies',
            'tasks.assignee:id,name,email,role',
            'tasks.taskParticipants',
            'tasks.projectPhase:id,name',
            'tasks.children',
            'tasks.predecessors:id,name,status',
        ]);
        $project->loadCount('csatResponses');

        $inv = max(1, (int) $project->csat_invites_sent);

        $this->projectListQuery->hydrateParticipantUsersForProjects(collect([$project]));

        foreach ($project->tasks as $task) {
            $task->mergeParticipantArraysIntoAttributes();
            $task->makeHidden(['task_participants']);
        }

        $payload = $project->toArray();
        // Luôn trả mảng JSON thuần (id, name, email) để client không bị lệch kiểu object/map.
        $payload['executor_users'] = $this->serializeProjectParticipantUsers($project->getAttribute('executor_users'));
        $payload['follower_users'] = $this->serializeProjectParticipantUsers($project->getAttribute('follower_users'));

        return response()->json(array_merge($payload, [
            'csat_metrics' => [
                'response_count' => (int) $project->csat_responses_count,
                'invites_sent' => (int) $project->csat_invites_sent,
                'response_rate_pct' => round(min(100, ($project->csat_responses_count / $inv) * 100), 2),
            ],
        ]));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\User>|null  $users
     * @return list<array{id: int, name: string, email: string}>
     */
    private function serializeProjectParticipantUsers($users): array
    {
        return collect($users ?? [])
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) ($u->email ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * All task attachments for this project (for project-level file views).
     */
    public function attachments(Project $project)
    {
        $this->authorize('view', $project);

        return TaskAttachment::query()
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->with(['task:id,name', 'uploader:id,name'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Unified project library + task attachments for the media tab (scope + q optional).
     */
    public function media(Request $request, Project $project, ProjectMediaService $mediaService)
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'scope' => 'nullable|in:all,project,task',
            'q' => 'nullable|string|max:2048',
        ]);

        $scope = $data['scope'] ?? 'all';
        $q = $data['q'] ?? null;

        $items = $mediaService->listForProject(
            $project,
            $scope === 'all' ? null : $scope,
            $q
        );

        return collect($items)
            ->map(fn (array $row) => Arr::except($row, ['sort_at']))
            ->values();
    }

    /**
     * BR-PM-04: dữ liệu Gantt + meta layout (filter assignee, status, root_only).
     */
    public function gantt(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $q = $project->tasks()->with([
            'assignee:id,name',
            'predecessors:id,name',
            'children.children',
        ]);

        if ($request->filled('assignee_id')) {
            $aid = (int) $request->query('assignee_id');
            $q->where(function ($w) use ($aid) {
                $w->where('assignee_id', $aid)
                    ->orWhereHas('taskParticipants', function ($p) use ($aid) {
                        $p->where('user_id', $aid)->where('role', TaskParticipant::ROLE_ASSIGNEE);
                    });
            });
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
                'progress_pct' => TaskProgressDisplayService::percent($t),
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
        $this->authorize('update', $project);

        $before = $project->getAttributes();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:64',
            'type' => 'sometimes|in:maintenance,delivery,rnd',
            'phase' => 'sometimes|in:planning,development,uat,done,maintenance,rnd',
            'status' => 'sometimes|in:on_track,at_risk,delayed,blocked',
            'owner_id' => ['sometimes', Rule::exists(User::class, 'id')],
            'deadline' => 'nullable|date',
            'start_date' => 'nullable|date',
            'actual_start_date' => 'nullable|date',
            'description' => 'nullable|string',
            'estimated_value' => 'nullable|numeric|min:0',
            'progress_calc' => 'nullable|in:weighted_tasks,average_task_pct,time_proportion',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'block_id' => ['nullable', 'integer', Rule::exists(Block::class, 'id')],
            'suppliers' => 'nullable|array',
            'suppliers.*.name' => 'required|string|max:255',
            'suppliers.*.contact' => 'nullable|string|max:255',
            'process_timeline' => 'nullable|array',
            'process_timeline.*.phase' => 'required|in:planning,development,uat,done,maintenance',
            'process_timeline.*.completed_at' => 'nullable|date',
            'stakeholder_emails' => 'nullable|array',
            'stakeholder_emails.*' => 'email',
            'labels' => 'nullable|array',
            'labels.*' => 'string|max:64',
            'executor_user_ids' => 'nullable|array|max:40',
            'executor_user_ids.*' => ['integer', Rule::exists(User::class, 'id')],
            'follower_user_ids' => 'nullable|array|max:40',
            'follower_user_ids.*' => ['integer', Rule::exists(User::class, 'id')],
            'permission_preset' => 'nullable|in:org_default,members_only,owner_only',
            'team_id' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
        ]);

        if (array_key_exists('labels', $data)) {
            $data['labels'] = Project::normalizeLabelList($data['labels'] ?? []) ?? [];
        }

        if (array_key_exists('executor_user_ids', $data) || array_key_exists('follower_user_ids', $data) || array_key_exists('owner_id', $data)) {
            if (array_key_exists('executor_user_ids', $data)) {
                $data['executor_user_ids'] = $this->normalizeProjectUserIdList($data['executor_user_ids']);
            }
            if (array_key_exists('follower_user_ids', $data)) {
                $data['follower_user_ids'] = $this->normalizeProjectUserIdList($data['follower_user_ids']);
            }
        }

        $project->update($data);
        $this->progressService->syncProjectProgress($project->fresh());

        AuditLogger::log('project.updated', $project, $before, $project->getAttributes());

        $fresh = $project->fresh()->load(['owner:id,name,email', 'team:id,name', 'department:id,name,code', 'block:id,name,code']);
        $this->projectListQuery->hydrateParticipantUsersForProjects(collect([$fresh]));

        return $fresh;
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        AuditLogger::log('project.deleted', $project, $project->getAttributes(), null);
        $project->delete();

        return response()->noContent();
    }

    /**
     * Permanently delete multiple projects (admin / PM only). Tasks cascade. Audit per project.
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'project_ids' => 'required|array|min:1|max:50',
            'project_ids.*' => 'integer|exists:projects,id',
        ]);

        $deleted = 0;

        DB::transaction(function () use ($data, &$deleted, $request) {
            $projects = Project::query()->whereIn('id', $data['project_ids'])->orderBy('id')->get();
            foreach ($projects as $project) {
                if (! $request->user()->can('delete', $project)) {
                    abort(403);
                }
                AuditLogger::log('project.deleted', $project, $project->getAttributes(), null);
                $project->delete();
                $deleted++;
            }
        });

        return response()->json(['deleted' => $deleted]);
    }

    public function archive(Project $project)
    {
        $this->authorize('update', $project);

        $project->update(['archived_at' => now()]);
        AuditLogger::log('project.archived', $project, null, ['archived_at' => (string) $project->archived_at]);

        return $project->fresh();
    }

    /**
     * Nhật ký thay đổi (audit) gắn dự án và công việc trong dự án.
     */
    public function activities(Project $project)
    {
        $this->authorize('view', $project);

        $taskIds = Task::query()->where('project_id', $project->id)->pluck('id');

        $logs = AuditLog::query()
            ->where(function ($q) use ($project, $taskIds) {
                $q->where(function ($q2) use ($project) {
                    $q2->where('auditable_type', Project::class)->where('auditable_id', $project->id);
                });
                if ($taskIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($taskIds) {
                        $q2->where('auditable_type', Task::class)->whereIn('auditable_id', $taskIds);
                    });
                }
            })
            ->orderByDesc('id')
            ->limit(80)
            ->with('user:id,name')
            ->get();

        return response()->json(['activities' => $logs]);
    }

    /**
     * Thêm email người dùng hiện tại vào danh sách bên liên quan (stakeholders).
     */
    public function joinStakeholder(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $email = strtolower(trim((string) $request->user()->email));
        if ($email === '') {
            abort(422, 'User has no email.');
        }

        $emails = array_map('strtolower', $project->stakeholder_emails ?? []);
        if (! in_array($email, $emails, true)) {
            $emails[] = $email;
            $project->update(['stakeholder_emails' => array_values(array_unique($emails))]);
        }

        return response()->json($project->fresh()->load('owner:id,name,email'));
    }

    /**
     * BR-PM-06: Clone project (optional reset dates / open tasks).
     */
    public function duplicate(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $this->authorize('create', Project::class);

        $reset = $request->boolean('reset_dates', true);

        $newId = DB::transaction(function () use ($project, $reset) {
            $project->loadMissing(['phases', 'supplies']);

            $np = $project->replicate();
            $np->name = $project->name.' (copy)';
            $np->code = null;
            $np->progress = 0;
            $np->archived_at = null;
            $np->status = 'on_track';
            if ($reset) {
                $np->deadline = null;
                $np->start_date = null;
                $np->actual_start_date = null;
            }
            $np->csat_invites_sent = 0;
            $np->csat_survey_sent_at = null;
            $np->save();
            if (trim((string) $np->code) === '') {
                ProjectCodeGenerator::assignIfEmpty($np);
            }

            $phaseMap = [];
            foreach ($project->phases as $ph) {
                $nph = $ph->replicate();
                $nph->project_id = $np->id;
                if ($reset) {
                    $nph->start_date = null;
                    $nph->end_date = null;
                }
                $nph->save();
                $phaseMap[$ph->id] = $nph->id;
            }

            foreach ($project->supplies as $sup) {
                $ns = $sup->replicate();
                $ns->project_id = $np->id;
                $ns->save();
            }

            $tasks = $project->tasks()->orderBy('id')->get();
            $map = [];
            foreach ($tasks as $t) {
                $nt = $t->replicate();
                $nt->project_id = $np->id;
                $nt->parent_id = null;
                if ($t->project_phase_id) {
                    $nt->project_phase_id = $phaseMap[$t->project_phase_id] ?? null;
                }
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
        $fresh = $fresh->fresh()->load('owner:id,name,email');
        $this->projectListQuery->hydrateParticipantUsersForProjects(collect([$fresh]));

        return response()->json($fresh, 201);
    }

    /**
     * @param  list<int>|null  $raw
     * @return list<int>
     */
    private function normalizeProjectUserIdList(?array $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($raw as $id) {
            $n = (int) $id;
            // Giữ đúng danh sách người dùng chọn (kể cả owner nếu được tick) — trước đây bỏ owner_id nên UI "Người thực hiện" thiếu người.
            if ($n < 1 || isset($seen[$n])) {
                continue;
            }
            $seen[$n] = true;
            $out[] = $n;
            if (count($out) >= 40) {
                break;
            }
        }

        return $out;
    }
}

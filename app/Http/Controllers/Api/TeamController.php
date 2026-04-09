<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    /** @return list<string> */
    private static function memberPermissionKeys(): array
    {
        return ['manage_members', 'edit_team_meta', 'view_team_kpi', 'assign_projects'];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $q = Team::query()
            ->with(['creator:id,name,email'])
            ->withCount('members')
            ->orderBy('name');

        if ($request->query('include') === 'members') {
            $q->with([
                'members' => fn ($mq) => $mq
                    ->select('users.id', 'users.name', 'users.email', 'users.role')
                    ->orderBy('name'),
            ]);
        }

        if (! in_array($user->role, ['admin', 'pm'], true)) {
            $q->where(function ($w) use ($user) {
                $w->where('created_by', $user->id)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
            });
        }

        $teams = $q->get();
        foreach ($teams as $team) {
            $team->setAttribute('can_manage', $this->userCanManageTeam($user, $team));
        }

        return $teams;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->assertCanCreateTeam($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $team = Team::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $user->id,
        ]);

        $team->members()->syncWithoutDetaching([
            $user->id => ['role' => 'leader'],
        ]);

        $team->load(['creator:id,name,email'])->loadCount('members');
        $team->setAttribute('can_manage', $this->userCanManageTeam($user, $team));

        return response()->json($team, 201);
    }

    public function show(Request $request, Team $team)
    {
        $this->assertUserCanAccessTeam($request->user(), $team);

        $team->load([
            'creator:id,name,email',
            'members' => fn ($q) => $q->select('users.id', 'users.name', 'users.email', 'users.role')->orderBy('name'),
        ]);
        $team->loadCount('members');
        $team->setAttribute('can_manage', $this->userCanManageTeam($request->user(), $team));

        return $team;
    }

    public function update(Request $request, Team $team)
    {
        $this->assertUserCanManageTeam($request->user(), $team);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $team->update($data);

        $team->load(['creator:id,name,email'])->loadCount('members');
        $team->setAttribute('can_manage', $this->userCanManageTeam($request->user(), $team));

        return $team;
    }

    public function destroy(Request $request, Team $team)
    {
        $this->assertUserCanManageTeam($request->user(), $team);
        $team->delete();

        return response()->json(['ok' => true]);
    }

    public function addMembers(Request $request, Team $team)
    {
        $this->assertUserCanManageTeam($request->user(), $team);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', Rule::exists(User::class, 'id')],
            'role' => ['nullable', 'string', Rule::in(['member', 'leader'])],
            'position' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::memberPermissionKeys())],
        ]);

        $role = $data['role'] ?? 'member';

        if ($role === 'leader') {
            if (count($data['user_ids']) !== 1) {
                abort(422, 'Chỉ thêm một trưởng nhóm mỗi lần.');
            }
            DB::connection($team->getConnectionName())->table('team_user')
                ->where('team_id', $team->id)
                ->update(['role' => 'member']);
        }

        $sync = [];
        foreach ($data['user_ids'] as $uid) {
            $sync[(int) $uid] = [
                'role' => $role,
                'position' => $data['position'] ?? null,
                'permissions' => isset($data['permissions']) ? $data['permissions'] : null,
            ];
        }

        $team->members()->syncWithoutDetaching($sync);

        return response()->json(['ok' => true]);
    }

    public function removeMember(Request $request, Team $team, int $userId)
    {
        $this->assertUserCanManageTeam($request->user(), $team);

        if ((int) $userId === (int) $team->created_by) {
            abort(422, 'Không gỡ người tạo team khỏi thành viên.');
        }

        $team->members()->detach($userId);

        return response()->json(['ok' => true]);
    }

    public function updateMember(Request $request, Team $team, int $userId)
    {
        $this->assertUserCanManageTeam($request->user(), $team);

        $data = $request->validate([
            'role' => ['sometimes', Rule::in(['leader', 'member'])],
            'position' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::memberPermissionKeys())],
        ]);

        $member = $team->members()->where('users.id', $userId)->first();
        if (! $member) {
            abort(404, 'Thành viên không thuộc nhóm này.');
        }

        $currentRole = $member->pivot->role;

        if (isset($data['role']) && $data['role'] === 'member' && $currentRole === 'leader') {
            $leaderCount = $team->members()->wherePivot('role', 'leader')->count();
            if ($leaderCount <= 1) {
                abort(422, 'Nhóm phải có ít nhất một trưởng nhóm.');
            }
        }

        if (isset($data['role']) && $data['role'] === 'leader') {
            DB::connection($team->getConnectionName())->table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', '!=', $userId)
                ->update(['role' => 'member']);
        }

        $updates = [];
        if (isset($data['role'])) {
            $updates['role'] = $data['role'];
        }
        if (array_key_exists('position', $data)) {
            $updates['position'] = $data['position'];
        }
        if (array_key_exists('permissions', $data)) {
            $updates['permissions'] = $data['permissions'];
        }

        if ($updates !== []) {
            $team->members()->updateExistingPivot($userId, $updates);
        }

        $team->load([
            'creator:id,name,email',
            'members' => fn ($q) => $q->select('users.id', 'users.name', 'users.email', 'users.role')->orderBy('name'),
        ]);
        $team->loadCount('members');
        $team->setAttribute('can_manage', $this->userCanManageTeam($request->user(), $team));

        return $team;
    }

    protected function assertCanCreateTeam(User $user): void
    {
        if (! in_array($user->role, ['admin', 'pm', 'tl'], true)) {
            abort(403, 'Chỉ admin, PM hoặc Team Lead mới tạo được team.');
        }
    }

    protected function assertUserCanAccessTeam(User $user, Team $team): void
    {
        if (in_array($user->role, ['admin', 'pm'], true)) {
            return;
        }

        $isMember = $team->members()->where('user_id', $user->id)->exists();
        $isCreator = (int) $team->created_by === (int) $user->id;

        if (! $isMember && ! $isCreator) {
            abort(403);
        }
    }

    protected function userCanManageTeam(User $user, Team $team): bool
    {
        if (in_array($user->role, ['admin', 'pm'], true)) {
            return true;
        }

        if ((int) $team->created_by === (int) $user->id) {
            return true;
        }

        return $team->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'leader')
            ->exists();
    }

    protected function assertUserCanManageTeam(User $user, Team $team): void
    {
        if (! $this->userCanManageTeam($user, $team)) {
            abort(403, 'Bạn không có quyền chỉnh sửa team này.');
        }
    }
}

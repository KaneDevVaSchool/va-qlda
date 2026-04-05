<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kaizen;
use App\Models\KaizenWeeklyReminder;
use App\Services\AuditLogger;
use App\Services\KaizenGamificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KaizenController extends Controller
{
    public function index(Request $request)
    {
        $q = Kaizen::query()->with(['submitter:id,name', 'reviewer:id,name']);

        if ($request->user()->role === 'developer') {
            $q->where('submitter_id', $request->user()->id);
        }

        return $q->orderByDesc('week_start')->orderByDesc('id')->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_start' => 'required|date',
            'problem' => 'required|string',
            'solution' => 'required|string',
            'outcome_measurable' => 'required|string|min:3',
            'estimated_value' => 'nullable|numeric|min:0',
        ]);

        $kaizen = Kaizen::create(array_merge($data, [
            'submitter_id' => $request->user()->id,
            'status' => 'submitted',
        ]));

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek()->toDateString();
        $rem = KaizenWeeklyReminder::firstOrNew([
            'user_id' => $request->user()->id,
            'week_start' => $weekStart,
        ]);
        $rem->fulfilled_at = now();
        $rem->kaizen_id = $kaizen->id;
        $rem->save();

        AuditLogger::log('kaizen.created', $kaizen, null, $kaizen->toArray());

        return response()->json($kaizen->load(['submitter:id,name']), 201);
    }

    public function badges(Request $request, KaizenGamificationService $gamification)
    {
        return response()->json(['badges' => $gamification->badgesFor($request->user())]);
    }

    public function reminderCompliance(Request $request, KaizenGamificationService $gamification)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'tl', 'hr'], true)) {
            abort(403);
        }

        return response()->json($gamification->reminderCompliance());
    }

    public function updateStatus(Request $request, Kaizen $kaizen)
    {
        $request->validate([
            'status' => 'required|in:submitted,approved,implemented,verified',
            'tl_rating' => 'nullable|integer|min:1|max:5',
        ]);

        if (! in_array($request->user()->role, ['pm', 'tl', 'hr', 'admin'], true)) {
            abort(403);
        }

        $before = $kaizen->getAttributes();
        $kaizen->status = $request->status;
        if ($request->filled('tl_rating')) {
            $kaizen->tl_rating = $request->tl_rating;
            $kaizen->score = round(($kaizen->tl_rating * 10) + 5, 2);
        }
        $kaizen->reviewed_by = $request->user()->id;
        $kaizen->save();

        AuditLogger::log('kaizen.status', $kaizen, $before, $kaizen->getAttributes());

        return $kaizen->fresh()->load(['submitter:id,name', 'reviewer:id,name']);
    }

    /**
     * BR-KZ-05: Leaderboard theo tháng (điểm Kaizen đã verified).
     */
    public function leaderboard(Request $request)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $rows = Kaizen::query()
            ->where('status', 'verified')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('submitter_id, SUM(COALESCE(score,0)) as total_score, COUNT(*) as kaizen_count')
            ->groupBy('submitter_id')
            ->orderByDesc('total_score')
            ->with('submitter:id,name')
            ->limit(30)
            ->get();

        return response()->json(['month' => $month, 'leaderboard' => $rows]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kaizen;
use App\Models\Project;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function weeklyStatusPdf(Request $request)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'tl'], true)) {
            abort(403);
        }

        $projects = Project::query()
            ->whereNull('archived_at')
            ->with('owner:id,name')
            ->orderBy('name')
            ->get();

        $taskOpen = Task::query()->whereNotIn('status', ['done'])->count();
        $taskDoneWeek = Task::query()
            ->where('status', 'done')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        $pdf = Pdf::loadView('reports.weekly-status', [
            'generatedAt' => now()->toDateTimeString(),
            'projects' => $projects,
            'taskOpen' => $taskOpen,
            'taskDoneWeek' => $taskDoneWeek,
        ]);

        return $pdf->download('ppms-weekly-status.pdf');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'hr'], true)) {
            abort(403);
        }

        $filename = 'ppms-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['project_id', 'name', 'customer_name', 'type', 'phase', 'status', 'progress', 'owner_id', 'suppliers_count']);
            Project::query()->whereNull('archived_at')->orderBy('id')->chunk(100, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    $supCount = is_array($p->suppliers) ? count($p->suppliers) : 0;
                    fputcsv($out, [$p->id, $p->name, $p->customer_name, $p->type, $p->phase, $p->status, $p->progress, $p->owner_id, $supCount]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function kaizenImpactSummary(Request $request)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'tl', 'hr'], true)) {
            abort(403);
        }

        $sum = Kaizen::query()->whereNotNull('estimated_value')->sum('estimated_value');
        $byMonth = Kaizen::query()
            ->whereNotNull('estimated_value')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Kaizen $k) => $k->created_at->format('Y-m'))
            ->map(fn ($group) => round((float) $group->sum('estimated_value'), 2));

        return response()->json([
            'estimated_value_sum' => (float) $sum,
            'by_month' => $byMonth,
        ]);
    }
}

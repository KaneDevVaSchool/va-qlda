<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kaizen;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectListQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const EXPORT_PDF_ROW_LIMIT = 500;

    /** @var list<string> */
    private const FILTERED_EXPORT_HEADERS = [
        'project_id', 'name', 'customer_name', 'customer_email', 'type', 'phase', 'status', 'progress',
        'deadline', 'start_date', 'actual_start_date', 'labels', 'created_at', 'updated_at', 'archived_at', 'owner_id', 'owner_name', 'owner_email',
        'tasks_count', 'suppliers_count', 'description',
    ];

    public function __construct(
        protected ProjectListQueryService $projectListQuery
    ) {}

    /**
     * @return list<int|string|float|null>
     */
    protected function projectFilteredExportValues(Project $p): array
    {
        $supCount = is_array($p->suppliers) ? count($p->suppliers) : 0;

        return [
            $p->id,
            $p->name,
            $p->customer_name,
            $p->customer_email,
            $p->type,
            $p->phase,
            $p->status,
            $p->progress,
            $p->deadline?->toDateString(),
            $p->start_date?->toDateString(),
            $p->actual_start_date?->toDateString(),
            is_array($p->labels) && $p->labels !== [] ? implode('|', $p->labels) : '',
            $p->created_at?->toDateTimeString(),
            $p->updated_at?->toDateTimeString(),
            $p->archived_at?->toDateTimeString(),
            $p->owner_id,
            $p->owner?->name,
            $p->owner?->email,
            $p->tasks_count ?? 0,
            $supCount,
            $p->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function projectFilteredExportAssoc(Project $p): array
    {
        return array_combine(self::FILTERED_EXPORT_HEADERS, $this->projectFilteredExportValues($p));
    }

    /**
     * @return list<int>|null null = no id filter; non-empty = whereIn (capped)
     */
    protected function parseExportIds(Request $request): ?array
    {
        if (! $request->filled('ids')) {
            return null;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $request->query('ids'))))));
        if ($ids === []) {
            return null;
        }

        return array_slice($ids, 0, 200);
    }

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

    /**
     * CSV export using the same filters as GET /api/projects (query string).
     */
    public function exportProjectsFilteredCsv(Request $request): StreamedResponse
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'hr', 'tl'], true)) {
            abort(403);
        }

        $filename = 'ppms-projects-filtered-'.now()->format('Y-m-d-His').'.csv';

        $ids = $this->parseExportIds($request);

        $response = response()->streamDownload(function () use ($request, $ids) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::FILTERED_EXPORT_HEADERS);
            $q = $this->projectListQuery->filteredQuery($request, true);
            if ($ids !== null) {
                $q->whereIn('id', $ids);
            }
            $q->reorder()->orderBy('id');
            $q->chunk(100, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, $this->projectFilteredExportValues($p));
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);

        $response->headers->set('X-PPMS-Export-Scope', $ids !== null ? 'selection' : 'filtered');

        return $response;
    }

    /**
     * JSON export (UTF-8): same filters / ids as CSV; suitable for integrations & round-trip editing.
     */
    public function exportProjectsFilteredJson(Request $request): StreamedResponse
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'hr', 'tl'], true)) {
            abort(403);
        }

        $filename = 'ppms-projects-filtered-'.now()->format('Y-m-d-His').'.json';
        $ids = $this->parseExportIds($request);

        $response = response()->streamDownload(function () use ($request, $ids) {
            $meta = [
                'export_version' => 1,
                'exported_at' => now()->toIso8601String(),
                'schema' => 'ppms_projects_filtered',
                'columns' => self::FILTERED_EXPORT_HEADERS,
                'query' => array_filter($request->query()),
            ];
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo substr($metaJson, 0, -1);
            echo ',"projects":[';

            $q = $this->projectListQuery->filteredQuery($request, true);
            if ($ids !== null) {
                $q->whereIn('id', $ids);
            }
            $q->reorder()->orderBy('id');

            $first = true;
            $q->chunk(100, function ($rows) use (&$first) {
                foreach ($rows as $p) {
                    if (! $first) {
                        echo ',';
                    }
                    $first = false;
                    echo json_encode($this->projectFilteredExportAssoc($p), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            });
            echo ']}';
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);

        $response->headers->set('X-PPMS-Export-Scope', $ids !== null ? 'selection' : 'filtered');

        return $response;
    }

    /**
     * PDF table (max 200 rows) with current list filters.
     */
    public function exportProjectsFilteredPdf(Request $request)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'hr', 'tl'], true)) {
            abort(403);
        }

        $ids = $this->parseExportIds($request);
        $q = $this->projectListQuery->filteredQuery($request, true);
        if ($ids !== null) {
            $q->whereIn('id', $ids);
        }
        $q->reorder()->orderBy('id');
        $totalMatching = (clone $q)->count();
        $projects = $q->limit(self::EXPORT_PDF_ROW_LIMIT)->get();
        $truncated = $totalMatching > $projects->count();

        $pdf = Pdf::loadView('reports.projects-filtered', [
            'generatedAt' => now()->toDateTimeString(),
            'projects' => $projects,
            'filterSummary' => array_filter($request->query()),
            'rowLimit' => self::EXPORT_PDF_ROW_LIMIT,
            'totalMatching' => $totalMatching,
            'truncated' => $truncated,
        ]);

        $response = $pdf->download('ppms-projects-filtered-'.now()->format('Y-m-d').'.pdf');
        $response->headers->set('X-PPMS-Export-Row-Limit', (string) self::EXPORT_PDF_ROW_LIMIT);
        $response->headers->set('X-PPMS-Export-Truncated', $truncated ? '1' : '0');
        $response->headers->set('X-PPMS-Export-Scope', $ids !== null ? 'selection' : 'filtered');

        return $response;
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

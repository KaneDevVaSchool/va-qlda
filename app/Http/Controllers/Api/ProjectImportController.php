<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\ProjectImportService;
use App\Services\ProjectProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectImportController extends Controller
{
    public function __construct(
        protected ProjectImportService $importService,
        protected ProjectProgressService $progressService
    ) {}

    public function templateCsv(): StreamedResponse
    {
        if (! $this->canImport(request())) {
            abort(403);
        }

        $filename = 'ppms-projects-import-template.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ProjectImportService::TEMPLATE_HEADERS);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function templateJson(): StreamedResponse
    {
        if (! $this->canImport(request())) {
            abort(403);
        }

        $filename = 'ppms-projects-import-template.json';

        return response()->streamDownload(function () {
            $sample = [
                '_comment' => 'Import file: use top-level array of projects, or { "projects": [ ... ] }. Keys match CSV template (see columns).',
                'export_version' => 1,
                'projects' => [
                    array_merge(
                        array_fill_keys(ProjectImportService::TEMPLATE_HEADERS, null),
                        [
                            'name' => 'Example delivery project',
                            'type' => 'delivery',
                            'phase' => 'planning',
                            'status' => 'on_track',
                            'owner_email' => 'pm@example.com',
                            'labels' => ['VIP', 'Q1'],
                        ]
                    ),
                ],
            ];
            echo json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function preview(Request $request)
    {
        if (! $this->canImport($request)) {
            abort(403);
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof \Illuminate\Http\UploadedFile) {
                        $fail('Invalid upload.');

                        return;
                    }
                    $ext = strtolower((string) $value->getClientOriginalExtension());
                    if (! in_array($ext, ['csv', 'txt', 'json'], true)) {
                        $fail('File must be .csv, .txt, or .json');
                    }
                },
            ],
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        if ($content === false) {
            abort(422, 'Could not read file.');
        }

        $ext = strtolower((string) $request->file('file')->getClientOriginalExtension());
        $parsed = $ext === 'json'
            ? $this->importService->parseImportJson($content)
            : $this->importService->parseCsv($content);
        if ($parsed === []) {
            return response()->json([
                'preview_id' => null,
                'summary' => ['total' => 0, 'valid' => 0, 'invalid' => 0],
                'rows' => [],
                'message' => 'No data rows found.',
            ]);
        }

        $preview = $this->importService->previewRows($parsed);
        $previewId = (string) Str::uuid();

        Cache::put(
            ProjectImportService::CACHE_PREFIX.$previewId,
            [
                'user_id' => $request->user()->id,
                'payloads' => $preview['valid_payloads'],
            ],
            now()->addMinutes(15)
        );

        return response()->json([
            'preview_id' => $previewId,
            'summary' => $preview['summary'],
            'rows' => $preview['rows'],
        ]);
    }

    public function commit(Request $request)
    {
        if (! $this->canImport($request)) {
            abort(403);
        }

        $data = $request->validate([
            'preview_id' => 'required|uuid',
        ]);

        $key = ProjectImportService::CACHE_PREFIX.$data['preview_id'];
        $cached = Cache::pull($key);
        if (! is_array($cached) || (int) ($cached['user_id'] ?? 0) !== (int) $request->user()->id) {
            abort(410, 'Preview expired or invalid. Run preview again.');
        }

        /** @var list<array<string, mixed>> $payloads */
        $payloads = $cached['payloads'] ?? [];
        if ($payloads === []) {
            return response()->json(['created' => 0, 'updated' => 0, 'message' => 'Nothing to import.']);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($payloads, &$created, &$updated) {
            foreach ($payloads as $payload) {
                if (isset($payload['project_id'])) {
                    $project = Project::query()->findOrFail((int) $payload['project_id']);
                    $before = $project->getAttributes();
                    $fresh = $this->importService->applyUpdate($project, $payload);
                    $this->progressService->syncProjectProgress($fresh);
                    AuditLogger::log('project.updated', $fresh, $before, $fresh->getAttributes());
                    $updated++;

                    continue;
                }

                $project = $this->importService->applyCreate($payload);
                $this->progressService->syncProjectProgress($project->fresh());
                AuditLogger::log('project.created', $project, null, $project->only([
                    'name', 'type', 'phase', 'status', 'owner_id', 'deadline',
                ]));
                $created++;
            }
        });

        return response()->json([
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    private function canImport(Request $request): bool
    {
        return in_array($request->user()->role, ['admin', 'pm'], true);
    }
}

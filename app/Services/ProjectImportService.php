<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Parse project CSV (UTF-8), validate rows, build payloads for preview/cache commit.
 */
class ProjectImportService
{
    public const CACHE_PREFIX = 'project_import_preview:';

    public const MAX_ROWS = 100;

    /** @var list<string> */
    public const TEMPLATE_HEADERS = [
        'project_id',
        'name',
        'customer_name',
        'customer_email',
        'type',
        'phase',
        'status',
        'progress',
        'deadline',
        'start_date',
        'owner_id',
        'owner_email',
        'labels',
        'description',
    ];

    /**
     * Prefer ';' when the header line has more semicolons than commas (Excel EU locale).
     */
    public function sniffCsvDelimiter(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $line = preg_split("/\r\n|\n|\r/", $content, 2)[0] ?? '';
        if ($line === '') {
            return ',';
        }
        $semi = substr_count($line, ';');
        $comma = substr_count($line, ',');

        return $semi > $comma ? ';' : ',';
    }

    /**
     * JSON import: top-level array of objects, or `{ "projects": [ ... ] }`.
     * Keys are case-insensitive (normalized to lowercase).
     *
     * @return list<array<string, string|null>>
     */
    public function parseImportJson(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [];
        }
        if (isset($decoded['projects']) && is_array($decoded['projects'])) {
            $items = $decoded['projects'];
        } elseif (array_is_list($decoded)) {
            $items = $decoded;
        } else {
            return [];
        }

        $rows = [];
        $line = 1;
        foreach ($items as $item) {
            $line++;
            if (! is_array($item)) {
                continue;
            }
            $assoc = [];
            foreach ($item as $k => $v) {
                $key = strtolower(trim((string) $k));
                if ($key === '' || str_starts_with($key, '_')) {
                    continue;
                }
                if (is_array($v)) {
                    if ($key === 'labels') {
                        $assoc[$key] = implode('|', array_map(fn ($x) => trim((string) $x), $v));
                    } else {
                        $assoc[$key] = json_encode($v, JSON_UNESCAPED_UNICODE);
                    }
                } elseif (is_bool($v)) {
                    $assoc[$key] = $v ? '1' : '0';
                } elseif ($v === null) {
                    $assoc[$key] = null;
                } else {
                    $assoc[$key] = trim((string) $v);
                    if ($assoc[$key] === '') {
                        $assoc[$key] = null;
                    }
                }
            }
            $assoc['_line'] = (string) $line;
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @return list<array<string, string|null>>
     */
    public function parseCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $delimiter = $this->sniffCsvDelimiter($content);
        $handle = fopen('php://memory', 'r+b');
        if ($handle === false) {
            return [];
        }
        fwrite($handle, $content);
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if ($headerRow === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow);
        $rows = [];
        $line = 1;

        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($this->rowIsEmpty($cells)) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
                if ($assoc[$key] === '') {
                    $assoc[$key] = null;
                }
            }
            $assoc['_line'] = (string) $line;
            $rows[] = $assoc;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{ok: bool, errors: list<string>, action: string|null, payload: array<string, mixed>|null}
     */
    public function validateRow(array $row): array
    {
        $line = (int) ($row['_line'] ?? 0);
        unset($row['_line']);

        if (isset($row['labels']) && is_array($row['labels'])) {
            $row['labels'] = implode('|', array_map(fn ($x) => trim((string) $x), $row['labels']));
        }

        $projectId = isset($row['project_id']) && $row['project_id'] !== null && $row['project_id'] !== ''
            ? (int) $row['project_id']
            : null;

        $rules = [
            'name' => ($projectId ? 'sometimes|nullable' : 'required').'|string|max:255',
            'type' => ($projectId ? 'sometimes|nullable' : 'required').'|in:maintenance,delivery,rnd',
            'phase' => 'nullable|in:planning,development,uat,done,maintenance,rnd',
            'status' => 'nullable|in:on_track,at_risk,delayed,blocked',
            'progress' => 'nullable|numeric|min:0|max:100',
            'deadline' => 'nullable|date',
            'start_date' => 'nullable|date',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:65535',
            'owner_id' => 'nullable|integer|exists:users,id',
            'owner_email' => 'nullable|email|max:255',
            'labels' => 'nullable|string|max:512',
        ];

        $validator = Validator::make($row, $rules);
        if ($validator->fails()) {
            return [
                'ok' => false,
                'errors' => array_values(array_unique($validator->errors()->all())),
                'action' => null,
                'payload' => null,
            ];
        }

        $data = $validator->validated();

        if ($projectId !== null && $projectId > 0) {
            $project = Project::query()->find($projectId);
            if (! $project) {
                return [
                    'ok' => false,
                    'errors' => ["Line {$line}: project_id {$projectId} not found."],
                    'action' => null,
                    'payload' => null,
                ];
            }

            $payload = ['project_id' => $projectId];
            foreach (['name', 'type', 'phase', 'status', 'deadline', 'start_date', 'customer_name', 'customer_email', 'description'] as $k) {
                if (array_key_exists($k, $data) && $data[$k] !== null && $data[$k] !== '') {
                    $payload[$k] = $data[$k];
                }
            }
            if (array_key_exists('progress', $data) && $data['progress'] !== null && $data['progress'] !== '') {
                $payload['progress'] = (float) $data['progress'];
            }

            if (array_key_exists('labels', $data) && $data['labels'] !== null && trim((string) $data['labels']) !== '') {
                $parsed = $this->parseLabelsCell($data['labels']);
                if ($parsed !== []) {
                    $payload['labels'] = Project::normalizeLabelList($parsed) ?? [];
                }
            }

            if (! empty($row['owner_id']) || ! empty($row['owner_email'])) {
                $ownerId = $this->resolveOwnerId($row);
                if ($ownerId === null) {
                    return [
                        'ok' => false,
                        'errors' => ["Line {$line}: owner_id or owner_email does not match a user."],
                        'action' => null,
                        'payload' => null,
                    ];
                }
                $payload['owner_id'] = $ownerId;
            }

            if (count($payload) <= 1) {
                return [
                    'ok' => false,
                    'errors' => ["Line {$line}: provide at least one field to update besides project_id."],
                    'action' => null,
                    'payload' => null,
                ];
            }

            return [
                'ok' => true,
                'errors' => [],
                'action' => 'update',
                'payload' => $payload,
            ];
        }

        if (empty($row['owner_id']) && empty($row['owner_email'])) {
            return [
                'ok' => false,
                'errors' => ["Line {$line}: owner_id or owner_email is required for new projects."],
                'action' => null,
                'payload' => null,
            ];
        }

        $ownerId = $this->resolveOwnerId($row);
        if ($ownerId === null) {
            return [
                'ok' => false,
                'errors' => ["Line {$line}: owner_id or owner_email does not match a user."],
                'action' => null,
                'payload' => null,
            ];
        }

        $payload = [
            'name' => $data['name'],
            'type' => $data['type'],
            'owner_id' => $ownerId,
            'phase' => $data['phase'] ?? 'planning',
            'status' => $data['status'] ?? 'on_track',
            'progress' => isset($data['progress']) ? (float) $data['progress'] : 0,
            'deadline' => $data['deadline'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        if (array_key_exists('labels', $data) && $data['labels'] !== null && trim((string) $data['labels']) !== '') {
            $parsed = $this->parseLabelsCell($data['labels']);
            if ($parsed !== []) {
                $payload['labels'] = Project::normalizeLabelList($parsed) ?? [];
            }
        }

        return [
            'ok' => true,
            'errors' => [],
            'action' => 'create',
            'payload' => $payload,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{summary: array{total: int, valid: int, invalid: int}, rows: list<array<string, mixed>>, valid_payloads: list<array<string, mixed>>}
     */
    public function previewRows(array $rows): array
    {
        $rows = array_slice($rows, 0, self::MAX_ROWS);
        $outRows = [];
        $validPayloads = [];
        $valid = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            $line = (int) ($row['_line'] ?? 0);
            $result = $this->validateRow($row);
            $entry = [
                'line' => $line,
                'status' => $result['ok'] ? 'valid' : 'invalid',
                'action' => $result['action'],
                'errors' => $result['errors'],
                'name' => $row['name'] ?? null,
                'project_id' => $row['project_id'] ?? null,
            ];
            $outRows[] = $entry;
            if ($result['ok'] && $result['payload'] !== null) {
                $validPayloads[] = $result['payload'];
                $valid++;
            } else {
                $invalid++;
            }
        }

        return [
            'summary' => [
                'total' => count($rows),
                'valid' => $valid,
                'invalid' => $invalid,
            ],
            'rows' => $outRows,
            'valid_payloads' => $validPayloads,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyCreate(array $payload): Project
    {
        $create = array_merge([
            'phase' => 'planning',
            'status' => 'on_track',
            'progress' => 0,
        ], array_intersect_key($payload, array_flip([
            'name', 'type', 'owner_id', 'phase', 'status', 'progress',
            'deadline', 'start_date', 'customer_name', 'customer_email', 'description', 'labels',
        ])));

        return Project::create($create);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyUpdate(Project $project, array $payload): Project
    {
        $updates = array_intersect_key($payload, array_flip([
            'name', 'type', 'phase', 'status', 'progress',
            'deadline', 'start_date', 'customer_name', 'customer_email', 'description', 'owner_id', 'labels',
        ]));
        unset($updates['project_id']);
        if ($updates === []) {
            return $project;
        }
        $project->update($updates);

        return $project->fresh();
    }

    /**
     * @return list<string>
     */
    private function parseLabelsCell(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return [];
        }
        $parts = preg_split('/[|,;]/u', $s) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($x) => $x !== ''));
    }

    private function rowIsEmpty(array $cells): bool
    {
        foreach ($cells as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveOwnerId(array $row): ?int
    {
        if (! empty($row['owner_id'])) {
            $id = (int) $row['owner_id'];
            if ($id > 0 && User::query()->whereKey($id)->exists()) {
                return $id;
            }

            return null;
        }
        if (! empty($row['owner_email'])) {
            $email = strtolower(trim((string) $row['owner_email']));
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            return $user?->id;
        }

        return null;
    }
}

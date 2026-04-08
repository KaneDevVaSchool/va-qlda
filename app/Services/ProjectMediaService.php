<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\TaskAttachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Aggregates project library documents and task file attachments into one normalized list for the project media UI.
 */
class ProjectMediaService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForProject(Project $project, ?string $scope, ?string $search): array
    {
        $docs = $project->documents()->with('creator:id,name')->get();
        $idToDoc = $docs->keyBy('id');

        $docRows = [];
        foreach ($docs as $doc) {
            $row = $this->serializeProjectDocument($doc, $idToDoc);
            if ($this->passesScope($row, $scope) && $this->passesSearch($row, $search)) {
                $docRows[] = $row;
            }
        }

        $attQuery = TaskAttachment::query()
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->with(['task:id,name', 'uploader:id,name']);

        $attRows = [];
        foreach ($attQuery->orderByDesc('id')->get() as $att) {
            $row = $this->serializeTaskAttachment($att);
            if ($this->passesScope($row, $scope) && $this->passesSearch($row, $search)) {
                $attRows[] = $row;
            }
        }

        $merged = array_merge($docRows, $attRows);

        usort($merged, function (array $a, array $b) {
            $ta = $a['sort_at'] ?? '';
            $tb = $b['sort_at'] ?? '';

            return strcmp((string) $tb, (string) $ta);
        });

        return array_values($merged);
    }

    /**
     * Cùng thứ tự với `path_label`: id gốc → … → id mục hiện tại.
     *
     * @param  \Illuminate\Support\Collection<int, ProjectDocument>  $idToDoc
     * @return list<int>
     */
    private function pathIds(ProjectDocument $doc, $idToDoc): array
    {
        $ids = [];
        $current = $doc;
        $guard = 0;
        while ($current && $guard < 64) {
            array_unshift($ids, (int) $current->id);
            $pid = $current->parent_id;
            $current = $pid ? ($idToDoc->get((int) $pid) ?? null) : null;
            $guard++;
        }

        return $ids;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProjectDocument>  $idToDoc
     * @return array<string, mixed>
     */
    private function serializeProjectDocument(ProjectDocument $doc, $idToDoc): array
    {
        $path = $this->pathLabel($doc, $idToDoc);
        $created = $doc->created_at ? Carbon::parse($doc->created_at) : null;
        $sortAt = $created ? $created->toIso8601String() : '';

        $uploader = $doc->creator ? ['id' => (int) $doc->creator->id, 'name' => (string) $doc->creator->name] : null;

        return [
            'source' => 'project_document',
            'source_id' => (int) $doc->id,
            'scope' => 'project',
            'doc_type' => $doc->doc_type,
            'name' => (string) $doc->name,
            'original_name' => $doc->original_name ? (string) $doc->original_name : null,
            'url' => $doc->url ? (string) $doc->url : null,
            'size_bytes' => $doc->size_bytes !== null ? (int) $doc->size_bytes : null,
            'mime_type' => $doc->mime_type ? (string) $doc->mime_type : null,
            'path_label' => $path,
            'path_ids' => $this->pathIds($doc, $idToDoc),
            'parent_id' => $doc->parent_id !== null ? (int) $doc->parent_id : null,
            'task' => null,
            'task_id' => null,
            'uploader' => $uploader,
            'created_at' => $doc->created_at,
            'sort_at' => $sortAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTaskAttachment(TaskAttachment $att): array
    {
        $created = $att->created_at ? Carbon::parse($att->created_at) : null;
        $sortAt = $created ? $created->toIso8601String() : '';

        $task = $att->task;
        $taskPayload = $task ? ['id' => (int) $task->id, 'name' => (string) $task->name] : null;

        $uploader = $att->uploader ? ['id' => (int) $att->uploader->id, 'name' => (string) $att->uploader->name] : null;

        return [
            'source' => 'task_attachment',
            'source_id' => (int) $att->id,
            'scope' => 'task',
            'doc_type' => 'file',
            'name' => (string) $att->original_name,
            'original_name' => (string) $att->original_name,
            'url' => null,
            'size_bytes' => $att->size_bytes !== null ? (int) $att->size_bytes : null,
            'mime_type' => null,
            'path_label' => '',
            'path_ids' => [],
            'parent_id' => null,
            'task' => $taskPayload,
            'task_id' => $task ? (int) $task->id : null,
            'uploader' => $uploader,
            'created_at' => $att->created_at,
            'sort_at' => $sortAt,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProjectDocument>  $idToDoc
     */
    private function pathLabel(ProjectDocument $doc, $idToDoc): string
    {
        $parts = [];
        $current = $doc;
        $guard = 0;
        while ($current && $guard < 64) {
            array_unshift($parts, (string) $current->name);
            $pid = $current->parent_id;
            $current = $pid ? ($idToDoc->get((int) $pid) ?? null) : null;
            $guard++;
        }

        return implode(' / ', $parts);
    }

    private function passesScope(array $row, ?string $scope): bool
    {
        $scope = $scope ? strtolower(trim($scope)) : 'all';
        if ($scope === '' || $scope === 'all') {
            return true;
        }
        if ($scope === 'project') {
            return ($row['scope'] ?? '') === 'project';
        }
        if ($scope === 'task') {
            return ($row['scope'] ?? '') === 'task';
        }

        return true;
    }

    private function passesSearch(array $row, ?string $search): bool
    {
        $q = $search !== null ? trim($search) : '';
        if ($q === '') {
            return true;
        }
        $needle = Str::lower($q);
        $hay = Str::lower(
            ($row['name'] ?? '').
            ' '.($row['original_name'] ?? '').
            ' '.($row['path_label'] ?? '').
            ' '.(isset($row['task']['name']) ? (string) $row['task']['name'] : '')
        );

        return str_contains($hay, $needle);
    }
}

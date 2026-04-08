<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectDocumentController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return $project->documents()
            ->with('creator:id,name')
            ->get();
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'doc_type' => 'required|in:folder,link',
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:2048',
            'parent_id' => 'nullable|integer|exists:project_documents,id',
            'sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        if ($data['doc_type'] === 'link' && empty($request->input('url'))) {
            throw ValidationException::withMessages(['url' => ['URL is required for this type.']]);
        }

        if (! empty($data['parent_id'])) {
            $this->assertParentFolder($project, (int) $data['parent_id']);
        }

        $row = $project->documents()->create([
            'parent_id' => $data['parent_id'] ?? null,
            'doc_type' => $data['doc_type'],
            'name' => $data['name'],
            'url' => $this->normalizeUrl($request->input('url')),
            'created_by' => $request->user()->id,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        AuditLogger::log('project_document.created', $row, null, $row->toArray());

        return response()->json($row->load('creator:id,name'), 201);
    }

    public function upload(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $maxKb = (int) config('ppms.upload_max_file_kb', 51200);
        $request->validate([
            'file' => 'required|file|max:'.$maxKb,
            'parent_id' => 'nullable|integer|exists:project_documents,id',
            'sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        if ($request->filled('parent_id')) {
            $this->assertParentFolder($project, (int) $request->input('parent_id'));
        }

        $file = $request->file('file');
        $path = $file->store('project-documents/'.$project->id, 'public');

        $row = $project->documents()->create([
            'parent_id' => $request->input('parent_id') ?: null,
            'doc_type' => ProjectDocument::TYPE_UPLOAD,
            'name' => $file->getClientOriginalName(),
            'url' => null,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'created_by' => $request->user()->id,
            'sort_order' => (int) ($request->input('sort_order') ?? 0),
        ]);

        AuditLogger::log('project_document.created', $row, null, $row->toArray());

        return response()->json($row->load('creator:id,name'), 201);
    }

    public function update(Request $request, ProjectDocument $document)
    {
        $this->authorize('update', $document->project);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'nullable|string|max:2048',
            'parent_id' => 'nullable|integer|exists:project_documents,id',
            'sort_order' => 'nullable|integer|min:0|max:999999',
        ]);

        if (array_key_exists('parent_id', $data)) {
            $newParent = $data['parent_id'] ?? null;
            if ($newParent !== null) {
                if ((int) $newParent === (int) $document->id) {
                    throw ValidationException::withMessages(['parent_id' => ['Cannot set parent to self.']]);
                }
                $this->assertParentFolder($document->project, (int) $newParent);
                if ($this->wouldCreateCycle($document, (int) $newParent)) {
                    throw ValidationException::withMessages(['parent_id' => ['Invalid parent (cycle).']]);
                }
            }
        }

        $before = $document->getAttributes();

        $updates = [];
        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
        }
        if (array_key_exists('url', $data)) {
            $updates['url'] = $this->normalizeUrl($data['url']);
        }
        if (array_key_exists('parent_id', $data)) {
            $updates['parent_id'] = $data['parent_id'];
        }
        if (array_key_exists('sort_order', $data)) {
            $updates['sort_order'] = $data['sort_order'];
        }

        if ($updates !== []) {
            $document->update($updates);
        }

        AuditLogger::log('project_document.updated', $document, $before, $document->fresh()->toArray());

        return response()->json($document->load('creator:id,name'));
    }

    public function destroy(Request $request, ProjectDocument $document)
    {
        $this->authorize('update', $document->project);

        $this->deleteDocumentTree($document);

        return response()->noContent();
    }

    public function download(ProjectDocument $document)
    {
        $this->authorize('view', $document->project);

        if ($document->doc_type !== ProjectDocument::TYPE_UPLOAD || ! $document->path || ! $document->disk) {
            abort(404);
        }

        if (! Storage::disk($document->disk)->exists($document->path)) {
            abort(404);
        }

        return Storage::disk($document->disk)->download($document->path, $document->original_name ?? $document->name);
    }

    private function assertParentFolder(Project $project, int $parentId): void
    {
        $parent = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->whereKey($parentId)
            ->firstOrFail();

        if ($parent->doc_type !== ProjectDocument::TYPE_FOLDER) {
            throw ValidationException::withMessages(['parent_id' => ['Parent must be a folder.']]);
        }
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = $url !== null ? trim($url) : null;

        return $url === '' ? null : $url;
    }

    private function wouldCreateCycle(ProjectDocument $node, int $newParentId): bool
    {
        $current = ProjectDocument::query()->find($newParentId);
        while ($current) {
            if ((int) $current->id === (int) $node->id) {
                return true;
            }
            $current = $current->parent_id
                ? ProjectDocument::query()->find($current->parent_id)
                : null;
        }

        return false;
    }

    private function deleteDocumentTree(ProjectDocument $node): void
    {
        $children = ProjectDocument::query()
            ->where('project_id', $node->project_id)
            ->where('parent_id', $node->id)
            ->get();

        foreach ($children as $child) {
            $this->deleteDocumentTree($child);
        }

        if ($node->doc_type === ProjectDocument::TYPE_UPLOAD && $node->path && $node->disk) {
            Storage::disk($node->disk)->delete($node->path);
        }

        AuditLogger::log('project_document.deleted', $node, $node->toArray(), null);
        $node->delete();
    }
}

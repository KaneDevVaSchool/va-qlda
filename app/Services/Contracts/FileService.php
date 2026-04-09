<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractFile;
use App\Models\ContractVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{
    public function __construct(
        protected ContractAuditService $audit
    ) {}

    /**
     * @return array{file: ContractFile, version: ?ContractVersion}
     */
    public function store(
        Contract $contract,
        UploadedFile $uploaded,
        User $user,
        bool $createVersion,
        ?string $versionNote
    ): array {
        $disk = config('contracts.filesystem_disk');
        $dir = 'contracts/'.$contract->id.'/'.now()->format('Y/m');
        $safe = Str::slug(pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = $uploaded->getClientOriginalExtension();
        $filename = $safe.'_'.Str::random(8).($ext ? '.'.$ext : '');
        $path = $uploaded->storeAs($dir, $filename, $disk);

        return DB::transaction(function () use ($contract, $uploaded, $user, $createVersion, $versionNote, $path, $disk) {
            $file = $contract->files()->create([
                'file_name' => $uploaded->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $uploaded->getMimeType(),
                'uploaded_by' => $user->id,
                'created_at' => now(),
            ]);

            $version = null;
            if ($createVersion) {
                $next = (int) ($contract->versions()->max('version') ?? 0) + 1;
                $version = $contract->versions()->create([
                    'version' => $next,
                    'file_path' => $path,
                    'note' => $versionNote,
                    'created_at' => now(),
                ]);
            }

            $this->audit->log(
                $contract,
                'file.uploaded',
                null,
                [
                    'file_id' => $file->id,
                    'version' => $version?->version,
                    'disk' => $disk,
                    'path' => $path,
                ],
                $user->id
            );

            return ['file' => $file, 'version' => $version];
        });
    }

    public function filesWithUploaders(Contract $contract): Collection
    {
        return $contract->files()
            ->with('uploader:id,name,email')
            ->orderByDesc('id')
            ->get();
    }

    public function downloadResponse(Contract $contract, ContractFile $file): StreamedResponse
    {
        if ($file->contract_id !== $contract->id) {
            abort(404);
        }

        [$disk, $path] = $this->resolveStoragePath($file);

        return Storage::disk($disk)->download($path, $file->file_name);
    }

    /**
     * Inline preview in browser (PDF / images only).
     */
    public function previewResponse(Contract $contract, ContractFile $file)
    {
        if ($file->contract_id !== $contract->id) {
            abort(404);
        }

        [$disk, $path] = $this->resolveStoragePath($file);
        $mime = (string) ($file->file_type ?? '');
        $lower = strtolower($file->file_name);

        if ($mime === '') {
            if (str_ends_with($lower, '.pdf')) {
                $mime = 'application/pdf';
            } elseif (preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $lower)) {
                $mime = 'image/jpeg';
            }
        }

        if ($mime === '' || (! str_starts_with($mime, 'application/pdf') && ! str_starts_with($mime, 'image/'))) {
            abort(415, 'Preview only supports PDF and images.');
        }

        return Storage::disk($disk)->response($path, $file->file_name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$file->file_name.'"',
        ]);
    }

    /**
     * @return array{0: string, 1: string} [disk, path]
     */
    private function resolveStoragePath(ContractFile $file): array
    {
        $disk = config('contracts.filesystem_disk');

        return [$disk, $file->file_path];
    }
}

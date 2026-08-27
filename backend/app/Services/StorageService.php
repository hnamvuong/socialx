<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    private const PUBLIC_DISK = 'public';

    public function storePublicImage(
        UploadedFile $file,
        string $directory
    ): string {
        return $file->store(
            $directory,
            self::PUBLIC_DISK
        );
    }

    public function deletePublic(
        ?string $path
    ): void {
        if (! $path) {
            return;
        }

        Storage::disk(
            self::PUBLIC_DISK
        )->delete($path);
    }

    public function publicUrl(
        ?string $path
    ): ?string {
        if (! $path) {
            return null;
        }

        return Storage::disk(
            self::PUBLIC_DISK
        )->url($path);
    }
}

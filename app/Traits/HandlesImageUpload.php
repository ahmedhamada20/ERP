<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesImageUpload
{
    protected function uploadImage(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        if ($oldPath) {
            $this->deleteImage($oldPath);
        }

        $extension = $file->getClientOriginalExtension();
        $filename  = Str::random(20) . '_' . time() . '.' . $extension;
        $path      = $file->storeAs($directory, $filename, 'public');

        return $path;
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

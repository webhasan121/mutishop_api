<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesFileUpload
{
    protected function handleFileUpload(
        Request $request,
        array $data,
        ?string $oldFile = null,
        string $field = 'file',
        string $disk = 'public',
        string $folder = 'uploads'
    ): array {
        if ($request->hasFile($field)) {
            // ❌ Delete old file if exists
            if ($oldFile && Storage::disk($disk)->exists($oldFile)) {
                Storage::disk($disk)->delete($oldFile);
            }
            $data[$field] = $this->uploadOne($request->file($field), $folder, $disk);
        }

        return $data;
    }

    protected function uploadOne($file, string $folder = 'uploads', string $disk = 'public'): string
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $fileName, $disk);
    }

    /**
     * 3. File Delete Helper
     */
    // public function deleteFile(?string $path, string $disk = 'public'): void
    // {
    //     if ($path && Storage::disk($disk)->exists($path)) {
    //         Storage::disk($disk)->delete($path);
    //     }
    // }
}

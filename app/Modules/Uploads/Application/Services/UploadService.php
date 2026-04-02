<?php

namespace App\Modules\Uploads\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadService
{
    public function uploadLogo(UploadedFile $file): array
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('logos', $filename, 'public');

        return [
            'path' => $path,
            'url' => asset('storage/' . $path),
            'filename' => $filename,
        ];
    }
}
<?php

namespace App\Modules\Uploads\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use App\Modules\Uploads\Application\Services\UploadService;
use App\Modules\Uploads\Presentation\Requests\UploadLogoRequest;

class UploadController extends Controller
{
    public function __construct(
        protected UploadService $uploadService
    ) {}

    public function uploadLogo(UploadLogoRequest $request)
    {
        try {
            $result = $this->uploadService->uploadLogo(
                $request->file('logo')
            );

            return ApiResponse::success(
                [
                    'url' => $result['url'],
                    'path' => $result['path'],
                    'filename' => $result['filename'],
                ],
                'Logo subido correctamente.',
                201
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
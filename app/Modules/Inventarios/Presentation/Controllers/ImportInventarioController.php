<?php

namespace App\Modules\Inventarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use App\Modules\Inventarios\Application\Services\ImportInventarioService;
use App\Modules\Inventarios\Presentation\Requests\ImportInventarioJsonRequest;

class ImportInventarioController extends Controller
{
    public function __construct(
        protected ImportInventarioService $importInventarioService
    ) {}

    public function importar(ImportInventarioJsonRequest $request)
    {
        try {
            if (!$request->user()->can('importar_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $resultado = $this->importInventarioService->importar(
                $request->validated()['items'],
                $request->user()->id
            );

            return ApiResponse::success(
                $resultado,
                'Importación de inventario completada.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
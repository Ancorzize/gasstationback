<?php

namespace App\Modules\Facturacion\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use App\Modules\Facturacion\Application\Services\DocumentoElectronicoService;
use App\Modules\Facturacion\Presentation\Requests\GetDocumentosElectronicosRequest;
use App\Modules\Facturacion\Presentation\Resources\DocumentoElectronicoResource;

class DocumentoElectronicoController extends Controller
{
    public function __construct(
        protected DocumentoElectronicoService $documentoService
    ) {}

    public function index(GetDocumentosElectronicosRequest $request)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para consultar documentos electrónicos.', 403);
            }

            $filters = $request->validated();
            $perPage = (int) $request->get('per_page', 15);

            $documentos = $this->documentoService->paginate($filters, $perPage);

            return ApiResponse::success([
                'items' => DocumentoElectronicoResource::collection($documentos->items()),
                'pagination' => [
                    'current_page' => $documentos->currentPage(),
                    'last_page' => $documentos->lastPage(),
                    'per_page' => $documentos->perPage(),
                    'total' => $documentos->total(),
                ]
            ], 'Listado de documentos electrónicos.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al consultar documentos electrónicos.', 500);
        }
    }
}

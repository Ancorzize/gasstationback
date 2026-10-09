<?php

namespace App\Modules\Facturacion\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use App\Modules\Facturacion\Application\Services\DocumentoElectronicoService;
use App\Modules\Facturacion\Application\Services\DocumentoSoporteService;
use App\Modules\Facturacion\Application\Services\NotaCreditoService;
use App\Modules\Facturacion\Application\Services\NotaDebitoService;
use App\Modules\Facturacion\Presentation\Requests\GetDocumentosElectronicosRequest;
use App\Modules\Facturacion\Presentation\Requests\PrepararDocumentoSoporteRequest;
use App\Modules\Facturacion\Presentation\Requests\PrepararNotaCreditoRequest;
use App\Modules\Facturacion\Presentation\Requests\PrepararNotaDebitoRequest;
use App\Modules\Facturacion\Presentation\Resources\DocumentoElectronicoResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentoElectronicoController extends Controller
{
    public function __construct(
        protected DocumentoElectronicoService $documentoService,
        protected DocumentoSoporteService $documentoSoporteService,
        protected NotaCreditoService $notaCreditoService,
        protected NotaDebitoService $notaDebitoService
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

    /**
     * Consultar el detalle de un Documento Electrónico por ID.
     */
    public function show(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para consultar documentos electrónicos.', 403);
            }

            $id = (int) $id;
            $documento = $this->documentoService->obtenerPorId($id);

            if (!$documento) {
                return ApiResponse::error("El documento electrónico #{$id} no fue encontrado.", 404);
            }

            return ApiResponse::success(
                new DocumentoElectronicoResource($documento),
                'Detalle del documento electrónico.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al consultar el documento electrónico.', 500);
        }
    }

    /**
     * Preparar la emisión de un Documento Soporte Electrónico (DS) a partir de una Compra o Gasto.
     */
    public function prepararDS(PrepararDocumentoSoporteRequest $request)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para preparar documentos soporte electrónicos.', 403);
            }

            $validated = $request->validated();

            $respuestaDTO = $this->documentoSoporteService->prepararEmisionPorOrigen(
                $validated['origen'],
                (int) $validated['origen_id']
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Documento Soporte preparado exitosamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al preparar el documento soporte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transmitir un Documento Soporte Electrónico previamente preparado.
     * En esta fase se mantiene $ejecutarHttpReal = false por seguridad (sin llamadas HTTP a MATIAS).
     */
    public function transmitirDS(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para transmitir documentos soporte electrónicos.', 403);
            }

            $id = (int) $id;
            $respuestaDTO = $this->documentoSoporteService->transmitirDocumentoPendiente(
                $id
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Proceso de transmisión de Documento Soporte completado.',
                200
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al transmitir el documento soporte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reintentar manualmente la transmisión de un Documento Soporte Electrónico rechazado.
     */
    public function reintentarDS(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para reintentar documentos soporte electrónicos.', 403);
            }

            $id = (int) $id;
            $respuestaDTO = $this->documentoSoporteService->reintentarDocumentoRechazado(
                $id
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Proceso de reintento de Documento Soporte completado.',
                200
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al reintentar el documento soporte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reintentar masivamente la transmisión de todos los Documentos Soporte Electrónicos rechazados.
     */
    public function reintentarMasivoDS(Request $request)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para reintentar documentos soporte electrónicos.', 403);
            }

            $resultado = $this->documentoSoporteService->reintentarMasivoDocumentosRechazados();

            return ApiResponse::success(
                $resultado,
                $resultado['total_encontrados'] > 0
                    ? 'Proceso de reintento masivo finalizado.'
                    : 'No existen documentos soporte en estado rechazado para reintentar.',
                200
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al procesar el reintento masivo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Preparar la emisión de una Nota Crédito Electrónica (NC) a partir de una Venta.
     */
    public function prepararNC(PrepararNotaCreditoRequest $request)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para preparar notas crédito electrónicas.', 403);
            }

            $validated = $request->validated();

            $respuestaDTO = $this->notaCreditoService->prepararEmisionPorVenta(
                (int) $validated['venta_id'],
                $validated
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Nota Crédito Electrónica preparada exitosamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al preparar la nota crédito: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transmitir una Nota Crédito Electrónica previamente preparada a MATIAS API.
     */
    public function transmitirNC(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para transmitir notas crédito electrónicas.', 403);
            }

            $id = (int) $id;
            $respuestaDTO = $this->notaCreditoService->transmitirNotaCreditoPendiente(
                $id
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Proceso de transmisión de Nota Crédito completado.',
                200
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al transmitir la nota crédito: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reintentar manualmente la transmisión de una Nota Crédito Electrónica rechazada.
     */
    public function reintentarNC(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para reintentar notas crédito electrónicas.', 403);
            }

            $id = (int) $id;
            $respuestaDTO = $this->notaCreditoService->reintentarNotaCreditoRechazada(
                $id
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Proceso de reintento de Nota Crédito completado.',
                200
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al reintentar la nota crédito: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Preparar la emisión de una Nota Débito Electrónica (ND) a partir de una Venta.
     */
    public function prepararND(PrepararNotaDebitoRequest $request)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para preparar notas débito electrónicas.', 403);
            }

            $validated = $request->validated();

            $respuestaDTO = $this->notaDebitoService->prepararEmisionPorVenta(
                (int) $validated['venta_id'],
                $validated
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Nota Débito Electrónica preparada exitosamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al preparar la nota débito: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transmitir una Nota Débito Electrónica previamente preparada a MATIAS API.
     */
    public function transmitirND(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para transmitir notas débito electrónicas.', 403);
            }

            $id = (int) $id;
            $respuestaDTO = $this->notaDebitoService->transmitirNotaDebitoPendiente(
                $id
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Proceso de transmisión de Nota Débito completado.',
                200
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al transmitir la nota débito: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reintentar manualmente la transmisión de una Nota Débito Electrónica rechazada.
     */
    public function reintentarND(Request $request, $id)
    {
        try {
            if (!$request->user()->can('ver_documentos_electronicos')) {
                return ApiResponse::error('Sin permisos para reintentar notas débito electrónicas.', 403);
            }

            $id = (int) $id;
            $respuestaDTO = $this->notaDebitoService->reintentarNotaDebitoRechazada(
                $id
            );

            return ApiResponse::success(
                $respuestaDTO->toArray(),
                $respuestaDTO->mensaje ?? 'Proceso de reintento de Nota Débito completado.',
                200
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor al reintentar la nota débito: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Modules\Ventas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Ventas\Application\Services\VentaService;
use App\Modules\Ventas\Infrastructure\Mappers\VentaMapper;
use App\Modules\Ventas\Presentation\Requests\StoreVentaRequest;
use App\Modules\Ventas\Presentation\Resources\VentaResource;
use App\Modules\Ventas\Presentation\Requests\AnularVentaRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ConfiguracionEmpresa;
use App\Modules\Ventas\Presentation\Requests\StoreVentaCombustibleRequest;

class VentaController extends Controller
{
    public function __construct(
        protected VentaService $ventaService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_ventas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'cliente_id' => $request->get('cliente_id'),
                'tipo_venta' => $request->get('tipo_venta'),
                'estado' => $request->get('estado'),
                'estado_pago' => $request->get('estado_pago'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $ventas = $this->ventaService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => VentaResource::collection($ventas->items()),
                'pagination' => [
                    'current_page' => $ventas->currentPage(),
                    'last_page' => $ventas->lastPage(),
                    'per_page' => $ventas->perPage(),
                    'total' => $ventas->total(),
                ]
            ], 'Listado de ventas.');

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_ventas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $venta = $this->ventaService->findById($id);

            return ApiResponse::success(
                new VentaResource($venta),
                'Venta encontrada.'
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function store(StoreVentaRequest $request)
    {
        try {
            if (!$request->user()->can('crear_ventas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = VentaMapper::fromArrayToCreateDTO(
                $request->validated(),
                $request->user()->id
            );

            $venta = $this->ventaService->create($dto);

            return ApiResponse::success(
                new VentaResource($venta),
                'Venta registrada correctamente.',
                201
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function anular(AnularVentaRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('anular_ventas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $venta = $this->ventaService->anular(
                $id,
                $request->validated()['motivo_anulacion'],
                $request->user()->id
            );

            return ApiResponse::success(
                new VentaResource($venta),
                'Venta anulada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function pdf(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_ventas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $venta = $this->ventaService->findById($id);

            $empresa = ConfiguracionEmpresa::with([
                'pais',
                'departamento',
                'ciudad'
            ])->first();

            return Pdf::loadView('pdfs.ventas.show', [
                'venta' => $venta,
                'empresa' => $empresa,
            ])->stream("venta_{$venta->numero_factura}.pdf");

        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function storeCombustible(StoreVentaCombustibleRequest $request)
    {
        try {
            if (!$request->user()->can('crear_ventas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = VentaMapper::fromArrayToCreateCombustibleDTO(
                $request->validated(),
                $request->user()->id
            );

            $venta = $this->ventaService->createCombustible($dto);

            return ApiResponse::success(
                new VentaResource($venta),
                'Venta de combustible registrada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
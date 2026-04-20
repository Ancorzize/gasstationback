<?php

namespace App\Modules\PagosCompra\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\PagosCompra\Application\Services\PagoCompraService;
use App\Modules\PagosCompra\Presentation\Resources\PagoCompraResource;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ConfiguracionEmpresa;

class PagoCompraController extends Controller
{
    public function __construct(
        protected PagoCompraService $pagoCompraService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_pagos_compra')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'compra_id' => $request->get('compra_id'),
                'proveedor_id' => $request->get('proveedor_id'),
                'metodo_pago' => $request->get('metodo_pago'),
                'user_id' => $request->get('user_id'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $pagos = $this->pagoCompraService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => PagoCompraResource::collection($pagos->items()),
                'pagination' => [
                    'current_page' => $pagos->currentPage(),
                    'last_page' => $pagos->lastPage(),
                    'per_page' => $pagos->perPage(),
                    'total' => $pagos->total(),
                ]
            ], 'Listado de pagos de compra.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_pagos_compra')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $pago = $this->pagoCompraService->findById($id);

            return ApiResponse::success(
                new PagoCompraResource($pago),
                'Pago de compra encontrado.'
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
            if (!$request->user()->can('ver_pagos_compra')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $pago = $this->pagoCompraService->findById($id);
            $empresa = ConfiguracionEmpresa::with(['pais', 'departamento', 'ciudad'])->first();

            return Pdf::loadView('pdfs.compras.pago_compra', [
                'data' => (new PagoCompraResource($pago))->resolve(),
                'empresa' => $empresa,
            ])->download("compra_{$id}.pdf");
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
<?php

namespace App\Modules\Compras\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Compras\Application\Services\CompraService;
use App\Modules\Compras\Infrastructure\Mappers\CompraMapper;
use App\Modules\Compras\Infrastructure\Mappers\PagoCompraMapper;
use App\Modules\Compras\Presentation\Requests\StoreCompraRequest;
use App\Modules\Compras\Presentation\Requests\UpdateCompraRequest;
use App\Modules\Compras\Presentation\Requests\StorePagoCompraRequest;
use App\Modules\Compras\Presentation\Resources\CompraResource;
use App\Modules\Compras\Presentation\Resources\PagoCompraResource;

class CompraController extends Controller
{
    public function __construct(
        protected CompraService $compraService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_compras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'proveedor_id' => $request->get('proveedor_id'),
                'bodega_id' => $request->get('bodega_id'),
                'estado' => $request->get('estado'),
                'estado_pago' => $request->get('estado_pago'),
                'tipo_pago' => $request->get('tipo_pago'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $compras = $this->compraService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => CompraResource::collection($compras->items()),
                'pagination' => [
                    'current_page' => $compras->currentPage(),
                    'last_page' => $compras->lastPage(),
                    'per_page' => $compras->perPage(),
                    'total' => $compras->total(),
                ]
            ], 'Listado de compras.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_compras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $compra = $this->compraService->findById($id);

            return ApiResponse::success(
                new CompraResource($compra),
                'Compra encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreCompraRequest $request)
    {
        try {
            if (!$request->user()->can('crear_compras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CompraMapper::fromArrayToCreateDTO(
                $request->validated(),
                $request->user()->id
            );

            $compra = $this->compraService->create($dto);

            return ApiResponse::success(
                new CompraResource($compra),
                'Compra creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateCompraRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_compras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CompraMapper::fromArrayToUpdateDTO($request->validated());
            $compra = $this->compraService->update($id, $dto);

            return ApiResponse::success(
                new CompraResource($compra),
                'Compra actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function confirmar(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('confirmar_compras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $compra = $this->compraService->confirmar($id);

            return ApiResponse::success(
                new CompraResource($compra),
                'Compra confirmada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function pagos(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_pagos_compra')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $pagos = $this->compraService->getPagos($id);

            return ApiResponse::success(
                PagoCompraResource::collection($pagos),
                'Listado de pagos de la compra.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function registrarPago(StorePagoCompraRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('registrar_pagos_compra')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = PagoCompraMapper::fromArrayToCreateDTO(
                $request->validated(),
                $id,
                $request->user()->id
            );

            $compra = $this->compraService->registrarPago($dto);

            return ApiResponse::success(
                new CompraResource($compra),
                'Pago registrado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
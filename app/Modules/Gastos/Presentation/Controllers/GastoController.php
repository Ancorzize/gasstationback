<?php

namespace App\Modules\Gastos\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Gastos\Application\Services\GastoService;
use App\Modules\Gastos\Infrastructure\Mappers\GastoMapper;
use App\Modules\Gastos\Presentation\Requests\StoreGastoRequest;
use App\Modules\Gastos\Presentation\Resources\GastoResource;

class GastoController extends Controller
{
    public function __construct(
        protected GastoService $gastoService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_gastos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'proveedor_id' => $request->get('proveedor_id'),
                'categoria_gasto_id' => $request->get('categoria_gasto_id'),
                'medio_pago' => $request->get('medio_pago'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $gastos = $this->gastoService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => GastoResource::collection($gastos->items()),
                'pagination' => [
                    'current_page' => $gastos->currentPage(),
                    'last_page' => $gastos->lastPage(),
                    'per_page' => $gastos->perPage(),
                    'total' => $gastos->total(),
                ]
            ], 'Listado de gastos.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_gastos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $gasto = $this->gastoService->findById($id);

            return ApiResponse::success(
                new GastoResource($gasto),
                'Gasto encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreGastoRequest $request)
    {
        try {
            if (!$request->user()->can('crear_gastos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = GastoMapper::fromArrayToCreateDTO(
                $request->validated(),
                $request->user()->id
            );

            $gasto = $this->gastoService->create($dto);

            return ApiResponse::success(
                new GastoResource($gasto),
                'Gasto registrado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
<?php

namespace App\Modules\CategoriasGasto\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\CategoriasGasto\Application\Services\CategoriaGastoService;
use App\Modules\CategoriasGasto\Infrastructure\Mappers\CategoriaGastoMapper;
use App\Modules\CategoriasGasto\Presentation\Requests\StoreCategoriaGastoRequest;
use App\Modules\CategoriasGasto\Presentation\Requests\UpdateCategoriaGastoRequest;
use App\Modules\CategoriasGasto\Presentation\Requests\ChangeCategoriaGastoStatusRequest;
use App\Modules\CategoriasGasto\Presentation\Resources\CategoriaGastoResource;

class CategoriaGastoController extends Controller
{
    public function __construct(
        protected CategoriaGastoService $categoriaGastoService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_categorias_gasto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $categorias = $this->categoriaGastoService->paginate(
                $filters,
                (int) $request->get('per_page', 1000)
            );

            return ApiResponse::success([
                'items' => CategoriaGastoResource::collection($categorias->items()),
                'pagination' => [
                    'current_page' => $categorias->currentPage(),
                    'last_page' => $categorias->lastPage(),
                    'per_page' => $categorias->perPage(),
                    'total' => $categorias->total(),
                ]
            ], 'Listado de categorías de gasto.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_categorias_gasto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $categoria = $this->categoriaGastoService->findById($id);

            return ApiResponse::success(
                new CategoriaGastoResource($categoria),
                'Categoría de gasto encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreCategoriaGastoRequest $request)
    {
        try {
            if (!$request->user()->can('crear_categorias_gasto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CategoriaGastoMapper::fromArrayToCreateDTO($request->validated());
            $categoria = $this->categoriaGastoService->create($dto);

            return ApiResponse::success(
                new CategoriaGastoResource($categoria),
                'Categoría de gasto creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateCategoriaGastoRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_categorias_gasto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CategoriaGastoMapper::fromArrayToUpdateDTO($request->validated());
            $categoria = $this->categoriaGastoService->update($id, $dto);

            return ApiResponse::success(
                new CategoriaGastoResource($categoria),
                'Categoría de gasto actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeCategoriaGastoStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_categorias_gasto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $categoria = $this->categoriaGastoService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new CategoriaGastoResource($categoria),
                'Estado de la categoría de gasto actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
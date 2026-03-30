<?php

namespace App\Modules\CategoriasProducto\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\CategoriasProducto\Application\Services\CategoriaProductoService;
use App\Modules\CategoriasProducto\Infrastructure\Mappers\CategoriaProductoMapper;
use App\Modules\CategoriasProducto\Presentation\Requests\StoreCategoriaProductoRequest;
use App\Modules\CategoriasProducto\Presentation\Requests\UpdateCategoriaProductoRequest;
use App\Modules\CategoriasProducto\Presentation\Requests\ChangeCategoriaProductoStatusRequest;
use App\Modules\CategoriasProducto\Presentation\Resources\CategoriaProductoResource;

class CategoriaProductoController extends Controller
{
    public function __construct(
        protected CategoriaProductoService $categoriaProductoService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_categorias_producto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $categorias = $this->categoriaProductoService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => CategoriaProductoResource::collection($categorias->items()),
                'pagination' => [
                    'current_page' => $categorias->currentPage(),
                    'last_page' => $categorias->lastPage(),
                    'per_page' => $categorias->perPage(),
                    'total' => $categorias->total(),
                ]
            ], 'Listado de categorías de producto.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_categorias_producto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $categoria = $this->categoriaProductoService->findById($id);

            return ApiResponse::success(
                new CategoriaProductoResource($categoria),
                'Categoría encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreCategoriaProductoRequest $request)
    {
        try {
            if (!$request->user()->can('crear_categorias_producto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CategoriaProductoMapper::fromArrayToCreateDTO($request->validated());
            $categoria = $this->categoriaProductoService->create($dto);

            return ApiResponse::success(
                new CategoriaProductoResource($categoria),
                'Categoría creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateCategoriaProductoRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_categorias_producto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CategoriaProductoMapper::fromArrayToUpdateDTO($request->validated());
            $categoria = $this->categoriaProductoService->update($id, $dto);

            return ApiResponse::success(
                new CategoriaProductoResource($categoria),
                'Categoría actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeCategoriaProductoStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_categorias_producto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $categoria = $this->categoriaProductoService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new CategoriaProductoResource($categoria),
                'Estado de la categoría actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('eliminar_categorias_producto')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->categoriaProductoService->delete($id);

            return ApiResponse::success(
                null,
                'Categoría eliminada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
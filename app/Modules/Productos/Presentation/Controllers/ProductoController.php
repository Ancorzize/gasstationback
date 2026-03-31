<?php

namespace App\Modules\Productos\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Productos\Application\Services\ProductoService;
use App\Modules\Productos\Infrastructure\Mappers\ProductoMapper;
use App\Modules\Productos\Presentation\Requests\StoreProductoRequest;
use App\Modules\Productos\Presentation\Requests\UpdateProductoRequest;
use App\Modules\Productos\Presentation\Requests\ChangeProductoStatusRequest;
use App\Modules\Productos\Presentation\Resources\ProductoResource;

class ProductoController extends Controller
{
    public function __construct(
        protected ProductoService $productoService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_productos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'marca_id' => $request->get('marca_id'),
                'categoria_producto_id' => $request->get('categoria_producto_id'),
                'unidad_medida_id' => $request->get('unidad_medida_id'),
                'is_active' => $request->get('is_active'),
            ];

            $productos = $this->productoService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => ProductoResource::collection($productos->items()),
                'pagination' => [
                    'current_page' => $productos->currentPage(),
                    'last_page' => $productos->lastPage(),
                    'per_page' => $productos->perPage(),
                    'total' => $productos->total(),
                ]
            ], 'Listado de productos.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_productos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $producto = $this->productoService->findById($id);

            return ApiResponse::success(
                new ProductoResource($producto),
                'Producto encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreProductoRequest $request)
    {
        try {
            if (!$request->user()->can('crear_productos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ProductoMapper::fromArrayToCreateDTO($request->validated());
            $producto = $this->productoService->create($dto);

            return ApiResponse::success(
                new ProductoResource($producto),
                'Producto creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateProductoRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_productos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ProductoMapper::fromArrayToUpdateDTO($request->validated());
            $producto = $this->productoService->update($id, $dto);

            return ApiResponse::success(
                new ProductoResource($producto),
                'Producto actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeProductoStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_productos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $producto = $this->productoService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new ProductoResource($producto),
                'Estado del producto actualizado correctamente.'
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
            if (!$request->user()->can('eliminar_productos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->productoService->delete($id);

            return ApiResponse::success(
                null,
                'Producto eliminado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
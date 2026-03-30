<?php

namespace App\Modules\Proveedores\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Proveedores\Application\Services\ProveedorService;
use App\Modules\Proveedores\Infrastructure\Mappers\ProveedorMapper;
use App\Modules\Proveedores\Presentation\Requests\StoreProveedorRequest;
use App\Modules\Proveedores\Presentation\Requests\UpdateProveedorRequest;
use App\Modules\Proveedores\Presentation\Requests\ChangeProveedorStatusRequest;
use App\Modules\Proveedores\Presentation\Resources\ProveedorResource;

class ProveedorController extends Controller
{
    public function __construct(
        protected ProveedorService $service
    ) {}

    public function index(Request $request)
{
    try {
        if (!$request->user()->can('ver_proveedores')) {
            return ApiResponse::error('Sin permisos.', 403);
        }

        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->get('is_active'),
        ];

        $proveedores = $this->service->paginate(
            $filters,
            (int) $request->get('per_page', 10)
        );

        return ApiResponse::success([
            'items' => ProveedorResource::collection($proveedores->items()),
            'pagination' => [
                'current_page' => $proveedores->currentPage(),
                'last_page' => $proveedores->lastPage(),
                'per_page' => $proveedores->perPage(),
                'total' => $proveedores->total(),
            ]
        ], 'Listado de proveedores.');
    } catch (\Throwable $e) {
        return ApiResponse::error('Error interno del servidor.', 500);
    }
}
    public function store(StoreProveedorRequest $request)
    {
        if (!$request->user()->can('crear_proveedores')) {
            return ApiResponse::error('Sin permisos.', 403);
        }

        $dto = ProveedorMapper::fromArrayToCreateDTO($request->validated());

        $proveedor = $this->service->create($dto);

        return ApiResponse::success($proveedor, 'Proveedor creado.', 201);
    }

     public function update(UpdateProveedorRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ProveedorMapper::fromArrayToUpdateDTO($request->validated());
            $cliente = $this->service->update($id, $dto);

            return ApiResponse::success(
                new ProveedorResource($cliente),
                'Cliente actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeProveedorStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $cliente = $this->service->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new ProveedorResource($cliente),
                'Estado del cliente actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
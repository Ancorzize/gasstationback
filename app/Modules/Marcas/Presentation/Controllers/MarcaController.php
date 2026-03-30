<?php

namespace App\Modules\Marcas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Marcas\Application\Services\MarcaService;
use App\Modules\Marcas\Infrastructure\Mappers\MarcaMapper;
use App\Modules\Marcas\Presentation\Requests\StoreMarcaRequest;
use App\Modules\Marcas\Presentation\Requests\UpdateMarcaRequest;
use App\Modules\Marcas\Presentation\Requests\ChangeMarcaStatusRequest;
use App\Modules\Marcas\Presentation\Resources\MarcaResource;

class MarcaController extends Controller
{
    public function __construct(
        protected MarcaService $marcaService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_marcas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $marcas = $this->marcaService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => MarcaResource::collection($marcas->items()),
                'pagination' => [
                    'current_page' => $marcas->currentPage(),
                    'last_page' => $marcas->lastPage(),
                    'per_page' => $marcas->perPage(),
                    'total' => $marcas->total(),
                ]
            ], 'Listado de marcas.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_marcas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $marca = $this->marcaService->findById($id);

            return ApiResponse::success(
                new MarcaResource($marca),
                'Marca encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreMarcaRequest $request)
    {
        try {
            if (!$request->user()->can('crear_marcas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MarcaMapper::fromArrayToCreateDTO($request->validated());
            $marca = $this->marcaService->create($dto);

            return ApiResponse::success(
                new MarcaResource($marca),
                'Marca creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateMarcaRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_marcas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MarcaMapper::fromArrayToUpdateDTO($request->validated());
            $marca = $this->marcaService->update($id, $dto);

            return ApiResponse::success(
                new MarcaResource($marca),
                'Marca actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeMarcaStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_marcas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $marca = $this->marcaService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new MarcaResource($marca),
                'Estado de la marca actualizado correctamente.'
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
            if (!$request->user()->can('eliminar_marcas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->marcaService->delete($id);

            return ApiResponse::success(
                null,
                'Marca eliminada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
<?php

namespace App\Modules\UnidadesMedida\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\UnidadesMedida\Application\Services\UnidadMedidaService;
use App\Modules\UnidadesMedida\Infrastructure\Mappers\UnidadMedidaMapper;
use App\Modules\UnidadesMedida\Presentation\Requests\StoreUnidadMedidaRequest;
use App\Modules\UnidadesMedida\Presentation\Requests\UpdateUnidadMedidaRequest;
use App\Modules\UnidadesMedida\Presentation\Requests\ChangeUnidadMedidaStatusRequest;
use App\Modules\UnidadesMedida\Presentation\Resources\UnidadMedidaResource;

class UnidadMedidaController extends Controller
{
    public function __construct(
        protected UnidadMedidaService $unidadMedidaService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_unidades_medida')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $unidadesMedida = $this->unidadMedidaService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => UnidadMedidaResource::collection($unidadesMedida->items()),
                'pagination' => [
                    'current_page' => $unidadesMedida->currentPage(),
                    'last_page' => $unidadesMedida->lastPage(),
                    'per_page' => $unidadesMedida->perPage(),
                    'total' => $unidadesMedida->total(),
                ]
            ], 'Listado de unidades de medida.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_unidades_medida')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $unidadMedida = $this->unidadMedidaService->findById($id);

            return ApiResponse::success(
                new UnidadMedidaResource($unidadMedida),
                'Unidad de medida encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreUnidadMedidaRequest $request)
    {
        try {
            if (!$request->user()->can('crear_unidades_medida')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = UnidadMedidaMapper::fromArrayToCreateDTO($request->validated());
            $unidadMedida = $this->unidadMedidaService->create($dto);

            return ApiResponse::success(
                new UnidadMedidaResource($unidadMedida),
                'Unidad de medida creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateUnidadMedidaRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_unidades_medida')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = UnidadMedidaMapper::fromArrayToUpdateDTO($request->validated());
            $unidadMedida = $this->unidadMedidaService->update($id, $dto);

            return ApiResponse::success(
                new UnidadMedidaResource($unidadMedida),
                'Unidad de medida actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeUnidadMedidaStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_unidades_medida')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $unidadMedida = $this->unidadMedidaService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new UnidadMedidaResource($unidadMedida),
                'Estado de la unidad de medida actualizado correctamente.'
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
            if (!$request->user()->can('eliminar_unidades_medida')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->unidadMedidaService->delete($id);

            return ApiResponse::success(
                null,
                'Unidad de medida eliminada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
<?php

namespace App\Modules\Bodegas\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Bodegas\Application\Services\BodegaService;
use App\Modules\Bodegas\Infrastructure\Mappers\BodegaMapper;
use App\Modules\Bodegas\Presentation\Requests\StoreBodegaRequest;
use App\Modules\Bodegas\Presentation\Requests\UpdateBodegaRequest;
use App\Modules\Bodegas\Presentation\Requests\ChangeBodegaStatusRequest;
use App\Modules\Bodegas\Presentation\Resources\BodegaResource;

class BodegaController extends Controller
{
    public function __construct(
        protected BodegaService $bodegaService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_bodegas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'responsable_id' => $request->get('responsable_id'),
                'is_principal' => $request->get('is_principal'),
                'is_active' => $request->get('is_active'),
            ];

            $bodegas = $this->bodegaService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => BodegaResource::collection($bodegas->items()),
                'pagination' => [
                    'current_page' => $bodegas->currentPage(),
                    'last_page' => $bodegas->lastPage(),
                    'per_page' => $bodegas->perPage(),
                    'total' => $bodegas->total(),
                ]
            ], 'Listado de bodegas.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_bodegas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $bodega = $this->bodegaService->findById($id);

            return ApiResponse::success(
                new BodegaResource($bodega),
                'Bodega encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreBodegaRequest $request)
    {
        try {
            if (!$request->user()->can('crear_bodegas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = BodegaMapper::fromArrayToCreateDTO($request->validated());
            $bodega = $this->bodegaService->create($dto);

            return ApiResponse::success(
                new BodegaResource($bodega),
                'Bodega creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateBodegaRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_bodegas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = BodegaMapper::fromArrayToUpdateDTO($request->validated());
            $bodega = $this->bodegaService->update($id, $dto);

            return ApiResponse::success(
                new BodegaResource($bodega),
                'Bodega actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeBodegaStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_bodegas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $bodega = $this->bodegaService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new BodegaResource($bodega),
                'Estado de la bodega actualizado correctamente.'
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
            if (!$request->user()->can('eliminar_bodegas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->bodegaService->delete($id);

            return ApiResponse::success(
                null,
                'Bodega eliminada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
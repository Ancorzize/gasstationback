<?php

namespace App\Modules\Bombas\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Bombas\Application\Services\BombaService;
use App\Modules\Bombas\Infrastructure\Mappers\BombaMapper;
use App\Modules\Bombas\Presentation\Requests\StoreBombaRequest;
use App\Modules\Bombas\Presentation\Requests\UpdateBombaRequest;
use App\Modules\Bombas\Presentation\Requests\ChangeBombaStatusRequest;
use App\Modules\Bombas\Presentation\Resources\BombaResource;

class BombaController extends Controller
{
    public function __construct(
        protected BombaService $bombaService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_bombas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'estacion_id' => $request->get('estacion_id'),
                'is_active' => $request->get('is_active'),
            ];

            $bombas = $this->bombaService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => BombaResource::collection($bombas->items()),
                'pagination' => [
                    'current_page' => $bombas->currentPage(),
                    'last_page' => $bombas->lastPage(),
                    'per_page' => $bombas->perPage(),
                    'total' => $bombas->total(),
                ],
            ], 'Listado de bombas.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_bombas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                new BombaResource($this->bombaService->findById($id)),
                'Bomba encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreBombaRequest $request)
    {
        try {
            if (!$request->user()->can('crear_bombas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = BombaMapper::fromArrayToCreateDTO($request->validated());
            $bomba = $this->bombaService->create($dto);

            return ApiResponse::success(
                new BombaResource($bomba),
                'Bomba creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateBombaRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_bombas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = BombaMapper::fromArrayToUpdateDTO($request->validated());
            $bomba = $this->bombaService->update($id, $dto);

            return ApiResponse::success(
                new BombaResource($bomba),
                'Bomba actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeBombaStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_bombas')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $bomba = $this->bombaService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new BombaResource($bomba),
                'Estado de la bomba actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
<?php

namespace App\Modules\Mangueras\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Mangueras\Application\Services\MangueraService;
use App\Modules\Mangueras\Infrastructure\Mappers\MangueraMapper;
use App\Modules\Mangueras\Presentation\Requests\StoreMangueraRequest;
use App\Modules\Mangueras\Presentation\Requests\UpdateMangueraRequest;
use App\Modules\Mangueras\Presentation\Requests\ChangeMangueraStatusRequest;
use App\Modules\Mangueras\Presentation\Resources\MangueraResource;
use App\Modules\Mangueras\Presentation\Resources\LecturaMangueraResource;

class MangueraController extends Controller
{
    public function __construct(
        protected MangueraService $mangueraService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_mangueras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'bomba_id' => $request->get('bomba_id'),
                'producto_id' => $request->get('producto_id'),
                'estacion_id' => $request->get('estacion_id'),
                'is_active' => $request->get('is_active'),
                
            ];

            $mangueras = $this->mangueraService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => MangueraResource::collection($mangueras->items()),
                'pagination' => [
                    'current_page' => $mangueras->currentPage(),
                    'last_page' => $mangueras->lastPage(),
                    'per_page' => $mangueras->perPage(),
                    'total' => $mangueras->total(),
                ],
            ], 'Listado de mangueras.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_mangueras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                new MangueraResource($this->mangueraService->findById($id)),
                'Manguera encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreMangueraRequest $request)
    {
        try {
            if (!$request->user()->can('crear_mangueras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MangueraMapper::fromArrayToCreateDTO($request->validated());
            $manguera = $this->mangueraService->create($dto);

            return ApiResponse::success(
                new MangueraResource($manguera),
                'Manguera creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateMangueraRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_mangueras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MangueraMapper::fromArrayToUpdateDTO($request->validated());
            $manguera = $this->mangueraService->update($id, $dto);

            return ApiResponse::success(
                new MangueraResource($manguera),
                'Manguera actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeMangueraStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_mangueras')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $manguera = $this->mangueraService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new MangueraResource($manguera),
                'Estado de la manguera actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function lecturas(Request $request)
    {
        try {

            if (!$request->user()->can('ver_mangueras')) {

                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );

            }

            $filters = [
                'search'=>$request->get('search'),
                'turno_id'=>$request->get('turno_id'),
                'manguera_id'=>$request->get('manguera_id'),
                'producto_id'=>$request->get('producto_id'),
                'bomba_id'=>$request->get('bomba_id'),
                'estacion_id'=>$request->get('estacion_id'),
                'fecha_desde'=>$request->get('fecha_desde'),
                'fecha_hasta'=>$request->get('fecha_hasta'),
            ];

            $lecturas = $this->mangueraService
                ->paginateLecturas(
                    $filters,
                    (int)$request->get('per_page',10)
                );

            return ApiResponse::success([

                'items'=>LecturaMangueraResource::collection(
                    $lecturas->items()
                ),

                'pagination'=>[
                    'current_page'=>$lecturas->currentPage(),
                    'last_page'=>$lecturas->lastPage(),
                    'per_page'=>$lecturas->perPage(),
                    'total'=>$lecturas->total(),
                ],

            ],'Listado de lecturas.');

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }
}
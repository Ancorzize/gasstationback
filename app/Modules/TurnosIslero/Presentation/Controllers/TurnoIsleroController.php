<?php

namespace App\Modules\TurnosIslero\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\TurnosIslero\Application\Services\TurnoIsleroService;
use App\Modules\TurnosIslero\Infrastructure\Mappers\TurnoIsleroMapper;
use App\Modules\TurnosIslero\Presentation\Requests\AbrirTurnoIsleroRequest;
use App\Modules\TurnosIslero\Presentation\Requests\CerrarTurnoIsleroRequest;
use App\Modules\TurnosIslero\Presentation\Resources\TurnoIsleroResource;

class TurnoIsleroController extends Controller
{
    public function __construct(
        protected TurnoIsleroService $turnoService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'estacion_id' => $request->get('estacion_id'),
                'user_id' => $request->get('user_id'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $turnos = $this->turnoService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => TurnoIsleroResource::collection($turnos->items()),
                'pagination' => [
                    'current_page' => $turnos->currentPage(),
                    'last_page' => $turnos->lastPage(),
                    'per_page' => $turnos->perPage(),
                    'total' => $turnos->total(),
                ],
            ], 'Listado de turnos de islero.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function actual(Request $request)
    {
        try {
            if (!$request->user()->can('ver_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $turno = $this->turnoService->actual($request->user()->id);

            return ApiResponse::success(
                $turno ? new TurnoIsleroResource($turno) : null,
                'Turno actual.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                new TurnoIsleroResource($this->turnoService->findById($id)),
                'Turno encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function abrir(AbrirTurnoIsleroRequest $request)
    {
        try {
            if (!$request->user()->can('abrir_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = TurnoIsleroMapper::fromArrayToAbrirDTO(
                $request->validated(),
                $request->user()->id
            );

            $turno = $this->turnoService->abrir($dto);

            return ApiResponse::success(
                new TurnoIsleroResource($turno),
                'Turno abierto correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function cerrar(CerrarTurnoIsleroRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cerrar_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = TurnoIsleroMapper::fromArrayToCerrarDTO(
                $request->validated(),
                $id,
                $request->user()->id
            );

            $turno = $this->turnoService->cerrar($dto);

            return ApiResponse::success(
                new TurnoIsleroResource($turno),
                'Turno cerrado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function resumenCierre(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                $this->turnoService->resumenCierre($id),
                'Resumen previo al cierre del turno.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
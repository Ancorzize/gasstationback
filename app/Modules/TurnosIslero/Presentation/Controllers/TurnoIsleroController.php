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
use App\Modules\TurnosIslero\Presentation\Requests\SolicitarCierreTurnoIsleroRequest;
use App\Modules\TurnosIslero\Presentation\Requests\AprobarCierreTurnoIsleroRequest;
use App\Modules\TurnosIslero\Presentation\Requests\DevolverTurnoIsleroRequest;
use App\Modules\TurnosIslero\Presentation\Resources\EditarCierreTurnoIsleroResource;
use Illuminate\Validation\ValidationException;
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

            $turnos = $this->turnoService->getAll($filters);

            return ApiResponse::success([
                'items' => TurnoIsleroResource::collection($turnos),
            ], 'Listado de turnos de islero.');

        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
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
        } catch (ValidationException $e) {
            return ApiResponse::error(
                'Debe enviar lectura inicial para algunas mangueras.',
                422,
                $e->errors()
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
        } catch (ValidationException $e) {
            return ApiResponse::error(
                'Debe enviar lectura final para todas las mangueras del turno.',
                422,
                $e->errors()
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

    public function manguerasDisponibles(Request $request)
    {
        try {
            if (!$request->user()->can('abrir_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $request->validate([
                'estacion_id' => ['required', 'integer', 'exists:estaciones,id'],
            ]);

            return ApiResponse::success(
                $this->turnoService->manguerasDisponibles((int) $request->get('estacion_id')),
                'Mangueras disponibles.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Datos inválidos.', 422, $e->errors());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function solicitarCierre(
        SolicitarCierreTurnoIsleroRequest $request,
        int $id
    ) {
        try {

            if (!$request->user()->can('cerrar_turnos_islero')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $dto = TurnoIsleroMapper::fromArrayToSolicitarCierreDTO(
                $request->validated(),
                $id,
                $request->user()->id
            );

            $turno = $this->turnoService->solicitarCierre($dto);

            return ApiResponse::success(
                new TurnoIsleroResource($turno),
                'Cierre enviado a revisión correctamente.'
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function aprobarCierre(
        AprobarCierreTurnoIsleroRequest $request,
        int $id
    ) {
        try {

            if (!$request->user()->can('aprobar_cierres_turnos_islero')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $dto = TurnoIsleroMapper::fromArrayToAprobarCierreDTO(
                $id,
                $request->user()->id
            );

            $turno = $this->turnoService->aprobarCierre($dto);

            return ApiResponse::success(
                new TurnoIsleroResource($turno),
                'Cierre de turno aprobado correctamente.'
            );

        } catch (ValidationException $e) {

            return ApiResponse::error(
                'Datos inválidos.',
                422,
                $e->errors()
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function devolverCierre(
        DevolverTurnoIsleroRequest $request,
        int $id
    )
    {
        try {

            if (!$request->user()->can('devolver_cierres_turnos_islero')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $dto = TurnoIsleroMapper::fromArrayToDevolverDTO(
                $request->validated(),
                $id,
                $request->user()->id
            );

            $turno = $this->turnoService->devolverTurno($dto);

            return ApiResponse::success(
                new TurnoIsleroResource($turno),
                'Turno devuelto al islero correctamente.'
            );

        } catch (ValidationException $e) {

            return ApiResponse::error(
                'Datos inválidos.',
                422,
                $e->errors()
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }


    public function pendientesCierre(Request $request)
    {
        try {

            if (!$request->user()->can('aprobar_cierres_turnos_islero')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $turnos = $this->turnoService->getAll([
                'estado' => 'pendiente_cierre',
            ]);

            return ApiResponse::success([
                'items' => TurnoIsleroResource::collection($turnos),
            ], 'Turnos pendientes por aprobar.');

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function devueltos(Request $request)
    {
        try {

            if (!$request->user()->can('ver_turnos_islero')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $turnos = $this->turnoService->getAll([
                'user_id' => $request->user()->id,
                'estado' => 'devuelto',
            ]);

            return ApiResponse::success([
                'items' => TurnoIsleroResource::collection($turnos),
            ], 'Turnos devueltos.');

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function revisionCierre(Request $request, int $id)
    {
        try {

            if (!$request->user()->can('aprobar_cierres_turnos_islero')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $revision = $this->turnoService->revisionCierre($id);

            return ApiResponse::success(
                $revision,
                'Información del cierre para revisión.'
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function editarCierre(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('cerrar_turnos_islero')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $turno = $this->turnoService->editarCierre(
                $id,
                $request->user()->id
            );

            return ApiResponse::success(
                new EditarCierreTurnoIsleroResource($turno),
                'Información del cierre disponible para edición.'
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    

    

    
}
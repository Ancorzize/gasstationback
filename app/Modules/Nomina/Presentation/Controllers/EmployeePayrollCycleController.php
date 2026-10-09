<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\EmployeePayrollCycleService;
use App\Modules\Nomina\Infrastructure\Mappers\EmployeePayrollCycleMapper;
use App\Modules\Nomina\Presentation\Requests\StoreEmployeePayrollCycleRequest;
use App\Modules\Nomina\Presentation\Requests\UpdateEmployeePayrollCycleRequest;
use App\Modules\Nomina\Presentation\Requests\ChangeEmployeePayrollCycleStatusRequest;
use App\Modules\Nomina\Presentation\Resources\EmployeePayrollCycleResource;

class EmployeePayrollCycleController extends Controller
{
    public function __construct(
        protected EmployeePayrollCycleService $service
    ) {}

    public function index(Request $request, int $employeeId)
    {
        try {
            $assignments = $this->service->getByEmployeeId($employeeId);

            return ApiResponse::success(
                EmployeePayrollCycleResource::collection($assignments),
                'Asignaciones históricas de ciclos de nómina del empleado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $assignment = $this->service->findById($id);

            return ApiResponse::success(
                new EmployeePayrollCycleResource($assignment),
                'Asignación de ciclo de nómina encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreEmployeePayrollCycleRequest $request, int $employeeId)
    {
        try {
            $dto = EmployeePayrollCycleMapper::fromArrayToCreateDTO($request->validated(), $employeeId);
            $assignment = $this->service->create($dto);

            return ApiResponse::success(
                new EmployeePayrollCycleResource($assignment),
                'Asignación de ciclo de nómina creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateEmployeePayrollCycleRequest $request, int $id)
    {
        try {
            $dto = EmployeePayrollCycleMapper::fromArrayToUpdateDTO($request->validated());
            $assignment = $this->service->update($id, $dto);

            return ApiResponse::success(
                new EmployeePayrollCycleResource($assignment),
                'Asignación de ciclo de nómina actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(ChangeEmployeePayrollCycleStatusRequest $request, int $id)
    {
        try {
            $assignment = $this->service->changeStatus($id, (bool) $request->validated()['is_active']);

            return ApiResponse::success(
                new EmployeePayrollCycleResource($assignment),
                'Estado de la asignación de ciclo de nómina actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}

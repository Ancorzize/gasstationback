<?php

namespace App\Modules\Empleados\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Empleados\Application\Services\EmployeeService;
use App\Modules\Empleados\Infrastructure\Mappers\EmployeeMapper;
use App\Modules\Empleados\Presentation\Requests\StoreEmployeeRequest;
use App\Modules\Empleados\Presentation\Requests\UpdateEmployeeRequest;
use App\Modules\Empleados\Presentation\Requests\ChangeEmployeeStatusRequest;
use App\Modules\Empleados\Presentation\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $perPage = (int) $request->get('per_page', 15);
            $employees = $this->employeeService->paginate($filters, $perPage);

            return ApiResponse::success([
                'items' => EmployeeResource::collection($employees->items()),
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                ]
            ], 'Listado de empleados.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $employee = $this->employeeService->findById($id);

            return ApiResponse::success(
                new EmployeeResource($employee),
                'Empleado encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreEmployeeRequest $request)
    {
        try {
            $dto = EmployeeMapper::fromArrayToCreateDTO($request->validated());
            $employee = $this->employeeService->create($dto);

            return ApiResponse::success(
                new EmployeeResource($employee),
                'Empleado creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateEmployeeRequest $request, int $id)
    {
        try {
            $dto = EmployeeMapper::fromArrayToUpdateDTO($request->validated());
            $employee = $this->employeeService->update($id, $dto);

            return ApiResponse::success(
                new EmployeeResource($employee),
                'Empleado actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(ChangeEmployeeStatusRequest $request, int $id)
    {
        try {
            $employee = $this->employeeService->changeStatus($id, (bool) $request->validated()['is_active']);

            return ApiResponse::success(
                new EmployeeResource($employee),
                'Estado del empleado actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}

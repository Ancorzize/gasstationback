<?php

namespace App\Modules\Empleados\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Empleados\Application\Services\EmployeePaymentAccountService;
use App\Modules\Empleados\Infrastructure\Mappers\EmployeePaymentAccountMapper;
use App\Modules\Empleados\Presentation\Requests\StoreEmployeePaymentAccountRequest;
use App\Modules\Empleados\Presentation\Requests\UpdateEmployeePaymentAccountRequest;
use App\Modules\Empleados\Presentation\Requests\ChangeEmployeePaymentAccountStatusRequest;
use App\Modules\Empleados\Presentation\Resources\EmployeePaymentAccountResource;

class EmployeePaymentAccountController extends Controller
{
    public function __construct(
        protected EmployeePaymentAccountService $accountService
    ) {}

    public function index(Request $request, int $employeeId)
    {
        try {
            $accounts = $this->accountService->getByEmployeeId($employeeId);

            return ApiResponse::success(
                EmployeePaymentAccountResource::collection($accounts),
                'Cuentas de pago del empleado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreEmployeePaymentAccountRequest $request, int $employeeId)
    {
        try {
            $dto = EmployeePaymentAccountMapper::fromArrayToCreateDTO($request->validated(), $employeeId);
            $account = $this->accountService->create($dto);

            return ApiResponse::success(
                new EmployeePaymentAccountResource($account),
                'Cuenta de pago creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateEmployeePaymentAccountRequest $request, int $id)
    {
        try {
            $dto = EmployeePaymentAccountMapper::fromArrayToUpdateDTO($request->validated());
            $account = $this->accountService->update($id, $dto);

            return ApiResponse::success(
                new EmployeePaymentAccountResource($account),
                'Cuenta de pago actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(ChangeEmployeePaymentAccountStatusRequest $request, int $id)
    {
        try {
            $account = $this->accountService->changeStatus($id, (bool) $request->validated()['is_active']);

            return ApiResponse::success(
                new EmployeePaymentAccountResource($account),
                'Estado de la cuenta de pago actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}

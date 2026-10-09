<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\PayrollPeriodService;
use App\Modules\Nomina\Infrastructure\Mappers\PayrollPeriodMapper;
use App\Modules\Nomina\Presentation\Requests\StorePayrollPeriodRequest;
use App\Modules\Nomina\Presentation\Requests\UpdatePayrollPeriodRequest;
use App\Modules\Nomina\Presentation\Requests\ChangePayrollPeriodStatusRequest;
use App\Modules\Nomina\Presentation\Resources\PayrollPeriodResource;

class PayrollPeriodController extends Controller
{
    public function __construct(
        protected PayrollPeriodService $payrollPeriodService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = [
                'payroll_cycle_id' => $request->get('payroll_cycle_id'),
                'search' => $request->get('search'),
                'status' => $request->get('status'),
            ];

            $perPage = (int) $request->get('per_page', 15);
            $periods = $this->payrollPeriodService->paginate($filters, $perPage);

            return ApiResponse::success([
                'items' => PayrollPeriodResource::collection($periods->items()),
                'pagination' => [
                    'current_page' => $periods->currentPage(),
                    'last_page' => $periods->lastPage(),
                    'per_page' => $periods->perPage(),
                    'total' => $periods->total(),
                ]
            ], 'Listado de períodos de nómina.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function getByCycle(Request $request, int $cycleId)
    {
        try {
            $periods = $this->payrollPeriodService->getByCycleId($cycleId);

            return ApiResponse::success(
                PayrollPeriodResource::collection($periods),
                'Períodos de nómina del ciclo.'
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
            $period = $this->payrollPeriodService->findById($id);

            return ApiResponse::success(
                new PayrollPeriodResource($period),
                'Período de nómina encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePayrollPeriodRequest $request, int $cycleId)
    {
        try {
            $dto = PayrollPeriodMapper::fromArrayToCreateDTO($request->validated(), $cycleId);
            $period = $this->payrollPeriodService->create($dto);

            return ApiResponse::success(
                new PayrollPeriodResource($period),
                'Período de nómina creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePayrollPeriodRequest $request, int $id)
    {
        try {
            $dto = PayrollPeriodMapper::fromArrayToUpdateDTO($request->validated());
            $period = $this->payrollPeriodService->update($id, $dto);

            return ApiResponse::success(
                new PayrollPeriodResource($period),
                'Período de nómina actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(ChangePayrollPeriodStatusRequest $request, int $id)
    {
        try {
            $period = $this->payrollPeriodService->changeStatus($id, $request->validated()['status']);

            return ApiResponse::success(
                new PayrollPeriodResource($period),
                'Estado del período de nómina actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}

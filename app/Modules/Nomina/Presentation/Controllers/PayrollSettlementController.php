<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\PayrollSettlementService;
use App\Modules\Nomina\Infrastructure\Mappers\PayrollSettlementMapper;
use App\Modules\Nomina\Presentation\Requests\StorePayrollSettlementRequest;
use App\Modules\Nomina\Presentation\Requests\UpdatePayrollSettlementRequest;
use App\Modules\Nomina\Presentation\Requests\ChangePayrollSettlementStatusRequest;
use App\Modules\Nomina\Presentation\Resources\PayrollSettlementResource;

class PayrollSettlementController extends Controller
{
    public function __construct(
        protected PayrollSettlementService $payrollSettlementService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = [
                'payroll_period_id' => $request->get('payroll_period_id'),
                'employee_id' => $request->get('employee_id'),
                'status' => $request->get('status'),
                'search' => $request->get('search'),
            ];

            $perPage = (int) $request->get('per_page', 15);
            $settlements = $this->payrollSettlementService->paginate($filters, $perPage);

            return ApiResponse::success([
                'items' => PayrollSettlementResource::collection($settlements->items()),
                'pagination' => [
                    'current_page' => $settlements->currentPage(),
                    'last_page' => $settlements->lastPage(),
                    'per_page' => $settlements->perPage(),
                    'total' => $settlements->total(),
                ]
            ], 'Listado de liquidaciones de nómina.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function getByPeriod(Request $request, int $periodId)
    {
        try {
            $settlements = $this->payrollSettlementService->getByPeriodId($periodId);

            return ApiResponse::success(
                PayrollSettlementResource::collection($settlements),
                'Liquidaciones de nómina del período.'
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
            $settlement = $this->payrollSettlementService->findById($id);

            return ApiResponse::success(
                new PayrollSettlementResource($settlement),
                'Liquidación de nómina encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePayrollSettlementRequest $request, int $periodId)
    {
        try {
            $dto = PayrollSettlementMapper::fromArrayToCreateDTO($request->validated(), $periodId);
            $settlement = $this->payrollSettlementService->create($dto);

            return ApiResponse::success(
                new PayrollSettlementResource($settlement),
                'Liquidación de nómina creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePayrollSettlementRequest $request, int $id)
    {
        try {
            $dto = PayrollSettlementMapper::fromArrayToUpdateDTO($request->validated());
            $settlement = $this->payrollSettlementService->update($id, $dto);

            return ApiResponse::success(
                new PayrollSettlementResource($settlement),
                'Liquidación de nómina actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(ChangePayrollSettlementStatusRequest $request, int $id)
    {
        try {
            $settlement = $this->payrollSettlementService->changeStatus($id, $request->validated()['status']);

            return ApiResponse::success(
                new PayrollSettlementResource($settlement),
                'Estado de la liquidación de nómina actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}

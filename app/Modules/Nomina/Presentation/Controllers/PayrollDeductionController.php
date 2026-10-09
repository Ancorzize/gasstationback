<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\PayrollDeductionService;
use App\Modules\Nomina\Infrastructure\Mappers\PayrollDeductionMapper;
use App\Modules\Nomina\Presentation\Requests\StorePayrollDeductionRequest;
use App\Modules\Nomina\Presentation\Requests\UpdatePayrollDeductionRequest;
use App\Modules\Nomina\Presentation\Resources\PayrollDeductionResource;

class PayrollDeductionController extends Controller
{
    public function __construct(
        protected PayrollDeductionService $deductionService
    ) {}

    public function index(Request $request, int $settlementId)
    {
        try {
            $deductions = $this->deductionService->getBySettlementId($settlementId);

            return ApiResponse::success(
                PayrollDeductionResource::collection($deductions),
                'Listado de deducciones de la liquidación.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $settlementId, int $id)
    {
        try {
            $deduction = $this->deductionService->findById($id);

            return ApiResponse::success(
                new PayrollDeductionResource($deduction),
                'Deducción encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePayrollDeductionRequest $request, int $settlementId)
    {
        try {
            $dto = PayrollDeductionMapper::fromArrayToCreateDTO($request->validated(), $settlementId);
            $deduction = $this->deductionService->create($dto);

            return ApiResponse::success(
                new PayrollDeductionResource($deduction),
                'Deducción creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePayrollDeductionRequest $request, int $settlementId, int $id)
    {
        try {
            $dto = PayrollDeductionMapper::fromArrayToUpdateDTO($request->validated());
            $deduction = $this->deductionService->update($id, $dto);

            return ApiResponse::success(
                new PayrollDeductionResource($deduction),
                'Deducción actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, int $settlementId, int $id)
    {
        try {
            $this->deductionService->delete($id);

            return ApiResponse::success(
                null,
                'Deducción eliminada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }
}

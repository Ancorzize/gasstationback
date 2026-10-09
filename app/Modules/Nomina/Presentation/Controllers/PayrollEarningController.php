<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\PayrollEarningService;
use App\Modules\Nomina\Infrastructure\Mappers\PayrollEarningMapper;
use App\Modules\Nomina\Presentation\Requests\StorePayrollEarningRequest;
use App\Modules\Nomina\Presentation\Requests\UpdatePayrollEarningRequest;
use App\Modules\Nomina\Presentation\Resources\PayrollEarningResource;

class PayrollEarningController extends Controller
{
    public function __construct(
        protected PayrollEarningService $earningService
    ) {}

    public function index(Request $request, int $settlementId)
    {
        try {
            $earnings = $this->earningService->getBySettlementId($settlementId);

            return ApiResponse::success(
                PayrollEarningResource::collection($earnings),
                'Listado de devengados de la liquidación.'
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
            $earning = $this->earningService->findById($id);

            return ApiResponse::success(
                new PayrollEarningResource($earning),
                'Devengado encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePayrollEarningRequest $request, int $settlementId)
    {
        try {
            $dto = PayrollEarningMapper::fromArrayToCreateDTO($request->validated(), $settlementId);
            $earning = $this->earningService->create($dto);

            return ApiResponse::success(
                new PayrollEarningResource($earning),
                'Devengado creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePayrollEarningRequest $request, int $settlementId, int $id)
    {
        try {
            $dto = PayrollEarningMapper::fromArrayToUpdateDTO($request->validated());
            $earning = $this->earningService->update($id, $dto);

            return ApiResponse::success(
                new PayrollEarningResource($earning),
                'Devengado actualizado correctamente.'
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
            $this->earningService->delete($id);

            return ApiResponse::success(
                null,
                'Devengado eliminado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\PayrollCycleService;
use App\Modules\Nomina\Infrastructure\Mappers\PayrollCycleMapper;
use App\Modules\Nomina\Presentation\Requests\StorePayrollCycleRequest;
use App\Modules\Nomina\Presentation\Requests\UpdatePayrollCycleRequest;
use App\Modules\Nomina\Presentation\Requests\ChangePayrollCycleStatusRequest;
use App\Modules\Nomina\Presentation\Resources\PayrollCycleResource;

class PayrollCycleController extends Controller
{
    public function __construct(
        protected PayrollCycleService $payrollCycleService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'period_type' => $request->get('period_type'),
                'is_active' => $request->get('is_active'),
            ];

            $perPage = (int) $request->get('per_page', 15);
            $cycles = $this->payrollCycleService->paginate($filters, $perPage);

            return ApiResponse::success([
                'items' => PayrollCycleResource::collection($cycles->items()),
                'pagination' => [
                    'current_page' => $cycles->currentPage(),
                    'last_page' => $cycles->lastPage(),
                    'per_page' => $cycles->perPage(),
                    'total' => $cycles->total(),
                ]
            ], 'Listado de ciclos de nómina.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $cycle = $this->payrollCycleService->findById($id);

            return ApiResponse::success(
                new PayrollCycleResource($cycle),
                'Ciclo de nómina encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePayrollCycleRequest $request)
    {
        try {
            $dto = PayrollCycleMapper::fromArrayToCreateDTO($request->validated());
            $cycle = $this->payrollCycleService->create($dto);

            return ApiResponse::success(
                new PayrollCycleResource($cycle),
                'Ciclo de nómina creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePayrollCycleRequest $request, int $id)
    {
        try {
            $dto = PayrollCycleMapper::fromArrayToUpdateDTO($request->validated());
            $cycle = $this->payrollCycleService->update($id, $dto);

            return ApiResponse::success(
                new PayrollCycleResource($cycle),
                'Ciclo de nómina actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(ChangePayrollCycleStatusRequest $request, int $id)
    {
        try {
            $cycle = $this->payrollCycleService->changeStatus($id, (bool) $request->validated()['is_active']);

            return ApiResponse::success(
                new PayrollCycleResource($cycle),
                'Estado del ciclo de nómina actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}

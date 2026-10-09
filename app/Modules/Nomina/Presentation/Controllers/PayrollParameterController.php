<?php

namespace App\Modules\Nomina\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Nomina\Application\Services\PayrollParameterService;
use App\Modules\Nomina\Infrastructure\Mappers\PayrollParameterMapper;
use App\Modules\Nomina\Presentation\Requests\StorePayrollParameterRequest;
use App\Modules\Nomina\Presentation\Requests\UpdatePayrollParameterRequest;
use App\Modules\Nomina\Presentation\Resources\PayrollParameterResource;

class PayrollParameterController extends Controller
{
    public function __construct(
        protected PayrollParameterService $parameterService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = [
                'year' => $request->get('year'),
            ];

            $perPage = (int) $request->get('per_page', 15);
            $parameters = $this->parameterService->paginate($filters, $perPage);

            return ApiResponse::success([
                'items' => PayrollParameterResource::collection($parameters->items()),
                'pagination' => [
                    'current_page' => $parameters->currentPage(),
                    'last_page' => $parameters->lastPage(),
                    'per_page' => $parameters->perPage(),
                    'total' => $parameters->total(),
                ]
            ], 'Listado de parámetros legales de nómina.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $parameter = $this->parameterService->findById($id);

            return ApiResponse::success(
                new PayrollParameterResource($parameter),
                'Parámetro legal de nómina encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePayrollParameterRequest $request)
    {
        try {
            $dto = PayrollParameterMapper::fromArrayToCreateDTO($request->validated());
            $parameter = $this->parameterService->create($dto);

            return ApiResponse::success(
                new PayrollParameterResource($parameter),
                'Parámetros legales de nómina creados correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdatePayrollParameterRequest $request, int $id)
    {
        try {
            $dto = PayrollParameterMapper::fromArrayToUpdateDTO($request->validated());
            $parameter = $this->parameterService->update($id, $dto);

            return ApiResponse::success(
                new PayrollParameterResource($parameter),
                'Parámetros legales de nómina actualizados correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }
}

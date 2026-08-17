<?php

namespace App\Modules\Clientes\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Clientes\Application\Services\ClienteService;
use App\Modules\Clientes\Infrastructure\Mappers\ClienteMapper;
use App\Modules\Clientes\Presentation\Requests\StoreClienteRequest;
use App\Modules\Clientes\Presentation\Requests\UpdateClienteRequest;
use App\Modules\Clientes\Presentation\Requests\ChangeClienteStatusRequest;
use App\Modules\Clientes\Presentation\Resources\ClienteResource;
use App\Modules\Cartera\Application\Services\CarteraService;
use App\Modules\Cartera\Presentation\Resources\MovimientoCarteraResource;

class ClienteController extends Controller
{
    public function __construct(
        protected ClienteService $clienteService,
        protected CarteraService $carteraService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $clientes = $this->clienteService->paginate(
                $filters,
                (int) $request->get('per_page', 1000)
            );

            return ApiResponse::success([
                'items' => ClienteResource::collection($clientes->items()),
                'pagination' => [
                    'current_page' => $clientes->currentPage(),
                    'last_page' => $clientes->lastPage(),
                    'per_page' => $clientes->perPage(),
                    'total' => $clientes->total(),
                ]
            ], 'Listado de clientes.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $cliente = $this->clienteService->findById($id);

            return ApiResponse::success(
                new ClienteResource($cliente),
                'Cliente encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreClienteRequest $request)
    {
        try {
            if (!$request->user()->can('crear_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ClienteMapper::fromArrayToCreateDTO($request->validated());
            $cliente = $this->clienteService->create($dto);

            return ApiResponse::success(
                new ClienteResource($cliente),
                'Cliente creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateClienteRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ClienteMapper::fromArrayToUpdateDTO($request->validated());
            $cliente = $this->clienteService->update($id, $dto);

            return ApiResponse::success(
                new ClienteResource($cliente),
                'Cliente actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeClienteStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $cliente = $this->clienteService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new ClienteResource($cliente),
                'Estado del cliente actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function estadoCuenta(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_estado_cuenta_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $data = $this->carteraService->estadoCuenta($id);

            return ApiResponse::success([
                'cliente' => new ClienteResource($data['cliente']),
                'cupo_credito' => $data['cupo_credito'],
                'saldo_credito' => $data['saldo_credito'],
                'cupo_disponible' => $data['cupo_disponible'],
                'movimientos' => MovimientoCarteraResource::collection($data['movimientos']),
            ], 'Estado de cuenta del cliente.');

        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function configurarCredito(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('configurar_credito_clientes')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $validated = $request->validate([
                'maneja_credito' => ['required', 'boolean'],
                'cupo_credito' => ['required_if:maneja_credito,true', 'numeric', 'min:0'],
                'dias_credito' => ['nullable', 'integer', 'min:0'],
            ]);

            $cliente = $this->carteraService->configurarCredito(
                $id,
                (bool) $validated['maneja_credito'],
                (float) ($validated['cupo_credito'] ?? 0),
                $validated['dias_credito'] ?? null
            );

            return ApiResponse::success(
                new ClienteResource($cliente),
                'Configuración de crédito actualizada correctamente.'
            );

        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
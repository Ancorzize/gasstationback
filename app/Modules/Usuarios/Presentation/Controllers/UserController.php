<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Usuarios\Application\Services\UserService;
use App\Modules\Usuarios\Infrastructure\Mappers\UserMapper;
use App\Modules\Usuarios\Presentation\Requests\StoreUserRequest;
use App\Modules\Usuarios\Presentation\Requests\UpdateUserRequest;
use App\Modules\Usuarios\Presentation\Requests\ChangeUserStatusRequest;
use App\Modules\Usuarios\Presentation\Resources\UserResource;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $users = $this->userService->paginate((int) $request->get('per_page', 1000));

            return ApiResponse::success([
                'items' => UserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ], 'Listado de usuarios.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $user = $this->userService->findById($id);

            return ApiResponse::success(
                new UserResource($user),
                'Usuario encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            if (!$request->user()->can('crear_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = UserMapper::fromArrayToCreateDTO($request->validated());
            $user = $this->userService->create($dto);

            return ApiResponse::success(
                new UserResource($user),
                'Usuario creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = UserMapper::fromArrayToUpdateDTO($request->validated());
            $user = $this->userService->update($id, $dto);

            return ApiResponse::success(
                new UserResource($user),
                'Usuario actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeUserStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $user = $this->userService->changeStatus($id, (bool) $request->validated()['is_active']);

            return ApiResponse::success(
                new UserResource($user),
                'Estado del usuario actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
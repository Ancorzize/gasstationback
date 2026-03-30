<?php

namespace App\Modules\Roles\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Roles\Application\Services\RoleService;
use App\Modules\Roles\Infrastructure\Mappers\RoleMapper;
use App\Modules\Roles\Presentation\Requests\UpdateRolePermissionsRequest;
use App\Modules\Roles\Presentation\Resources\RoleResource;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $roles = $this->roleService->getAll();

            return ApiResponse::success(
                RoleResource::collection($roles),
                'Listado de roles.'
            );
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

            $role = $this->roleService->findById($id);

            return ApiResponse::success(
                new RoleResource($role),
                'Rol encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = RoleMapper::fromArrayToUpdatePermissionsDTO($request->validated());
            $role = $this->roleService->updatePermissions($id, $dto);

            return ApiResponse::success(
                new RoleResource($role),
                'Permisos del rol actualizados correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
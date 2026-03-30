<?php

namespace App\Modules\Roles\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use App\Modules\Roles\Application\Services\RoleService;
use App\Modules\Roles\Presentation\Resources\PermissionResource;

class PermissionController extends Controller
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

            $permissions = $this->roleService->getAllPermissions();

            return ApiResponse::success(
                PermissionResource::collection($permissions),
                'Listado de permisos.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function grouped(Request $request)
    {
        try {
            if (!$request->user()->can('ver_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                $this->roleService->getPermissionsGrouped(),
                'Listado de permisos agrupados.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function authUserPermissions(Request $request)
    {
        try {
            return ApiResponse::success(
                $this->roleService->getAuthUserPermissions($request->user()),
                'Permisos del usuario autenticado.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
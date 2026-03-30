<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_usuarios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $roles = Role::query()
                ->where('guard_name', 'sanctum')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return ApiResponse::success(
                $roles,
                'Listado de roles.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
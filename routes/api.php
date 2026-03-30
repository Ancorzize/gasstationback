<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Presentation\Controllers\AuthController;
use App\Modules\Usuarios\Presentation\Controllers\UserController;
use App\Modules\Clientes\Presentation\Controllers\ClienteController;
use App\Modules\Proveedores\Presentation\Controllers\ProveedorController;
use App\Modules\Roles\Presentation\Controllers\PermissionController;
use App\Modules\Roles\Presentation\Controllers\RoleController;
use App\Modules\Marcas\Presentation\Controllers\MarcaController;
use App\Modules\CategoriasProducto\Presentation\Controllers\CategoriaProductoController;
use App\Modules\UnidadesMedida\Presentation\Controllers\UnidadMedidaController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin', function () {
        return 'solo admin';
    });
});

Route::middleware(['auth:sanctum', 'permission:crear_clientes'])->group(function () {
    //Route::post('/clientes', [ClienteController::class, 'store']);
});


Route::middleware(['auth:sanctum'])->prefix('usuarios')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::patch('/{id}/status', [UserController::class, 'changeStatus']);
    Route::get('/roles', [RoleController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->prefix('clientes')->group(function () {
    Route::get('/', [ClienteController::class, 'index']);
    Route::get('/{id}', [ClienteController::class, 'show']);
    Route::post('/', [ClienteController::class, 'store']);
    Route::put('/{id}', [ClienteController::class, 'update']);
    Route::patch('/{id}/status', [ClienteController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}/permissions', [RoleController::class, 'updatePermissions']);
    });

    Route::prefix('permisos')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/grouped', [PermissionController::class, 'grouped']);
    });

    Route::get('/auth/me/permissions', [PermissionController::class, 'authUserPermissions']);
});

Route::middleware(['auth:sanctum'])->prefix('proveedores')->group(function () {
    Route::get('/', [ProveedorController::class, 'index']);
    Route::post('/', [ProveedorController::class, 'store']);
    Route::get('/{id}', [ProveedorController::class, 'show']);
    Route::put('/{id}', [ProveedorController::class, 'update']);
    Route::patch('/{id}/status', [ProveedorController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('marcas')->group(function () {
    Route::get('/', [MarcaController::class, 'index']);
    Route::get('/{id}', [MarcaController::class, 'show']);
    Route::post('/', [MarcaController::class, 'store']);
    Route::put('/{id}', [MarcaController::class, 'update']);
    Route::patch('/{id}/status', [MarcaController::class, 'changeStatus']);
    Route::delete('/{id}', [MarcaController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('categorias-producto')->group(function () {
    Route::get('/', [CategoriaProductoController::class, 'index']);
    Route::get('/{id}', [CategoriaProductoController::class, 'show']);
    Route::post('/', [CategoriaProductoController::class, 'store']);
    Route::put('/{id}', [CategoriaProductoController::class, 'update']);
    Route::patch('/{id}/status', [CategoriaProductoController::class, 'changeStatus']);
    Route::delete('/{id}', [CategoriaProductoController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('unidades-medida')->group(function () {
    Route::get('/', [UnidadMedidaController::class, 'index']);
    Route::get('/{id}', [UnidadMedidaController::class, 'show']);
    Route::post('/', [UnidadMedidaController::class, 'store']);
    Route::put('/{id}', [UnidadMedidaController::class, 'update']);
    Route::patch('/{id}/estado', [UnidadMedidaController::class, 'changeStatus']);
    Route::delete('/{id}', [UnidadMedidaController::class, 'destroy']);
});
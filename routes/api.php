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
use App\Modules\Productos\Presentation\Controllers\ProductoController;
use App\Modules\Servicios\Presentation\Controllers\ServicioController;
use App\Modules\Ubicaciones\Presentation\Controllers\UbicacionController;
use App\Modules\ConfiguracionEmpresa\Presentation\Controllers\ConfiguracionEmpresaController;
use App\Modules\DestinoRecaudo\Presentation\Controllers\DestinoRecaudoController;
use App\Modules\Perfil\Presentation\Controllers\PerfilController;
use App\Modules\Bodegas\Presentation\Controllers\BodegaController;
use App\Modules\Inventarios\Presentation\Controllers\ImportInventarioController;
use App\Modules\Inventarios\Presentation\Controllers\InventarioController;
use App\Modules\MovimientosInventario\Presentation\Controllers\MovimientoInventarioController;
use App\Modules\Compras\Presentation\Controllers\CompraController;
use App\Modules\Caja\Presentation\Controllers\CajaController;
use App\Modules\CategoriasGasto\Presentation\Controllers\CategoriaGastoController;
use App\Modules\Gastos\Presentation\Controllers\GastoController;
use App\Modules\PagosCompra\Presentation\Controllers\PagoCompraController;
use App\Modules\Cartera\Presentation\Controllers\CarteraController;
use App\Modules\Ventas\Presentation\Controllers\VentaController;
use App\Modules\Estaciones\Presentation\Controllers\EstacionController;
use App\Modules\Bombas\Presentation\Controllers\BombaController;
use App\Modules\Mangueras\Presentation\Controllers\MangueraController;
use App\Modules\TurnosIslero\Presentation\Controllers\TurnoIsleroController;
use App\Modules\PreciosCombustible\Presentation\Controllers\PrecioCombustibleController;
use App\Modules\Dashboard\Presentation\Controllers\DashboardController;
use App\Modules\Dashboard\Presentation\Controllers\DashboardConfigController;
use App\Modules\IndicadoresFinancieros\Presentation\Controllers\IndicadorFinancieroController;
//Route::get('/historico', [CajaController::class, 'historico']);

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
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
    Route::post('/', [ClienteController::class, 'store']);
    Route::get('/{id}/estado-cuenta', [ClienteController::class, 'estadoCuenta']);
    Route::patch('/{id}/credito', [ClienteController::class, 'configurarCredito']);
    Route::get('/{id}', [ClienteController::class, 'show']);
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
    Route::patch('/{id}/status', [UnidadMedidaController::class, 'changeStatus']);
    Route::delete('/{id}', [UnidadMedidaController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('productos')->group(function () {
    Route::get('/', [ProductoController::class, 'index']);
    Route::get('/{id}', [ProductoController::class, 'show']);
    Route::post('/', [ProductoController::class, 'store']);
    Route::put('/{id}', [ProductoController::class, 'update']);
     Route::post('/{id}/codigo-barras', [ProductoController::class, 'asociarCodigoBarras']);
    Route::patch('/{id}/status', [ProductoController::class, 'changeStatus']);
    Route::delete('/{id}', [ProductoController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('servicios')->group(function () {
    Route::get('/', [ServicioController::class, 'index']);
    Route::get('/{id}', [ServicioController::class, 'show']);
    Route::post('/', [ServicioController::class, 'store']);
    Route::put('/{id}', [ServicioController::class, 'update']);
    Route::patch('/{id}/status', [ServicioController::class, 'changeStatus']);
    Route::delete('/{id}', [ServicioController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('ubicaciones')->group(function () {
    Route::get('/paises', [UbicacionController::class, 'paises']);
    Route::get('/paises/{paisId}/departamentos', [UbicacionController::class, 'departamentosPorPais']);
    Route::get('/departamentos/{departamentoId}/ciudades', [UbicacionController::class, 'ciudadesPorDepartamento']);
});

Route::middleware(['auth:sanctum'])->prefix('configuracion-empresa')->group(function () { 
    Route::put('/', [ConfiguracionEmpresaController::class, 'update']);
});

Route::get('/configuracion-empresa', [ConfiguracionEmpresaController::class, 'show']);

Route::middleware(['auth:sanctum'])->prefix('perfil')->group(function () {
    Route::put('/', [PerfilController::class, 'update']);
});

Route::middleware(['auth:sanctum'])->prefix('bodegas')->group(function () {
    Route::get('/', [BodegaController::class, 'index']);
    Route::get('/{id}', [BodegaController::class, 'show']);
    Route::post('/', [BodegaController::class, 'store']);
    Route::put('/{id}', [BodegaController::class, 'update']);
    Route::patch('/{id}/status', [BodegaController::class, 'changeStatus']);
    Route::delete('/{id}', [BodegaController::class, 'destroy']);
});


Route::middleware(['auth:sanctum'])->prefix('inventarios')->group(function () {
    Route::get('/', [InventarioController::class, 'index']);
    Route::get('/resumen', [InventarioController::class, 'resumen']);
    Route::get('/mi-bodega', [InventarioController::class, 'miBodega']);
    Route::get('/bodega/{bodegaId}', [InventarioController::class, 'porBodega']);
    Route::post('/importar', [ImportInventarioController::class, 'importar']);
});

Route::middleware(['auth:sanctum'])->prefix('movimientos-inventario')->group(function () {
    Route::get('/', [MovimientoInventarioController::class, 'index']);
    Route::get('/lotes', [MovimientoInventarioController::class,'lotes']);
    Route::get('/lotes/{codigoLote}', [MovimientoInventarioController::class,'productosLote']);
    Route::post('/', [MovimientoInventarioController::class, 'store']);
    Route::post('/masivo', [MovimientoInventarioController::class, 'storeMasivo']);
});

Route::middleware(['auth:sanctum'])->prefix('compras')->group(function () {
    Route::get('/', [CompraController::class, 'index']);
    Route::get('/{id}', [CompraController::class, 'show']);
    Route::post('/', [CompraController::class, 'store']);
    Route::put('/{id}', [CompraController::class, 'update']);
    Route::post('/{id}/confirmar', [CompraController::class, 'confirmar']);

    Route::get('/{id}/pagos', [CompraController::class, 'pagos']);
    Route::post('/{id}/pagos', [CompraController::class, 'registrarPago']);

     Route::get('/{id}/pdf', [CompraController::class, 'pdf']); 
});

Route::middleware(['auth:sanctum'])->prefix('caja')->group(function () {
    Route::get('/actual', [CajaController::class, 'actual']);
    Route::post('/abrir', [CajaController::class, 'abrir']);
    Route::post('/cerrar', [CajaController::class, 'cerrar']);
    Route::get('/movimientos', [CajaController::class, 'movimientos']);
    Route::get('/resumen', [CajaController::class, 'resumen']);
    Route::get('/historico', [CajaController::class, 'historico']);
    Route::get('/sugerencias-apertura',[CajaController::class, 'sugerenciasApertura']);
    Route::post('/ingresos', [CajaController::class,'ingresoManual']);
    Route::post('/retiros', [CajaController::class,'retiroManual']);
    Route::post('/transferencias', [CajaController::class,'transferencia']);
});

Route::middleware(['auth:sanctum'])->prefix('categorias-gasto')->group(function () {
    Route::get('/', [CategoriaGastoController::class, 'index']);
    Route::get('/{id}', [CategoriaGastoController::class, 'show']);
    Route::post('/', [CategoriaGastoController::class, 'store']);
    Route::put('/{id}', [CategoriaGastoController::class, 'update']);
    Route::patch('/{id}/status', [CategoriaGastoController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('gastos')->group(function () {
    Route::get('/', [GastoController::class, 'index']);
    Route::get('/{id}', [GastoController::class, 'show']);
    Route::post('/', [GastoController::class, 'store']);
    Route::post('/{id}/anular', [GastoController::class, 'anular']);
});

Route::middleware(['auth:sanctum'])->prefix('pagos-compra')->group(function () {
    Route::get('/', [PagoCompraController::class, 'index']);
    Route::get('/{id}', [PagoCompraController::class, 'show']);
    Route::get('/{id}/pdf', [PagoCompraController::class, 'pdf']);
});

Route::middleware(['auth:sanctum'])->prefix('cartera')->group(function () {
    Route::get('/resumen', [CarteraController::class, 'resumen']);
    Route::get('/movimientos', [CarteraController::class, 'movimientos']);
    Route::post('/abonos', [CarteraController::class, 'registrarAbono']);
    Route::post('/saldos-iniciales',[CarteraController::class, 'registrarSaldoInicial']);
});

Route::middleware(['auth:sanctum'])->prefix('ventas')->group(function () {
    Route::get('/', [VentaController::class, 'index']);
    Route::post('/', [VentaController::class, 'store']);

    Route::post('/combustible', [VentaController::class, 'storeCombustible']);

    Route::get('/{id}', [VentaController::class, 'show']);
    Route::post('/{id}/anular', [VentaController::class, 'anular']);
    Route::get('/{id}/pdf', [VentaController::class, 'pdf']);
});

Route::middleware(['auth:sanctum'])->prefix('estaciones')->group(function () {
    Route::get('/', [EstacionController::class, 'index']);
    Route::get('/{id}', [EstacionController::class, 'show']);
    Route::post('/', [EstacionController::class, 'store']);
    Route::put('/{id}', [EstacionController::class, 'update']);
    Route::patch('/{id}/status', [EstacionController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('bombas')->group(function () {
    Route::get('/', [BombaController::class, 'index']);
    Route::get('/{id}', [BombaController::class, 'show']);
    Route::post('/', [BombaController::class, 'store']);
    Route::put('/{id}', [BombaController::class, 'update']);
    Route::patch('/{id}/status', [BombaController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('mangueras')->group(function () {
    Route::get('/', [MangueraController::class, 'index']);
    Route::get('/lecturas',[MangueraController::class, 'lecturas']);
    Route::get('/{id}', [MangueraController::class, 'show']);
    Route::post('/', [MangueraController::class, 'store']);
    Route::put('/{id}', [MangueraController::class, 'update']);
    Route::patch('/{id}/status', [MangueraController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('turnos-islero')->group(function () {
    Route::get('/', [TurnoIsleroController::class, 'index']);
    Route::get('/actual', [TurnoIsleroController::class, 'actual']);
    Route::get('/mangueras-disponibles', [TurnoIsleroController::class, 'manguerasDisponibles']);
    Route::get('/pendientes-cierre', [ TurnoIsleroController::class, 'pendientesCierre']);
    Route::get('/devueltos', [TurnoIsleroController::class, 'devueltos']);
    Route::get('/{id}/resumen-cierre', [TurnoIsleroController::class, 'resumenCierre']);
    Route::get('/{id}/revision-cierre', [ TurnoIsleroController::class, 'revisionCierre' ]);
    Route::get('/{id}/editar-cierre', [TurnoIsleroController::class, 'editarCierre']);
    Route::get('/{id}', [TurnoIsleroController::class, 'show']);
    Route::post('/abrir', [TurnoIsleroController::class, 'abrir']);
    Route::post('/{id}/solicitar-cierre', [TurnoIsleroController::class, 'solicitarCierre']);
    Route::post('/{id}/aprobar-cierre', [TurnoIsleroController::class, 'aprobarCierre']);
    Route::post('/{id}/devolver-cierre', [ TurnoIsleroController::class, 'devolverCierre']);
    Route::post('/{id}/cerrar', [TurnoIsleroController::class, 'cerrar']);
});

Route::middleware(['auth:sanctum'])->prefix('precios-combustible')->group(function () {
    Route::get('/', [PrecioCombustibleController::class, 'index']);
    Route::get('/{id}', [PrecioCombustibleController::class, 'show']);
    Route::post('/', [PrecioCombustibleController::class, 'store']);
    Route::patch('/{id}/status', [PrecioCombustibleController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum'])->prefix('destinos-recaudo')->group(function () {
    Route::get('/', [DestinoRecaudoController::class,'index']);
    Route::get('{id}', [DestinoRecaudoController::class,'show']);
    Route::post('/', [DestinoRecaudoController::class,'store']);
    Route::put('{id}', [DestinoRecaudoController::class,'update']);
    Route::patch('{id}/status', [DestinoRecaudoController::class,'changeStatus']);
    Route::delete('{id}', [DestinoRecaudoController::class,'destroy']);
});


Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
    Route::get('/dashboard',[DashboardController::class, 'index']);
    Route::get('/roles',[DashboardConfigController::class, 'roles']);
    Route::get('/configuracion/{roleId}', [DashboardConfigController::class, 'configuracion']);
    Route::put('/configuracion/{roleId}', [DashboardConfigController::class, 'guardar']);

});

Route::middleware(['auth:sanctum'])->prefix('indicadores-financieros')->group(function () {
    Route::get('/', [IndicadorFinancieroController::class, 'index']);
});
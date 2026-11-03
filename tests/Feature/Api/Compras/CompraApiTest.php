<?php

namespace Tests\Feature\Api\Compras;

use App\Models\Bodega;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\BaseApiTestCase;

/**
 * Suite de Pruebas API del Módulo de Compras (Fase 3).
 *
 * Valida los endpoints de listado, consulta por ID, creación en borrador,
 * confirmación (a crédito y contado), efectos físicos y contables,
 * y restricciones de autorización RBAC sobre PostgreSQL (gasstation_testing).
 */
class CompraApiTest extends BaseApiTestCase
{
    /**
     * Correo del usuario administrador de pruebas.
     */
    protected const ADMIN_EMAIL = 'test_admin@gasstation.local';

    /**
     * Correo del usuario islero de pruebas.
     */
    protected const ISLERO_EMAIL = 'test_islero@gasstation.local';

    /**
     * Correo del usuario vendedor de pruebas.
     */
    protected const VENDEDOR_EMAIL = 'test_vendedor@gasstation.local';

    /**
     * Recupera un usuario de pruebas aprovisionado previamente.
     */
    protected function getTestUser(string $email): User
    {
        $user = User::where('email', $email)->first();

        $this->assertNotNull(
            $user,
            "El usuario de prueba [{$email}] no existe en la base de datos de pruebas. " .
            "Asegúrese de haber ejecutado la etapa de aprovisionamiento de usuarios."
        );

        return $user;
    }

    /**
     * Crea un proveedor exclusivo para la prueba actual mediante la API oficial.
     */
    protected function createExclusiveProveedor(): int
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $this->actingAsSanctumUser($admin);

        $unique = bin2hex(random_bytes(3));
        $payload = [
            'nombre' => 'TEST_PROV_COMPRA_' . $unique . '_' . $this->getTestRunId(),
            'nit' => 'NIT-' . substr(time(), -5) . $unique,
            'telefono' => '310' . rand(1000000, 9999999),
            'direccion' => 'Zona Comercial Test #' . rand(1, 99),
            'email' => 'prov_compra_' . $unique . '_' . $this->getTestRunId() . '@gasstation.test',
        ];

        $response = $this->apiJson('POST', '/api/proveedores', $payload);
        $proveedorId = (int) $response->json('data.id');
        $this->assertGreaterThan(0, $proveedorId, 'El proveedor creado debe retornar un ID válido.');

        $this->trackIdForCleanup('proveedores', $proveedorId);

        return $proveedorId;
    }

    /**
     * Genera un payload válido para StoreCompraRequest.
     */
    protected function makeCompraPayload(int $proveedorId, int $bodegaId, int $productoId, array $overrides = []): array
    {
        $unique = bin2hex(random_bytes(3));
        $cantidad = (float) ($overrides['cantidad'] ?? 2.0);
        $costoUnitario = (float) ($overrides['costo_unitario'] ?? 5000.0);
        $total = $cantidad * $costoUnitario;

        $detalles = $overrides['detalles'] ?? [
            [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'iva' => 0,
                'soldicom' => 0.0,
                'total' => $total,
                'iva_valor' => 0.0,
                'sobre_tasa' => 0.0,
            ],
        ];

        return array_merge([
            'proveedor_id' => $proveedorId,
            'bodega_id' => $bodegaId,
            'numero_documento' => 'TEST-FAC-' . $unique . '-' . substr(time(), -4),
            'fecha_compra' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'tipo_pago' => 'credito',
            'impuesto' => 0.0,
            'soldicom' => 0.0,
            'sobre_tasa' => 0.0,
            'observacion' => 'Compra de prueba ' . $this->getTestRunId(),
            'documento_soporte_electronico' => false,
            'detalles' => $detalles,
        ], $overrides);
    }

    /**
     * Crea una compra mediante la API y registra inmediatamente su cabecera y detalles para limpieza.
     */
    protected function createCompraViaApi(array $payload): int
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $this->actingAsSanctumUser($admin);

        $response = $this->apiJson('POST', '/api/compras', $payload);
        $response->assertStatus(201);

        $compraId = (int) $response->json('data.id');
        $this->assertGreaterThan(0, $compraId, 'La compra creada debe retornar un ID válido.');

        // Registro explícito e inmediato en la pila selectiva LIFO
        $this->trackIdForCleanup('compras', $compraId, 'id', Compra::class);

        $detalles = $response->json('data.detalles') ?? [];
        foreach ($detalles as $det) {
            $this->trackIdForCleanup('detalle_compras', (int) $det['id'], 'id', DetalleCompra::class);
        }

        return $compraId;
    }

    // =========================================================================
    // BLOQUE 1: LECTURA Y AUTORIZACIÓN (CASOS 1 A 5)
    // =========================================================================

    /**
     * CASO 1: Administrador consulta listado de compras: HTTP 200.
     * Valida status, message y data.items sin esperar paginación.
     */
    public function test_administrador_consulta_listado_de_compras_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('GET', '/api/compras');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Listado de compras.',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'items',
            ],
        ]);
        $this->assertIsArray($response->json('data.items'));
        // Confirmar que NO contiene bloque de paginación
        $this->assertNull($response->json('data.pagination'), 'El listado de compras no debe contener metadata de paginación.');
    }

    /**
     * CASO 2: Islero y vendedor intentan consultar listado: HTTP 403.
     */
    public function test_islero_y_vendedor_intentan_consultar_listado_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);

        // 1. Islero
        $this->actingAsSanctumUser($islero);
        $responseIslero = $this->apiJson('GET', '/api/compras');
        $responseIslero->assertStatus(403);
        $responseIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // 2. Vendedor
        $this->actingAsSanctumUser($vendedor);
        $responseVendedor = $this->apiJson('GET', '/api/compras');
        $responseVendedor->assertStatus(403);
        $responseVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 3: Administrador consulta una compra existente: HTTP 200.
     * Valida información de cabecera y detalles.
     */
    public function test_administrador_consulta_compra_existente_por_id_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        // Aprovisionar entidades exclusivas
        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'cantidad' => 4.0,
            'costo_unitario' => 6000.0,
        ]);
        $compraId = $this->createCompraViaApi($payload);

        // Consulta por ID
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('GET', "/api/compras/{$compraId}");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Compra encontrada.',
            'data' => [
                'id' => $compraId,
                'proveedor_id' => $proveedorId,
                'bodega_id' => $bodega->id,
                'estado' => 'borrador',
                'estado_pago' => 'pendiente',
                'subtotal' => 24000.0,
                'total' => 24000.0,
                'saldo_pendiente' => 24000.0,
            ],
        ]);
        $this->assertNotEmpty($response->json('data.detalles'));
        $this->assertSame($producto->id, $response->json('data.detalles.0.producto_id'));
    }

    /**
     * CASO 4: Administrador consulta una compra inexistente: HTTP 404 y mensaje exacto.
     */
    public function test_administrador_consulta_compra_inexistente_retorna_404(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('GET', '/api/compras/999999999');

        $response->assertStatus(404);
        $response->assertJson([
            'status' => false,
            'message' => 'Compra no encontrada.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 5: Islero y vendedor intentan consultar una compra: HTTP 403.
     */
    public function test_islero_y_vendedor_intentan_consultar_compra_por_id_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);

        // Aprovisionar compra exclusiva
        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();
        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id);
        $compraId = $this->createCompraViaApi($payload);

        // 1. Islero
        $this->actingAsSanctumUser($islero);
        $responseIslero = $this->apiJson('GET', "/api/compras/{$compraId}");
        $responseIslero->assertStatus(403);
        $responseIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // 2. Vendedor
        $this->actingAsSanctumUser($vendedor);
        $responseVendedor = $this->apiJson('GET', "/api/compras/{$compraId}");
        $responseVendedor->assertStatus(403);
        $responseVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
    }

    // =========================================================================
    // BLOQUE 2: CREACIÓN Y VALIDACIONES (CASOS 6 A 8)
    // =========================================================================

    /**
     * CASO 6: Administrador crea una compra: HTTP 201.
     * Valida estado borrador, estado_pago pendiente y detalles persistidos.
     * Confirma que no se modifica inventario ni precio de compra del producto
     * y que no se generan movimientos de inventario ni caja.
     */
    public function test_administrador_crea_compra_en_borrador_exitoso_sin_efectos_fisicos(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto(['precio_compra' => 5000.0]);

        $precioCompraInicial = (float) $producto->precio_compra;
        $movInvCountAntes = DB::table('movimientos_inventario')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // Verificar que no existe inventario previo para esta tupla
        $inventarioPrevio = DB::table('inventarios')
            ->where('bodega_id', $bodega->id)
            ->where('producto_id', $producto->id)
            ->first();
        $this->assertNull($inventarioPrevio, 'No debe existir inventario previo para una bodega y producto nuevos.');

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'cantidad' => 5.0,
            'costo_unitario' => 8500.0,
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => true,
            'message' => 'Compra creada correctamente.',
            'data' => [
                'proveedor_id' => $proveedorId,
                'bodega_id' => $bodega->id,
                'estado' => 'borrador',
                'estado_pago' => 'pendiente',
                'subtotal' => 42500.0,
                'total' => 42500.0,
                'saldo_pendiente' => 42500.0,
            ],
        ]);

        $compraId = (int) $response->json('data.id');
        $this->assertGreaterThan(0, $compraId);
        $this->trackIdForCleanup('compras', $compraId, 'id', Compra::class);

        foreach ($response->json('data.detalles') as $det) {
            $this->trackIdForCleanup('detalle_compras', (int) $det['id'], 'id', DetalleCompra::class);
        }

        // 1. Verificación en base de datos: compra en borrador y detalles
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'estado' => 'borrador',
            'estado_pago' => 'pendiente',
        ]);
        $this->assertDatabaseHas('detalle_compras', [
            'compra_id' => $compraId,
            'producto_id' => $producto->id,
            'cantidad' => 5.0,
            'costo_unitario' => 8500.0,
        ]);

        // 2. Verificación de ausencia total de efectos físicos y contables
        $this->assertNull(
            DB::table('inventarios')->where('bodega_id', $bodega->id)->where('producto_id', $producto->id)->first(),
            'La creación en borrador NO debe crear filas en la tabla inventarios.'
        );
        $this->assertSame(
            $precioCompraInicial,
            (float) $producto->fresh()->precio_compra,
            'El precio_compra del producto NO debe modificarse al crear en borrador.'
        );
        $this->assertSame(
            $movInvCountAntes,
            DB::table('movimientos_inventario')->count(),
            'NO deben generarse movimientos en el kardex al crear en borrador.'
        );
        $this->assertSame(
            $movCajaCountAntes,
            DB::table('movimientos_caja')->count(),
            'NO deben generarse movimientos de caja al crear en borrador.'
        );
    }

    /**
     * CASO 7: Administrador intenta crear una compra sin detalles: HTTP 422.
     * Comprobar que no se persiste la compra.
     */
    public function test_administrador_intenta_crear_compra_sin_detalles_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'detalles' => [],
        ]);

        $comprasAntes = DB::table('compras')->count();

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['detalles']);

        // Comprobar que no se persistió la compra
        $comprasDespues = DB::table('compras')->count();
        $this->assertSame($comprasAntes, $comprasDespues, 'Una petición con detalles vacíos no debe persistir compras.');
    }

    /**
     * CASO 8: Islero y vendedor intentan crear una compra con payload válido: HTTP 403.
     * Comprobar que no se persisten compras ni detalles.
     */
    public function test_islero_y_vendedor_intentan_crear_compra_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        $payloadIslero = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id);
        $payloadVendedor = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id);

        // 1. Islero
        $this->actingAsSanctumUser($islero);
        $responseIslero = $this->apiJson('POST', '/api/compras', $payloadIslero);
        $responseIslero->assertStatus(403);
        $responseIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
        $this->assertDatabaseMissing('compras', [
            'numero_documento' => $payloadIslero['numero_documento'],
        ]);

        // 2. Vendedor
        $this->actingAsSanctumUser($vendedor);
        $responseVendedor = $this->apiJson('POST', '/api/compras', $payloadVendedor);
        $responseVendedor->assertStatus(403);
        $responseVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
        $this->assertDatabaseMissing('compras', [
            'numero_documento' => $payloadVendedor['numero_documento'],
        ]);
    }

    // =========================================================================
    // BLOQUE 3: CONFIRMACIÓN Y EFECTOS (CASOS 9 A 13)
    // =========================================================================

    /**
     * CASO 9: Administrador confirma una compra a crédito con payload vacío {}: HTTP 200.
     * AJUSTE 1 APLICADO: Invoca trackConfirmationEffects inmediatamente después
     * de la petición HTTP y antes de las aserciones.
     * Validar:
     * - Estado confirmada y estado_pago pendiente.
     * - Incremento correcto del inventario.
     * - Movimiento de inventario tipo entrada_compra.
     * - Actualización de productos.precio_compra.
     * - Ausencia de movimientos de caja.
     */
    public function test_administrador_confirma_compra_credito_con_payload_vacio_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto(['precio_compra' => 5000.0]);

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'credito',
            'cantidad' => 3.0,
            'costo_unitario' => 9000.0,
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // 1. Snapshot obligatorio antes de confirmar
        $this->snapshotPreConfirmation($compra);

        // 2. Petición HTTP con payload vacío {}
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", []);

        // 3. Tracking explícito e inmediato de efectos secundarios para limpieza LIFO (antes de aserciones)
        $this->trackConfirmationEffects($compra);

        // 4. Aserciones de respuesta HTTP
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Compra confirmada correctamente.',
            'data' => [
                'id' => $compraId,
                'estado' => 'confirmada',
                'estado_pago' => 'pendiente',
                'total_pagado' => 0.0,
                'saldo_pendiente' => 27000.0,
            ],
        ]);

        // 5. Validar estado y saldos en base de datos
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'estado' => 'confirmada',
            'estado_pago' => 'pendiente',
            'saldo_pendiente' => 27000.0,
        ]);

        // 6. Validar incremento de inventario
        $this->assertDatabaseHas('inventarios', [
            'bodega_id' => $bodega->id,
            'producto_id' => $producto->id,
            'cantidad' => 3.0,
        ]);

        // 7. Validar movimiento de kardex tipo entrada_compra
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo_movimiento' => 'entrada_compra',
            'producto_id' => $producto->id,
            'bodega_destino_id' => $bodega->id,
            'cantidad' => 3.0,
            'observacion' => 'Entrada por compra #' . $compraId,
        ]);

        // 8. Validar actualización de precio_compra en el producto
        $this->assertSame(
            9000.0,
            (float) $producto->fresh()->precio_compra,
            'El precio_compra del producto debe actualizarse al costo unitario de la compra.'
        );

        // 9. Validar ausencia de movimientos de caja (compra a crédito)
        $this->assertSame(
            $movCajaCountAntes,
            DB::table('movimientos_caja')->count(),
            'Una compra a crédito confirmada NO debe generar movimientos de caja.'
        );
    }

    /**
     * CASO 10: Administrador confirma una compra de contado con caja abierta y saldo suficiente: HTTP 200.
     * AJUSTE 1 APLICADO: Invoca trackConfirmationEffects inmediatamente después
     * de la petición HTTP y antes de las aserciones.
     * Validar:
     * - Estado confirmada y estado_pago pagado.
     * - Incremento de inventario.
     * - Movimiento de inventario.
     * - Egreso en movimientos de caja con origen_modulo = 'compras', origen_id y monto correcto.
     * - Saldo y efectos esperados de la caja.
     */
    public function test_administrador_confirma_compra_contado_con_caja_abierta_y_saldo_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto(['precio_compra' => 4000.0]);

        // Crear caja exclusiva y dotarla de saldo real con un ingreso inicial
        $caja = $this->builder->createTestCaja('efectivo');
        $this->builder->createMovimientoCaja($caja, [
            'tipo_movimiento' => 'ingreso',
            'categoria_movimiento' => 'apertura',
            'origen_modulo' => 'caja',
            'monto' => 500000.0,
        ]);

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'efectivo',
            'cantidad' => 2.0,
            'costo_unitario' => 15000.0, // Total = 30000.0
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        // 1. Snapshot obligatorio antes de confirmar
        $this->snapshotPreConfirmation($compra);

        // 2. Petición HTTP enviando caja_id
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", [
            'caja_id' => $caja->id,
        ]);

        // 3. Tracking explícito e inmediato de efectos secundarios para limpieza LIFO (antes de aserciones)
        $this->trackConfirmationEffects($compra);

        // 4. Aserciones de respuesta HTTP
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Compra confirmada correctamente.',
            'data' => [
                'id' => $compraId,
                'estado' => 'confirmada',
                'estado_pago' => 'pagado',
                'total_pagado' => 30000.0,
                'saldo_pendiente' => 0.0,
            ],
        ]);

        // 5. Validar estado y saldos de compra
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'estado' => 'confirmada',
            'estado_pago' => 'pagado',
            'saldo_pendiente' => 0.0,
        ]);

        // 6. Validar incremento de inventario y kardex
        $this->assertDatabaseHas('inventarios', [
            'bodega_id' => $bodega->id,
            'producto_id' => $producto->id,
            'cantidad' => 2.0,
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo_movimiento' => 'entrada_compra',
            'producto_id' => $producto->id,
            'bodega_destino_id' => $bodega->id,
            'cantidad' => 2.0,
            'observacion' => 'Entrada por compra #' . $compraId,
        ]);

        // 7. Validar egreso exacto en movimientos_caja
        $this->assertDatabaseHas('movimientos_caja', [
            'caja_id' => $caja->id,
            'tipo_movimiento' => 'egreso',
            'categoria_movimiento' => 'compra',
            'origen_modulo' => 'compras',
            'origen_id' => $compraId,
            'monto' => 30000.0,
        ]);

        // 8. Validar saldo contable neto de la caja: 500000 (ingreso) - 30000 (egreso) = 470000
        $totalIngresos = (float) DB::table('movimientos_caja')->where('caja_id', $caja->id)->where('tipo_movimiento', 'ingreso')->sum('monto');
        $totalEgresos = (float) DB::table('movimientos_caja')->where('caja_id', $caja->id)->where('tipo_movimiento', 'egreso')->sum('monto');
        $this->assertSame(500000.0, $totalIngresos);
        $this->assertSame(30000.0, $totalEgresos);
        $this->assertSame(470000.0, $totalIngresos - $totalEgresos);
    }

    /**
     * CASO 11: Administrador intenta confirmar una compra de contado con caja cerrada: HTTP 422.
     * Validar:
     * - Compra permanece en borrador.
     * - Inventario no cambia.
     * - No se crean movimientos de inventario.
     * - No se crea movimiento de caja.
     */
    public function test_administrador_intenta_confirmar_contado_con_caja_cerrada_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        // Crear una caja cerrada
        $cajaCerrada = $this->builder->createTestCaja('efectivo', [
            'estado' => 'cerrada',
        ]);

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'efectivo',
            'cantidad' => 2.0,
            'costo_unitario' => 5000.0,
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        $movInvCountAntes = DB::table('movimientos_inventario')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // Petición HTTP enviando caja cerrada
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", [
            'caja_id' => $cajaCerrada->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La caja se encuentra cerrada.',
            'errors' => null,
        ]);

        // Verificaciones de rollback y no persistencia física
        $this->assertSame('borrador', $compra->fresh()->estado);
        $this->assertSame('pendiente', $compra->fresh()->estado_pago);

        $this->assertNull(
            DB::table('inventarios')->where('bodega_id', $bodega->id)->where('producto_id', $producto->id)->first(),
            'El inventario NO debe crearse ni modificarse si la confirmación falla.'
        );
        $this->assertSame(
            $movInvCountAntes,
            DB::table('movimientos_inventario')->count(),
            'NO deben generarse movimientos en el kardex si la confirmación falla.'
        );
        $this->assertSame(
            $movCajaCountAntes,
            DB::table('movimientos_caja')->count(),
            'NO deben generarse movimientos de caja si la confirmación falla.'
        );
    }

    /**
     * CASO 12: Islero y vendedor intentan confirmar una compra: HTTP 403.
     * Validar que la compra permanece en borrador y no se generan efectos.
     */
    public function test_islero_y_vendedor_intentan_confirmar_compra_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        $movInvCountAntes = DB::table('movimientos_inventario')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // 1. Islero
        $this->actingAsSanctumUser($islero);
        $responseIslero = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", []);
        $responseIslero->assertStatus(403);
        $responseIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // 2. Vendedor
        $this->actingAsSanctumUser($vendedor);
        $responseVendedor = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", []);
        $responseVendedor->assertStatus(403);
        $responseVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // Verificaciones de no persistencia
        $this->assertSame('borrador', $compra->fresh()->estado);
        $this->assertSame('pendiente', $compra->fresh()->estado_pago);
        $this->assertSame($movInvCountAntes, DB::table('movimientos_inventario')->count());
        $this->assertSame($movCajaCountAntes, DB::table('movimientos_caja')->count());
    }

    /**
     * CASO 13: Administrador intenta confirmar compra de contado con caja abierta pero saldo insuficiente: HTTP 422.
     * AJUSTE 2 APLICADO:
     * - Caja abierta con saldo contable de 5000.0.
     * - Compra de contado con desembolso de 20000.0.
     * - Valida mensaje exacto devuelto por CompraService con formato numérico ($20.000,00).
     * - Comprueba que la compra permanece en borrador.
     * - Comprueba que no se crea inventario ni movimientos en kardex ni egresos de caja.
     * - Garantiza la limpieza selectiva de todos los registros creados.
     */
    public function test_administrador_intenta_confirmar_contado_con_saldo_insuficiente_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        // Crear una caja abierta pero con saldo insuficiente (ingreso de 5000.0)
        $caja = $this->builder->createTestCaja('efectivo');
        $this->builder->createMovimientoCaja($caja, [
            'tipo_movimiento' => 'ingreso',
            'categoria_movimiento' => 'apertura',
            'origen_modulo' => 'caja',
            'monto' => 5000.0,
        ]);

        // Crear compra de contado con total de 20000.0 (supera el saldo de 5000.0)
        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'efectivo',
            'cantidad' => 2.0,
            'costo_unitario' => 10000.0, // Total = 20000.0
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        $movInvCountAntes = DB::table('movimientos_inventario')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // Petición HTTP enviando caja con saldo insuficiente
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", [
            'caja_id' => $caja->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La caja no tiene saldo suficiente para cubrir el desembolso restante ($20.000,00).',
            'errors' => null,
        ]);

        // Verificaciones de rollback y no persistencia física ni contable
        $this->assertSame('borrador', $compra->fresh()->estado);
        $this->assertSame('pendiente', $compra->fresh()->estado_pago);

        $this->assertNull(
            DB::table('inventarios')->where('bodega_id', $bodega->id)->where('producto_id', $producto->id)->first(),
            'El inventario NO debe crearse ni modificarse si la confirmación falla por saldo insuficiente.'
        );
        $this->assertSame(
            $movInvCountAntes,
            DB::table('movimientos_inventario')->count(),
            'NO deben generarse movimientos en el kardex si la confirmación falla por saldo insuficiente.'
        );
        $this->assertSame(
            $movCajaCountAntes,
            DB::table('movimientos_caja')->count(),
            'NO deben generarse egresos en movimientos de caja si la confirmación falla por saldo insuficiente.'
        );
    }
}

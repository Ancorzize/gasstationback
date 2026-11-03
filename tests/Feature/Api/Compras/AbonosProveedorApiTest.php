<?php

namespace Tests\Feature\Api\Compras;

use App\Models\Bodega;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\PagoCompra;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\BaseApiTestCase;

/**
 * Suite de Pruebas API del Módulo de Abonos a Proveedores (Fase 3B).
 *
 * Valida los endpoints de consulta de pagos y registro de abonos sobre compras
 * a crédito, autorización RBAC, validaciones de negocio de saldos y cajas,
 * persistencia física y contable, y orden de limpieza LIFO sobre PostgreSQL (gasstation_testing).
 */
class AbonosProveedorApiTest extends BaseApiTestCase
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
            'nombre' => 'TEST_PROV_ABONO_' . $unique . '_' . $this->getTestRunId(),
            'nit' => 'NIT-' . substr(time(), -5) . $unique,
            'telefono' => '310' . rand(1000000, 9999999),
            'direccion' => 'Zona Comercial Abonos Test #' . rand(1, 99),
            'email' => 'prov_abono_' . $unique . '_' . $this->getTestRunId() . '@gasstation.test',
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
        $cantidad = (float) ($overrides['cantidad'] ?? 1.0);
        $costoUnitario = (float) ($overrides['costo_unitario'] ?? 10000.0);
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
            'observacion' => 'Compra abono test ' . $this->getTestRunId(),
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

        $this->trackIdForCleanup('compras', $compraId, 'id', Compra::class);

        $detalles = $response->json('data.detalles') ?? [];
        foreach ($detalles as $det) {
            $this->trackIdForCleanup('detalle_compras', (int) $det['id'], 'id', DetalleCompra::class);
        }

        return $compraId;
    }

    /**
     * Helper integral: Crea y confirma una compra a crédito con el saldo especificado.
     *
     * @return array{0: int, 1: Compra, 2: Bodega, 3: Producto, 4: int}
     */
    protected function createAndConfirmCreditoCompra(float $total = 10000.0, float $cantidad = 1.0): array
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $costoUnitario = round($total / $cantidad, 2);
        $producto = $this->builder->createTestProducto([
            'precio_compra' => $costoUnitario,
        ]);

        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'credito',
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
        ]);

        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        // Snapshot y confirmación oficial
        $this->snapshotPreConfirmation($compra);

        $this->actingAsSanctumUser($admin);
        $respConfirm = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", []);
        $this->trackConfirmationEffects($compra);

        $respConfirm->assertStatus(200);

        return [$compraId, $compra, $bodega, $producto, $proveedorId];
    }

    /**
     * Helper: Crea una caja abierta de prueba con fondos contables reales.
     */
    protected function createOpenCajaWithFunds(string $tipo = 'efectivo', float $saldo = 50000.0): Caja
    {
        $caja = $this->builder->createTestCaja($tipo);

        $this->builder->createMovimientoCaja($caja, [
            'tipo_movimiento' => 'ingreso',
            'categoria_movimiento' => 'apertura',
            'origen_modulo' => 'caja',
            'medio_pago' => $tipo === 'efectivo' ? 'efectivo' : 'digital',
            'monto' => $saldo,
            'descripcion' => 'Fondeo inicial test ' . $this->getTestRunId(),
        ]);

        return $caja;
    }

    // =========================================================================
    // SECCIÓN 1: CONSULTAS Y PERMISOS
    // =========================================================================

    /**
     * CASO 1: Administrador consulta los pagos de una compra: HTTP 200.
     */
    public function test_01_administrador_consulta_pagos_de_compra_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // Registrar un abono previo de $2.000
        $this->actingAsSanctumUser($admin);
        $pagoResp = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 2000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Abono previo test 01',
        ]);
        $this->trackConfirmationEffects($compra);
        $pagoResp->assertStatus(200);

        // Consultar listado de pagos de la compra
        $response = $this->apiJson('GET', "/api/compras/{$compraId}/pagos");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Listado de pagos de la compra.',
        ]);

        $items = $response->json('data');
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame($compraId, (int) $items[0]['compra_id']);
        $this->assertEquals(2000.0, (float) $items[0]['monto']);
        $this->assertSame('efectivo', $items[0]['metodo_pago']);
    }

    /**
     * CASO 2: Islero y vendedor intentan consultar pagos: HTTP 403.
     */
    public function test_02_islero_y_vendedor_intentan_consultar_pagos_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);
        [$compraId] = $this->createAndConfirmCreditoCompra(10000.0);

        // 1. Intento por usuario islero
        $this->actingAsSanctumUser($islero);
        $respIslero = $this->apiJson('GET', "/api/compras/{$compraId}/pagos");
        $respIslero->assertStatus(403);
        $respIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);

        // 2. Intento por usuario vendedor
        $this->actingAsSanctumUser($vendedor);
        $respVendedor = $this->apiJson('GET', "/api/compras/{$compraId}/pagos");
        $respVendedor->assertStatus(403);
        $respVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);
    }

    /**
     * CASO 3: Islero y vendedor intentan registrar abonos: HTTP 403.
     */
    public function test_03_islero_y_vendedor_intentan_registrar_abonos_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $payload = [
            'fecha_pago' => now()->toDateString(),
            'monto' => 3000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Intento sin permiso',
        ];

        $pagosCountAntes = DB::table('pagos_compra')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // 1. Intento por usuario islero
        $this->actingAsSanctumUser($islero);
        $respIslero = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", $payload);
        $this->trackConfirmationEffects($compra);
        $respIslero->assertStatus(403);
        $respIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);

        // 2. Intento por usuario vendedor
        $this->actingAsSanctumUser($vendedor);
        $respVendedor = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", $payload);
        $this->trackConfirmationEffects($compra);
        $respVendedor->assertStatus(403);
        $respVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);

        // Verificación de no alteración ni persistencia
        $this->assertSame($pagosCountAntes, DB::table('pagos_compra')->count(), 'No deben crearse pagos.');
        $this->assertSame($movCajaCountAntes, DB::table('movimientos_caja')->count(), 'No deben crearse egresos.');
    }

    // =========================================================================
    // SECCIÓN 2: ABONOS EXITOSOS
    // =========================================================================

    /**
     * CASO 4: Registrar un abono parcial: compra de $10.000, abono de $4.000. Saldo $6.000.
     */
    public function test_04_administrador_registra_abono_parcial_exitoso_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 4000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Abono parcial de prueba 4000',
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Pago registrado correctamente.',
            'data' => [
                'id' => $compraId,
                'estado' => 'confirmada',
                'estado_pago' => 'pendiente',
                'total_pagado' => 4000.0,
                'saldo_pendiente' => 6000.0,
            ],
        ]);

        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 4000.0,
            'saldo_pendiente' => 6000.0,
            'estado_pago' => 'pendiente',
        ]);
    }

    /**
     * CASO 5: Registrar varios abonos sucesivos: primer abono $4.000 y segundo $3.000. Total pagado $7.000 y saldo $3.000.
     */
    public function test_05_administrador_registra_varios_abonos_sucesivos_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);

        // Primer abono: $4.000
        $resp1 = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 4000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Primer abono de prueba',
        ]);
        $this->trackConfirmationEffects($compra);
        $resp1->assertStatus(200);
        $resp1->assertJson([
            'status' => true,
            'data' => [
                'total_pagado' => 4000.0,
                'saldo_pendiente' => 6000.0,
                'estado_pago' => 'pendiente',
            ],
        ]);

        // Segundo abono: $3.000
        $resp2 = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 3000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Segundo abono de prueba',
        ]);
        $this->trackConfirmationEffects($compra);
        $resp2->assertStatus(200);
        $resp2->assertJson([
            'status' => true,
            'message' => 'Pago registrado correctamente.',
            'data' => [
                'id' => $compraId,
                'total_pagado' => 7000.0,
                'saldo_pendiente' => 3000.0,
                'estado_pago' => 'pendiente',
            ],
        ]);

        // Verificaciones acumulativas en BD
        $this->assertSame(2, DB::table('pagos_compra')->where('compra_id', $compraId)->count());
        $this->assertEquals(7000.0, (float) DB::table('pagos_compra')->where('compra_id', $compraId)->sum('monto'));
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 7000.0,
            'saldo_pendiente' => 3000.0,
            'estado_pago' => 'pendiente',
        ]);
    }

    /**
     * CASO 6: Registrar un abono que liquide el saldo: abono de $3.000 sobre saldo de $3.000. Estado pagado.
     */
    public function test_06_administrador_registra_abono_que_liquida_saldo_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);

        // Abono preliminar de $7.000 para dejar saldo pendiente exactamente en $3.000
        $respPrelim = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 7000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra);
        $respPrelim->assertStatus(200);

        // Abono liquidador de $3.000 exactos sobre saldo de $3.000
        $respLiquidar = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 3000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Abono de liquidación final',
        ]);
        $this->trackConfirmationEffects($compra);

        $respLiquidar->assertStatus(200);
        $respLiquidar->assertJson([
            'status' => true,
            'message' => 'Pago registrado correctamente.',
            'data' => [
                'id' => $compraId,
                'estado' => 'confirmada',
                'estado_pago' => 'pagado',
                'total_pagado' => 10000.0,
                'saldo_pendiente' => 0.0,
            ],
        ]);

        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 10000.0,
            'saldo_pendiente' => 0.0,
            'estado_pago' => 'pagado',
        ]);
    }

    // =========================================================================
    // SECCIÓN 3: VALIDACIONES DE NEGOCIO
    // =========================================================================

    /**
     * CASO 7: Abono superior al saldo: HTTP 422, mensaje exacto 'El monto no puede superar el saldo pendiente.'
     */
    public function test_07_administrador_intenta_abono_superior_al_saldo_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $pagosCountAntes = DB::table('pagos_compra')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // Intento de abonar $15.000 cuando el saldo pendiente es $10.000
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 15000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'El monto no puede superar el saldo pendiente.',
            'errors' => null,
        ]);

        // Verificaciones de rollback
        $this->assertSame($pagosCountAntes, DB::table('pagos_compra')->count());
        $this->assertSame($movCajaCountAntes, DB::table('movimientos_caja')->count());
        $this->assertSame(10000.0, (float) $compra->fresh()->saldo_pendiente);
        $this->assertSame(0.0, (float) $compra->fresh()->total_pagado);
    }

    /**
     * CASO 8: Compra inexistente: HTTP 404, mensaje exacto 'Compra no encontrada.'
     */
    public function test_08_administrador_intenta_abono_a_compra_inexistente_retorna_404(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/99999999/pagos', [
            'fecha_pago' => now()->toDateString(),
            'monto' => 1000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'status' => false,
            'message' => 'Compra no encontrada.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 9: Compra en borrador: HTTP 422, mensaje exacto 'Solo se pueden registrar pagos a compras confirmadas.'
     */
    public function test_09_administrador_intenta_abono_a_compra_en_borrador_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // Crear compra pero NO confirmarla (permanece en estado 'borrador')
        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'credito',
            'cantidad' => 1.0,
            'costo_unitario' => 10000.0,
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::find($compraId);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 2000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'Solo se pueden registrar pagos a compras confirmadas.',
            'errors' => null,
        ]);

        $this->assertSame('borrador', $compra->fresh()->estado);
        $this->assertSame(0.0, (float) $compra->fresh()->total_pagado);
    }

    /**
     * CASO 10: Compra ya pagada: HTTP 422, mensaje exacto 'La compra ya se encuentra pagada.'
     */
    public function test_10_administrador_intenta_abono_a_compra_ya_pagada_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // Crear y confirmar compra de contado (saldo_pendiente = 0, estado_pago = 'pagado')
        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'efectivo',
            'cantidad' => 1.0,
            'costo_unitario' => 10000.0,
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        $this->snapshotPreConfirmation($compra);
        $this->actingAsSanctumUser($admin);
        $respConfirm = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", [
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra);
        $respConfirm->assertStatus(200);

        $pagosCountAntes = DB::table('pagos_compra')->count();

        // Intento de registrar abono a compra ya liquidada
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 1000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La compra ya se encuentra pagada.',
            'errors' => null,
        ]);

        $this->assertSame($pagosCountAntes, DB::table('pagos_compra')->count(), 'No deben agregarse pagos.');
    }

    /**
     * CASO 11: Caja cerrada: HTTP 422, mensaje exacto 'La caja seleccionada se encuentra cerrada.'
     */
    public function test_11_administrador_intenta_abono_con_caja_cerrada_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);

        // Caja exclusiva creada directamente en estado 'cerrada'
        $cajaCerrada = $this->builder->createTestCaja('efectivo', [
            'estado' => 'cerrada',
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 3000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $cajaCerrada->id,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La caja seleccionada se encuentra cerrada.',
            'errors' => null,
        ]);

        $this->assertSame(0.0, (float) $compra->fresh()->total_pagado);
    }

    /**
     * CASO 12: Saldo insuficiente en caja: HTTP 422, mensaje exacto 'No hay saldo suficiente en la caja.'
     */
    public function test_12_administrador_intenta_abono_con_saldo_insuficiente_en_caja_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);

        // Caja abierta pero con saldo contable de solo $2.000
        $caja = $this->createOpenCajaWithFunds('efectivo', 2000.0);

        // Intento de abonar $5.000 (supera el saldo contable de $2.000 de la caja)
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 5000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'No hay saldo suficiente en la caja.',
            'errors' => null,
        ]);

        $this->assertSame(0.0, (float) $compra->fresh()->total_pagado);
    }

    /**
     * CASO 13: Incompatibilidad entre método de pago y tipo de caja: HTTP 422, mensaje exacto 'La caja seleccionada no corresponde al método de pago.'
     */
    public function test_13_administrador_intenta_abono_con_metodo_pago_incompatible_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra(10000.0);

        // Caja abierta de tipo 'digital' con fondos suficientes
        $cajaDigital = $this->createOpenCajaWithFunds('digital', 50000.0);

        // Intento de registrar abono en 'efectivo' utilizando una caja 'digital'
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 3000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $cajaDigital->id,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La caja seleccionada no corresponde al método de pago.',
            'errors' => null,
        ]);

        $this->assertSame(0.0, (float) $compra->fresh()->total_pagado);
    }

    // =========================================================================
    // SECCIÓN 4: PERSISTENCIA Y EFECTOS CONTABLES
    // =========================================================================

    /**
     * CASO 14: Verificar persistencia del abono en pagos_compra, egreso correspondiente en movimientos_caja y actualización de los saldos de la compra.
     */
    public function test_14_verificar_persistencia_abono_egreso_caja_y_actualizacion_saldos(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        [$compraId, $compra, $bodega, $producto] = $this->createAndConfirmCreditoCompra(10000.0);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // Snapshot de inventarios y kardex antes del abono
        $invRowAntes = DB::table('inventarios')
            ->where('bodega_id', $bodega->id)
            ->where('producto_id', $producto->id)
            ->first();
        $this->assertNotNull($invRowAntes);
        $cantidadInvAntes = (float) $invRowAntes->cantidad;
        $kardexCountAntes = DB::table('movimientos_inventario')->count();

        // Ejecutar abono de $5.000
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/pagos", [
            'fecha_pago' => now()->toDateString(),
            'monto' => 5000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Abono para validación de efectos contables',
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(200);

        // 1. Verificación en tabla 'pagos_compra'
        $this->assertDatabaseHas('pagos_compra', [
            'compra_id' => $compraId,
            'user_id' => $admin->id,
            'monto' => 5000.0,
            'metodo_pago' => 'efectivo',
            'observacion' => 'Abono para validación de efectos contables',
        ]);

        // 2. Verificación en tabla 'movimientos_caja' (egreso generado)
        $this->assertDatabaseHas('movimientos_caja', [
            'caja_id' => $caja->id,
            'tipo_movimiento' => 'egreso',
            'categoria_movimiento' => 'pago_proveedor',
            'origen_modulo' => 'compras',
            'origen_id' => $compraId,
            'medio_pago' => 'efectivo',
            'monto' => 5000.0,
            'user_id' => $admin->id,
        ]);

        // 3. Verificación de actualización en tabla 'compras'
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 5000.0,
            'saldo_pendiente' => 5000.0,
            'estado_pago' => 'pendiente',
        ]);

        // 4. Verificación de integridad física: Ningún cambio en inventarios ni kardex
        $invRowDespues = DB::table('inventarios')
            ->where('bodega_id', $bodega->id)
            ->where('producto_id', $producto->id)
            ->first();
        $this->assertSame(
            $cantidadInvAntes,
            (float) $invRowDespues->cantidad,
            'Un abono de pago NO debe alterar las existencias físicas de inventario.'
        );
        $this->assertSame(
            $kardexCountAntes,
            DB::table('movimientos_inventario')->count(),
            'Un abono de pago NO debe registrar movimientos en el kardex de inventario.'
        );
    }
}

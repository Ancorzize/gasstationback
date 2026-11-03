<?php

namespace Tests\Feature\Api\Compras;

use App\Models\AnticipoProveedor;
use App\Models\AplicacionAnticipoCompra;
use App\Models\Bodega;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\MovimientoCaja;
use App\Models\PagoCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\BaseApiTestCase;

/**
 * Suite de Pruebas API del Módulo de Anticipos a Proveedores (Fase 3C).
 *
 * Valida los endpoints de registro de anticipos directos, consulta de anticipos,
 * aplicaciones parciales y totales a compras a crédito (FIFO), validaciones de negocio,
 * generación de anticipos por excedentes en pagos generales y conciliación contable.
 */
class AnticiposProveedorApiTest extends BaseApiTestCase
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
    protected function createExclusiveProveedor(array $overrides = []): int
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $this->actingAsSanctumUser($admin);

        $unique = bin2hex(random_bytes(3));
        $payload = array_merge([
            'nombre' => 'TEST_PROV_ANT_' . $unique . '_' . $this->getTestRunId(),
            'nit' => 'NIT-' . substr(time(), -5) . $unique,
            'telefono' => '310' . rand(1000000, 9999999),
            'direccion' => 'Zona Comercial Anticipos Test #' . rand(1, 99),
            'email' => 'prov_ant_' . $unique . '_' . $this->getTestRunId() . '@gasstation.test',
        ], $overrides);

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
            'observacion' => 'Compra anticipo test ' . $this->getTestRunId(),
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
     * Helper integral: Crea y confirma una compra a crédito vinculada a un proveedor.
     *
     * @return array{0: int, 1: Compra, 2: Bodega, 3: Producto, 4: int}
     */
    protected function createAndConfirmCreditoCompra(?int $proveedorId = null, float $total = 10000.0, float $cantidad = 1.0): array
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $provId = $proveedorId ?? $this->createExclusiveProveedor();
        $bodega = $this->builder->createTestBodega();
        $costoUnitario = round($total / $cantidad, 2);
        $producto = $this->builder->createTestProducto([
            'precio_compra' => $costoUnitario,
        ]);

        $payload = $this->makeCompraPayload($provId, $bodega->id, $producto->id, [
            'tipo_pago' => 'credito',
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
        ]);

        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::with('detalles')->find($compraId);

        $this->snapshotPreConfirmation($compra);

        $this->actingAsSanctumUser($admin);
        $respConfirm = $this->apiJson('POST', "/api/compras/{$compraId}/confirmar", []);
        $this->trackConfirmationEffects($compra);

        $respConfirm->assertStatus(200);

        return [$compraId, $compra, $bodega, $producto, $provId];
    }

    /**
     * Helper: Crea una caja abierta de prueba con fondos contables reales en movimientos_caja.
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
    // SECCIÓN A: ANTICIPOS DIRECTOS
    // =========================================================================

    /**
     * CASO 1: Administrador registra un anticipo correctamente.
     */
    public function test_01_administrador_registra_anticipo_directo_correctamente(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => 15000.0,
            'metodo_pago' => 'efectivo',
            'referencia' => 'REF-TEST-01',
            'observacion' => 'Anticipo directo de prueba caso 01',
        ]);

        $anticipoId = (int) $response->json('data.anticipo_id');
        $movCajaId = (int) $response->json('data.movimiento_caja_id');
        if ($anticipoId > 0) {
            $this->trackIdForCleanup('anticipos_proveedor', $anticipoId, 'id', AnticipoProveedor::class);
        }
        if ($movCajaId > 0) {
            $this->trackIdForCleanup('movimientos_caja', $movCajaId, 'id', MovimientoCaja::class);
        }

        $response->assertStatus(201);
        $response->assertJson([
            'status' => true,
            'message' => 'Anticipo directo registrado correctamente.',
            'data' => [
                'proveedor_id' => $proveedorId,
                'monto' => 15000.0,
                'saldo_a_favor_total' => 15000.0,
            ],
        ]);

        $numeroAnticipo = $response->json('data.numero');
        $this->assertMatchesRegularExpression('/^ANT-\d{6}$/', $numeroAnticipo);

        // Verificación en tabla anticipos_proveedor
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $anticipoId,
            'numero' => $numeroAnticipo,
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'movimiento_caja_id' => $movCajaId,
            'origen' => 'directo',
            'monto_original' => 15000.0,
            'saldo_disponible' => 15000.0,
            'estado' => 'disponible',
            'metodo_pago' => 'efectivo',
        ]);

        // Verificación en tabla movimientos_caja (egreso generado)
        $this->assertDatabaseHas('movimientos_caja', [
            'id' => $movCajaId,
            'caja_id' => $caja->id,
            'tipo_movimiento' => 'egreso',
            'categoria_movimiento' => 'pago_proveedor',
            'origen_modulo' => 'proveedores',
            'origen_id' => $proveedorId,
            'monto' => 15000.0,
            'medio_pago' => 'efectivo',
        ]);

        // Verificación en tabla proveedores (saldo incrementado)
        $proveedor = Proveedor::find($proveedorId);
        $this->assertEquals(15000.0, (float) $proveedor->saldo_anticipo);
    }

    /**
     * CASO 2: Administrador consulta anticipos de un proveedor.
     */
    public function test_02_administrador_consulta_anticipos_de_proveedor_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);

        // Crear un anticipo de prueba con el builder
        $anticipo = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 25000.0,
            'saldo_disponible' => 25000.0,
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('GET', "/api/compras/proveedores/{$proveedorId}/anticipos");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Anticipos del proveedor obtenidos correctamente.',
        ]);

        $items = $response->json('data');
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame($anticipo->id, (int) $items[0]['id']);
        $this->assertEquals(25000.0, (float) $items[0]['monto_original']);
        $this->assertSame('disponible', $items[0]['estado']);
    }

    /**
     * CASO 3: Islero y vendedor no pueden consultar anticipos.
     */
    public function test_03_islero_y_vendedor_no_pueden_consultar_anticipos_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();

        // 1. Islero
        $this->actingAsSanctumUser($islero);
        $respIslero = $this->apiJson('GET', "/api/compras/proveedores/{$proveedorId}/anticipos");
        $respIslero->assertStatus(403);
        $respIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);

        // 2. Vendedor
        $this->actingAsSanctumUser($vendedor);
        $respVendedor = $this->apiJson('GET', "/api/compras/proveedores/{$proveedorId}/anticipos");
        $respVendedor->assertStatus(403);
        $respVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);
    }

    /**
     * CASO 4: Islero y vendedor no pueden registrar anticipos.
     */
    public function test_04_islero_y_vendedor_no_pueden_registrar_anticipos_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $payload = [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => 10000.0,
            'metodo_pago' => 'efectivo',
        ];

        $antCountAntes = DB::table('anticipos_proveedor')->count();
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // 1. Islero
        $this->actingAsSanctumUser($islero);
        $respIslero = $this->apiJson('POST', '/api/compras/anticipos-directos', $payload);
        $respIslero->assertStatus(403);
        $respIslero->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);

        // 2. Vendedor
        $this->actingAsSanctumUser($vendedor);
        $respVendedor = $this->apiJson('POST', '/api/compras/anticipos-directos', $payload);
        $respVendedor->assertStatus(403);
        $respVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
        ]);

        // Verificar que no se creó ningún registro
        $this->assertSame($antCountAntes, DB::table('anticipos_proveedor')->count());
        $this->assertSame($movCajaCountAntes, DB::table('movimientos_caja')->count());
    }

    /**
     * CASO 5: Proveedor inexistente.
     */
    public function test_05_intento_registrar_anticipo_con_proveedor_inexistente_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => 99999999,
            'caja_id' => $caja->id,
            'monto' => 10000.0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['proveedor_id']);
        $this->assertStringContainsString(
            'El proveedor seleccionado no existe.',
            $response->json('errors.proveedor_id.0')
        );
    }

    /**
     * CASO 6: Proveedor inactivo.
     */
    public function test_06_intento_registrar_anticipo_con_proveedor_inactivo_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);
        $proveedor->update(['is_active' => false]);

        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => 10000.0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'El proveedor seleccionado se encuentra inactivo.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 7: Datos obligatorios inválidos y monto cero o negativo.
     */
    public function test_07_intento_registrar_anticipo_con_monto_invalido_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $this->actingAsSanctumUser($admin);

        // Monto = 0
        $respCero = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => 0.0,
            'metodo_pago' => 'efectivo',
        ]);
        $respCero->assertStatus(422);
        $respCero->assertJsonValidationErrors(['monto']);
        $this->assertStringContainsString(
            'El monto del anticipo debe ser mayor a cero.',
            $respCero->json('errors.monto.0')
        );

        // Monto negativo
        $respNegativo = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => -5000.0,
            'metodo_pago' => 'efectivo',
        ]);
        $respNegativo->assertStatus(422);
        $respNegativo->assertJsonValidationErrors(['monto']);
    }

    // =========================================================================
    // SECCIÓN B: VALIDACIONES DE CAJA
    // =========================================================================

    /**
     * CASO 8: Intentar registrar anticipo con caja cerrada.
     */
    public function test_08_intento_registrar_anticipo_con_caja_cerrada_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();

        $cajaCerrada = $this->builder->createTestCaja('efectivo', [
            'estado' => 'cerrada',
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $cajaCerrada->id,
            'monto' => 5000.0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La caja seleccionada se encuentra cerrada.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 9: Intentar registrar anticipo con saldo insuficiente en caja.
     */
    public function test_09_intento_registrar_anticipo_con_saldo_insuficiente_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();

        // Caja con saldo contable de $3.000
        $caja = $this->createOpenCajaWithFunds('efectivo', 3000.0);

        // Intento de anticipo por $10.000
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => 10000.0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'No hay saldo suficiente en la caja.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 10: Método de pago incompatible con tipo de caja.
     */
    public function test_10_intento_registrar_anticipo_con_metodo_incompatible_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();

        // Caja abierta de tipo digital con fondos suficientes
        $cajaDigital = $this->createOpenCajaWithFunds('digital', 50000.0);

        // Intento de pagar en efectivo usando caja digital
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $cajaDigital->id,
            'monto' => 5000.0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La caja seleccionada no corresponde al método de pago.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 11: Caja inexistente.
     */
    public function test_11_intento_registrar_anticipo_con_caja_inexistente_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => 99999999,
            'monto' => 5000.0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['caja_id']);
        $this->assertStringContainsString(
            'La caja seleccionada no existe.',
            $response->json('errors.caja_id.0')
        );
    }

    // =========================================================================
    // SECCIÓN C: APLICACIÓN DE ANTICIPOS
    // =========================================================================

    /**
     * CASO 12: Aplicación parcial.
     * Compra de $10.000, anticipo de $15.000, aplicar $4.000.
     * Compra: total_pagado $4.000 y saldo pendiente $6.000.
     * Anticipo: saldo disponible $11.000. Proveedor: saldo_anticipo $11.000.
     */
    public function test_12_aplicacion_parcial_de_anticipo_a_compra_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);

        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 10000.0);

        // Crear anticipo previo de $15.000 para este proveedor
        $anticipo = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 15000.0,
            'saldo_disponible' => 15000.0,
            'estado' => 'disponible',
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 4000.0,
            'observacion' => 'Aplicación parcial caso 12',
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Saldo a favor aplicado a la compra correctamente.',
            'data' => [
                'compra_id' => $compraId,
                'monto_aplicado' => 4000.0,
                'nuevo_saldo_pendiente' => 6000.0,
                'estado_pago' => 'pendiente',
                'saldo_a_favor_restante' => 11000.0,
            ],
        ]);

        // Verificación de estado de compra
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 4000.0,
            'saldo_pendiente' => 6000.0,
            'estado_pago' => 'pendiente',
        ]);

        // Verificación de estado de anticipo
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $anticipo->id,
            'saldo_disponible' => 11000.0,
            'estado' => 'disponible',
        ]);

        // Verificación de saldo de proveedor
        $this->assertEquals(11000.0, (float) $proveedor->fresh()->saldo_anticipo);

        // Verificación de registro en aplicaciones_anticipo_compra
        $this->assertDatabaseHas('aplicaciones_anticipo_compra', [
            'anticipo_proveedor_id' => $anticipo->id,
            'compra_id' => $compraId,
            'monto_aplicado' => 4000.0,
            'saldo_anterior' => 15000.0,
            'saldo_nuevo' => 11000.0,
            'estado' => 'aplicada',
        ]);
    }

    /**
     * CASO 13: Aplicación total.
     * Aplicar el saldo pendiente exacto. Compra queda pagada, saldo pendiente cero.
     */
    public function test_13_aplicacion_total_de_anticipo_liquida_compra_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);

        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 10000.0);

        // Anticipo por exactamente $10.000
        $anticipo = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 10000.0,
            'saldo_disponible' => 10000.0,
            'estado' => 'disponible',
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 10000.0,
            'observacion' => 'Aplicación total caso 13',
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Saldo a favor aplicado a la compra correctamente.',
            'data' => [
                'compra_id' => $compraId,
                'monto_aplicado' => 10000.0,
                'nuevo_saldo_pendiente' => 0.0,
                'estado_pago' => 'pagado',
                'saldo_a_favor_restante' => 0.0,
            ],
        ]);

        // Compra queda completamente pagada
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 10000.0,
            'saldo_pendiente' => 0.0,
            'estado_pago' => 'pagado',
        ]);

        // Anticipo queda agotado
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $anticipo->id,
            'saldo_disponible' => 0.0,
            'estado' => 'agotado',
        ]);

        $this->assertEquals(0.0, (float) $proveedor->fresh()->saldo_anticipo);
    }

    /**
     * CASO 14: Aplicaciones FIFO con dos anticipos.
     * Dos anticipos de $5.000. Aplicar $8.000. Primero agotado, segundo con $2.000 disponible.
     */
    public function test_14_aplicacion_fifo_con_dos_anticipos_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);

        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 10000.0);

        // Primer anticipo ($5.000)
        $ant1 = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 5000.0,
            'saldo_disponible' => 5000.0,
        ]);

        // Segundo anticipo ($5.000)
        $ant2 = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 5000.0,
            'saldo_disponible' => 5000.0,
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 8000.0,
            'observacion' => 'Consumo FIFO de dos anticipos',
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'compra_id' => $compraId,
                'monto_aplicado' => 8000.0,
                'nuevo_saldo_pendiente' => 2000.0,
                'estado_pago' => 'pendiente',
                'saldo_a_favor_restante' => 2000.0,
            ],
        ]);

        // El primer anticipo debe quedar completamente agotado
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $ant1->id,
            'saldo_disponible' => 0.0,
            'estado' => 'agotado',
        ]);

        // El segundo anticipo debe conservar $2.000 disponible
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $ant2->id,
            'saldo_disponible' => 2000.0,
            'estado' => 'disponible',
        ]);

        // Se deben haber registrado dos aplicaciones individuales
        $this->assertSame(
            2,
            DB::table('aplicaciones_anticipo_compra')->where('compra_id', $compraId)->count()
        );
    }

    /**
     * CASO 15: Saldo a favor insuficiente.
     */
    public function test_15_intento_aplicar_mas_del_saldo_a_favor_disponible_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);

        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 10000.0);

        // Anticipo de solo $3.000
        $anticipo = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 3000.0,
            'saldo_disponible' => 3000.0,
        ]);

        // Intento de aplicar $5.000 (proveedor solo tiene $3.000)
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 5000.0,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'El proveedor no dispone de suficiente saldo a favor.',
            'errors' => null,
        ]);

        // Ningún saldo modificado
        $this->assertEquals(3000.0, (float) $proveedor->fresh()->saldo_anticipo);
        $this->assertEquals(10000.0, (float) $compra->fresh()->saldo_pendiente);
    }

    /**
     * CASO 16: Monto superior al saldo pendiente de la compra.
     */
    public function test_16_intento_aplicar_monto_superior_al_saldo_pendiente_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);

        // Compra por $4.000
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 4000.0);

        // Proveedor tiene saldo suficiente ($10.000)
        $anticipo = $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 10000.0,
            'saldo_disponible' => 10000.0,
        ]);

        // Intento de aplicar $6.000 a compra que solo debe $4.000
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 6000.0,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'El monto a aplicar no puede ser superior al saldo pendiente de la compra.',
            'errors' => null,
        ]);

        $this->assertEquals(4000.0, (float) $compra->fresh()->saldo_pendiente);
        $this->assertEquals(10000.0, (float) $proveedor->fresh()->saldo_anticipo);
    }

    /**
     * CASO 17: Compra en borrador.
     */
    public function test_17_intento_aplicar_anticipo_a_compra_en_borrador_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();

        // Compra no confirmada (borrador)
        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'credito',
        ]);
        $compraId = $this->createCompraViaApi($payload);
        $compra = Compra::find($compraId);

        $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 10000.0,
            'saldo_disponible' => 10000.0,
        ]);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 5000.0,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'Solo se pueden aplicar anticipos a compras confirmadas.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 18: Compra sin saldo pendiente (ya pagada).
     */
    public function test_18_intento_aplicar_anticipo_a_compra_ya_pagada_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);
        $bodega = $this->builder->createTestBodega();
        $producto = $this->builder->createTestProducto();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // Compra de contado confirmada (saldo_pendiente = 0)
        $payload = $this->makeCompraPayload($proveedorId, $bodega->id, $producto->id, [
            'tipo_pago' => 'efectivo',
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

        $this->builder->createAnticipoProveedor($proveedor, [
            'monto_original' => 10000.0,
            'saldo_disponible' => 10000.0,
        ]);

        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 2000.0,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'La compra no tiene saldo pendiente por pagar.',
            'errors' => null,
        ]);
    }

    /**
     * CASO 19: Compra perteneciente a otro proveedor.
     */
    public function test_19_intento_aplicar_anticipo_a_compra_de_otro_proveedor_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        // Proveedor A (con saldo a favor)
        $proveedorAId = $this->createExclusiveProveedor();
        $proveedorA = Proveedor::find($proveedorAId);
        $this->builder->createAnticipoProveedor($proveedorA, [
            'monto_original' => 10000.0,
            'saldo_disponible' => 10000.0,
        ]);

        // Proveedor B (con compra a crédito confirmada)
        $proveedorBId = $this->createExclusiveProveedor();
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorBId, 10000.0);

        // Intentar aplicar anticipo a la compra del proveedor B, pero el proveedor B no tiene anticipos
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 5000.0,
        ]);
        $this->trackConfirmationEffects($compra);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'El proveedor no dispone de suficiente saldo a favor.',
            'errors' => null,
        ]);

        // El saldo del Proveedor A y los saldos de la compra del Proveedor B permanecen intactos
        $this->assertEquals(10000.0, (float) $proveedorA->fresh()->saldo_anticipo);
        $this->assertEquals(10000.0, (float) $compra->fresh()->saldo_pendiente);
        $this->assertEquals(0.0, (float) $compra->fresh()->total_pagado);
    }

    // =========================================================================
    // SECCIÓN D: ANTICIPOS POR PAGOS GENERALES
    // =========================================================================

    /**
     * CASO 20: Pago general con excedente.
     * Compra pendiente $10.000, abono general de $15.000.
     * Compra pagada, anticipo por $5.000 con origen 'pago_excedente'.
     * Exactamente un movimiento de egreso por $15.000.
     */
    public function test_20_pago_general_con_excedente_genera_anticipo_y_un_solo_egreso(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 10000.0);

        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);
        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        // Petición de abono general por $15.000
        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/pagos-general', [
            'proveedor_id' => $proveedorId,
            'fecha_pago' => now()->toDateString(),
            'monto' => 15000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Abono general con excedente caso 20',
        ]);
        $this->trackConfirmationEffects($compra);

        $anticipoId = (int) $response->json('data.anticipo.id');
        $movCajaId = (int) $response->json('data.movimiento_caja_id');
        if ($anticipoId > 0) {
            $this->trackIdForCleanup('anticipos_proveedor', $anticipoId, 'id', AnticipoProveedor::class);
        }
        if ($movCajaId > 0) {
            $this->trackIdForCleanup('movimientos_caja', $movCajaId, 'id', MovimientoCaja::class);
        }

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Abono al proveedor registrado correctamente.',
            'data' => [
                'proveedor_id' => $proveedorId,
                'monto_abono' => 15000.0,
                'total_aplicado' => 10000.0,
                'saldo_a_favor_generado' => 5000.0,
                'anticipo' => [
                    'id' => $anticipoId,
                    'monto' => 5000.0,
                    'origen' => 'pago_excedente',
                ],
            ],
        ]);

        // 1. Compra queda pagada con saldo cero
        $this->assertDatabaseHas('compras', [
            'id' => $compraId,
            'total_pagado' => 10000.0,
            'saldo_pendiente' => 0.0,
            'estado_pago' => 'pagado',
        ]);

        // 2. Anticipo creado con origen 'pago_excedente'
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $anticipoId,
            'proveedor_id' => $proveedorId,
            'origen' => 'pago_excedente',
            'monto_original' => 5000.0,
            'saldo_disponible' => 5000.0,
            'estado' => 'disponible',
            'movimiento_caja_id' => $movCajaId,
        ]);

        // 3. Saldo a favor del proveedor actualizado
        $proveedor = Proveedor::find($proveedorId);
        $this->assertEquals(5000.0, (float) $proveedor->saldo_anticipo);

        // 4. Exactamente un solo movimiento de egreso en caja por los $15.000 totales
        $this->assertSame($movCajaCountAntes + 1, DB::table('movimientos_caja')->count());
        $this->assertDatabaseHas('movimientos_caja', [
            'id' => $movCajaId,
            'caja_id' => $caja->id,
            'tipo_movimiento' => 'egreso',
            'categoria_movimiento' => 'pago_proveedor',
            'origen_modulo' => 'proveedores',
            'origen_id' => $proveedorId,
            'monto' => 15000.0,
        ]);
    }

    /**
     * CASO 21: Pago general sin deudas pendientes.
     * Proveedor sin deudas recibe abono general de $8.000.
     * Crea anticipo con origen 'directo' y un solo egreso por $8.000.
     */
    public function test_21_pago_general_sin_deudas_genera_anticipo_directo(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        $movCajaCountAntes = DB::table('movimientos_caja')->count();

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/compras/pagos-general', [
            'proveedor_id' => $proveedorId,
            'fecha_pago' => now()->toDateString(),
            'monto' => 8000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
            'observacion' => 'Abono general directo caso 21',
        ]);

        $anticipoId = (int) $response->json('data.anticipo.id');
        $movCajaId = (int) $response->json('data.movimiento_caja_id');
        if ($anticipoId > 0) {
            $this->trackIdForCleanup('anticipos_proveedor', $anticipoId, 'id', AnticipoProveedor::class);
        }
        if ($movCajaId > 0) {
            $this->trackIdForCleanup('movimientos_caja', $movCajaId, 'id', MovimientoCaja::class);
        }

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => [
                'proveedor_id' => $proveedorId,
                'monto_abono' => 8000.0,
                'total_aplicado' => 0.0,
                'saldo_a_favor_generado' => 8000.0,
                'anticipo' => [
                    'id' => $anticipoId,
                    'monto' => 8000.0,
                    'origen' => 'directo',
                ],
            ],
        ]);

        // Confirmar origen 'directo' en la tabla
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $anticipoId,
            'proveedor_id' => $proveedorId,
            'origen' => 'directo',
            'monto_original' => 8000.0,
            'saldo_disponible' => 8000.0,
            'estado' => 'disponible',
        ]);

        $this->assertSame($movCajaCountAntes + 1, DB::table('movimientos_caja')->count());
        $this->assertEquals(8000.0, (float) Proveedor::find($proveedorId)->saldo_anticipo);
    }

    /**
     * CASO 22: Aplicar posteriormente un anticipo generado por excedente.
     */
    public function test_22_aplicar_posteriormente_anticipo_generado_por_excedente(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // 1. Generar compra inicial de $6.000 y pagar $10.000 (excedente de $4.000)
        [$compra1Id, $compra1] = $this->createAndConfirmCreditoCompra($proveedorId, 6000.0);

        $this->actingAsSanctumUser($admin);
        $respPagoGen = $this->apiJson('POST', '/api/compras/pagos-general', [
            'proveedor_id' => $proveedorId,
            'fecha_pago' => now()->toDateString(),
            'monto' => 10000.0,
            'metodo_pago' => 'efectivo',
            'caja_id' => $caja->id,
        ]);
        $this->trackConfirmationEffects($compra1);
        $respPagoGen->assertStatus(200);

        $anticipoId = (int) $respPagoGen->json('data.anticipo.id');
        $movCajaId = (int) $respPagoGen->json('data.movimiento_caja_id');
        if ($anticipoId > 0) {
            $this->trackIdForCleanup('anticipos_proveedor', $anticipoId, 'id', AnticipoProveedor::class);
        }
        if ($movCajaId > 0) {
            $this->trackIdForCleanup('movimientos_caja', $movCajaId, 'id', MovimientoCaja::class);
        }

        $this->assertEquals(4000.0, (float) $proveedor->fresh()->saldo_anticipo);

        // 2. Crear nueva compra a crédito por $4.000 para el mismo proveedor
        [$compra2Id, $compra2] = $this->createAndConfirmCreditoCompra($proveedorId, 4000.0);

        // 3. Aplicar el anticipo generado por excedente a la nueva compra
        $respAplicar = $this->apiJson('POST', "/api/compras/{$compra2Id}/aplicar-anticipo", [
            'monto' => 4000.0,
            'observacion' => 'Aplicación de saldo excedente a nueva compra',
        ]);
        $this->trackConfirmationEffects($compra2);

        $respAplicar->assertStatus(200);
        $respAplicar->assertJson([
            'status' => true,
            'data' => [
                'compra_id' => $compra2Id,
                'monto_aplicado' => 4000.0,
                'nuevo_saldo_pendiente' => 0.0,
                'estado_pago' => 'pagado',
                'saldo_a_favor_restante' => 0.0,
            ],
        ]);

        // Anticipo por excedente pasa a 'agotado'
        $this->assertDatabaseHas('anticipos_proveedor', [
            'id' => $anticipoId,
            'saldo_disponible' => 0.0,
            'estado' => 'agotado',
        ]);

        // Nueva compra queda completamente pagada
        $this->assertDatabaseHas('compras', [
            'id' => $compra2Id,
            'total_pagado' => 4000.0,
            'saldo_pendiente' => 0.0,
            'estado_pago' => 'pagado',
        ]);

        $this->assertEquals(0.0, (float) $proveedor->fresh()->saldo_anticipo);
    }

    // =========================================================================
    // SECCIÓN E: CONCILIACIÓN INTEGRAL
    // =========================================================================

    /**
     * CASO 23: Conciliación integral de saldos y ausencia de nuevo egreso en aplicación.
     */
    public function test_23_conciliacion_integral_de_saldos_y_ausencia_de_egreso_en_aplicacion(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $proveedorId = $this->createExclusiveProveedor();
        $proveedor = Proveedor::find($proveedorId);
        $caja = $this->createOpenCajaWithFunds('efectivo', 50000.0);

        // 1. Registro de anticipo directo por $20.000
        $this->actingAsSanctumUser($admin);
        $respAnticipo = $this->apiJson('POST', '/api/compras/anticipos-directos', [
            'proveedor_id' => $proveedorId,
            'caja_id' => $caja->id,
            'monto' => 20000.0,
            'metodo_pago' => 'efectivo',
        ]);
        $anticipoId = (int) $respAnticipo->json('data.anticipo_id');
        $movCajaId = (int) $respAnticipo->json('data.movimiento_caja_id');
        if ($anticipoId > 0) {
            $this->trackIdForCleanup('anticipos_proveedor', $anticipoId, 'id', AnticipoProveedor::class);
        }
        if ($movCajaId > 0) {
            $this->trackIdForCleanup('movimientos_caja', $movCajaId, 'id', MovimientoCaja::class);
        }

        // 2. Creación y confirmación de compra a crédito por $12.000
        [$compraId, $compra] = $this->createAndConfirmCreditoCompra($proveedorId, 12000.0);

        // Conteo de movimientos de caja inmediatamente antes de aplicar el anticipo
        $movCajaCountAntesDeAplicar = DB::table('movimientos_caja')->count();

        // 3. Aplicación parcial del anticipo por $7.000
        $respAplicar = $this->apiJson('POST', "/api/compras/{$compraId}/aplicar-anticipo", [
            'monto' => 7000.0,
        ]);
        $this->trackConfirmationEffects($compra);
        $respAplicar->assertStatus(200);

        // REGLA CRÍTICA: La aplicación NO debe crear un nuevo movimiento en movimientos_caja
        $this->assertSame(
            $movCajaCountAntesDeAplicar,
            DB::table('movimientos_caja')->count(),
            'La aplicación de un saldo a favor NO debe generar ningún movimiento de caja adicional.'
        );

        // 4. Conciliación exacta de saldos:
        $proveedorActualizado = Proveedor::find($proveedorId);
        $compraActualizada = Compra::find($compraId);
        $anticipoActualizado = AnticipoProveedor::find($anticipoId);

        // proveedores.saldo_anticipo == 13000.0
        $this->assertEquals(13000.0, (float) $proveedorActualizado->saldo_anticipo);

        // SUM(anticipos_proveedor.saldo_disponible) == 13000.0
        $sumAnticipos = (float) DB::table('anticipos_proveedor')
            ->where('proveedor_id', $proveedorId)
            ->sum('saldo_disponible');
        $this->assertEquals(13000.0, $sumAnticipos);
        $this->assertEquals((float) $proveedorActualizado->saldo_anticipo, $sumAnticipos);

        // anticipos_proveedor.saldo_disponible individual == 13000.0
        $this->assertEquals(13000.0, (float) $anticipoActualizado->saldo_disponible);
        $this->assertSame('disponible', $anticipoActualizado->estado);

        // compras.total_pagado == 7000.0
        $this->assertEquals(7000.0, (float) $compraActualizada->total_pagado);

        // compras.saldo_pendiente == 5000.0
        $this->assertEquals(5000.0, (float) $compraActualizada->saldo_pendiente);
        $this->assertSame('pendiente', $compraActualizada->estado_pago);

        // aplicaciones_anticipo_compra registrada correctamente
        $this->assertDatabaseHas('aplicaciones_anticipo_compra', [
            'anticipo_proveedor_id' => $anticipoId,
            'compra_id' => $compraId,
            'monto_aplicado' => 7000.0,
            'saldo_anterior' => 20000.0,
            'saldo_nuevo' => 13000.0,
            'estado' => 'aplicada',
        ]);
    }
}

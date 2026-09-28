<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\User;
use App\Models\ConfiguracionEmpresa;
use App\Models\ConfiguracionFacturacion;
use App\Models\ResolucionFacturacion;
use App\Modules\Ventas\Application\Services\VentaService;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasInvoiceMapper;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FacturacionElectronicaVentasDryRunTest extends TestCase
{
    use RefreshDatabase;

    protected VentaService $ventaService;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ventaService = app(VentaService::class);
        $this->testUser = User::create([
            'name' => 'Tester Islero',
            'email' => 'test' . uniqid() . '@gasstation.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Caso A: Venta con factura_electronica = true y cliente real.
     * Debe producir un SolicitudFacturaDTO y payload de MATIAS con los datos reales del cliente.
     */
    public function test_caso_a_factura_electronica_true_con_cliente_real()
    {
        $empresa = ConfiguracionEmpresa::create([
            'nombre_empresa' => 'ESTACION PRUEBAS S.A.S',
            'nit' => '900123456',
            'dv' => '1',
            'prefijo_factura' => 'SETP',
            'numero_resolucion' => '18764074347312',
        ]);

        ResolucionFacturacion::create([
            'configuracion_empresa_id' => $empresa->id,
            'tipo_documento' => 'factura',
            'prefijo' => 'SETP',
            'numero_resolucion' => '18764074347312',
            'rango_desde' => 1,
            'rango_hasta' => 10000,
            'consecutivo_actual' => 100,
            'fecha_resolucion' => '2026-01-01',
            'fecha_vencimiento' => '2027-01-01',
            'is_active' => true,
        ]);

        ConfiguracionFacturacion::create([
            'facturacion_activa' => true,
            'facturacion_electronica_activa' => true,
            'facturar_ventas_pos' => true,
            'facturar_ventas_combustible' => true,
            'proveedor_activo' => 'matias',
            'ambiente' => 'sandbox',
        ]);

        $clienteReal = Cliente::create([
            'nombre' => 'JUAN ALBERTO',
            'apellidos' => 'PEREZ GOMEZ',
            'documento' => '1089123456',
            'email' => 'juan.perez@emailreal.com',
            'telefono_uno' => '3159876543',
            'direccion' => 'Carrera 15 # 45-20',
            'is_active' => true,
            'tipo_persona' => '2',
            'tipo_documento_id' => '13',
            'tipo_organization_id' => 2,
            'tax_regime_id' => 2,
            'tax_level_id' => 5,
        ]);

        $venta = Venta::create([
            'prefijo' => 'SETP',
            'numero_factura' => '2005',
            'cliente_id' => $clienteReal->id,
            'user_id' => $this->testUser->id,
            'tipo_venta' => 'contado',
            'tipo_origen' => 'pos',
            'factura_electronica' => true,
            'estado' => 'confirmada',
            'estado_pago' => 'pagado',
            'subtotal' => 50000,
            'total' => 50000,
            'fecha_venta' => now(),
        ]);

        $reflection = new \ReflectionClass(VentaService::class);
        $method = $reflection->getMethod('construirSolicitudFacturaDTO');
        $method->setAccessible(true);

        $solicitudDTO = $method->invoke($this->ventaService, $venta, $empresa, ConfiguracionFacturacion::first(), ResolucionFacturacion::first());

        $this->assertEquals('JUAN ALBERTO PEREZ GOMEZ', $solicitudDTO->cliente['company_name']);
        $this->assertEquals('1089123456', $solicitudDTO->cliente['dni']);
        $this->assertEquals('juan.perez@emailreal.com', $solicitudDTO->cliente['email']);
        $this->assertEquals('3159876543', $solicitudDTO->cliente['telefono']);
        $this->assertEquals('Carrera 15 # 45-20', $solicitudDTO->cliente['direccion']);

        // Mapeo hacia el payload de MATIAS API
        $payloadMatias = MatiasInvoiceMapper::toMatiasPayload($solicitudDTO);

        $this->assertEquals(2005, $payloadMatias['document_number']);
        $this->assertEquals('18764074347312', $payloadMatias['resolution_number']);
        $this->assertEquals('SETP', $payloadMatias['prefix']);
        $this->assertEquals('JUAN ALBERTO PEREZ GOMEZ', $payloadMatias['customer']['company_name']);
        $this->assertEquals('1089123456', $payloadMatias['customer']['dni']);
        $this->assertEquals('juan.perez@emailreal.com', $payloadMatias['customer']['email']);
        $this->assertEquals('3159876543', $payloadMatias['customer']['mobile']);
        $this->assertEquals('Carrera 15 # 45-20', $payloadMatias['customer']['address']);
    }

    /**
     * Caso B: Venta con factura_electronica = true y cliente = null.
     * Debe ser rechazada con excepción 422.
     */
    public function test_caso_b_factura_electronica_true_sin_cliente_es_rechazada()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Se requiere un cliente registrado para emitir una factura electrónica a nombre de un adquirente.');

        $venta = Venta::create([
            'prefijo' => 'SETP',
            'numero_factura' => '2006',
            'cliente_id' => null,
            'user_id' => $this->testUser->id,
            'tipo_venta' => 'contado',
            'tipo_origen' => 'pos',
            'factura_electronica' => true,
            'estado' => 'confirmada',
            'estado_pago' => 'pagado',
            'subtotal' => 30000,
            'total' => 30000,
            'fecha_venta' => now(),
        ]);

        $reflection = new \ReflectionClass(VentaService::class);
        $method = $reflection->getMethod('construirSolicitudFacturaDTO');
        $method->setAccessible(true);

        $method->invoke($this->ventaService, $venta, null, null, null);
    }

    /**
     * Caso C: Venta con factura_electronica = false y cliente = null sin facturación global.
     * No procesa facturación electrónica.
     */
    public function test_caso_c_factura_electronica_false_sin_facturacion_global()
    {
        ConfiguracionFacturacion::create([
            'facturacion_activa' => true,
            'facturacion_electronica_activa' => true,
            'facturar_ventas_pos' => false,
            'facturar_ventas_combustible' => false,
        ]);

        $venta = Venta::create([
            'prefijo' => 'POS',
            'numero_factura' => '2007',
            'cliente_id' => null,
            'user_id' => $this->testUser->id,
            'tipo_venta' => 'contado',
            'tipo_origen' => 'pos',
            'factura_electronica' => false,
            'estado' => 'confirmada',
            'estado_pago' => 'pagado',
            'subtotal' => 15000,
            'total' => 15000,
            'fecha_venta' => now(),
        ]);

        $resultado = $this->ventaService->procesarFacturacionElectronica($venta);
        $this->assertNull($resultado);
    }

    /**
     * Caso D: Venta con factura_electronica = false pero con configuración global activa.
     * Mantiene el comportamiento actual de consumidor final.
     */
    public function test_caso_d_factura_electronica_false_con_facturacion_global_usa_consumidor_final()
    {
        $empresa = ConfiguracionEmpresa::create([
            'nombre_empresa' => 'ESTACION PRUEBAS S.A.S',
            'nit' => '900123456',
            'dv' => '1',
            'prefijo_factura' => 'SETP',
            'numero_resolucion' => '18764074347312',
        ]);

        ResolucionFacturacion::create([
            'configuracion_empresa_id' => $empresa->id,
            'tipo_documento' => 'factura',
            'prefijo' => 'SETP',
            'numero_resolucion' => '18764074347312',
            'rango_desde' => 1,
            'rango_hasta' => 10000,
            'consecutivo_actual' => 100,
            'fecha_resolucion' => '2026-01-01',
            'fecha_vencimiento' => '2027-01-01',
            'is_active' => true,
        ]);

        ConfiguracionFacturacion::create([
            'facturacion_activa' => true,
            'facturacion_electronica_activa' => true,
            'facturar_ventas_pos' => true,
            'facturar_ventas_combustible' => true,
            'proveedor_activo' => 'matias',
            'ambiente' => 'sandbox',
        ]);

        $venta = Venta::create([
            'prefijo' => 'SETP',
            'numero_factura' => '2008',
            'cliente_id' => null,
            'user_id' => $this->testUser->id,
            'tipo_venta' => 'contado',
            'tipo_origen' => 'pos',
            'factura_electronica' => false,
            'estado' => 'confirmada',
            'estado_pago' => 'pagado',
            'subtotal' => 20000,
            'total' => 20000,
            'fecha_venta' => now(),
        ]);

        $reflection = new \ReflectionClass(VentaService::class);
        $method = $reflection->getMethod('construirSolicitudFacturaDTO');
        $method->setAccessible(true);

        $solicitudDTO = $method->invoke($this->ventaService, $venta, $empresa, ConfiguracionFacturacion::first(), ResolucionFacturacion::first());

        $this->assertEquals('CONSUMIDOR FINAL', $solicitudDTO->cliente['company_name']);
        $this->assertEquals('222222222222', $solicitudDTO->cliente['dni']);

        $payloadMatias = MatiasInvoiceMapper::toMatiasPayload($solicitudDTO);

        $this->assertEquals('CONSUMIDOR FINAL', $payloadMatias['customer']['company_name']);
        $this->assertEquals('222222222222', $payloadMatias['customer']['dni']);
        $this->assertEquals(2008, $payloadMatias['document_number']);
        $this->assertEquals('18764074347312', $payloadMatias['resolution_number']);
        $this->assertEquals('SETP', $payloadMatias['prefix']);
    }
}

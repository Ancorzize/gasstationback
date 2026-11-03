<?php

namespace Tests\Support;

use App\Models\AnticipoProveedor;
use App\Models\AplicacionAnticipoCompra;
use App\Models\Bodega;
use App\Models\Caja;
use App\Models\CategoriaProducto;
use App\Models\Ciudad;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DocumentoElectronico;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\PagoCompra;
use App\Models\Pais;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

/**
 * TestEntityBuilder
 *
 * Constructor seguro de entidades de prueba para la API de GASSTATION.
 * - Asigna identificadores y marcas únicas con prefijo TEST_.
 * - Registra cada entidad persistida individualmente en la pila LIFO de CleanableTestTrait.
 * - Garantiza aislamiento total de inventario: SIEMPRE crea productos, bodegas
 *   y cajas dedicadas de prueba, NUNCA reutiliza ni altera registros preexistentes.
 * - Valida estrictamente la existencia de catálogos maestros de solo lectura sin crearlos ni sembrarlos.
 * - Ofrece el método trackConfirmationEffects() para registrar explícitamente en el stack
 *   todos los efectos secundarios creados por servicios mediante relaciones y FKs directas.
 */
class TestEntityBuilder
{
    protected BaseApiTestCase $testCase;

    /**
     * Registro de existencia previa de inventarios antes de la confirmación de compras.
     * Formato: ["{compra_id}:{bodega_id}:{producto_id}" => bool]
     */
    protected array $preExistingInventarios = [];

    public function __construct(BaseApiTestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Retorna el token único de la ejecución actual.
     */
    public function getTestRunId(): string
    {
        return $this->testCase->getTestRunId();
    }

    /**
     * Genera un identificador con prefijo único.
     */
    public function makeIdentifier(string $prefix = 'TEST'): string
    {
        return $this->testCase->generateTestIdentifier($prefix);
    }

    /**
     * Crea un usuario de prueba autenticable vía Sanctum con permisos Spatie verificados.
     *
     * @param array $attributes Atributos opcionales para sobreescribir
     * @param array<string> $permissions Nombres de permisos a otorgar (deben existir previamente)
     * @return User
     */
    public function createTestUser(array $attributes = [], array $permissions = []): User
    {
        $unique = bin2hex(random_bytes(3));

        $data = array_merge([
            'name' => 'Usuario Test ' . $unique,
            'email' => 'test_' . $unique . '_' . $this->getTestRunId() . '@gasstation.test',
            'password' => Hash::make('SecretTest123!'),
            'is_active' => true,
        ], $attributes);

        $user = User::create($data);
        $this->testCase->trackForCleanup($user, "User #{$user->id} ({$user->email})");

        // Otorgar exclusivamente permisos preexistentes verificando el guard 'sanctum'
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'sanctum')
                ->first();

            if (!$permission) {
                throw new \InvalidArgumentException(
                    "SEGURIDAD SPATIE: El permiso '{$permissionName}' no existe registrado en la base de datos para el guard 'sanctum'. " .
                    "No se permite crear ni inventar permisos arbitrarios."
                );
            }

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        return $user;
    }

    /**
     * Crea un proveedor de prueba con NIT y nombre únicos, validando catálogos maestros.
     */
    public function createProveedor(array $attributes = []): Proveedor
    {
        // Validación estricta de catálogos maestros (solo lectura, sin creación automática)
        $ciudad = Ciudad::first();
        if (!$ciudad) {
            throw new \RuntimeException(
                "CONFIGURACIÓN MAESTRA REQUERIDA: No existe ninguna Ciudad en la tabla 'ciudades'. " .
                "Se requiere que la base de datos cuente con los catálogos maestros base de ubicaciones."
            );
        }

        $pais = Pais::first();
        if (!$pais) {
            throw new \RuntimeException(
                "CONFIGURACIÓN MAESTRA REQUERIDA: No existe ningún País en la tabla 'paises'. " .
                "Se requiere que la base de datos cuente con los catálogos maestros base de ubicaciones."
            );
        }

        $unique = bin2hex(random_bytes(3));

        $data = array_merge([
            'nombre' => 'TEST_PROV_' . $unique . '_' . $this->getTestRunId(),
            'nit' => 'NIT-' . substr(time(), -5) . $unique,
            'telefono' => '310' . rand(1000000, 9999999),
            'direccion' => 'Calle de Prueba #' . rand(1, 99),
            'email' => 'prov_' . $unique . '@gasstation.test',
            'is_active' => true,
            'saldo_anticipo' => 0.00,
            'tipo_persona' => 'Juridica',
            'ciudad_id' => $ciudad->id,
            'pais_id' => $pais->id,
        ], $attributes);

        $proveedor = Proveedor::create($data);

        return $this->testCase->trackForCleanup($proveedor, "Proveedor #{$proveedor->id} ({$proveedor->nombre})");
    }

    /**
     * Crea una bodega aislada y exclusiva de prueba.
     * NUNCA reutiliza bodegas operativas preexistentes para proteger los inventarios.
     */
    public function createTestBodega(array $attributes = []): Bodega
    {
        $unique = bin2hex(random_bytes(3));

        $data = array_merge([
            'nombre' => 'TEST_BODEGA_' . $unique . '_' . $this->getTestRunId(),
            'codigo' => 'BOD_' . $unique,
            'descripcion' => 'Bodega aislada de prueba ' . $this->getTestRunId(),
            'direccion' => 'Zona de Pruebas',
            'telefono' => '1234567',
            'is_principal' => false,
            'is_active' => true,
        ], $attributes);

        $bodega = Bodega::create($data);

        return $this->testCase->trackForCleanup($bodega, "Bodega #{$bodega->id} ({$bodega->codigo})");
    }

    /**
     * Crea un producto aislado y exclusivo de prueba, validando catálogos maestros.
     * NUNCA reutiliza productos del catálogo comercial para evitar alteraciones de stock.
     */
    public function createTestProducto(array $attributes = []): Producto
    {
        // Validación estricta de catálogos maestros (solo lectura, sin creación automática)
        $marca = Marca::first();
        if (!$marca) {
            throw new \RuntimeException(
                "CONFIGURACIÓN MAESTRA REQUERIDA: No existe ninguna Marca en la tabla 'marcas'. " .
                "Se requiere que la base de datos cuente con catálogos maestros iniciales."
            );
        }

        $categoria = CategoriaProducto::first();
        if (!$categoria) {
            throw new \RuntimeException(
                "CONFIGURACIÓN MAESTRA REQUERIDA: No existe ninguna Categoría en la tabla 'categorias_producto'. " .
                "Se requiere que la base de datos cuente con catálogos maestros iniciales."
            );
        }

        $unidad = UnidadMedida::first();
        if (!$unidad) {
            throw new \RuntimeException(
                "CONFIGURACIÓN MAESTRA REQUERIDA: No existe ninguna Unidad de Medida en la tabla 'unidades_medida'. " .
                "Se requiere que la base de datos cuente con catálogos maestros iniciales."
            );
        }

        $unique = bin2hex(random_bytes(3));

        $data = array_merge([
            'codigo' => 'PROD_' . $unique . '_' . substr(time(), -4),
            'nombre' => 'TEST_PRODUCTO_' . $unique . '_' . $this->getTestRunId(),
            'descripcion' => 'Producto aislado de prueba ' . $this->getTestRunId(),
            'marca_id' => $marca->id,
            'categoria_producto_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'precio_compra' => 5000.00,
            'precio_venta' => 8000.00,
            'permite_decimal' => false,
            'is_active' => true,
        ], $attributes);

        $producto = Producto::create($data);

        return $this->testCase->trackForCleanup($producto, "Producto #{$producto->id} ({$producto->codigo})");
    }

    /**
     * Crea una caja aislada y exclusiva de prueba en estado abierta.
     * NUNCA reutiliza cajas reales para evitar alterar saldos ni arqueos operativos.
     */
    public function createTestCaja(string $tipoCaja = 'efectivo', array $attributes = []): Caja
    {
        $user = User::first()?->id ?? 1;
        $unique = bin2hex(random_bytes(3));

        $data = array_merge([
            'nombre' => 'TEST_CAJA_' . $unique . '_' . $this->getTestRunId(),
            'tipo_caja' => $tipoCaja,
            'fecha_apertura' => now(),
            'monto_apertura' => 500000.00,
            'monto_cierre_sistema' => 0.00,
            'monto_cierre_real' => 0.00,
            'diferencia_cierre' => 0.00,
            'estado' => 'abierta',
            'user_apertura_id' => $user,
            'observacion_apertura' => 'Caja aislada de prueba ' . $this->getTestRunId(),
        ], $attributes);

        $caja = Caja::create($data);

        return $this->testCase->trackForCleanup($caja, "Caja #{$caja->id} ({$caja->nombre})");
    }

    /**
     * Crea una compra de prueba en estado borrador vinculada a una bodega aislada.
     */
    public function createCompra(Proveedor $proveedor, array $attributes = []): Compra
    {
        // Garantizar bodega aislada de prueba si no se proporciona explícitamente
        $bodegaId = $attributes['bodega_id'] ?? $this->createTestBodega()->id;
        $user = $attributes['user_id'] ?? User::first()?->id ?? 1;
        $unique = bin2hex(random_bytes(3));

        $subtotal = $attributes['subtotal'] ?? 10000.00;
        $impuesto = $attributes['impuesto'] ?? 0.00;
        $total = $attributes['total'] ?? ($subtotal + $impuesto);

        $data = array_merge([
            'proveedor_id' => $proveedor->id,
            'bodega_id' => $bodegaId,
            'user_id' => $user,
            'numero_documento' => 'TEST-FAC-' . $unique . '-' . substr(time(), -4),
            'fecha_compra' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'tipo_pago' => 'contado',
            'estado' => 'borrador',
            'estado_pago' => 'pendiente',
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'soldicom' => 0.00,
            'sobre_tasa' => 0.00,
            'total' => $total,
            'total_pagado' => 0.00,
            'saldo_pendiente' => $total,
            'observacion' => 'Compra de prueba ' . $this->getTestRunId(),
            'documento_soporte_electronico' => false,
        ], $attributes);

        $compra = Compra::create($data);

        return $this->testCase->trackForCleanup($compra, "Compra #{$compra->id} ({$compra->numero_documento})");
    }

    /**
     * Crea un detalle de compra vinculado a un producto aislado de prueba.
     */
    public function createDetalleCompra(Compra $compra, ?Producto $producto = null, array $attributes = []): DetalleCompra
    {
        // Garantizar producto aislado de prueba si no se pasa uno explícito
        $prod = $producto ?? $this->createTestProducto();
        $cantidad = $attributes['cantidad'] ?? 1.00;
        $costoUnitario = $attributes['costo_unitario'] ?? $compra->subtotal;
        $subtotal = $attributes['subtotal'] ?? ($cantidad * $costoUnitario);
        $total = $attributes['total'] ?? $subtotal;

        $data = array_merge([
            'compra_id' => $compra->id,
            'producto_id' => $prod->id,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'subtotal' => $subtotal,
            'iva' => 0,
            'soldicom' => 0.00,
            'total' => $total,
            'iva_valor' => 0.00,
            'sobre_tasa' => 0.00,
        ], $attributes);

        $detalle = DetalleCompra::create($data);

        // Capturar automáticamente el estado previo de inventarios antes de cualquier confirmación
        $key = "{$compra->id}:{$compra->bodega_id}:{$prod->id}";
        $this->preExistingInventarios[$key] = DB::table('inventarios')
            ->where('bodega_id', $compra->bodega_id)
            ->where('producto_id', $prod->id)
            ->exists();

        return $this->testCase->trackForCleanup($detalle, "DetalleCompra #{$detalle->id} (Compra #{$compra->id})");
    }

    /**
     * Registra explícitamente el estado previo de inventarios de una compra antes de su confirmación.
     */
    public function snapshotPreConfirmationInventoryState(Compra $compra): void
    {
        $detalles = DB::table('detalle_compras')
            ->where('compra_id', $compra->id)
            ->get(['producto_id']);

        foreach ($detalles as $det) {
            $key = "{$compra->id}:{$compra->bodega_id}:{$det->producto_id}";
            $this->preExistingInventarios[$key] = DB::table('inventarios')
                ->where('bodega_id', $compra->bodega_id)
                ->where('producto_id', $det->producto_id)
                ->exists();
        }
    }

    /**
     * Crea un anticipo directo para un proveedor, vinculado a una caja de prueba.
     */
    public function createAnticipoProveedor(Proveedor $proveedor, array $attributes = []): AnticipoProveedor
    {
        $unique = bin2hex(random_bytes(3));
        $monto = $attributes['monto_original'] ?? 50000.00;
        $user = $attributes['user_id'] ?? User::first()?->id ?? 1;

        $data = array_merge([
            'numero' => 'TEST-ANT-' . $unique,
            'proveedor_id' => $proveedor->id,
            'caja_id' => $attributes['caja_id'] ?? null,
            'movimiento_caja_id' => $attributes['movimiento_caja_id'] ?? null,
            'pago_compra_id' => $attributes['pago_compra_id'] ?? null,
            'origen' => $attributes['origen'] ?? 'directo',
            'monto_original' => $monto,
            'saldo_disponible' => $attributes['saldo_disponible'] ?? $monto,
            'estado' => 'disponible',
            'metodo_pago' => 'efectivo',
            'referencia' => 'TEST REF ' . $unique,
            'observacion' => 'Anticipo test ' . $this->getTestRunId(),
            'user_id' => $user,
        ], $attributes);

        $anticipo = AnticipoProveedor::create($data);

        // Actualizar saldo_anticipo del proveedor de prueba
        $proveedor->increment('saldo_anticipo', $anticipo->saldo_disponible);

        return $this->testCase->trackForCleanup($anticipo, "AnticipoProveedor #{$anticipo->id} ({$anticipo->numero})");
    }

    /**
     * Crea una aplicación de anticipo sobre una compra.
     */
    public function createAplicacionAnticipo(
        AnticipoProveedor $anticipo,
        Compra $compra,
        float $monto,
        array $attributes = []
    ): AplicacionAnticipoCompra {
        $user = $attributes['user_id'] ?? User::first()?->id ?? 1;

        $data = array_merge([
            'anticipo_proveedor_id' => $anticipo->id,
            'compra_id' => $compra->id,
            'monto_aplicado' => $monto,
            'saldo_anterior' => $anticipo->saldo_disponible,
            'saldo_nuevo' => max(0, $anticipo->saldo_disponible - $monto),
            'estado' => 'aplicada',
            'observacion' => 'Aplicación de prueba ' . $this->getTestRunId(),
            'user_id' => $user,
        ], $attributes);

        $aplicacion = AplicacionAnticipoCompra::create($data);

        return $this->testCase->trackForCleanup($aplicacion, "AplicacionAnticipo #{$aplicacion->id}");
    }

    /**
     * Crea un pago de compra de prueba.
     */
    public function createPagoCompra(Compra $compra, array $attributes = []): PagoCompra
    {
        $user = $attributes['user_id'] ?? User::first()?->id ?? 1;
        $monto = $attributes['monto'] ?? $compra->saldo_pendiente;

        $data = array_merge([
            'compra_id' => $compra->id,
            'user_id' => $user,
            'fecha_pago' => now()->toDateString(),
            'monto' => $monto,
            'metodo_pago' => 'efectivo',
            'observacion' => 'Pago test ' . $this->getTestRunId(),
        ], $attributes);

        $pago = PagoCompra::create($data);

        return $this->testCase->trackForCleanup($pago, "PagoCompra #{$pago->id}");
    }

    /**
     * Crea un movimiento de caja vinculado a prueba.
     */
    public function createMovimientoCaja(Caja $caja, array $attributes = []): MovimientoCaja
    {
        $user = $attributes['user_id'] ?? User::first()?->id ?? 1;
        $monto = $attributes['monto'] ?? 10000.00;

        $data = array_merge([
            'caja_id' => $caja->id,
            'tipo_movimiento' => 'EGRESO',
            'categoria_movimiento' => 'COMPRA',
            'origen_modulo' => 'COMPRAS',
            'origen_id' => $attributes['origen_id'] ?? null,
            'medio_pago' => 'efectivo',
            'monto' => $monto,
            'descripcion' => 'Movimiento test ' . $this->getTestRunId(),
            'user_id' => $user,
            'fecha_movimiento' => now(),
        ], $attributes);

        $mov = MovimientoCaja::create($data);

        return $this->testCase->trackForCleanup($mov, "MovimientoCaja #{$mov->id}");
    }

    /**
     * Registra explícitamente en la pila LIFO todos los efectos secundarios creados indirectamente
     * al confirmar una compra de prueba (inventarios, kardex, pagos, movimientos de caja).
     *
     * Condiciones de seguridad obligatorias:
     * - La compra debe estar registrada previamente como propiedad de la ejecución actual.
     * - Se identifican los registros EXCLUSIVAMENTE mediante las FK y relaciones reales de la base de datos.
     * - No se utilizan fechas, nombres ni coincidencias difusas.
     * - Cada registro se agrega individualmente por su clave primaria ID en $cleanupStack.
     *
     * @param Compra $compra Compra de prueba previamente registrada
     * @return void
     */
    public function trackConfirmationEffects(Compra $compra): void
    {
        // 1. Verificación obligatoria de propiedad: La compra debe pertenecer a la prueba actual
        if (!$this->testCase->isAlreadyTracked('compras', $compra->id)) {
            throw new \InvalidArgumentException(
                "SEGURIDAD CRÍTICA: La compra ID {$compra->id} no pertenece a la ejecución actual de pruebas. " .
                "Operación de tracking de efectos secundarios detenida para evitar afectar datos ajenos."
            );
        }

        // Obtener los productos vinculados a los detalles de esta compra específica
        $productoIds = DB::table('detalle_compras')
            ->where('compra_id', $compra->id)
            ->pluck('producto_id')
            ->all();

        // 2. Rastrear registros de 'inventarios' creados EXCLUSIVAMENTE por esta prueba
        // Reglas estrictas:
        // - Se verifica contra el snapshot previo si la combinación producto_id + bodega_id ya existía antes de confirmar.
        // - ÚNICAMENTE se registran para eliminación las filas que NO existían antes y fueron creadas por la prueba.
        // - Si la fila ya existía previamente, NO se registra para eliminación y sus saldos no se tocan.
        // - Si no se puede determinar con certeza si una fila es nueva (falta snapshot), NO se elimina y se reporta el riesgo.
        if (!empty($productoIds) && !empty($compra->bodega_id)) {
            foreach ($productoIds as $prodId) {
                $key = "{$compra->id}:{$compra->bodega_id}:{$prodId}";

                if (!array_key_exists($key, $this->preExistingInventarios)) {
                    // No se tiene certeza del estado previo: no eliminar y reportar riesgo
                    $this->testCase->recordCleanupWarning(
                        'inventarios',
                        "No se pudo determinar con seguridad si la fila de inventarios (Prod #{$prodId}, Bod #{$compra->bodega_id}) es nueva (falta snapshot previo). No se registrará para eliminación."
                    );
                    continue;
                }

                $alreadyExistedBefore = $this->preExistingInventarios[$key];

                if ($alreadyExistedBefore) {
                    // La fila ya existía antes de la prueba: conservar intacta
                    continue;
                }

                // La fila NO existía antes de la confirmación: es 100% nueva creada por la prueba
                $invRow = DB::table('inventarios')
                    ->where('bodega_id', $compra->bodega_id)
                    ->where('producto_id', $prodId)
                    ->first(['id']);

                if ($invRow) {
                    $this->testCase->trackIdForCleanup(
                        'inventarios',
                        $invRow->id,
                        'id',
                        Inventario::class,
                        "Inventario #{$invRow->id} (Nuevo por Compra #{$compra->id}, Prod #{$prodId}, Bod #{$compra->bodega_id})"
                    );
                }
            }
        }

        // 3. Rastrear registros de 'movimientos_inventario' (kardex) generados inequívocamente por esta compra
        // Se valida relación exacta: tipo 'entrada_compra', bodega_destino_id, user_id, producto_id y observación exacta con el ID de la compra
        if (!empty($productoIds) && !empty($compra->bodega_id)) {
            $movInvIds = DB::table('movimientos_inventario')
                ->where('tipo_movimiento', 'entrada_compra')
                ->where('bodega_destino_id', $compra->bodega_id)
                ->whereNull('bodega_origen_id')
                ->where('observacion', 'Entrada por compra #' . $compra->id)
                ->where('user_id', $compra->user_id)
                ->whereIn('producto_id', $productoIds)
                ->pluck('id');

            foreach ($movInvIds as $mInvId) {
                $this->testCase->trackIdForCleanup(
                    'movimientos_inventario',
                    $mInvId,
                    'id',
                    MovimientoInventario::class,
                    "MovimientoInventario #{$mInvId} (Compra #{$compra->id})"
                );
            }
        }

        // 4. Rastrear registros de 'movimientos_caja' generados por la confirmación de esta compra
        $movCajaIds = DB::table('movimientos_caja')
            ->where('origen_modulo', 'compras')
            ->where('origen_id', $compra->id)
            ->pluck('id');

        foreach ($movCajaIds as $mCajaId) {
            $this->testCase->trackIdForCleanup(
                'movimientos_caja',
                $mCajaId,
                'id',
                MovimientoCaja::class,
                "MovimientoCaja #{$mCajaId} (Compra #{$compra->id})"
            );
        }

        // 5. Rastrear registros de 'pagos_compra' generados por la confirmación de esta compra
        $pagoIds = DB::table('pagos_compra')
            ->where('compra_id', $compra->id)
            ->pluck('id');

        foreach ($pagoIds as $pagoId) {
            $this->testCase->trackIdForCleanup(
                'pagos_compra',
                $pagoId,
                'id',
                PagoCompra::class,
                "PagoCompra #{$pagoId} (Compra #{$compra->id})"
            );
        }

        // 6. Rastrear 'aplicaciones_anticipo_compra' generadas para esta compra
        $appAnticipoIds = DB::table('aplicaciones_anticipo_compra')
            ->where('compra_id', $compra->id)
            ->pluck('id');

        foreach ($appAnticipoIds as $appId) {
            $this->testCase->trackIdForCleanup(
                'aplicaciones_anticipo_compra',
                $appId,
                'id',
                AplicacionAnticipoCompra::class,
                "AplicacionAnticipo #{$appId} (Compra #{$compra->id})"
            );
        }

        // 7. Rastrear documentos electrónicos vinculados directamente si existieran
        $docElecIds = DB::table('documentos_electronicos')
            ->where('compra_id', $compra->id)
            ->pluck('id');

        foreach ($docElecIds as $docId) {
            $this->testCase->trackIdForCleanup(
                'documentos_electronicos',
                $docId,
                'id',
                DocumentoElectronico::class,
                "DocumentoElectronico #{$docId} (Compra #{$compra->id})"
            );
        }
    }
}

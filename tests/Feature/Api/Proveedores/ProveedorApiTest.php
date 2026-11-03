<?php

namespace Tests\Feature\Api\Proveedores;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\BaseApiTestCase;

/**
 * Suite de Pruebas API del Módulo de Proveedores (Fase 3).
 *
 * Valida los endpoints de listado, creación, validación de duplicidad,
 * autorización por roles (admin, islero, vendedor), actualización
 * y cambio de estado sobre la base de pruebas segura PostgreSQL (gasstation_testing).
 */
class ProveedorApiTest extends BaseApiTestCase
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
     * Genera un payload válido y único para StoreProveedorRequest.
     */
    protected function makeUniqueProveedorPayload(array $overrides = []): array
    {
        $unique = bin2hex(random_bytes(3));

        return array_merge([
            'nombre' => 'TEST_PROV_' . $unique . '_' . $this->getTestRunId(),
            'nit' => 'NIT-' . substr(time(), -5) . $unique,
            'telefono' => '310' . rand(1000000, 9999999),
            'direccion' => 'Calle de Prueba #' . rand(1, 99),
            'email' => 'prov_' . $unique . '_' . $this->getTestRunId() . '@gasstation.test',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // CASO 1: Listar proveedores como administrador: HTTP 200
    // -------------------------------------------------------------------------

    public function test_listar_proveedores_como_administrador_retorna_200(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('GET', '/api/proveedores');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Listado de proveedores.',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'items',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 2: Listar proveedores como islero: HTTP 403
    // -------------------------------------------------------------------------

    public function test_listar_proveedores_como_islero_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);

        $this->actingAsSanctumUser($islero);
        $response = $this->apiJson('GET', '/api/proveedores');

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 3: Listar proveedores como vendedor: HTTP 403
    // -------------------------------------------------------------------------

    public function test_listar_proveedores_como_vendedor_retorna_403(): void
    {
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);

        $this->actingAsSanctumUser($vendedor);
        $response = $this->apiJson('GET', '/api/proveedores');

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 4: Crear proveedor como administrador: HTTP 201
    // -------------------------------------------------------------------------

    public function test_crear_proveedor_como_administrador_exitoso(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $payload = $this->makeUniqueProveedorPayload();

        $this->actingAsSanctumUser($admin);
        $response = $this->apiJson('POST', '/api/proveedores', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => true,
            'message' => 'Proveedor creado.',
            'data' => [
                'nombre' => $payload['nombre'],
                'nit' => $payload['nit'],
                'telefono' => $payload['telefono'],
                'direccion' => $payload['direccion'],
                'email' => $payload['email'],
                'is_active' => true,
            ],
        ]);

        $proveedorId = $response->json('data.id');
        $this->assertNotNull($proveedorId, 'El proveedor creado debe retornar un ID numérico.');

        // Registro explícito e inmediato para limpieza selectiva LIFO
        $this->trackIdForCleanup('proveedores', $proveedorId);

        // Verificación de persistencia en base de datos
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedorId,
            'nit' => $payload['nit'],
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 5: Intentar crear proveedor con NIT duplicado: HTTP 422
    // -------------------------------------------------------------------------

    public function test_intentar_crear_proveedor_con_nit_duplicado_retorna_422(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $payloadInicial = $this->makeUniqueProveedorPayload();

        $this->actingAsSanctumUser($admin);

        // 1. Crear el primer proveedor legítimo
        $primerResponse = $this->apiJson('POST', '/api/proveedores', $payloadInicial);
        $primerResponse->assertStatus(201);
        $primerProveedorId = $primerResponse->json('data.id');
        $this->assertNotNull($primerProveedorId);
        $this->trackIdForCleanup('proveedores', $primerProveedorId);

        // 2. Intentar crear un segundo proveedor con el mismo NIT
        $payloadDuplicado = $this->makeUniqueProveedorPayload([
            'nit' => $payloadInicial['nit'],
        ]);

        $duplicadoResponse = $this->apiJson('POST', '/api/proveedores', $payloadDuplicado);

        $duplicadoResponse->assertStatus(422);
        $duplicadoResponse->assertJsonValidationErrors(['nit']);
        $duplicadoResponse->assertJsonFragment([
            'nit' => ['El nit ya está registrado.'],
        ]);

        // Verificación de que no se persistió un registro duplicado
        $totalConMismoNit = DB::table('proveedores')->where('nit', $payloadInicial['nit'])->count();
        $this->assertSame(1, $totalConMismoNit, 'No debe existir más de un proveedor con el mismo NIT.');
    }

    // -------------------------------------------------------------------------
    // CASO 6: Intentar crear proveedor como islero: HTTP 403
    // -------------------------------------------------------------------------

    public function test_intentar_crear_proveedor_como_islero_retorna_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $payload = $this->makeUniqueProveedorPayload();

        $this->actingAsSanctumUser($islero);
        $response = $this->apiJson('POST', '/api/proveedores', $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // Verificación de no persistencia en base de datos
        $this->assertDatabaseMissing('proveedores', [
            'nit' => $payload['nit'],
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 7: Intentar crear proveedor como vendedor: HTTP 403
    // -------------------------------------------------------------------------

    public function test_intentar_crear_proveedor_como_vendedor_retorna_403(): void
    {
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);
        $payload = $this->makeUniqueProveedorPayload();

        $this->actingAsSanctumUser($vendedor);
        $response = $this->apiJson('POST', '/api/proveedores', $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // Verificación de no persistencia en base de datos
        $this->assertDatabaseMissing('proveedores', [
            'nit' => $payload['nit'],
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 8: Actualizar proveedor como administrador: comprobar comportamiento real
    // -------------------------------------------------------------------------

    public function test_actualizar_proveedor_como_administrador_comportamiento_real(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $payloadInicial = $this->makeUniqueProveedorPayload();

        $this->actingAsSanctumUser($admin);

        // 1. Crear proveedor de prueba exclusivo para la actualización
        $createResponse = $this->apiJson('POST', '/api/proveedores', $payloadInicial);
        $createResponse->assertStatus(201);
        $proveedorId = $createResponse->json('data.id');
        $this->assertNotNull($proveedorId);
        $this->trackIdForCleanup('proveedores', $proveedorId);

        // 2. Preparar datos actualizados para UpdateProveedorRequest
        $nuevoNombre = $payloadInicial['nombre'] . '_EDITADO';
        $nuevoTelefono = '3119876543';
        $nuevaDireccion = 'Avenida Actualizada # 45-67';
        $nuevoEmail = 'edit_' . bin2hex(random_bytes(3)) . '@gasstation.test';

        $updatePayload = [
            'nombre' => $nuevoNombre,
            'nit' => $payloadInicial['nit'], // Mismo NIT ignorado por Rule::unique
            'telefono' => $nuevoTelefono,
            'direccion' => $nuevaDireccion,
            'email' => $nuevoEmail,
        ];

        // 3. Ejecutar PUT /api/proveedores/{id}
        // NOTA DE AUDITORÍA: ProveedorController@update evalúa can('editar_clientes')
        // debido a código heredado. El usuario admin cuenta con dicho permiso en el seeder,
        // por lo que supera la verificación y retorna el mensaje 'Cliente actualizado correctamente.'
        $updateResponse = $this->apiJson('PUT', "/api/proveedores/{$proveedorId}", $updatePayload);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson([
            'status' => true,
            'message' => 'Cliente actualizado correctamente.',
            'data' => [
                'id' => $proveedorId,
                'nombre' => $nuevoNombre,
                'nit' => $payloadInicial['nit'],
                'telefono' => $nuevoTelefono,
                'direccion' => $nuevaDireccion,
                'email' => $nuevoEmail,
            ],
        ]);

        // Verificación de actualización en base de datos
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedorId,
            'nombre' => $nuevoNombre,
            'telefono' => $nuevoTelefono,
            'direccion' => $nuevaDireccion,
            'email' => $nuevoEmail,
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 9: Cambiar estado de proveedor como administrador: comprobar comportamiento real
    // -------------------------------------------------------------------------

    public function test_cambiar_estado_proveedor_como_administrador_comportamiento_real(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $payloadInicial = $this->makeUniqueProveedorPayload();

        $this->actingAsSanctumUser($admin);

        // 1. Crear proveedor de prueba exclusivo con estado inicial activo (true)
        $createResponse = $this->apiJson('POST', '/api/proveedores', $payloadInicial);
        $createResponse->assertStatus(201);
        $proveedorId = $createResponse->json('data.id');
        $this->assertNotNull($proveedorId);
        $this->trackIdForCleanup('proveedores', $proveedorId);

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedorId,
            'is_active' => true,
        ]);

        // 2. Ejecutar PATCH /api/proveedores/{id}/status con is_active = false
        // NOTA DE AUDITORÍA: ProveedorController@changeStatus evalúa can('editar_clientes')
        // y responde 'Estado del cliente actualizado correctamente.'
        $statusPayload = [
            'is_active' => false,
        ];

        $patchResponse = $this->apiJson('PATCH', "/api/proveedores/{$proveedorId}/status", $statusPayload);

        $patchResponse->assertStatus(200);
        $patchResponse->assertJson([
            'status' => true,
            'message' => 'Estado del cliente actualizado correctamente.',
            'data' => [
                'id' => $proveedorId,
                'is_active' => false,
            ],
        ]);

        // Verificación de cambio de estado en base de datos
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedorId,
            'is_active' => false,
        ]);
    }
}

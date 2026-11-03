<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Bomba;
use App\Models\Estacion;
use App\Models\Manguera;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BaseApiTestCase;

/**
 * Suite de Pruebas de Autenticación y Autorización API (Fase 2).
 *
 * Valida los flujos de login, logout, revocación de tokens de Sanctum,
 * consulta de permisos y diferenciación de accesos entre los roles
 * administrador, islero y vendedor sobre la base de pruebas segura PostgreSQL.
 */
class AuthenticationAndAuthorizationApiTest extends BaseApiTestCase
{
    /**
     * Contraseña estándar aprovisionada para los usuarios de pruebas.
     */
    protected const TEST_PASSWORD = 'TestingPass123*';

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
     * Registro de IDs de tokens de Sanctum creados durante las pruebas
     * para garantizar su revocación explícita y no dejar registros residuales.
     */
    protected array $createdTokenIds = [];

    /**
     * Registro de IDs de entidades de combustible temporales para limpieza LIFO estricta.
     */
    protected array $temporaryMangueraIds = [];
    protected array $temporaryBombaIds = [];
    protected array $temporaryEstacionIds = [];

    /**
     * Limpieza selectiva de tokens y entidades auxiliares antes de la limpieza general.
     */
    protected function tearDown(): void
    {
        try {
            if (!empty($this->createdTokenIds)) {
                DB::table('personal_access_tokens')
                    ->whereIn('id', $this->createdTokenIds)
                    ->delete();
                $this->createdTokenIds = [];
            }

            if (!empty($this->temporaryMangueraIds)) {
                DB::table('mangueras')
                    ->whereIn('id', $this->temporaryMangueraIds)
                    ->delete();
                $this->temporaryMangueraIds = [];
            }

            if (!empty($this->temporaryBombaIds)) {
                DB::table('bombas')
                    ->whereIn('id', $this->temporaryBombaIds)
                    ->delete();
                $this->temporaryBombaIds = [];
            }

            if (!empty($this->temporaryEstacionIds)) {
                DB::table('estaciones')
                    ->whereIn('id', $this->temporaryEstacionIds)
                    ->delete();
                $this->temporaryEstacionIds = [];
            }
        } finally {
            parent::tearDown();
        }
    }

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
     * Registra el token devuelto por la API para su eliminación en el tearDown.
     */
    protected function registerTokenForCleanup(?string $plainTextToken): void
    {
        if (empty($plainTextToken)) {
            return;
        }

        $parts = explode('|', $plainTextToken, 2);
        if (isset($parts[0]) && is_numeric($parts[0])) {
            $this->createdTokenIds[] = (int) $parts[0];
        }
    }

    /**
     * Obtiene una manguera existente o crea una efímera para la prueba,
     * registrándola para su eliminación segura en el tearDown.
     */
    protected function getOrCreateTestManguera(): Manguera
    {
        $manguera = Manguera::first();
        if ($manguera) {
            return $manguera;
        }

        $producto = $this->builder->createTestProducto();

        $unique = bin2hex(random_bytes(3));
        $estacion = Estacion::create([
            'codigo' => 'EST_' . $unique,
            'nombre' => 'Estacion Test ' . $unique,
            'is_active' => true,
        ]);
        $this->temporaryEstacionIds[] = $estacion->id;

        $bomba = Bomba::create([
            'estacion_id' => $estacion->id,
            'codigo' => 'BOM_' . $unique,
            'nombre' => 'Bomba Test ' . $unique,
            'is_active' => true,
        ]);
        $this->temporaryBombaIds[] = $bomba->id;

        $manguera = Manguera::create([
            'bomba_id' => $bomba->id,
            'producto_id' => $producto->id,
            'codigo' => 'MAN_' . $unique,
            'nombre' => 'Manguera Test ' . $unique,
            'is_active' => true,
        ]);
        $this->temporaryMangueraIds[] = $manguera->id;

        return $manguera;
    }

    // -------------------------------------------------------------------------
    // CASO 1: Login exitoso de administrador
    // -------------------------------------------------------------------------

    public function test_login_exitoso_administrador(): void
    {
        $this->getTestUser(self::ADMIN_EMAIL);

        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => self::ADMIN_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'user' => [
                    'email' => self::ADMIN_EMAIL,
                    'is_active' => true,
                ],
                'rol' => 'admin',
            ],
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token, 'El login de administrador debe retornar un token.');
        $this->registerTokenForCleanup($token);
    }

    // -------------------------------------------------------------------------
    // CASO 2: Login exitoso de islero
    // -------------------------------------------------------------------------

    public function test_login_exitoso_islero(): void
    {
        $this->getTestUser(self::ISLERO_EMAIL);

        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => self::ISLERO_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'user' => [
                    'email' => self::ISLERO_EMAIL,
                    'is_active' => true,
                ],
                'rol' => 'islero',
            ],
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token, 'El login de islero debe retornar un token.');
        $this->registerTokenForCleanup($token);
    }

    // -------------------------------------------------------------------------
    // CASO 3: Login exitoso de vendedor
    // -------------------------------------------------------------------------

    public function test_login_exitoso_vendedor(): void
    {
        $this->getTestUser(self::VENDEDOR_EMAIL);

        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => self::VENDEDOR_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'user' => [
                    'email' => self::VENDEDOR_EMAIL,
                    'is_active' => true,
                ],
                'rol' => 'vendedor',
            ],
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token, 'El login de vendedor debe retornar un token.');
        $this->registerTokenForCleanup($token);
    }

    // -------------------------------------------------------------------------
    // CASO 4: Login con credenciales incorrectas: 401
    // -------------------------------------------------------------------------

    public function test_login_con_credenciales_incorrectas_retorna_401(): void
    {
        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => self::ADMIN_EMAIL,
            'password' => 'PasswordCompletamenteErroneo123*',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => false,
            'message' => 'Credenciales incorrectas.',
            'errors' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 5: Login de usuario inactivo: 403
    // -------------------------------------------------------------------------

    public function test_login_de_usuario_inactivo_retorna_403(): void
    {
        // Se crea un usuario temporal inactivo mediante TestEntityBuilder
        // Queda registrado automáticamente en cleanupStack para su eliminación LIFO
        $inactiveUser = $this->builder->createTestUser([
            'is_active' => false,
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->apiJson('POST', '/api/auth/login', [
            'email' => $inactiveUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => false,
            'message' => 'El usuario está inactivo.',
            'errors' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 6: Acceso a ruta protegida sin token: 401
    // -------------------------------------------------------------------------

    public function test_acceso_a_ruta_protegida_sin_token_retorna_401(): void
    {
        $response = $this->apiJson('GET', '/api/auth/me');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 7: Acceso con token inválido: 401
    // -------------------------------------------------------------------------

    public function test_acceso_con_token_invalido_retorna_401(): void
    {
        $response = $this->apiJson('GET', '/api/auth/me', [], [
            'Authorization' => 'Bearer token_invalido_inexistente_999999',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 8: Logout y comprobación de revocación del token: 200 y luego 401
    // -------------------------------------------------------------------------

    public function test_logout_y_comprobacion_de_revocacion_del_token(): void
    {
        // 1. Iniciar sesión para obtener token real
        $loginResponse = $this->apiJson('POST', '/api/auth/login', [
            'email' => self::ADMIN_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);
        $this->registerTokenForCleanup($token);

        // 2. Verificar que el token es válido
        $meResponse = $this->apiJson('GET', '/api/auth/me', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $meResponse->assertStatus(200);

        // 3. Ejecutar Logout
        $logoutResponse = $this->apiJson('POST', '/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $logoutResponse->assertStatus(200);
        $logoutResponse->assertJson([
            'status' => true,
            'message' => 'Sesión cerrada correctamente.',
            'data' => null,
        ]);

        // Limpiar los guards de autenticación en memoria de Laravel
        $this->app['auth']->forgetGuards();

        // 4. Comprobar que el token fue revocado y ya no tiene acceso
        $revokedResponse = $this->apiJson('GET', '/api/auth/me', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $revokedResponse->assertStatus(401);
        $revokedResponse->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // -------------------------------------------------------------------------
    // CASO 9: Consulta de permisos: admin 124, islero 7 y vendedor 6
    // -------------------------------------------------------------------------

    public function test_consulta_de_permisos_admin_124_islero_7_y_vendedor_6(): void
    {
        // 1. Verificación para Administrador (124 permisos únicos de sanctum)
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $this->actingAsSanctumUser($admin);
        $adminResponse = $this->apiJson('GET', '/api/auth/me/permissions');

        $adminResponse->assertStatus(200);
        $adminPermissions = $adminResponse->json('data');
        $this->assertIsArray($adminPermissions);
        $this->assertCount(124, $adminPermissions, 'El rol admin debe poseer exactamente 124 permisos únicos.');
        $this->assertContains('ver_compras', $adminPermissions);
        $this->assertContains('crear_usuarios', $adminPermissions);
        $this->assertContains('vender_combustible', $adminPermissions);

        // 2. Verificación para Islero (7 permisos exactos)
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $this->actingAsSanctumUser($islero);
        $isleroResponse = $this->apiJson('GET', '/api/auth/me/permissions');

        $isleroResponse->assertStatus(200);
        $isleroPermissions = $isleroResponse->json('data');
        $this->assertIsArray($isleroPermissions);
        $this->assertCount(7, $isleroPermissions, 'El rol islero debe poseer exactamente 7 permisos.');

        $expectedIsleroPermissions = [
            'ver_turnos_islero',
            'abrir_turnos_islero',
            'cerrar_turnos_islero',
            'vender_combustible',
            'vender_lubricantes',
            'crear_ventas',
            'registrar_abonos_cartera',
        ];
        foreach ($expectedIsleroPermissions as $expectedPerm) {
            $this->assertContains($expectedPerm, $isleroPermissions);
        }

        // 3. Verificación para Vendedor (6 permisos exactos, sin vender_combustible)
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);
        $this->actingAsSanctumUser($vendedor);
        $vendedorResponse = $this->apiJson('GET', '/api/auth/me/permissions');

        $vendedorResponse->assertStatus(200);
        $vendedorPermissions = $vendedorResponse->json('data');
        $this->assertIsArray($vendedorPermissions);
        $this->assertCount(6, $vendedorPermissions, 'El rol vendedor debe poseer exactamente 6 permisos.');

        $expectedVendedorPermissions = [
            'ver_turnos_islero',
            'abrir_turnos_islero',
            'cerrar_turnos_islero',
            'vender_lubricantes',
            'crear_ventas',
            'registrar_abonos_cartera',
        ];
        foreach ($expectedVendedorPermissions as $expectedPerm) {
            $this->assertContains($expectedPerm, $vendedorPermissions);
        }
        $this->assertNotContains(
            'vender_combustible',
            $vendedorPermissions,
            'El rol vendedor NO debe tener el permiso vender_combustible.'
        );
    }

    // -------------------------------------------------------------------------
    // CASO 10: Diferenciación de permiso vender_combustible: islero autorizado y vendedor 403
    // -------------------------------------------------------------------------

    public function test_diferenciacion_de_permiso_vender_combustible_islero_autorizado_y_vendedor_403(): void
    {
        $islero = $this->getTestUser(self::ISLERO_EMAIL);
        $vendedor = $this->getTestUser(self::VENDEDOR_EMAIL);

        // Verificación estricta a nivel Spatie RBAC
        $this->assertTrue(
            $islero->can('vender_combustible'),
            'El usuario islero debe tener el permiso vender_combustible.'
        );
        $this->assertFalse(
            $vendedor->can('vender_combustible'),
            'El usuario vendedor NO debe tener el permiso vender_combustible.'
        );

        // 1. Islero: Se envía petición incompleta para evitar registrar una venta comercial real
        // Al estar autorizado por Sanctum y contar con el permiso, NO debe responder 403
        $this->actingAsSanctumUser($islero);
        $responseIslero = $this->apiJson('POST', '/api/ventas/combustible', []);

        $this->assertNotEquals(
            403,
            $responseIslero->getStatusCode(),
            'El islero cuenta con el permiso vender_combustible y no debe ser rechazado con 403.'
        );
        $this->assertEquals(
            422,
            $responseIslero->getStatusCode(),
            'La petición incompleta debe ser rechazada por validación (422) sin registrar ventas.'
        );

        // 2. Vendedor: Petición HTTP real con el payload mínimo válido de StoreVentaCombustibleRequest
        // Supera la validación del FormRequest y alcanza el controlador, donde la primera instrucción
        // evalúa can('vender_combustible') y aborta inmediatamente con 403 antes de cualquier escritura
        $manguera = $this->getOrCreateTestManguera();

        $payloadValido = [
            'manguera_id' => $manguera->id,
            'tipo_venta' => 'contado',
            'metodo_pago' => 'efectivo',
            'monto' => 1000.00,
        ];

        $ventasAntes = DB::table('ventas')->where('user_id', $vendedor->id)->count();

        $this->actingAsSanctumUser($vendedor);
        $responseVendedor = $this->apiJson('POST', '/api/ventas/combustible', $payloadValido);

        $responseVendedor->assertStatus(403);
        $responseVendedor->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);

        // Verificación de no persistencia mediante comparación estricta de conteo
        $ventasDespues = DB::table('ventas')->where('user_id', $vendedor->id)->count();
        $this->assertSame(
            $ventasAntes,
            $ventasDespues,
            'La petición rechazada por falta de permisos no debe crear registros de venta.'
        );
    }

    // -------------------------------------------------------------------------
    // CASO 11: Diferenciación de permiso administrativo: admin autorizado e islero 403
    // -------------------------------------------------------------------------

    public function test_diferenciacion_de_permiso_administrativo_admin_autorizado_e_islero_403(): void
    {
        $admin = $this->getTestUser(self::ADMIN_EMAIL);
        $islero = $this->getTestUser(self::ISLERO_EMAIL);

        // 1. Administrador accede a ruta de solo lectura 'ver_compras' (GET /api/compras)
        $this->actingAsSanctumUser($admin);
        $adminResponse = $this->apiJson('GET', '/api/compras');

        $adminResponse->assertStatus(200);
        $adminResponse->assertJson([
            'status' => true,
        ]);

        // 2. Islero intenta acceder a ruta administrativa 'ver_compras' (GET /api/compras)
        $this->actingAsSanctumUser($islero);
        $isleroResponse = $this->apiJson('GET', '/api/compras');

        $isleroResponse->assertStatus(403);
        $isleroResponse->assertJson([
            'status' => false,
            'message' => 'Sin permisos.',
            'errors' => null,
        ]);
    }
}

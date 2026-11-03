<?php

namespace Tests\Support;

use App\Models\Compra;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * BaseApiTestCase
 *
 * Clase base protegida para pruebas de API de GASSTATION sobre PostgreSQL.
 * Implementa verificaciones estrictas de entorno, previene traits destructivos,
 * valida permisos de Spatie con Sanctum y coordina la limpieza LIFO de datos.
 */
abstract class BaseApiTestCase extends TestCase
{
    use CleanableTestTrait;

    /**
     * Instancia auxiliar para la construcción segura de entidades de prueba.
     */
    protected TestEntityBuilder $builder;

    /**
     * Hosts de PostgreSQL permitidos para el entorno de pruebas.
     */
    protected array $allowedPgsqlHosts = ['localhost', '127.0.0.1'];

    /**
     * Puertos de PostgreSQL autorizados para pruebas.
     */
    protected array $allowedPgsqlPorts = [5437];

    /**
     * Nombres de bases de datos de PostgreSQL autorizadas para pruebas.
     */
    protected array $allowedPgsqlDatabases = ['gasstation_testing'];

    /**
     * Configuración inicial antes de cada prueba.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Verificación obligatoria: Prohibir traits de reseteo masivo
        $this->assertNoDangerousTraitsUsed();

        // 2. Verificación obligatoria: APP_ENV debe ser 'testing'
        $this->assertTestingEnvironment();

        // 3. Verificación obligatoria: Conexión debe ser PostgreSQL
        // 4. Verificación obligatoria: Host, puerto y base de datos autorizados
        $this->assertPostgresDatabaseSecurity();

        // 5. Inicializar identificación de prueba y pila LIFO de limpieza
        $this->initializeCleanable();

        // 6. Inicializar constructor de entidades de prueba
        $this->builder = new TestEntityBuilder($this);
    }

    /**
     * Limpieza posterior a cada prueba.
     */
    protected function tearDown(): void
    {
        try {
            // Ejecutar limpieza selectiva LIFO de registros creados en esta prueba
            $this->tearDownCleanable();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Valida que la clase de prueba actual no utilice traits destructivos.
     */
    protected function assertNoDangerousTraitsUsed(): void
    {
        $traits = class_uses_recursive(static::class);

        $forbiddenTraits = [
            RefreshDatabase::class => 'RefreshDatabase (ejecuta migrate:fresh o destruye esquemas)',
            DatabaseMigrations::class => 'DatabaseMigrations (ejecuta migraciones o reversiones globales)',
            DatabaseTruncation::class => 'DatabaseTruncation (trunca tablas indiscriminadamente)',
        ];

        foreach ($forbiddenTraits as $traitClass => $description) {
            if (in_array($traitClass, $traits, true)) {
                throw new \RuntimeException(
                    sprintf(
                        "SEGURIDAD CRÍTICA ABORTADA: La clase de prueba [%s] incluye el trait prohibido [%s]. " .
                        "En GASSTATION está estrictamente vetado el uso de reseteos globales para proteger la integridad de PostgreSQL.",
                        static::class,
                        $description
                    )
                );
            }
        }
    }

    /**
     * Valida que la aplicación se encuentre en entorno explícito de pruebas.
     */
    protected function assertTestingEnvironment(): void
    {
        $appEnv = (string) config('app.env');

        if ($appEnv !== 'testing') {
            throw new \RuntimeException(
                sprintf(
                    "SEGURIDAD CRÍTICA ABORTADA: APP_ENV debe ser estrictamente 'testing'. Detectado: '%s'.",
                    $appEnv
                )
            );
        }
    }

    /**
     * Valida la conexión, host, puerto y base de datos autorizada de PostgreSQL.
     */
    protected function assertPostgresDatabaseSecurity(): void
    {
        $defaultConnection = (string) config('database.default');

        if ($defaultConnection !== 'pgsql') {
            throw new \RuntimeException(
                sprintf(
                    "SEGURIDAD CRÍTICA ABORTADA: La conexión por defecto debe ser 'pgsql'. Conexión actual: '%s'.",
                    $defaultConnection
                )
            );
        }

        $host = (string) config('database.connections.pgsql.host');
        $port = (int) config('database.connections.pgsql.port');
        $database = (string) config('database.connections.pgsql.database');

        if (!in_array($host, $this->allowedPgsqlHosts, true)) {
            throw new \RuntimeException(
                sprintf(
                    "SEGURIDAD CRÍTICA ABORTADA: El host de PostgreSQL ('%s') no está autorizado. Permitidos: [%s].",
                    $host,
                    implode(', ', $this->allowedPgsqlHosts)
                )
            );
        }

        if (!in_array($port, $this->allowedPgsqlPorts, true)) {
            throw new \RuntimeException(
                sprintf(
                    "SEGURIDAD CRÍTICA ABORTADA: El puerto de PostgreSQL (%d) no corresponde al puerto de pruebas autorizado (5437).",
                    $port
                )
            );
        }

        if (!in_array($database, $this->allowedPgsqlDatabases, true)) {
            throw new \RuntimeException(
                sprintf(
                    "SEGURIDAD CRÍTICA ABORTADA: La base de datos de PostgreSQL ('%s') no está en la lista de bases autorizadas: [%s].",
                    $database,
                    implode(', ', $this->allowedPgsqlDatabases)
                )
            );
        }
    }

    /**
     * Autentica un usuario con Sanctum y verifica que cuente con los permisos de Spatie requeridos.
     *
     * @param User|null $user Usuario a autenticar. Si es null, crea uno nuevo registrado en la limpieza.
     * @param array<string> $permissions Permisos de Spatie requeridos para la prueba.
     * @return User
     */
    protected function actingAsSanctumUser(?User $user = null, array $permissions = []): User
    {
        if ($user === null) {
            $user = $this->builder->createTestUser([], $permissions);
        } else {
            // Asignar permisos faltantes al usuario existente verificando que existan
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
        }

        // Verificación estricta: asegurar que Spatie reconoce los permisos para este usuario
        foreach ($permissions as $permissionName) {
            if (!$user->hasPermissionTo($permissionName, 'sanctum')) {
                throw new \RuntimeException(
                    sprintf(
                        "SEGURIDAD SPATIE: No se pudo verificar el permiso '%s' (guard: 'sanctum') para el usuario de prueba ID %s.",
                        $permissionName,
                        $user->id
                    )
                );
            }
        }

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    /**
     * Helper para peticiones JSON con encabezados de API por defecto.
     */
    protected function apiJson(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return $this->json($method, $uri, $data, array_merge($defaultHeaders, $headers));
    }

    /**
     * Captura el estado de inventarios inmediatamente antes de confirmar una compra de prueba.
     */
    protected function snapshotPreConfirmation(Compra $compra): void
    {
        $this->builder->snapshotPreConfirmationInventoryState($compra);
    }

    /**
     * Rastreia y registra explícitamente todos los efectos secundarios generados tras confirmar una compra.
     */
    protected function trackConfirmationEffects(Compra $compra): void
    {
        $this->builder->trackConfirmationEffects($compra);
    }
}

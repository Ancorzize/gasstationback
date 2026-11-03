<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Inicializa los traits de prueba con salvaguarda estricta para PostgreSQL.
     *
     * Este método del ciclo de vida de Laravel se ejecuta inmediatamente DESPUÉS de que
     * $this->refreshApplication() ha instanciado la aplicación y el contenedor, y ANTES de que
     * cualquier trait (como RefreshDatabase) ejecute operaciones en la base de datos.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        // 1. Obtener la conexión configurada a través de la aplicación ya instanciada
        $connection = (string) (config('database.default') ?: env('DB_CONNECTION'));

        // 2. Si la conexión activa es PostgreSQL, bloquear traits destructivos
        if ($connection === 'pgsql') {
            $traits = class_uses_recursive(static::class);

            $dangerousTraits = [
                RefreshDatabase::class => 'RefreshDatabase (ejecuta migrate:fresh o destruye esquemas)',
                DatabaseMigrations::class => 'DatabaseMigrations (ejecuta migraciones o reversiones globales)',
                DatabaseTruncation::class => 'DatabaseTruncation (trunca tablas indiscriminadamente)',
            ];

            foreach ($dangerousTraits as $traitClass => $description) {
                if (in_array($traitClass, $traits, true)) {
                    throw new \RuntimeException(
                        sprintf(
                            "SEGURIDAD CRÍTICA BLOQUEADA: La clase de prueba [%s] utiliza [%s] sobre la conexión PostgreSQL. " .
                            "Operación abortada inmediatamente antes de ejecutar migraciones para proteger la base de datos.",
                            static::class,
                            $description
                        )
                    );
                }
            }
        }

        return parent::setUpTraits();
    }
}

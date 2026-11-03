<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait CleanableTestTrait
 *
 * Provee mecanismos de identificación única, registro explícito de IDs
 * y limpieza en orden de dependencias estrictamente compatible con las FKs de PostgreSQL,
 * sin truncamiento global, sin borrados masivos no rastreados y sin afectación de datos preexistentes.
 */
trait CleanableTestTrait
{
    /**
     * Identificador unívoco para la ejecución de la prueba actual.
     */
    protected string $testRunId = '';

    /**
     * Contador secuencial de inserción para garantizar orden LIFO intra-tabla.
     */
    protected int $cleanupSequence = 0;

    /**
     * Pila de registros creados durante la prueba que deben limpiarse.
     * Cada elemento: ['class', 'table', 'id', 'key_name', 'label', 'test_run_id', 'insertion_order']
     */
    protected array $cleanupStack = [];

    /**
     * Registro de fallos ocurridos durante la limpieza.
     */
    protected array $cleanupFailures = [];

    /**
     * Registro de operaciones de limpieza exitosas para auditoría.
     */
    protected array $cleanupLog = [];

    /**
     * Prioridad de eliminación por tabla para garantizar que las dependencias hijas
     * se eliminen siempre antes que las tablas referenciadas con FK restrictivas de PostgreSQL.
     * Menor valor = se elimina primero.
     * Solo se permiten tablas registradas explícitamente en esta matriz.
     */
    protected array $tableDeletionPriority = [
        'aplicaciones_anticipo_compra'         => 10,
        'aplicaciones_saldo_inicial_proveedor' => 15,
        'anticipos_proveedor'                  => 20,
        'saldos_iniciales_proveedor'           => 25,
        'pagos_compra'                         => 30,
        'movimientos_caja'                     => 40,
        'movimientos_inventario'               => 50,
        'inventarios'                          => 60,
        'documentos_electronicos'              => 70,
        'detalle_compras'                      => 80,
        'compras'                              => 90,
        'productos'                            => 100,
        'bodegas'                              => 110,
        'cajas'                                => 120,
        'proveedores'                          => 130,
        'users'                                => 140,
    ];

    /**
     * Inicializa el identificador único y las pilas de limpieza.
     */
    protected function initializeCleanable(): void
    {
        $this->testRunId = 'TEST_' . bin2hex(random_bytes(4)) . '_' . time();
        $this->cleanupSequence = 0;
        $this->cleanupStack = [];
        $this->cleanupFailures = [];
        $this->cleanupLog = [];
    }

    /**
     * Retorna el ID único de la ejecución actual.
     */
    public function getTestRunId(): string
    {
        if (empty($this->testRunId)) {
            $this->initializeCleanable();
        }

        return $this->testRunId;
    }

    /**
     * Genera un valor con prefijo de prueba unívoco (para NITs, números de documento, etc.).
     */
    public function generateTestIdentifier(string $prefix = 'TEST'): string
    {
        return sprintf('%s_%s_%s', $prefix, bin2hex(random_bytes(3)), $this->getTestRunId());
    }

    /**
     * Comprueba si un registro específico ya se encuentra en la pila de limpieza.
     */
    public function isAlreadyTracked(string $table, mixed $id): bool
    {
        foreach ($this->cleanupStack as $entry) {
            if ($entry['table'] === $table && (string) $entry['id'] === (string) $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registra un modelo Eloquent en la pila de limpieza.
     */
    public function trackForCleanup(Model $model, ?string $label = null): Model
    {
        if (!$model->exists || $model->getKey() === null) {
            throw new \InvalidArgumentException(
                "No se puede registrar para limpieza un modelo no persistido o con llave primaria nula: " . get_class($model)
            );
        }

        $table = $model->getTable();

        // Validar que la tabla tenga prioridad definida; no se permiten tablas desconocidas
        if (!array_key_exists($table, $this->tableDeletionPriority)) {
            throw new \InvalidArgumentException(
                "SEGURIDAD CRÍTICA: La tabla '{$table}' no tiene prioridad definida en tableDeletionPriority. " .
                "Debe registrarse en la matriz de prioridades antes de permitir su seguimiento."
            );
        }

        $id = $model->getKey();

        // Evitar duplicados en la pila
        if ($this->isAlreadyTracked($table, $id)) {
            return $model;
        }

        $keyName = $model->getKeyName() ?: 'id';
        $class = get_class($model);
        $displayLabel = $label ?? (class_basename($model) . " #{$id}");
        $this->cleanupSequence++;

        $this->cleanupStack[] = [
            'class' => $class,
            'table' => $table,
            'id' => $id,
            'key_name' => $keyName,
            'label' => $displayLabel,
            'test_run_id' => $this->getTestRunId(),
            'insertion_order' => $this->cleanupSequence,
        ];

        return $model;
    }

    /**
     * Registra una tabla e ID explícitamente en la pila de limpieza.
     */
    public function trackIdForCleanup(
        string $table,
        mixed $id,
        string $keyName = 'id',
        ?string $modelClass = null,
        ?string $label = null
    ): void {
        if (empty($id) || empty($table)) {
            throw new \InvalidArgumentException(
                "No se puede registrar para limpieza un registro con tabla o ID vacío."
            );
        }

        // Validar que la tabla tenga prioridad definida; no se permiten tablas desconocidas
        if (!array_key_exists($table, $this->tableDeletionPriority)) {
            throw new \InvalidArgumentException(
                "SEGURIDAD CRÍTICA: La tabla '{$table}' no tiene prioridad definida en tableDeletionPriority. " .
                "Debe registrarse en la matriz de prioridades antes de permitir su seguimiento."
            );
        }

        // Evitar duplicados en la pila
        if ($this->isAlreadyTracked($table, $id)) {
            return;
        }

        $displayLabel = $label ?? "{$table} #{$id}";
        $this->cleanupSequence++;

        $this->cleanupStack[] = [
            'class' => $modelClass,
            'table' => $table,
            'id' => $id,
            'key_name' => $keyName,
            'label' => $displayLabel,
            'test_run_id' => $this->getTestRunId(),
            'insertion_order' => $this->cleanupSequence,
        ];
    }

    /**
     * Registra una advertencia o riesgo de limpieza sin interrumpir otras cadenas válidas.
     */
    public function recordCleanupWarning(string $table, string $message): void
    {
        $this->cleanupFailures[] = [
            'entry' => ['table' => $table, 'id' => 'N/A', 'label' => "Alerta en tabla {$table}"],
            'error' => $message,
        ];

        Log::channel('single')->warning("[CleanableTestTrait Warning] Tabla {$table}: {$message}");
        fwrite(STDERR, "\n[CleanableTestTrait Warning] Tabla {$table}: {$message}\n");
    }

    /**
     * Retorna la cola de limpieza ordenada estrictamente según las FKs de PostgreSQL.
     *
     * Protecciones obligatorias:
     * - Si una entrada pertenece a una tabla desconocida (no listada en tableDeletionPriority),
     *   NO se le asigna prioridad arbitraria, NO se elimina silenciosamente,
     *   se registra el fallo y se excluye de la cola de eliminación de forma segura.
     * - Para entradas de la misma tabla, preserva el orden LIFO estricto (mayor insertion_order primero).
     */
    public function getSortedCleanupQueue(): array
    {
        $validEntries = [];

        foreach ($this->cleanupStack as $entry) {
            $table = $entry['table'] ?? '';

            if (!array_key_exists($table, $this->tableDeletionPriority)) {
                // Reportar tabla desconocida y detener la limpieza para esta entrada
                $this->cleanupFailures[] = [
                    'entry' => $entry,
                    'error' => "SEGURIDAD CRÍTICA: La tabla '{$table}' no tiene prioridad definida en tableDeletionPriority. " .
                               "Se detuvo la limpieza de este registro para evitar eliminaciones desordenadas o no autorizadas.",
                ];
                continue;
            }

            $validEntries[] = $entry;
        }

        usort($validEntries, function ($a, $b) {
            $prioA = $this->tableDeletionPriority[$a['table']];
            $prioB = $this->tableDeletionPriority[$b['table']];

            if ($prioA !== $prioB) {
                return $prioA <=> $prioB; // Menor número = se elimina primero
            }

            // A igual prioridad de tabla, orden LIFO estricto (último insertado se elimina primero)
            return ($b['insertion_order'] ?? 0) <=> ($a['insertion_order'] ?? 0);
        });

        return $validEntries;
    }

    /**
     * Ejecuta la limpieza selectiva respetando el orden estricto de dependencias FK.
     *
     * Reglas estrictas:
     * - Se borran EXCLUSIVAMENTE los registros individuales registrados en $cleanupStack.
     * - No se ejecutan borrados automáticos no rastreados ni consultas masivas.
     * - Cada registro se elimina filtrando estrictamente por su clave primaria ($keyName = $id).
     * - Si ocurre un error de FK, se registra el fallo sin intentar cascadas forzadas.
     */
    protected function tearDownCleanable(): void
    {
        $orderedQueue = $this->getSortedCleanupQueue();

        foreach ($orderedQueue as $entry) {
            $id = $entry['id'];
            $table = $entry['table'];
            $keyName = $entry['key_name'] ?? 'id';
            $modelClass = $entry['class'] ?? null;
            $label = $entry['label'] ?? "{$table}#{$id}";

            // Salvaguarda: nunca borrar sin ID específico ni tabla válida
            if (empty($id) || empty($table)) {
                $this->cleanupFailures[] = [
                    'entry' => $entry,
                    'error' => 'Intento de eliminación bloqueado: ID o tabla vacía en entrada de la pila.',
                ];
                continue;
            }

            try {
                $deleted = false;

                // 1. Si existe clase de modelo, intentar borrado a través del modelo Eloquent
                if ($modelClass && class_exists($modelClass)) {
                    $instance = $modelClass::where($keyName, $id)->first();
                    if ($instance) {
                        $instance->delete();
                        $deleted = true;
                    }
                }

                // 2. Si no se borró por modelo, ejecutar borrado directo en DB con WHERE estricto por clave primaria
                if (!$deleted) {
                    $affected = DB::table($table)
                        ->where($keyName, $id)
                        ->delete();

                    $deleted = ($affected > 0);
                }

                $this->cleanupLog[] = [
                    'entry' => $entry,
                    'status' => $deleted ? 'deleted' : 'not_found',
                ];
            } catch (\Throwable $e) {
                // Registrar el fallo de forma explícita sin intentar borrados masivos ni cascadas forzadas
                $this->cleanupFailures[] = [
                    'entry' => $entry,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Vaciar la pila después de la ejecución
        $this->cleanupStack = [];

        // Si ocurrieron fallos de limpieza, reportarlos claramente en logs y STDERR
        if (!empty($this->cleanupFailures)) {
            $report = sprintf(
                "\n[CleanableTestTrait] ADVERTENCIA: Se presentaron %d incidentes durante la limpieza (testRunId: %s):\n",
                count($this->cleanupFailures),
                $this->testRunId
            );

            foreach ($this->cleanupFailures as $fail) {
                $report .= sprintf(" - %s (tabla: %s, ID: %s): %s\n", $fail['entry']['label'], $fail['entry']['table'], $fail['entry']['id'], $fail['error']);
            }

            Log::channel('single')->warning($report);
            fwrite(STDERR, $report . "\n");
        }
    }

    /**
     * Retorna los fallos ocurridos durante la última limpieza.
     */
    public function getCleanupFailures(): array
    {
        return $this->cleanupFailures;
    }

    /**
     * Retorna el registro de operaciones de limpieza.
     */
    public function getCleanupLog(): array
    {
        return $this->cleanupLog;
    }

    /**
     * Retorna el número de elementos actualmente pendientes en la pila de limpieza.
     */
    public function getCleanupStackCount(): int
    {
        return count($this->cleanupStack);
    }
}

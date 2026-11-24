<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder idempotente de homologaciones de ciudades de Colombia (1.122 municipios)
 * entre el código DANE (5 dígitos) y el ID oficial de MATÍAS API (GET /api/ubl2.1/cities).
 *
 * Ejecución individual segura en producción:
 *   php artisan db:seed --class=MatiasCiudadesCatalogoSeeder
 */
class MatiasCiudadesCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/mapeos_ciudades_matias.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        if (!is_array($items) || empty($items)) {
            return;
        }

        $now = now();

        // Consultar en una sola query las ciudades MATÍAS ya registradas (evita 1.122 round-trips)
        $existentes = DB::table('mapeos_catalogos_facturacion')
            ->where('proveedor', 'matias')
            ->where('categoria', 'ciudad')
            ->pluck('id', 'codigo_interno')
            ->all();

        $nuevos = [];
        foreach ($items as $item) {
            $codigoInterno = strtolower(trim((string) ($item['codigo_interno'] ?? '')));
            if ($codigoInterno === '' || isset($existentes[$codigoInterno])) {
                // No sobrescribir homologaciones de ciudad ya configuradas manualmente en BD
                continue;
            }

            $nuevos[] = [
                'proveedor' => strtolower(trim((string) ($item['proveedor'] ?? 'matias'))),
                'categoria' => 'ciudad',
                'codigo_interno' => $codigoInterno,
                'codigo_externo' => trim((string) $item['codigo_externo']),
                'codigo_externo_secundario' => isset($item['codigo_externo_secundario'])
                    ? trim((string) $item['codigo_externo_secundario'])
                    : null,
                'descripcion' => $item['descripcion'] ?? null,
                'is_active' => (bool) ($item['is_active'] ?? true),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($nuevos)) {
            DB::transaction(function () use ($nuevos) {
                foreach (array_chunk($nuevos, 250) as $chunk) {
                    DB::table('mapeos_catalogos_facturacion')->insert($chunk);
                }
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('mapeos_catalogos_facturacion', 'id'), COALESCE((SELECT MAX(id) FROM mapeos_catalogos_facturacion), 1));"
            );
        }
    }
}

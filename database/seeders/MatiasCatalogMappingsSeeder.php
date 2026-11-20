<?php

namespace Database\Seeders;

use App\Models\MapeoCatalogoFacturacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder idempotente para inicializar las homologaciones fiscales verificadas
 * del proveedor MATÍAS en la tabla mapeos_catalogos_facturacion.
 *
 * Ejecución manual:
 * php artisan db:seed --class=MatiasCatalogMappingsSeeder
 */
class MatiasCatalogMappingsSeeder extends Seeder
{
    /**
     * Ejecutar el seeder de equivalencias de catálogos fiscales MATÍAS.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/mapeos_catalogos_facturacion.json');
        if (file_exists($path)) {
            $items = json_decode(file_get_contents($path), true);
            foreach ($items as $item) {
                MapeoCatalogoFacturacion::updateOrCreate(
                    [
                        'proveedor' => strtolower(trim($item['proveedor'])),
                        'categoria' => strtolower(trim($item['categoria'])),
                        'codigo_interno' => strtolower(trim($item['codigo_interno'])),
                    ],
                    [
                        'codigo_externo' => trim((string) $item['codigo_externo']),
                        'codigo_externo_secundario' => isset($item['codigo_externo_secundario']) ? trim((string) $item['codigo_externo_secundario']) : null,
                        'descripcion' => $item['descripcion'] ?? null,
                        'is_active' => (bool) ($item['is_active'] ?? true),
                    ]
                );
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('mapeos_catalogos_facturacion', 'id'), COALESCE((SELECT MAX(id) FROM mapeos_catalogos_facturacion), 1));");
        }
    }
}

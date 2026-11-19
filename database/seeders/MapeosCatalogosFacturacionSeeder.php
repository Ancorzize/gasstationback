<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapeosCatalogosFacturacionSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/mapeos_catalogos_facturacion.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('mapeos_catalogos_facturacion')->updateOrInsert(
                [
                    'proveedor' => $item['proveedor'],
                    'categoria' => $item['categoria'],
                    'codigo_interno' => $item['codigo_interno'],
                ],
                [
                    'codigo_externo' => $item['codigo_externo'],
                    'codigo_externo_secundario' => $item['codigo_externo_secundario'] ?? null,
                    'descripcion' => $item['descripcion'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('mapeos_catalogos_facturacion', 'id'), COALESCE((SELECT MAX(id) FROM mapeos_catalogos_facturacion), 1));");
        }
    }
}

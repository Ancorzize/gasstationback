<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreciosCombustibleSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/precios_combustible.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('precios_combustible')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'producto_id' => $item['producto_id'],
                    'precio' => $item['precio'],
                    'fecha_inicio' => $item['fecha_inicio'] ?? null,
                    'fecha_fin' => $item['fecha_fin'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('precios_combustible', 'id'), COALESCE((SELECT MAX(id) FROM precios_combustible), 1));");
        }
    }
}

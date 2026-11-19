<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BombasSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/bombas.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('bombas')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'estacion_id' => $item['estacion_id'],
                    'codigo' => $item['codigo'],
                    'nombre' => $item['nombre'],
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('bombas', 'id'), COALESCE((SELECT MAX(id) FROM bombas), 1));");
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasGastoSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/categorias_gasto.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('categorias_gasto')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('categorias_gasto', 'id'), COALESCE((SELECT MAX(id) FROM categorias_gasto), 1));");
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/servicios.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            DB::table('servicios')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'codigo' => $item['codigo'],
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'precio' => $item['precio'] ?? 0,
                    'unidad_medida_id' => $item['unidad_medida_id'] ?? null,
                    'permite_decimal' => (bool) ($item['permite_decimal'] ?? false),
                    'duracion_minutos' => $item['duracion_minutos'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('servicios', 'id'), COALESCE((SELECT MAX(id) FROM servicios), 1));");
        }
    }
}

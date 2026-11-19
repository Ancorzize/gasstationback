<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstacionesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/estaciones.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('estaciones')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'codigo' => $item['codigo'],
                    'nombre' => $item['nombre'],
                    'direccion' => $item['direccion'] ?? null,
                    'telefono' => $item['telefono'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('estaciones', 'id'), COALESCE((SELECT MAX(id) FROM estaciones), 1));");
        }
    }
}

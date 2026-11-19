<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BodegasSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/bodegas.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('bodegas')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nombre' => $item['nombre'],
                    'codigo' => $item['codigo'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'direccion' => $item['direccion'] ?? null,
                    'telefono' => $item['telefono'] ?? null,
                    'responsable_id' => $item['responsable_id'] ?? null,
                    'tipo_bodega' => $item['tipo_bodega'] ?? 'principal',
                    'is_principal' => (bool) ($item['is_principal'] ?? false),
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('bodegas', 'id'), COALESCE((SELECT MAX(id) FROM bodegas), 1));");
        }
    }
}

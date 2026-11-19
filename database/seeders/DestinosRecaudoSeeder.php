<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DestinosRecaudoSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/destinos_recaudo.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('destinos_recaudo')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'codigo' => $item['codigo'],
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('destinos_recaudo', 'id'), COALESCE((SELECT MAX(id) FROM destinos_recaudo), 1));");
        }
    }
}

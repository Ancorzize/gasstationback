<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadesMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/unidades_medida.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('unidades_medida')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nombre' => $item['nombre'],
                    'abreviatura' => $item['abreviatura'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'codigo_dian' => $item['codigo_dian'] ?? null,
                    'simbolo_dian' => $item['simbolo_dian'] ?? null,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'deleted_at' => $item['deleted_at'] ?? null,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('unidades_medida', 'id'), COALESCE((SELECT MAX(id) FROM unidades_medida), 1));");
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManguerasSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/mangueras.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('mangueras')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'bomba_id' => $item['bomba_id'],
                    'producto_id' => $item['producto_id'],
                    'codigo' => $item['codigo'],
                    'nombre' => $item['nombre'],
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('mangueras', 'id'), COALESCE((SELECT MAX(id) FROM mangueras), 1));");
        }
    }
}

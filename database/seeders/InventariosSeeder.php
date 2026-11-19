<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventariosSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/inventarios.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('inventarios')->updateOrInsert(
                [
                    'producto_id' => $item['producto_id'],
                    'bodega_id' => $item['bodega_id'],
                ],
                [
                    // Todos los saldos y existencias arrancan estrictamente en 0.00
                    'cantidad' => 0.00,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('inventarios', 'id'), COALESCE((SELECT MAX(id) FROM inventarios), 1));");
        }
    }
}

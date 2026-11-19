<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/proveedores.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('proveedores')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nombre' => $item['nombre'],
                    'nit' => $item['nit'],
                    'telefono' => $item['telefono'] ?? null,
                    'direccion' => $item['direccion'] ?? null,
                    'email' => $item['email'] ?? null,
                    'tipo_persona' => $item['tipo_persona'] ?? 'juridica',
                    'tipo_documento_id' => $item['tipo_documento_id'] ?? null,
                    'tipo_organization_id' => $item['tipo_organization_id'] ?? null,
                    'tax_regime_id' => $item['tax_regime_id'] ?? null,
                    'tax_level_id' => $item['tax_level_id'] ?? null,
                    'codigo_postal' => $item['codigo_postal'] ?? null,
                    'pais_id' => $item['pais_id'] ?? null,
                    'ciudad_id' => $item['ciudad_id'] ?? null,
                    // Garantizar saldo cero para inicio limpio en produccion
                    'saldo_anticipo' => 0.00,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('proveedores', 'id'), COALESCE((SELECT MAX(id) FROM proveedores), 1));");
        }
    }
}

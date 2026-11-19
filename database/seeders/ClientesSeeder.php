<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/clientes.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('clientes')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nombre' => $item['nombre'],
                    'apellidos' => $item['apellidos'] ?? null,
                    'documento' => $item['documento'],
                    'telefono_uno' => $item['telefono_uno'] ?? null,
                    'telefono_dos' => $item['telefono_dos'] ?? null,
                    'email' => $item['email'] ?? null,
                    'direccion' => $item['direccion'] ?? null,
                    'tipo_persona' => $item['tipo_persona'] ?? 'natural',
                    'tipo_documento_id' => $item['tipo_documento_id'] ?? null,
                    'tipo_organization_id' => $item['tipo_organization_id'] ?? null,
                    'tax_regime_id' => $item['tax_regime_id'] ?? null,
                    'tax_level_id' => $item['tax_level_id'] ?? null,
                    'codigo_postal' => $item['codigo_postal'] ?? null,
                    'pais_id' => $item['pais_id'] ?? null,
                    'ciudad_id' => $item['ciudad_id'] ?? null,
                    'maneja_credito' => (bool) ($item['maneja_credito'] ?? false),
                    'cupo_credito' => $item['cupo_credito'] ?? 0,
                    'dias_credito' => $item['dias_credito'] ?? 0,
                    // Garantizar saldo cero para inicio limpio en produccion
                    'saldo_credito' => 0.00,
                    'saldo_a_favor' => 0.00,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('clientes', 'id'), COALESCE((SELECT MAX(id) FROM clientes), 1));");
        }
    }
}

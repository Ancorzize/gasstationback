<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        $pathPaises = database_path('seeders/data/paises.json');
        $pathDeptos = database_path('seeders/data/departamentos.json');
        $pathCiudades = database_path('seeders/data/ciudades.json');

        if (file_exists($pathPaises)) {
            $paises = json_decode(file_get_contents($pathPaises), true);
            foreach ($paises as $item) {
                DB::table('paises')->updateOrInsert(
                    ['id' => $item['id']],
                    [
                        'nombre' => $item['nombre'],
                        'codigo_iso' => $item['codigo_iso'],
                        'is_active' => (bool) $item['is_active'],
                        'created_at' => $item['created_at'] ?? now(),
                        'updated_at' => $item['updated_at'] ?? now(),
                    ]
                );
            }
            $this->syncSequence('paises');
        }

        if (file_exists($pathDeptos)) {
            $deptos = json_decode(file_get_contents($pathDeptos), true);
            foreach ($deptos as $item) {
                DB::table('departamentos')->updateOrInsert(
                    ['id' => $item['id']],
                    [
                        'pais_id' => $item['pais_id'],
                        'nombre' => $item['nombre'],
                        'codigo' => $item['codigo'],
                        'is_active' => (bool) $item['is_active'],
                        'created_at' => $item['created_at'] ?? now(),
                        'updated_at' => $item['updated_at'] ?? now(),
                    ]
                );
            }
            $this->syncSequence('departamentos');
        }

        if (file_exists($pathCiudades)) {
            $ciudades = json_decode(file_get_contents($pathCiudades), true);
            foreach ($ciudades as $item) {
                DB::table('ciudades')->updateOrInsert(
                    ['id' => $item['id']],
                    [
                        'departamento_id' => $item['departamento_id'],
                        'nombre' => $item['nombre'],
                        'codigo' => $item['codigo'],
                        'is_active' => (bool) $item['is_active'],
                        'created_at' => $item['created_at'] ?? now(),
                        'updated_at' => $item['updated_at'] ?? now(),
                    ]
                );
            }
            $this->syncSequence('ciudades');
        }
    }

    protected function syncSequence(string $table): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1));");
        }
    }
}

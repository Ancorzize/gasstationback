<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/productos.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('productos')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'codigo' => $item['codigo'],
                    'nombre' => $item['nombre'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'codigo_barras' => $item['codigo_barras'] ?? null,
                    'marca_id' => $item['marca_id'] ?? null,
                    'categoria_producto_id' => $item['categoria_producto_id'] ?? null,
                    'unidad_medida_id' => $item['unidad_medida_id'] ?? null,
                    'precio_compra' => $item['precio_compra'] ?? 0,
                    'precio_venta' => $item['precio_venta'] ?? 0,
                    'tipo_impuesto' => $item['tipo_impuesto'] ?? 'gravado',
                    'porcentaje_iva' => $item['porcentaje_iva'] ?? 19.00,
                    'codigo_impuesto' => $item['codigo_impuesto'] ?? '01',
                    'permite_decimal' => (bool) ($item['permite_decimal'] ?? false),
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('productos', 'id'), COALESCE((SELECT MAX(id) FROM productos), 1));");
        }
    }
}

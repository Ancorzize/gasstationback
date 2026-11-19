<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResolucionesFacturacionSeeder extends Seeder
{
    /**
     * Seeder de resoluciones de facturacion.
     * Conserva la estructura y parametros recuperados del entorno previo.
     * NOTA IMPORTANTE PARA PRODUCCION:
     * Antes de emitir facturas en ambiente real, el Administrador y la Contadora
     * deben validar en la DIAN (Formulario 1876):
     * 1. Si el prefijo FEMT corresponde a habilitacion o a la resolucion definitiva.
     * 2. El numero oficial de resolucion, fecha de vigencia y rango autorizado.
     * 3. El consecutivo inicial autorizado (consecutivo_actual).
     * 4. La clave tecnica oficial asignada por la DIAN.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/resoluciones_facturacion.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('resoluciones_facturacion')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'configuracion_empresa_id' => $item['configuracion_empresa_id'],
                    'tipo_documento' => $item['tipo_documento'],
                    'prefijo' => $item['prefijo'] ?? null,
                    'numero_resolucion' => $item['numero_resolucion'],
                    'fecha_resolucion' => $item['fecha_resolucion'],
                    'rango_desde' => $item['rango_desde'],
                    'rango_hasta' => $item['rango_hasta'],
                    'consecutivo_actual' => $item['consecutivo_actual'] ?? $item['rango_desde'],
                    'fecha_vencimiento' => $item['fecha_vencimiento'],
                    'clave_tecnica' => $item['clave_tecnica'] ?? null,
                    'proveedor' => $item['proveedor'] ?? 'matias',
                    'ambiente' => $item['ambiente'] ?? 'produccion',
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('resoluciones_facturacion', 'id'), COALESCE((SELECT MAX(id) FROM resoluciones_facturacion), 1));");
        }
    }
}

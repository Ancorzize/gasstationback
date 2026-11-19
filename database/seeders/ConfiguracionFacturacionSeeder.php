<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionFacturacionSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/configuracion_facturacion.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('configuracion_facturacion')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'configuracion_empresa_id' => $item['configuracion_empresa_id'],
                    'proveedor_activo' => $item['proveedor_activo'] ?? 'matias',
                    'ambiente' => $item['ambiente'] ?? 'produccion',
                    'facturacion_electronica_activa' => (bool) ($item['facturacion_electronica_activa'] ?? true),
                    'reintentos_automaticos' => (bool) ($item['reintentos_automaticos'] ?? true),
                    'max_reintentos' => $item['max_reintentos'] ?? 3,
                    'facturar_ventas_pos' => (bool) ($item['facturar_ventas_pos'] ?? true),
                    'facturar_ventas_combustible' => (bool) ($item['facturar_ventas_combustible'] ?? true),
                    'permitir_ventas_sin_datos_fiscales' => (bool) ($item['permitir_ventas_sin_datos_fiscales'] ?? true),
                    // Token sin configurar inicialmente; el administrador lo ingresara
                    // desde la interfaz web (/configuracion/facturacion) para que
                    // Laravel lo encripte con el APP_KEY definitivo del ambiente.
                    'matias_api_token' => null,
                    'matias_sandbox_url' => $item['matias_sandbox_url'] ?? 'https://api-v2.matias-api.com/api/ubl2.1',
                    'matias_production_url' => $item['matias_production_url'] ?? 'https://api-v2.matias-api.com/api/ubl2.1',
                    'matias_ds_real_transmission' => (bool) ($item['matias_ds_real_transmission'] ?? false),
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('configuracion_facturacion', 'id'), COALESCE((SELECT MAX(id) FROM configuracion_facturacion), 1));");
        }
    }
}

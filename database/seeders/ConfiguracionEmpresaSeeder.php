<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionEmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/configuracion_empresa.json');
        if (!file_exists($path)) {
            return;
        }

        $items = json_decode(file_get_contents($path), true);
        foreach ($items as $item) {
            DB::table('configuracion_empresa')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nombre_empresa' => $item['nombre_empresa'],
                    'nombre_comercial' => $item['nombre_comercial'] ?? null,
                    'nit' => $item['nit'],
                    'dv' => $item['dv'] ?? null,
                    'email' => $item['email'] ?? null,
                    'telefono' => $item['telefono'] ?? null,
                    'direccion' => $item['direccion'] ?? null,
                    'pais_id' => $item['pais_id'] ?? null,
                    'departamento_id' => $item['departamento_id'] ?? null,
                    'ciudad_id' => $item['ciudad_id'] ?? null,
                    'logo_url' => $item['logo_url'] ?? null,
                    'logo_base64' => $item['logo_base64'] ?? null,
                    'logo_mime_type' => $item['logo_mime_type'] ?? null,
                    'responsable_iva' => (bool) ($item['responsable_iva'] ?? false),
                    'regimen' => $item['regimen'] ?? null,
                    'porcentaje_iva' => $item['porcentaje_iva'] ?? 19.00,
                    'maneja_iva_incluido' => (bool) ($item['maneja_iva_incluido'] ?? true),
                    'prefijo_factura' => $item['prefijo_factura'] ?? null,
                    'numero_resolucion' => $item['numero_resolucion'] ?? null,
                    'fecha_resolucion' => $item['fecha_resolucion'] ?? null,
                    'rango_desde' => $item['rango_desde'] ?? null,
                    'rango_hasta' => $item['rango_hasta'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                    'moneda' => $item['moneda'] ?? 'COP',
                    'simbolo_moneda' => $item['simbolo_moneda'] ?? '$',
                    'decimales' => $item['decimales'] ?? 2,
                    'tipo_persona' => $item['tipo_persona'] ?? 'juridica',
                    'tipo_documento' => $item['tipo_documento'] ?? 'NIT',
                    'tipo_regimen' => $item['tipo_regimen'] ?? null,
                    'responsabilidades_fiscales' => $item['responsabilidades_fiscales'] ?? null,
                    'codigo_postal' => $item['codigo_postal'] ?? null,
                    'matricula_mercantil' => $item['matricula_mercantil'] ?? null,
                    'url_descarga_apk' => $item['url_descarga_apk'] ?? null,
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => $item['updated_at'] ?? now(),
                ]
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('configuracion_empresa', 'id'), COALESCE((SELECT MAX(id) FROM configuracion_empresa), 1));");
        }
    }
}

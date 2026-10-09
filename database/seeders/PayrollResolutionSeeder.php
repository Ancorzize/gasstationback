<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ResolucionFacturacion;

class PayrollResolutionSeeder extends Seeder
{
    /**
     * Resolución de prueba para Nómina Electrónica en ambiente Sandbox de MATIAS API.
     * Nota: El número de resolución '18760000001' se utiliza exclusivamente para pruebas
     * en ambiente Sandbox de MATIAS. No debe usarse como resolución DIAN real en Producción.
     */
    public function run(): void
    {
        ResolucionFacturacion::updateOrCreate(
            [
                'configuracion_empresa_id' => 1,
                'tipo_documento'           => 'nomina_electronica',
                'proveedor'                => 'matias',
                'ambiente'                 => 'sandbox',
            ],
            [
                'prefijo'            => 'NE',
                'numero_resolucion' => '18760000001',
                'fecha_resolucion'  => '2026-01-01',
                'rango_desde'        => 1,
                'rango_hasta'        => 1000,
                'consecutivo_actual' => 1,
                'fecha_vencimiento'  => '2030-01-01',
                'clave_tecnica'      => null,
                'is_active'          => true,
            ]
        );
    }
}

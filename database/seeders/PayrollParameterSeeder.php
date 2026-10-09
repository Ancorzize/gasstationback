<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayrollParameter;

class PayrollParameterSeeder extends Seeder
{
    /**
     * Parámetros legales de nómina para el año 2026.
     * Fuente legal:
     * - Decreto 1469 de 2025: Salario Mínimo Legal Mensual Vigente (SMLMV) 2026.
     * - Decreto 1470 de 2025: Auxilio de Transporte 2026 y límite de hasta 2 SMLMV.
     */
    public function run(): void
    {
        PayrollParameter::updateOrCreate(
            ['year' => 2026],
            [
                'smlmv' => 1750905.00,
                'transport_assistance' => 249095.00,
                'max_smlmv_for_transport' => 2.00,
            ]
        );
    }
}

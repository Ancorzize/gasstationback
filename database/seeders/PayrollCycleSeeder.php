<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayrollCycle;

class PayrollCycleSeeder extends Seeder
{
    public function run(): void
    {
        $cycles = [
            [
                'name' => 'Nómina mensual',
                'code' => 'MENSUAL',
                'period_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Nómina quincenal',
                'code' => 'QUINCENAL',
                'period_type' => 'biweekly',
                'is_active' => true,
            ],
        ];

        foreach ($cycles as $cycle) {
            PayrollCycle::updateOrCreate(
                ['code' => $cycle['code']],
                $cycle
            );
        }
    }
}

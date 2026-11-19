<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NominaParametrosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Payroll cycles
        $cyclesPath = database_path('seeders/data/payroll_cycles.json');
        if (file_exists($cyclesPath)) {
            $cycles = json_decode(file_get_contents($cyclesPath), true);
            foreach ($cycles as $cycle) {
                DB::table('payroll_cycles')->updateOrInsert(
                    ['id' => $cycle['id']],
                    [
                        'name' => $cycle['name'],
                        'code' => $cycle['code'],
                        'period_type' => $cycle['period_type'] ?? 'monthly',
                        'is_active' => (bool) ($cycle['is_active'] ?? true),
                        'created_at' => $cycle['created_at'] ?? now(),
                        'updated_at' => $cycle['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('payroll_cycles', 'id'), COALESCE((SELECT MAX(id) FROM payroll_cycles), 1));");
            }
        }

        // 2. Payroll parameters
        $paramsPath = database_path('seeders/data/payroll_parameters.json');
        if (file_exists($paramsPath)) {
            $params = json_decode(file_get_contents($paramsPath), true);
            foreach ($params as $param) {
                DB::table('payroll_parameters')->updateOrInsert(
                    ['id' => $param['id']],
                    [
                        'year' => $param['year'],
                        'smlmv' => $param['smlmv'],
                        'transport_assistance' => $param['transport_assistance'],
                        'max_smlmv_for_transport' => $param['max_smlmv_for_transport'],
                        'created_at' => $param['created_at'] ?? now(),
                        'updated_at' => $param['updated_at'] ?? now(),
                    ]
                );
            }
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('payroll_parameters', 'id'), COALESCE((SELECT MAX(id) FROM payroll_parameters), 1));");
            }
        }
    }
}

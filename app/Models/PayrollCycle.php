<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollCycle extends Model
{
    protected $table = 'payroll_cycles';

    protected $fillable = [
        'name',
        'code',
        'period_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function employeePayrollCycles(): HasMany
    {
        return $this->hasMany(EmployeePayrollCycle::class, 'payroll_cycle_id');
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class, 'payroll_cycle_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'worker_code',
        'identity_document_id',
        'document_number',
        'first_name',
        'other_names',
        'first_surname',
        'second_surname',
        'working_country_id',
        'work_city_id',
        'work_address',
        'date_entry',
        'departure_date',
        'worker_type_id',
        'worker_subtype_id',
        'contract_type_id',
        'salary',
        'integral_salary',
        'high_risk_pension',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_entry' => 'date',
            'departure_date' => 'date',
            'salary' => 'decimal:2',
            'integral_salary' => 'boolean',
            'high_risk_pension' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relación opcional con el Usuario de acceso al sistema.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cuentas / Medios de pago del empleado.
     */
    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(EmployeePaymentAccount::class, 'employee_id');
    }

    /**
     * Asignaciones históricas de ciclos de nómina.
     */
    public function employeePayrollCycles(): HasMany
    {
        return $this->hasMany(EmployeePayrollCycle::class, 'employee_id');
    }

    /**
     * Liquidaciones de nómina del empleado.
     */
    public function payrollSettlements(): HasMany
    {
        return $this->hasMany(PayrollSettlement::class, 'employee_id');
    }
}

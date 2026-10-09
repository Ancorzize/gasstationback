<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSettlement extends Model
{
    protected $table = 'payroll_settlements';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'worker_code',
        'identity_document_id',
        'document_number',
        'first_name',
        'other_names',
        'first_surname',
        'second_surname',
        'worker_type_id',
        'worker_subtype_id',
        'contract_type_id',
        'date_entry',
        'departure_date',
        'salary',
        'integral_salary',
        'days_worked',
        'basic_salary',
        'total_earned',
        'deductions_total',
        'total_voucher',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_entry' => 'date',
            'departure_date' => 'date',
            'salary' => 'decimal:2',
            'integral_salary' => 'boolean',
            'days_worked' => 'integer',
            'basic_salary' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'total_voucher' => 'decimal:2',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayrollEarning::class, 'payroll_settlement_id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class, 'payroll_settlement_id');
    }

    public function documentosElectronicos(): HasMany
    {
        return $this->hasMany(DocumentoElectronico::class, 'payroll_settlement_id');
    }
}

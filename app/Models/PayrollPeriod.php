<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $table = 'payroll_periods';

    protected $fillable = [
        'payroll_cycle_id',
        'name',
        'start_date',
        'end_date',
        'payment_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class, 'payroll_cycle_id');
    }

    public function payrollSettlements(): HasMany
    {
        return $this->hasMany(PayrollSettlement::class, 'payroll_period_id');
    }
}

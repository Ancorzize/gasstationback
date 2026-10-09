<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeduction extends Model
{
    protected $table = 'payroll_deductions';

    protected $fillable = [
        'payroll_settlement_id',
        'type_code',
        'concept_name',
        'percentage',
        'base_amount',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function payrollSettlement(): BelongsTo
    {
        return $this->belongsTo(PayrollSettlement::class, 'payroll_settlement_id');
    }
}

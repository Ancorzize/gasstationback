<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEarning extends Model
{
    protected $table = 'payroll_earnings';

    protected $fillable = [
        'payroll_settlement_id',
        'type_code',
        'concept_name',
        'quantity',
        'percentage',
        'unit_value',
        'amount',
        'is_taxable',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'percentage' => 'decimal:2',
            'unit_value' => 'decimal:2',
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
        ];
    }

    public function payrollSettlement(): BelongsTo
    {
        return $this->belongsTo(PayrollSettlement::class, 'payroll_settlement_id');
    }
}

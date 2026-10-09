<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePaymentAccount extends Model
{
    protected $table = 'employee_payment_accounts';

    protected $fillable = [
        'employee_id',
        'payment_method_id',
        'means_payment_id',
        'bank',
        'account_type',
        'account_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

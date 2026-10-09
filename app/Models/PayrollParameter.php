<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollParameter extends Model
{
    protected $table = 'payroll_parameters';

    protected $fillable = [
        'year',
        'smlmv',
        'transport_assistance',
        'max_smlmv_for_transport',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'smlmv' => 'decimal:2',
            'transport_assistance' => 'decimal:2',
            'max_smlmv_for_transport' => 'decimal:2',
        ];
    }
}

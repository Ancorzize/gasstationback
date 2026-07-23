<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoCarteraDetalle extends Model
{
    protected $table = 'abonos_cartera_detalle';

    protected $fillable = [

        'abono_cartera_id',

        'venta_id',

        'valor_aplicado',

    ];

    protected $casts = [

        'valor_aplicado' => 'decimal:2',

    ];

    public function abono(): BelongsTo
    {
        return $this->belongsTo(
            AbonoCartera::class,
            'abono_cartera_id'
        );
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(
            Venta::class
        );
    }
}
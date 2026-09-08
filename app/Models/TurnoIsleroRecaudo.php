<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoIsleroRecaudo extends Model
{
    protected $table = 'turno_islero_recaudos';

    protected $fillable = [
        'turno_islero_id',
        'destino_recaudo_id',
        'efectivo',
        'qr',
        'datafono',
        'transferencia',
        'consignacion',
        'total',
    ];

    protected $casts = [
        'efectivo' => 'decimal:2',
        'qr' => 'decimal:2',
        'datafono' => 'decimal:2',
        'transferencia' => 'decimal:2',
        'consignacion' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(
            TurnoIslero::class,
            'turno_islero_id'
        );
    }

    public function destinoRecaudo(): BelongsTo
    {
        return $this->belongsTo(
            DestinoRecaudo::class,
            'destino_recaudo_id'
        );
    }
}